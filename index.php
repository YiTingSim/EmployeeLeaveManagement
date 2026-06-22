<?php
session_start();

// Structural validation check to prevent arbitrary bypasses
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root"; 
$password = "";     
$dbname = "leave_management";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Database Connection Failed."); }

$message = "";
$employee_id = $_SESSION['user_id']; 

// ======================================================================
// DYNAMIC LEAVE TRACKER LOGIC: Fetch allocations and compute remaining balance
// ======================================================================
// 1. Fetch the total original baseline maximum allocation from employee registry
$alloc_stmt = $conn->prepare("SELECT allocated_leaves FROM employees WHERE emp_id = ?");
$alloc_stmt->bind_param("s", $employee_id);
$alloc_stmt->execute();
$alloc_res = $alloc_stmt->get_result()->fetch_assoc();
$total_allocated = $alloc_res['allocated_leaves'] ?? 22; // Default fallback matches schema rules
$alloc_stmt->close();

// 2. Sum up total number of days already registered in active status states
$used_stmt = $conn->prepare("SELECT SUM(DATEDIFF(end_date, start_date) + 1) as used FROM leave_requests WHERE employee_id = ? AND status IN ('Approved', 'Pending')");
$used_stmt->bind_param("s", $employee_id);
$used_stmt->execute();
$used_res = $used_stmt->get_result()->fetch_assoc();
$total_used = $used_res['used'] ?? 0;
$used_stmt->close();

// 3. Compute structural remaining available allowance string
$remaining_balance = $total_allocated - $total_used;


// ======================================================================
// PROCESSING LAYER: Form Submissions
// ======================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_leave'])) {
    $leave_type = htmlspecialchars($_POST['leave_type']);
    $start_date = htmlspecialchars($_POST['start_date']);
    $end_date = htmlspecialchars($_POST['end_date']);
    $reason = htmlspecialchars($_POST['reason']);
    if ($_SESSION['user_role'] === 'Admin') {
        $status = "Approved";
        $approver_id = $_SESSION['user_id']; // Admin approves themselves
    } else {
        $status = "Pending";
        $approver_id = NULL;
    }

    if (!empty($leave_type) && !empty($start_date) && !empty($end_date)) {
        $days_requested = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;

        $stmt = $conn->prepare("INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, days_requested, reason, status, approver_emp_id, approval_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssisss", $employee_id, $leave_type, $start_date, $end_date, $days_requested, $reason, $status, $approver_id);

        try {
            if ($stmt->execute()) {
                header("Location: index.php?success=1");
                exit();
            }
        } catch (mysqli_sql_exception $e) {
            // This grabs JUST your custom text from the trigger: "Error: Leave allocation limit..."
            $clean_error = $e->getMessage();
            
            // Format it beautifully into your standard error box style
            $message = "<div class='alert error'>❌ " . htmlspecialchars($clean_error) . "</div>";
        }
        
        $stmt->close();
	} else {
		$message = "<div class='alert error'>⚠️ Please fill out all required fields.</div>";
	}
}

// Intercept success flag parameter hooks following post updates
if (isset($_GET['success'])) {
    $message = "<div class='alert success'>🚀 Leave request submitted successfully! Tracking profile: " . htmlspecialchars($employee_id) . "</div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DayAway | Leave Management Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="leave_management.css">
</head>

<body class="<?php echo (in_array($_SESSION['user_role'], ['Manager', 'Admin'])) ? 'manager-layout' : 'employee-layout'; ?>">
<?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['Manager', 'Admin'])): ?>
    <aside id="sidebar">
        <div class="brand"><i class="fa-solid fa-layer-group"></i> <span>DayAway</span></div>
        <ul class="nav-links">
            <li class="active"><a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>            
            <?php if (in_array($_SESSION['user_role'], ['Manager', 'Admin'])): ?>
                <li><a href="requests.php"><i class="fa-solid fa-calendar-check"></i> Leave Requests</a></li>
                <li><a href="employees.php"><i class="fa-solid fa-users"></i> Employees</a></li>
                <li><a href="analytics.php"><i class="fa-solid fa-chart-pie"></i> Analytics</a></li>
                <li><a href="password_requests.php"><i class="fa-solid fa-key"></i>Password Requests</a></li>
            <?php endif; ?>
            <li style="margin-top: auto;"><a href="#" onclick="confirmLogout()" style="color: #ef4444;"><i class="fa-solid fa-power-off"></i> Logout</a></li>
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
				<a href="requests.php"><i class="fa-solid fa-calendar-check"></i> My Requests</a></li>
                <a href="#" class="logout-link" onclick="confirmLogout()"><i class="fa-solid fa-power-off"></i> Exit</a>
            </div>
        </div>
    </nav>
<?php endif; ?>

<main>
    <header>
        <div>
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 5px;">Logged in as: <strong><?php echo $_SESSION['user_role']; ?></strong></p>
        </div>
    </header>

    <?php echo $message; ?>

    <div style="margin-bottom: 24px; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
        <div class="neon-card" style="border-left: 4px solid var(--accent-primary); padding: 20px;">
            <div style="color: var(--text-muted); font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">
                <i class="fa-solid fa-wallet" style="margin-right: 6px; color: var(--accent-primary);"></i> Available Leave Balance
            </div>
            <div style="font-size: 2.2rem; font-weight: 700; margin-top: 10px; display: flex; align-items: baseline; gap: 6px;">
                <span style="color: var(--text-color);"><?php echo $remaining_balance; ?></span>
                <span style="font-size: 1rem; color: var(--text-muted); font-weight: 400;">/ <?php echo $total_allocated; ?> Days Remaining</span>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <section class="neon-card">
            <div class="card-title">
                <i class="fa-solid fa-paper-plane" style="color: var(--accent-primary);"></i>
                <span>Apply for Leave</span>
            </div>
            <form id="leaveForm" action="index.php" method="POST">
                <div class="form-group">
                    <label>Leave Type *</label>
                    <select name="leave_type" class="form-control" required>
                        <option value="" disabled selected>Select Leave Type</option>
                        <option value="Annual Leave">Annual Leave</option>
                        <option value="Medical Leave">Medical Leave</option>
                        <option value="Maternity Leave">Maternity Leave</option>
                        <option value="Casual Leave">Casual Leave</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date *</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date *</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Reason / Statement</label>
                    <textarea name="reason" class="form-control" placeholder="Provide reasons for leave..."></textarea>
                </div>
                <button type="submit" name="submit_leave" class="btn-submit">Submit Application</button>
            </form>
        </section>

        <section class="neon-card">
            <div class="card-title">
                <i class="fa-solid fa-clock-rotate-left" style="color: var(--accent-primary);"></i>
                <span>Your Request History Logs</span>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Duration Bounds</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT leave_type, start_date, end_date, status FROM leave_requests WHERE employee_id = ? ORDER BY id DESC LIMIT 5";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("s", $employee_id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result && $result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $statusClass = strtolower($row['status']);
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['leave_type']) . "</td>";
                                echo "<td>" . date("M d", strtotime($row['start_date'])) . " - " . date("M d", strtotime($row['end_date'])) . "</td>";
                                echo "<td><span class='badge " . $statusClass . "'>" . htmlspecialchars($row['status']) . "</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' style='text-align:center; color: var(--text-muted); padding: 2rem;'>You have no leave request yet.</td></tr>";
                        }
                        $stmt->close();
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<script>
function confirmLogout() {
    if (confirm("Are you sure you want to log out?")) {
        window.location.href = "logout.php";
    }
}
    document.addEventListener('DOMContentLoaded', () => {
        const start = document.getElementById('start_date');
        const end = document.getElementById('end_date');
        const today = new Date().toISOString().split('T')[0];
        start.min = today; end.min = today;

        document.getElementById('leaveForm').addEventListener('submit', (e) => {
            if (new Date(end.value) < new Date(start.value)) {
                e.preventDefault();
                alert('⚠️ Operational Conflict: End date boundary constraint error.');
            }
        });
        start.addEventListener('change', () => { if(start.value) end.min = start.value; });
    });
</script>
</body>
</html>