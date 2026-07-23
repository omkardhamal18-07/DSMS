<?php
// notification_helper.php
if (!function_exists('ensure_notifications_table')) {
    function ensure_notifications_table($conn) {
        static $checked = false;
        if ($checked) return;

        $sql = "CREATE TABLE IF NOT EXISTS notifications (
            notification_id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NULL,
            receiver_id INT NULL,
            receiver_role ENUM('ADMIN', 'FACULTY', 'ALL') NOT NULL,
            notification_type ENUM('FACULTY_REQUEST', 'LOW_STOCK', 'STOCK_UPDATED', 'REQUEST_STATUS') NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            reference_id INT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_receiver (receiver_role, receiver_id),
            INDEX idx_read (is_read),
            INDEX idx_type (notification_type),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        @$conn->query($sql);
        $checked = true;
    }
}

if (!function_exists('create_notification')) {
    function create_notification($conn, $sender_id, $receiver_id, $receiver_role, $notification_type, $title, $message, $reference_id = null) {
        ensure_notifications_table($conn);
        $stmt = $conn->prepare("INSERT INTO notifications (sender_id, receiver_id, receiver_role, notification_type, title, message, reference_id, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())");
        if (!$stmt) return false;
        $stmt->bind_param("iissssi", $sender_id, $receiver_id, $receiver_role, $notification_type, $title, $message, $reference_id);
        return $stmt->execute();
    }
}

if (!function_exists('check_and_create_low_stock_notification')) {
    function check_and_create_low_stock_notification($conn, $stationery_id) {
        ensure_notifications_table($conn);

        // Fetch stationery details
        $stmt = $conn->prepare("SELECT stationery_id, item_name, category, quantity_available, minimum_stock FROM stationery WHERE stationery_id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("i", $stationery_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) return false;
        $item = $res->fetch_assoc();

        if ($item['quantity_available'] <= $item['minimum_stock']) {
            // Deduplication: check if an unread LOW_STOCK notification already exists for this item
            $dup_check = $conn->prepare("SELECT notification_id FROM notifications WHERE reference_id = ? AND notification_type = 'LOW_STOCK' AND is_read = 0");
            $dup_check->bind_param("i", $stationery_id);
            $dup_check->execute();
            if ($dup_check->get_result()->num_rows === 0) {
                // Generate notification
                $title = "Low Stock Alert: " . $item['item_name'];
                $msg = "Item Name: " . $item['item_name'] . "\n" .
                       "Item Code: #STN-" . str_pad($item['stationery_id'], 4, '0', STR_PAD_LEFT) . "\n" .
                       "Current Quantity: " . $item['quantity_available'] . "\n" .
                       "Minimum Required Quantity: " . $item['minimum_stock'] . "\n" .
                       "Category: " . ($item['category'] ?? 'General') . "\n" .
                       "Date & Time: " . date("Y-m-d H:i:s");
                
                return create_notification($conn, null, null, 'ADMIN', 'LOW_STOCK', $title, $msg, $stationery_id);
            }
        }
        return false;
    }
}

if (!function_exists('resolve_low_stock_notification')) {
    function resolve_low_stock_notification($conn, $stationery_id) {
        ensure_notifications_table($conn);
        // If stock is now above minimum_stock, mark past unread LOW_STOCK notifications for this item as read
        $stmt = $conn->prepare("SELECT quantity_available, minimum_stock FROM stationery WHERE stationery_id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("i", $stationery_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $item = $res->fetch_assoc();
            if ($item['quantity_available'] > $item['minimum_stock']) {
                $upd = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE reference_id = ? AND notification_type = 'LOW_STOCK'");
                $upd->bind_param("i", $stationery_id);
                $upd->execute();
            }
        }
    }
}
?>
