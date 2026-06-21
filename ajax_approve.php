<?php
session_start();

// Only managers and admins can approve
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['Manager', 'Admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = new mysqli("localhost", "root", "", "leave_management");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['request_id']) || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit();
}

$request_id = (int)$input['request_id'];
$action = $input['action']; // 'Approve' or 'Reject'
$current_user = $_SESSION['user_id'];
$role = $_SESSION['user_role'];

// 1. Fetch request details and requester info
$stmt = $conn->prepare("
    SELECT lr.employee_id, lr.status, e.role as requester_role, e.manager_emp_id
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.emp_id
    WHERE lr.id = ?
");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Request not found']);
    exit();
}
$row = $result->fetch_assoc();
$stmt->close();

// 2. Security checks
if ($row['status'] !== 'Pending') {
    echo json_encode(['success' => false, 'message' => 'Request already processed']);
    exit();
}
if ($row['employee_id'] === $current_user) {
    echo json_encode(['success' => false, 'message' => 'You cannot approve your own request']);
    exit();
}

if ($role === 'Admin') {
    // Admin can approve only Managers
    if ($row['requester_role'] !== 'Manager') {
        echo json_encode(['success' => false, 'message' => 'Admins can only approve Managers']);
        exit();
    }
} elseif ($role === 'Manager') {
    // Manager can approve only employees who report to them
    if ($row['requester_role'] !== 'Employee' || $row['manager_emp_id'] !== $current_user) {
        echo json_encode(['success' => false, 'message' => 'You are not authorized for this request']);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit();
}

// 3. Update the request
$new_status = ($action === 'Approve') ? 'Approved' : 'Rejected';
$date_field = ($action === 'Approve') ? 'approval_date' : 'rejection_date';

$update = $conn->prepare("
    UPDATE leave_requests
    SET status = ?, approver_emp_id = ?, $date_field = NOW()
    WHERE id = ?
");
$update->bind_param("ssi", $new_status, $current_user, $request_id);
if (!$update->execute()) {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
    exit();
}
$update->close();

// 4. Insert notification for the employee
$msg = ($action === 'Approve')
    ? "Your leave request #$request_id has been <strong>Approved</strong> by $current_user."
    : "Your leave request #$request_id has been <strong>Rejected</strong> by $current_user.";

$notif = $conn->prepare("INSERT INTO notifications (employee_id, request_id, message) VALUES (?, ?, ?)");
$notif->bind_param("sis", $row['employee_id'], $request_id, $msg);
$notif->execute();
$notif->close();

$conn->close();

echo json_encode([
    'success' => true,
    'message' => "Request successfully $new_status",
    'new_status' => $new_status,
    'request_id' => $request_id
]);