<?php
session_start();

// SECURITY CHECK GATE: Verify authorization level
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Manager') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "leave_management");
if ($conn->connect_error) { die("Database link failure."); }

$message = "";

// Handle Approval / Rejection Actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $request_id = intval($_POST['request_id']);
    $new_status = $_POST['status_action'] === 'Approve' ? 'Approved' : 'Rejected';
    $current_manager_id = $_SESSION['user_id']; // The ID of the manager currently using the kiosk

    // ----------------------------------------------------------------------
    // ANTI-SELF-APPROVAL GATE: Check who originally made this leave request
    // ----------------------------------------------------------------------
    $identity_stmt = $conn->prepare("SELECT employee_id FROM leave_requests WHERE id = ?");
    $identity_stmt->bind_param("i", $request_id);
    $identity_stmt->execute();
    $identity_result = $identity_stmt->get_result()->fetch_assoc();
    $identity_stmt->close();

    if ($identity_result && $identity_result['employee_id'] === $current_manager_id) {
        // SCENARIO DETECTED: Manager is trying to approve their own application
        $message = "<div class='alert error'>❌ <strong>Access Denied:</strong> Conflict of interest. You cannot approve or reject your own leave requests! Another manager must process this.</div>";
    } else {
        // SECURE: Request belongs to someone else, proceed with the database update
        $stmt = $conn->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $request_id);
        
        if ($stmt->execute()) {
            $message = "<div class='alert success'>Transaction complete: Leave request status mutated to $new_status.</div>";
        } else {
            $message = "<div class='alert error'>Processing fault error.</div>";
        }
        $stmt->close();
    }
    // ----------------------------------------------------------------------
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quantum | Manage Requests</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="leave_management.css">
</head>
<body class="<?php echo ($_SESSION['user_role'] === 'Manager') ? 'manager-layout' : 'employee-layout'; ?>">
<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Manager'): ?>
    <aside id="sidebar">
        <div class="brand"><i class="fa-solid fa-layer-group"></i> <span>DayAway</span>
		</div>
        <ul class="nav-links">
            <li><a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            
            <?php if ($_SESSION['user_role'] === 'Manager'): ?>
                <li class="active"><a href="requests.php"><i class="fa-solid fa-calendar-check"></i> Leave Requests</a></li>
                <li><a href="employees.php"><i class="fa-solid fa-users"></i> Employees</a></li>
                <li><a href="analytics.php"><i class="fa-solid fa-chart-pie"></i> Analytics</a></li>
            <?php endif; ?>
            
            <li style="margin-top: auto;"><a href="logout.php" style="color: #ef4444;"><i class="fa-solid fa-power-off"></i> Logout</a></li>
        </ul>
    </aside>
	
	<?php else: ?>
    <nav class="top-navbar">
        <div class="nav-container">
            <div class="brand">
                <i class="fa-solid fa-layer-group"></i>
                <span>DayAway</span>
            </div>
            <div class="top-nav-links">
                <a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="logout.php" class="logout-link"><i class="fa-solid fa-power-off"></i> Exit</a>
			</div>
		</div>
    </nav>
	
	
	
<?php endif; ?>


    <main>
        <header>
            <div>
                <h1>Leave Approvals Terminal</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Authoritative Pending Operations Overview</p>
            </div>
        </header>

        <?php echo $message; ?>

        <section class="neon-card">
            <div class="card-title"><i class="fa-solid fa-list-check" style="color: var(--accent-primary);"></i> Operations Pipeline</div>
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
                        $sql = "SELECT id, employee_id, leave_type, start_date, end_date, reason FROM leave_requests WHERE status = 'Pending' ORDER BY id ASC";
                        $result = $conn->query($sql);
                        if ($result && $result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
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
                            echo "<tr><td colspan='6' style='text-align:center; color: var(--text-muted); padding: 2rem;'>All operational records are current. No pending evaluations.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
<?php $conn->close(); ?>