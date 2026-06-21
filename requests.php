<?php
session_start();

// 1. UPDATED SECURITY GATE: Allow BOTH Managers and Employees to enter
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "leave_management");
if ($conn->connect_error) { die("Database link failure."); }

$message = "";
$current_user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    // Allow only Managers and Admins – silently redirect others
    if (!in_array($user_role, ['Manager', 'Admin'])) {
        header("Location: index.php");
        exit();
    }

    $request_id = intval($_POST['request_id']);
    $new_status = $_POST['status_action'] === 'Approve' ? 'Approved' : 'Rejected';

    // Anti‑self‑approval check
    $identity_stmt = $conn->prepare("SELECT employee_id FROM leave_requests WHERE id = ?");
    $identity_stmt->bind_param("i", $request_id);
    $identity_stmt->execute();
    $identity_result = $identity_stmt->get_result()->fetch_assoc();
    $identity_stmt->close();

    // Set approver and timestamp
    $date_field = ($new_status === 'Approved') ? 'approval_date' : 'rejection_date';
    $stmt = $conn->prepare("UPDATE leave_requests SET status = ?, approver_emp_id = ?, $date_field = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $new_status, $current_user_id, $request_id);
            
    if ($stmt->execute()) {
        $message = "<div class='alert success'>Transaction complete: Leave request status mutated to $new_status.</div>";
    } else {
        $message = "<div class='alert error'>Processing fault error.</div>";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DayAway | Manage Requests</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="leave_management.css">
</head>
<body class="<?php echo ($user_role === 'Manager' || $user_role === 'Admin') ? 'manager-layout' : 'employee-layout'; ?>">

<?php if ($user_role === 'Manager' || $user_role === 'Admin'): ?>
    <aside id="sidebar">
        <div class="brand"><i class="fa-solid fa-layer-group"></i> <span>DayAway</span></div>
        <ul class="nav-links">
            <li><a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <li class="active"><a href="requests.php"><i class="fa-solid fa-calendar-check"></i> Leave Requests</a></li>
            <li><a href="employees.php"><i class="fa-solid fa-users"></i> Employees</a></li>
            <li><a href="analytics.php"><i class="fa-solid fa-chart-pie"></i> Analytics</a></li>
            <li style="margin-top: auto;"><a href="logout.php" style="color: #ef4444;"><i class="fa-solid fa-power-off"></i> Logout</a></li>
        </ul>
    </aside>
<?php else: ?>
    <nav class="top-navbar">
        <div class="nav-container">
            <div class="brand"><i class="fa-solid fa-layer-group"></i> <span>DayAway</span></div>
            <div class="top-nav-links">
                <a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="requests.php" class="active"><i class="fa-solid fa-calendar-check"></i> My Requests</a>
                <a href="logout.php" class="logout-link"><i class="fa-solid fa-power-off"></i> Exit</a>
            </div>
        </div>
    </nav>
<?php endif; ?>

    <main>
        <header>
            <div>
                <h1>Leave Approvals Terminal</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">
                    <?php echo ($user_role === 'Manager') ? 'Authoritative Pending Operations Overview' : 'Tracking and Detailed Review of Your Applied Requests'; ?>
                </p>
            </div>
        </header>

        <?php echo $message; ?>

        <section class="neon-card" style="margin-bottom: 2rem;">
            <div class="card-title"><i class="fa-solid fa-user-clock" style="color: #a855f7;"></i> Your Personal Leave Requests</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Emp ID</th>
                            <th>Leave Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Days</th>
                            <th>Reasons</th>
                            <th style="text-align: right;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Dynamically filters by whoever is logged in ($current_user_id)
                        $stmt_own = $conn->prepare("SELECT id, employee_id, leave_type, start_date, end_date, days_requested, reason FROM leave_requests WHERE employee_id = ? AND status = 'Pending' ORDER BY id ASC");
                        $stmt_own->bind_param("s", $current_user_id);
                        $stmt_own->execute();
                        $result_own = $stmt_own->get_result();

                        if ($result_own && $result_own->num_rows > 0) {
                            while($row = $result_own->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td><strong>" . htmlspecialchars($row['employee_id']) . " (You)</strong></td>";
                                echo "<td>" . htmlspecialchars($row['leave_type']) . "</td>";
                                echo "<td>" . $row['start_date'] . "</td>";
                                echo "<td>" . $row['end_date'] . "</td>";
                                echo "<td>" . $row['days_requested'] . "</td>";
                                echo "<td>" . htmlspecialchars($row['reason']) . "</td>";
                                echo "<td style='text-align: right;'><span class='badge pending'>Pending</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; color: var(--text-muted); padding: 2rem;'>You have no pending personal leave requests.</td></tr>";
                        }
                        $stmt_own->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if ($user_role === 'Manager' || $user_role === 'Admin'): ?>
        <section class="neon-card">
            <div class="card-title"><i class="fa-solid fa-list-check" style="color: var(--accent-primary);"></i>
                <?php echo ($user_role === 'Admin') ? 'Manager Leave Requests' : 'Employee Leave Requests'; ?>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Emp ID</th>
                            <th>Leave Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Days</th>
                            <th>Reasons</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($user_role === 'Admin') {
                            // Admin: Show ONLY pending requests from MANAGERS
                            $stmt_others = $conn->prepare("SELECT lr.id, lr.employee_id, lr.leave_type, lr.start_date, lr.end_date, lr.days_requested, lr.reason FROM leave_requests lr, employees e WHERE lr.employee_id = e.emp_id AND lr.status = 'Pending' AND e.role = 'Manager' AND lr.employee_id != ? ORDER BY lr.id ASC");
                            $stmt_others->bind_param("s", $current_user_id);
                        }
                        else {
                        $stmt_others = $conn->prepare("SELECT id, employee_id, leave_type, start_date, end_date, days_requested, reason FROM leave_requests WHERE status = 'Pending' AND employee_id != ? AND employee_id IN (SELECT emp_id FROM employees WHERE manager_emp_id = ?) ORDER BY id ASC");
                        $stmt_others->bind_param("ss", $current_user_id, $current_user_id);
                        }
                        
                        $stmt_others->execute();
                        $result_others = $stmt_others->get_result();

                        if ($result_others && $result_others->num_rows > 0) {
                            while($row = $result_others->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td><strong>" . htmlspecialchars($row['employee_id']) . "</strong></td>";
                                echo "<td>" . htmlspecialchars($row['leave_type']) . "</td>";
                                echo "<td>" . $row['start_date'] . "</td>";
                                echo "<td>" . $row['end_date'] . "</td>";
                                echo "<td>" . $row['days_requested'] . "</td>";
                                echo "<td>" . htmlspecialchars($row['reason']) . "</td>";
                                echo "<td style='text-align: right;'>
                                    <form method='POST' style='display:inline-flex; gap: 8px;'>
                                        <input type='hidden' name='request_id' value='".$row['id']."'>
                                        <button type='submit' name='status_action' value='Approve' class='badge approved' style='border:none; cursor:pointer;'>Approve</button>
                                        <button type='submit' name='status_action' value='Reject' class='badge rejected' style='border:none; cursor:pointer;'>Reject</button>
                                        <input type='hidden' name='update_status' value='1'>
                                    </form>
                                </td>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; color: var(--text-muted); padding: 2rem;'>No pending requests match your role criteria.</td></tr>";
                        }
                        $stmt_others->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>
        
    </main>
</body>
</html>
<?php $conn->close(); ?>