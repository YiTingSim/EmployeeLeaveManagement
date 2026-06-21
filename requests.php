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

// 2. PROTECTED ACTION HANDLER: Only execute if the user is an authorized Manager
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    if ($user_role !== 'Manager') {
        $message = "<div class='alert error'>❌ <strong>Security Violation:</strong> Staff members do not have approval clearance.</div>";
    } else {
        $request_id = intval($_POST['request_id']);
        $new_status = $_POST['status_action'] === 'Approve' ? 'Approved' : 'Rejected';

        // ANTI-SELF-APPROVAL GATE
        $identity_stmt = $conn->prepare("SELECT employee_id FROM leave_requests WHERE id = ?");
        $identity_stmt->bind_param("i", $request_id);
        $identity_stmt->execute();
        $identity_result = $identity_stmt->get_result()->fetch_assoc();
        $identity_stmt->close();

        if ($identity_result && $identity_result['employee_id'] === $current_user_id) {
            $message = "<div class='alert error'>❌ <strong>Access Denied:</strong> Conflict of interest. You cannot approve or reject your own leave requests! Another manager must process this.</div>";
        } else {
            $stmt = $conn->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $request_id);
            
            if ($stmt->execute()) {
                $message = "<div class='alert success'>Transaction complete: Leave request status mutated to $new_status.</div>";
            } else {
                $message = "<div class='alert error'>Processing fault error.</div>";
            }
            $stmt->close();
        }
    }
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
<body class="<?php echo ($user_role === 'Manager') ? 'manager-layout' : 'employee-layout'; ?>">

<?php if ($user_role === 'Manager'): ?>
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
                            <th>Reasons</th>
                            <th style="text-align: right;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Dynamically filters by whoever is logged in ($current_user_id)
                        $stmt_own = $conn->prepare("SELECT id, employee_id, leave_type, start_date, end_date, reason FROM leave_requests WHERE employee_id = ? AND status = 'Pending' ORDER BY id ASC");
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

        <?php if ($user_role === 'Manager'): ?>
        <section class="neon-card">
            <div class="card-title"><i class="fa-solid fa-list-check" style="color: var(--accent-primary);"></i> Employee Leave Request</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Emp ID</th>
                            <th>Leave Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Reasons</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt_others = $conn->prepare("SELECT id, employee_id, leave_type, start_date, end_date, reason FROM leave_requests WHERE employee_id != ? AND status = 'Pending' ORDER BY id ASC");
                        $stmt_others->bind_param("s", $current_user_id);
                        $stmt_others->execute();
                        $result_others = $stmt_others->get_result();

                        if ($result_others && $result_others->num_rows > 0) {
                            while($row = $result_others->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td><strong>" . htmlspecialchars($row['employee_id']) . "</strong></td>";
                                echo "<td>" . htmlspecialchars($row['leave_type']) . "</td>";
                                echo "<td>" . $row['start_date'] . "</td>";
                                echo "<td>" . $row['end_date'] . "</td>";
                                echo "<td>" . htmlspecialchars($row['reason']) . "</td>";
                                echo "<td style='text-align: right;'>
                                        <form method='POST' style='display:inline-flex; gap: 8px;'>
                                            <input type='hidden' name='request_id' value='".$row['id']."'>
                                            <button type='submit' name='status_action' value='Approve' class='badge approved' style='border:none; cursor:pointer;'>Approve</button>
                                            <button type='submit' name='status_action' value='Reject' class='badge rejected' style='border:none; cursor:pointer;'>Reject</button>
                                            <input type='hidden' name='update_status' value='1'>
                                        </form>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; color: var(--text-muted); padding: 2rem;'>All operational team records are current. No pending evaluations.</td></tr>";
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