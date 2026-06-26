<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['Manager', 'Admin'])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "leave_management");
if ($conn->connect_error) { die("Database link failure."); }

$message = "";
$manager_list = [];
$managers_sql = "SELECT emp_id, name FROM employees WHERE role = 'Manager' ORDER BY name";
$managers_result = $conn->query($managers_sql);
if ($managers_result && $managers_result->num_rows > 0) {
    while ($m = $managers_result->fetch_assoc()) {
        $manager_list[] = $m;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_employee'])) {
    $emp_id = htmlspecialchars(trim($_POST['emp_id']));
    $name = htmlspecialchars($_POST['name']);
    $dept = htmlspecialchars($_POST['department']);
    $leaves = intval($_POST['leaves']);
    $role = htmlspecialchars($_POST['role']);
    $pass = trim($_POST['password']);
    $manager_emp_id = !empty($_POST['manager_emp_id']) ? $_POST['manager_emp_id'] : NULL;

    $stmt = $conn->prepare("INSERT INTO employees (emp_id, name, department, allocated_leaves, role, password, manager_emp_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssisss", $emp_id, $name, $dept, $leaves, $role, $pass, $manager_emp_id);
    
    try {
        if ($stmt->execute()) {
            $message = "<div class='alert success'>✅ Profile creation confirmed! Identity code: $emp_id</div>";
        }
    } catch (mysqli_sql_exception $e) {
        // Check if the error is due to duplicate entry (error code 1062)
        if ($e->getCode() == 1062) {
            $message = "<div class='alert error'>❌ Employee ID <strong>'$emp_id'</strong> already exists. Please use a unique ID.</div>";
        } else {
            $message = "<div class='alert error'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DayAway | Personnel Provisioning</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="leave_management.css">
</head>
<body class="<?php echo (in_array($_SESSION['user_role'], ['Manager', 'Admin'])) ? 'manager-layout' : 'employee-layout'; ?>">
<?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['Manager', 'Admin'])): ?>
    <aside id="sidebar">
        <div class="brand"><i class="fa-solid fa-layer-group"></i> <span>DayAway</span>
		</div>
        <ul class="nav-links">
            <li><a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            
            <?php if (in_array($_SESSION['user_role'], ['Manager', 'Admin'])): ?>
                <li><a href="requests.php"><i class="fa-solid fa-calendar-check"></i> Leave Requests</a></li>
                <li class="active"><a href="employees.php"><i class="fa-solid fa-users"></i> Employees</a></li>
                <li><a href="analytics.php"><i class="fa-solid fa-chart-pie"></i> Analytics</a></li>
                <li><a href="reset_password.php"><i class="fa-solid fa-rotate"></i>Reset Password</a></li>
            <?php endif; ?>
            
            <li style="margin-top: auto;"><a href="logout.php" style="color: #ef4444;" onclick="return confirmLogout()"><i class="fa-solid fa-power-off"></i> Logout</a></li>
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
                <a href="logout.php" class="logout-link" onclick="return confirmLogout()"><i class="fa-solid fa-power-off"></i> Exit</a>
			</div>
		</div>
    </nav>
	
	
	
<?php endif; ?>


    <main>
        <header>
            <div>
                <h1>Workspace Provisioning Registry</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Register operational profile nodes</p>
            </div>
        </header>

        <?php echo $message; ?>

        <div class="dashboard-grid">
            <section class="neon-card">
                <div class="card-title"><i class="fa-solid fa-user-plus" style="color: var(--accent-primary);"></i> Profile Initialization Form</div>
                <form method="POST" action="employees.php">
                    <div class="form-group">
                        <label>Unique ID Code</label>
                        <input type="text" name="emp_id" class="form-control" placeholder="e.g., EMP-1094" required>
                    </div>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Jane Doe" maxlength="18" data-validate="name" required>
                        <small style="color: var(--text-muted); font-size: 0.75rem;">Full Name should less than 18 characters.</small>
                    </div>
                    <div class="form-group">
                        <label>Operational Domain / Department</label>
                        <input type="text" name="department" class="form-control" placeholder="Development" required>
                    </div>
                    <div class="form-group">
                        <label>System Account Role Clearance</label>
                        <select name="role" class="form-control" required>
                            <option value="Employee">Employee Profile (Limited Clearance)</option>
                            <option value="Manager">Manager Profile (Authoritative Clearance)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Immediate Supervisor (Manager)</label>
                        <select name="manager_emp_id" class="form-control">
                            <option value="">-- None / Unassigned --</option>
                            <?php foreach ($manager_list as $manager): ?>
                                <option value="<?php echo htmlspecialchars($manager['emp_id']); ?>">
                                    <?php echo htmlspecialchars($manager['emp_id'] . ' - ' . $manager['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: var(--text-muted); font-size: 0.75rem;">If this employee is a manager, leave as "None".</small>
                    </div>
                    <div class="form-group">
                        <label>Default Access Password</label>
                        <input type="text" name="password" class="form-control" value="password123" readonly required style="background-color: #2a2a3a; color: #6c757d; cursor: not-allowed; opacity: 0.8;">
                    </div>
                    <div class="form-group">
                        <label>Leave Allocation Quota (Days)</label>
                        <input type="number" name="leaves" class="form-control" value="22" required>
                    </div>
                    <button type="submit" name="add_employee" class="btn-submit">Register Profile</button>
                </form>
            </section>

            <section class="neon-card">
                <div class="card-title"><i class="fa-solid fa-address-book" style="color: var(--accent-primary);"></i> System Manifest Logs</div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Emp ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT emp_id, name, department, role FROM employees ORDER BY id DESC";
                            $result = $conn->query($sql);
                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td><code>" . htmlspecialchars($row['emp_id']) . "</code></td>";
                                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['department']) . "</td>";
                                    echo "<td><span class='badge " . strtolower($row['role']) . "'>" . htmlspecialchars($row['role']) . "</span></td>";
                                    echo "</tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
    <script>
        function confirmLogout() {
            return confirm(
                "Are you sure you want to log out?"
            );
        }
    </script>
    <script src="validation.js"></script>
</body>
</html>
<?php $conn->close(); ?>