<?php
session_start();
include("database/db.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'approve_request') {
        $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
        $admin_id = $_SESSION['user_id'];
        
        if ($request_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
            exit();
        }
        
        $conn->begin_transaction();
        try {
            // Check request status and quantity
            $stmt = $conn->prepare("SELECT r.stationery_id, r.requested_quantity, r.status, s.quantity_available, s.item_name FROM stationery_requests r JOIN stationery s ON r.stationery_id = s.stationery_id WHERE r.request_id = ?");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                throw new Exception("Request not found.");
            }
            $req = $result->fetch_assoc();
            
            if ($req['status'] !== 'PENDING') {
                throw new Exception("Request is already processed.");
            }
            
            if ($req['quantity_available'] < $req['requested_quantity']) {
                throw new Exception("Insufficient stock available for " . $req['item_name'] . ". Please update inventory first or Reject the request.");
            }
            
            // Deduct stock
            $new_qty = $req['quantity_available'] - $req['requested_quantity'];
            $update_stock = $conn->prepare("UPDATE stationery SET quantity_available = ? WHERE stationery_id = ?");
            $update_stock->bind_param("ii", $new_qty, $req['stationery_id']);
            $update_stock->execute();
            
            // Log in stock history
            $action_log = "Reduced by " . $req['requested_quantity'] . " (Approved Request #" . $request_id . ")";
            $log_stmt = $conn->prepare("INSERT INTO stock_history (stationery_id, previous_quantity, new_quantity, action, admin_id) VALUES (?, ?, ?, ?, ?)");
            $log_stmt->bind_param("iiisi", $req['stationery_id'], $req['quantity_available'], $new_qty, $action_log, $admin_id);
            $log_stmt->execute();
            
            // Update request status
            $update_req = $conn->prepare("UPDATE stationery_requests SET status = 'APPROVED', reviewed_by = ?, review_date = NOW() WHERE request_id = ?");
            $update_req->bind_param("ii", $admin_id, $request_id);
            $update_req->execute();
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Request approved and stock deducted successfully.']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
    
    if ($action === 'reject_request') {
        $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
        $reason = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';
        $admin_id = $_SESSION['user_id'];
        
        if ($request_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
            exit();
        }
        
        if (empty($reason)) {
            echo json_encode(['success' => false, 'message' => 'Rejection reason is mandatory.']);
            exit();
        }
        
        $conn->begin_transaction();
        try {
            // Check request status
            $stmt = $conn->prepare("SELECT status FROM stationery_requests WHERE request_id = ?");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                throw new Exception("Request not found.");
            }
            $req = $result->fetch_assoc();
            
            if ($req['status'] !== 'PENDING') {
                throw new Exception("Request is already processed.");
            }
            
            // Update request status
            $update_req = $conn->prepare("UPDATE stationery_requests SET status = 'REJECTED', remarks = ?, reviewed_by = ?, review_date = NOW() WHERE request_id = ?");
            $update_req->bind_param("sii", $reason, $admin_id, $request_id);
            $update_req->execute();
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Request rejected successfully.']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit();
}
?>
