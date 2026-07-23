<?php
session_start();
include("database/db.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FACULTY') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    // Action: Submit Request
    if ($action === 'submit_request') {
        $faculty_id = $_SESSION['user_id'];
        $stationery_id = isset($_POST['stationery_id']) ? intval($_POST['stationery_id']) : 0;
        $qty = isset($_POST['requested_quantity']) ? intval($_POST['requested_quantity']) : 0;
        $priority = trim($_POST['priority'] ?? 'MEDIUM');
        $required_date = trim($_POST['required_date'] ?? '');
        $purpose = trim($_POST['purpose'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        // Validation
        if ($stationery_id <= 0 || $qty <= 0 || empty($required_date) || empty($purpose)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
            exit();
        }

        if (!in_array($priority, ['LOW', 'MEDIUM', 'HIGH'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid priority value selected.']);
            exit();
        }

        // Validate date is not in the past
        $today = date('Y-m-d');
        if ($required_date < $today) {
            echo json_encode(['success' => false, 'message' => 'Required date cannot be in the past.']);
            exit();
        }

        // Verify stationery item exists
        $item_stmt = $conn->prepare("SELECT stationery_id FROM stationery WHERE stationery_id = ?");
        $item_stmt->bind_param("i", $stationery_id);
        $item_stmt->execute();
        if ($item_stmt->get_result()->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Stationery item not found in inventory.']);
            exit();
        }

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO stationery_requests (faculty_id, stationery_id, requested_quantity, purpose, priority, required_date, remarks, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING')");
        $stmt->bind_param("iiissss", $faculty_id, $stationery_id, $qty, $purpose, $priority, $required_date, $remarks);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Stationery request submitted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit request: ' . $conn->error]);
        }
        exit();
    }

    // Action: Cancel Request
    if ($action === 'cancel_request') {
        $faculty_id = $_SESSION['user_id'];
        $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;

        if ($request_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
            exit();
        }

        // Verify request exists, belongs to faculty, and is PENDING
        $check_stmt = $conn->prepare("SELECT status FROM stationery_requests WHERE request_id = ? AND faculty_id = ?");
        $check_stmt->bind_param("ii", $request_id, $faculty_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Request not found.']);
            exit();
        }

        $req = $result->fetch_assoc();
        if ($req['status'] !== 'PENDING') {
            echo json_encode(['success' => false, 'message' => 'Only pending requests can be cancelled.']);
            exit();
        }

        // Delete request or set status to CANCELLED? The requirements say "cancel pending requests". 
        // We will delete the request to keep it clean, or we could change the status. Let's delete it.
        $delete_stmt = $conn->prepare("DELETE FROM stationery_requests WHERE request_id = ? AND faculty_id = ? AND status = 'PENDING'");
        $delete_stmt->bind_param("ii", $request_id, $faculty_id);

        if ($delete_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Request cancelled and removed successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to cancel request.']);
        }
        exit();
    }

    // Action: Edit Request
    if ($action === 'edit_request') {
        $faculty_id = $_SESSION['user_id'];
        $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
        $qty = isset($_POST['requested_quantity']) ? intval($_POST['requested_quantity']) : 0;
        $priority = trim($_POST['priority'] ?? 'MEDIUM');
        $required_date = trim($_POST['required_date'] ?? '');
        $purpose = trim($_POST['purpose'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        if ($request_id <= 0 || $qty <= 0 || empty($required_date) || empty($purpose)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
            exit();
        }

        if (!in_array($priority, ['LOW', 'MEDIUM', 'HIGH'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid priority value.']);
            exit();
        }

        // Validate date
        $today = date('Y-m-d');
        if ($required_date < $today) {
            echo json_encode(['success' => false, 'message' => 'Required date cannot be in the past.']);
            exit();
        }

        // Verify request belongs to faculty and is PENDING
        $check_stmt = $conn->prepare("SELECT status FROM stationery_requests WHERE request_id = ? AND faculty_id = ?");
        $check_stmt->bind_param("ii", $request_id, $faculty_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Request not found.']);
            exit();
        }

        $req = $result->fetch_assoc();
        if ($req['status'] !== 'PENDING') {
            echo json_encode(['success' => false, 'message' => 'Only pending requests can be edited.']);
            exit();
        }

        // Update the request
        $update_stmt = $conn->prepare("UPDATE stationery_requests SET requested_quantity = ?, purpose = ?, priority = ?, required_date = ?, remarks = ? WHERE request_id = ? AND faculty_id = ? AND status = 'PENDING'");
        $update_stmt->bind_param("issssii", $qty, $purpose, $priority, $required_date, $remarks, $request_id, $faculty_id);

        if ($update_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Request updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update request.']);
        }
        exit();
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid action requested.']);
?>
