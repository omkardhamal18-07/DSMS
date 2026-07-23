<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

include("database/db.php");

$action = $_POST['action'] ?? '';

if ($action === 'issue_request') {
    $request_id = intval($_POST['request_id']);
    $issue_qty = intval($_POST['issue_quantity']);
    $remarks = trim($_POST['remarks'] ?? '');
    $admin_id = $_SESSION['user_id'];

    // Validate request
    $req_query = $conn->prepare("SELECT * FROM stationery_requests WHERE request_id = ? AND status = 'APPROVED'");
    $req_query->bind_param("i", $request_id);
    $req_query->execute();
    $req_res = $req_query->get_result();
    
    if ($req_res->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid or already processed request.']);
        exit();
    }
    $req = $req_res->fetch_assoc();
    $faculty_id = $req['faculty_id'];
    $stationery_id = $req['stationery_id'];

    if ($issue_qty <= 0) {
         echo json_encode(['success' => false, 'message' => 'Issue quantity must be greater than zero.']);
         exit();
    }

    // Check stock
    $stock_query = $conn->prepare("SELECT quantity_available FROM stationery WHERE stationery_id = ?");
    $stock_query->bind_param("i", $stationery_id);
    $stock_query->execute();
    $stock_res = $stock_query->get_result();
    $stock = $stock_res->fetch_assoc();

    if ($issue_qty > $stock['quantity_available']) {
        echo json_encode(['success' => false, 'message' => 'Insufficient stock for this issue.']);
        exit();
    }

    $conn->begin_transaction();
    try {
        // 1. Create issue_records
        $source = 'REQUEST';
        $stmt = $conn->prepare("INSERT INTO issue_records (request_id, faculty_id, issue_source, issued_by, remarks) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisis", $request_id, $faculty_id, $source, $admin_id, $remarks);
        $stmt->execute();
        $issue_id = $conn->insert_id;

        // 2. Create issue_items (trigger deducts stock)
        $stmt2 = $conn->prepare("INSERT INTO issue_items (issue_id, stationery_id, issued_quantity) VALUES (?, ?, ?)");
        $stmt2->bind_param("iii", $issue_id, $stationery_id, $issue_qty);
        $stmt2->execute();

        // 3. Update request status
        $stmt3 = $conn->prepare("UPDATE stationery_requests SET status = 'COMPLETED' WHERE request_id = ?");
        $stmt3->bind_param("i", $request_id);
        $stmt3->execute();

        // 4. Activity Log
        $activity = "Issued stationery (Req #$request_id)";
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)");
        $log_stmt->bind_param("is", $admin_id, $activity);
        $log_stmt->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Stationery issued successfully from request.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
elseif ($action === 'issue_direct') {
    $faculty_id = intval($_POST['faculty_id']);
    $issue_date = $_POST['issue_date'];
    $remarks = trim($_POST['remarks'] ?? '');
    $admin_id = $_SESSION['user_id'];
    
    // Parse items array: items[0][id], items[0][qty]
    $items = $_POST['items'] ?? [];
    
    if (empty($items) || !is_array($items)) {
        echo json_encode(['success' => false, 'message' => 'No items selected.']);
        exit();
    }
    
    $conn->begin_transaction();
    try {
        // 1. Create issue_records
        $source = 'DIRECT';
        // Validate date
        if(empty($issue_date)){
            $issue_date = date('Y-m-d H:i:s');
        } else {
            $issue_date = date('Y-m-d H:i:s', strtotime($issue_date));
        }

        $stmt = $conn->prepare("INSERT INTO issue_records (faculty_id, issue_source, issued_by, issue_date, remarks) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isiss", $faculty_id, $source, $admin_id, $issue_date, $remarks);
        $stmt->execute();
        $issue_id = $conn->insert_id;

        // 2. Insert items and check stock
        $stmt2 = $conn->prepare("INSERT INTO issue_items (issue_id, stationery_id, issued_quantity) VALUES (?, ?, ?)");
        $check_stock = $conn->prepare("SELECT quantity_available, item_name FROM stationery WHERE stationery_id = ?");
        
        foreach ($items as $item) {
            $stat_id = intval($item['id']);
            $qty = intval($item['qty']);
            
            if ($qty <= 0) continue;
            
            // Check stock
            $check_stock->bind_param("i", $stat_id);
            $check_stock->execute();
            $res = $check_stock->get_result();
            if ($res->num_rows === 0) {
                 throw new Exception("Invalid item ID: $stat_id");
            }
            $stock_data = $res->fetch_assoc();
            
            if ($qty > $stock_data['quantity_available']) {
                 throw new Exception("Insufficient stock for " . $stock_data['item_name']);
            }
            
            $stmt2->bind_param("iii", $issue_id, $stat_id, $qty);
            $stmt2->execute();
        }

        // 3. Activity Log
        $activity = "Directly issued stationery to Faculty ID $faculty_id (Issue #$issue_id)";
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)");
        $log_stmt->bind_param("is", $admin_id, $activity);
        $log_stmt->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Direct issue completed successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
elseif ($action === 'get_issue_details') {
    $issue_id = intval($_POST['issue_id']);
    
    $query = "SELECT ir.*, u.name as faculty_name, u.department, u2.name as issued_by_name 
              FROM issue_records ir
              JOIN users u ON ir.faculty_id = u.user_id
              JOIN users u2 ON ir.issued_by = u2.user_id
              WHERE ir.issue_id = ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $issue_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Issue not found']);
        exit();
    }
    $record = $res->fetch_assoc();
    
    $items_query = "SELECT ii.*, s.item_name FROM issue_items ii 
                    JOIN stationery s ON ii.stationery_id = s.stationery_id 
                    WHERE ii.issue_id = ?";
    $stmt2 = $conn->prepare($items_query);
    $stmt2->bind_param("i", $issue_id);
    $stmt2->execute();
    $items_res = $stmt2->get_result();
    
    $items = [];
    while($row = $items_res->fetch_assoc()) {
        $items[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'record' => $record,
        'items' => $items
    ]);
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
?>
