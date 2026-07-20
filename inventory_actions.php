<?php
session_start();
include("database/db.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$action = $_POST['action'] ?? '';

if ($action === 'add_item') {
    $name = trim($_POST['item_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);
    $min_stock = intval($_POST['minimum_stock'] ?? 0);
    $unit = trim($_POST['unit'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (empty($name) || empty($category) || empty($unit)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
        exit();
    }
    
    if ($quantity < 0 || $min_stock < 0) {
        echo json_encode(['success' => false, 'message' => 'Quantity and Minimum Stock cannot be negative.']);
        exit();
    }

    // Check for duplicate
    $check_stmt = $conn->prepare("SELECT stationery_id FROM stationery WHERE item_name = ?");
    $check_stmt->bind_param("s", $name);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This stationery item already exists. Please update its stock instead.']);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO stationery (item_name, category, quantity_available, minimum_stock, unit, description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiiss", $name, $category, $quantity, $min_stock, $unit, $desc);
    
    if ($stmt->execute()) {
        $new_id = $stmt->insert_id;
        // Log to stock history
        $admin_id = $_SESSION['user_id'];
        $log_stmt = $conn->prepare("INSERT INTO stock_history (stationery_id, previous_quantity, new_quantity, admin_id, action) VALUES (?, 0, ?, ?, 'Initial Stock Added')");
        $log_stmt->bind_param("iii", $new_id, $quantity, $admin_id);
        $log_stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Item added successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    exit();
}

if ($action === 'edit_item') {
    $id = intval($_POST['stationery_id'] ?? 0);
    $name = trim($_POST['item_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $min_stock = intval($_POST['minimum_stock'] ?? 0);
    $unit = trim($_POST['unit'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if ($id <= 0 || empty($name) || empty($category) || empty($unit)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
        exit();
    }

    if ($min_stock < 0) {
        echo json_encode(['success' => false, 'message' => 'Minimum Stock cannot be negative.']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE stationery SET item_name=?, category=?, minimum_stock=?, unit=?, description=? WHERE stationery_id=?");
    $stmt->bind_param("ssissi", $name, $category, $min_stock, $unit, $desc, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Item updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit();
}

if ($action === 'update_stock') {
    $id = intval($_POST['stationery_id'] ?? 0);
    $operation = $_POST['operation'] ?? ''; // 'increase' or 'reduce'
    $amount = intval($_POST['amount'] ?? 0);
    $admin_id = $_SESSION['user_id'];

    if ($id <= 0 || $amount <= 0 || !in_array($operation, ['increase', 'reduce'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid stock update parameters.']);
        exit();
    }

    // Get current quantity
    $stmt = $conn->prepare("SELECT quantity_available, item_name FROM stationery WHERE stationery_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Item not found.']);
        exit();
    }
    $item = $result->fetch_assoc();
    $current_qty = $item['quantity_available'];
    
    $new_qty = ($operation === 'increase') ? ($current_qty + $amount) : ($current_qty - $amount);
    
    if ($new_qty < 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot reduce stock below 0. Current stock is ' . $current_qty]);
        exit();
    }

    $update_stmt = $conn->prepare("UPDATE stationery SET quantity_available = ? WHERE stationery_id = ?");
    $update_stmt->bind_param("ii", $new_qty, $id);
    
    if ($update_stmt->execute()) {
        $action_desc = ($operation === 'increase') ? "Stock Increased by $amount" : "Stock Reduced by $amount";
        $log_stmt = $conn->prepare("INSERT INTO stock_history (stationery_id, previous_quantity, new_quantity, admin_id, action) VALUES (?, ?, ?, ?, ?)");
        $log_stmt->bind_param("iiiis", $id, $current_qty, $new_qty, $admin_id, $action_desc);
        $log_stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Stock updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update stock.']);
    }
    exit();
}

if ($action === 'delete_item') {
    $id = intval($_POST['stationery_id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid item ID.']);
        exit();
    }

    $check_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM stationery_requests WHERE stationery_id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $res = $check_stmt->get_result()->fetch_assoc();
    if ($res['cnt'] > 0) {
         echo json_encode(['success' => false, 'message' => 'Cannot delete item because it has associated faculty requests. Consider updating the stock to 0 instead.']);
         exit();
    }

    $stmt = $conn->prepare("DELETE FROM stationery WHERE stationery_id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Item deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete item.']);
    }
    exit();
}

if ($action === 'reset_inventory') {
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    $stmt1 = $conn->query("TRUNCATE TABLE stationery");
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    
    if ($stmt1) {
        echo json_encode(['success' => true, 'message' => 'Inventory reset successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reset inventory.']);
    }
    exit();
}

if ($action === 'clear_history') {
    if ($conn->query("TRUNCATE TABLE stock_history")) {
        echo json_encode(['success' => true, 'message' => 'Stock history cleared successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to clear stock history.']);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
?>
