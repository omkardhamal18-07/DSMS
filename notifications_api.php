<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

include("database/db.php");
include_once("notification_helper.php");

ensure_notifications_table($conn);

$user_id = intval($_SESSION['user_id']);
$user_role = $_SESSION['role'] ?? 'FACULTY';
$action = $_REQUEST['action'] ?? '';

// Helper for relative timestamps
function get_relative_time($diff_seconds, $datetime_str) {
    $diff_seconds = max(0, intval($diff_seconds));
    
    if ($diff_seconds < 60) {
        return "Just now";
    } elseif ($diff_seconds < 3600) {
        $mins = floor($diff_seconds / 60);
        return $mins . " min ago";
    } elseif ($diff_seconds < 86400) {
        $hours = floor($diff_seconds / 3600);
        return $hours . ($hours == 1 ? " hour ago" : " hours ago");
    } elseif ($diff_seconds < 172800) {
        return "Yesterday";
    } elseif ($diff_seconds < 604800) {
        $days = floor($diff_seconds / 86400);
        return $days . " days ago";
    } else {
        return date("j M Y", strtotime($datetime_str));
    }
}

// Helper to construct role WHERE clause
function get_role_where_sql($user_role, $user_id, &$params, &$types) {
    if ($user_role === 'ADMIN') {
        return "(receiver_role = 'ADMIN' OR receiver_role = 'ALL')";
    } else {
        $params[] = $user_id;
        $types .= "i";
        return "((receiver_role = 'FACULTY' AND (receiver_id = ? OR receiver_id IS NULL)) OR receiver_role = 'ALL')";
    }
}

if ($action === 'unread_count') {
    $params = [];
    $types = "";
    $role_where = get_role_where_sql($user_role, $user_id, $params, $types);
    
    $sql = "SELECT COUNT(*) as unread FROM notifications WHERE is_read = 0 AND $role_where";
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    
    echo json_encode(['success' => true, 'unread_count' => intval($res['unread'])]);
    exit();
}

if ($action === 'fetch') {
    $filter = isset($_GET['filter']) ? trim($_GET['filter']) : 'ALL';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
    $offset = ($page - 1) * $limit;

    $params = [];
    $types = "";
    
    $role_where = get_role_where_sql($user_role, $user_id, $params, $types);
    $where_conditions = [$role_where];

    if ($filter !== 'ALL' && in_array($filter, ['FACULTY_REQUEST', 'LOW_STOCK', 'STOCK_UPDATED', 'REQUEST_STATUS'])) {
        $where_conditions[] = "notification_type = ?";
        $params[] = $filter;
        $types .= "s";
    }

    if (!empty($search)) {
        $where_conditions[] = "(title LIKE ? OR message LIKE ?)";
        $search_param = "%" . $search . "%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }

    $where_sql = implode(" AND ", $where_conditions);

    // Count Total
    $count_sql = "SELECT COUNT(*) as total FROM notifications WHERE $where_sql";
    $stmt_count = $conn->prepare($count_sql);
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $total_records = $stmt_count->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $limit);

    // Count Unread
    $params_unread = [];
    $types_unread = "";
    $role_where_unread = get_role_where_sql($user_role, $user_id, $params_unread, $types_unread);
    $unread_sql = "SELECT COUNT(*) as unread FROM notifications WHERE is_read = 0 AND $role_where_unread";
    $stmt_unread = $conn->prepare($unread_sql);
    if (!empty($params_unread)) {
        $stmt_unread->bind_param($types_unread, ...$params_unread);
    }
    $stmt_unread->execute();
    $unread_count = $stmt_unread->get_result()->fetch_assoc()['unread'];

    // Fetch Notifications
    $fetch_sql = "SELECT *, TIMESTAMPDIFF(SECOND, created_at, NOW()) AS diff_seconds FROM notifications WHERE $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt_fetch = $conn->prepare($fetch_sql);
    $stmt_fetch->bind_param($types, ...$params);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $row['relative_time'] = get_relative_time($row['diff_seconds'], $row['created_at']);
        $row['formatted_date'] = date("d M Y, h:i A", strtotime($row['created_at']));
        $notifications[] = $row;
    }

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'total_records' => intval($total_records),
        'unread_count' => intval($unread_count),
        'total_pages' => intval($total_pages),
        'current_page' => $page
    ]);
    exit();
}

if ($action === 'mark_read') {
    $notification_id = intval($_POST['notification_id'] ?? 0);
    $new_status = 1;

    if ($notification_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid notification ID.']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE notifications SET is_read = ? WHERE notification_id = ?");
    $stmt->bind_param("ii", $new_status, $notification_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Notification status updated.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed.']);
    }
    exit();
}

if ($action === 'mark_all_read') {
    $params = [];
    $types = "";
    $role_where = get_role_where_sql($user_role, $user_id, $params, $types);

    $sql = "UPDATE notifications SET is_read = 1 WHERE is_read = 0 AND $role_where";
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'All notifications marked as read.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark all as read.']);
    }
    exit();
}

if ($action === 'delete') {
    $notification_id = intval($_POST['notification_id'] ?? 0);

    if ($notification_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid notification ID.']);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM notifications WHERE notification_id = ?");
    $stmt->bind_param("i", $notification_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Notification deleted.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete notification.']);
    }
    exit();
}

if ($action === 'get_request_details') {
    $request_id = intval($_GET['request_id'] ?? 0);
    if ($request_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
        exit();
    }

    $stmt = $conn->prepare("
        SELECT r.*, u.name as faculty_name, u.email as faculty_email, u.department, u.contact_number, 
               s.item_name, s.category, s.quantity_available, s.unit,
               rev.name as reviewer_name
        FROM stationery_requests r
        JOIN users u ON r.faculty_id = u.user_id
        JOIN stationery s ON r.stationery_id = s.stationery_id
        LEFT JOIN users rev ON r.reviewed_by = rev.user_id
        WHERE r.request_id = ?
    ");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Request details not found.']);
        exit();
    }

    $data = $res->fetch_assoc();
    $data['formatted_date'] = date("d M Y, h:i A", strtotime($data['request_date']));
    if (!empty($data['review_date'])) {
        $data['formatted_review_date'] = date("d M Y, h:i A", strtotime($data['review_date']));
    }

    echo json_encode(['success' => true, 'data' => $data]);
    exit();
}

if ($action === 'submit_faculty_request') {
    if ($user_role !== 'FACULTY') {
        echo json_encode(['success' => false, 'message' => 'Only faculty members can submit requests.']);
        exit();
    }

    $stationery_id = intval($_POST['stationery_id'] ?? 0);
    $requested_quantity = intval($_POST['requested_quantity'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');

    if ($stationery_id <= 0 || $requested_quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please select an item and enter a valid quantity.']);
        exit();
    }

    // Fetch user details
    $u_stmt = $conn->prepare("SELECT name, department FROM users WHERE user_id = ?");
    $u_stmt->bind_param("i", $user_id);
    $u_stmt->execute();
    $user_data = $u_stmt->get_result()->fetch_assoc();
    $faculty_name = $user_data['name'] ?? 'Faculty Member';
    $faculty_dept = $user_data['department'] ?? 'General';

    // Fetch stationery details
    $s_stmt = $conn->prepare("SELECT item_name, unit FROM stationery WHERE stationery_id = ?");
    $s_stmt->bind_param("i", $stationery_id);
    $s_stmt->execute();
    $s_res = $s_stmt->get_result();
    if ($s_res->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Stationery item not found.']);
        exit();
    }
    $item_data = $s_res->fetch_assoc();
    $item_name = $item_data['item_name'];
    $unit = $item_data['unit'] ?? 'Units';

    // Insert Request
    $stmt = $conn->prepare("INSERT INTO stationery_requests (faculty_id, stationery_id, requested_quantity, remarks, status, request_date) VALUES (?, ?, ?, ?, 'PENDING', NOW())");
    $stmt->bind_param("iiis", $user_id, $stationery_id, $requested_quantity, $remarks);

    if ($stmt->execute()) {
        $req_id = $stmt->insert_id;

        // Generate Notification for Admin
        $title = "New Faculty Request from " . $faculty_name;
        $msg = "Faculty Name: " . $faculty_name . "\n" .
               "Faculty ID: #FAC-" . str_pad($user_id, 4, '0', STR_PAD_LEFT) . "\n" .
               "Department: " . $faculty_dept . "\n" .
               "Request Type: Stationery (" . $item_name . " x " . $requested_quantity . " " . $unit . ")\n" .
               "Short Description: " . (!empty($remarks) ? $remarks : "No additional description provided.") . "\n" .
               "Date & Time: " . date("Y-m-d H:i:s");

        create_notification($conn, $user_id, null, 'ADMIN', 'FACULTY_REQUEST', $title, $msg, $req_id);

        echo json_encode(['success' => true, 'message' => 'Request submitted successfully.', 'request_id' => $req_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit request.']);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
?>
