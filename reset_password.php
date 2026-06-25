<?php
session_start();

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Manager','Admin'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost","root","","leave_management");
$message = "";

// Handle Reset action
if (isset($_POST['reset_password'])) {
    $emp_id = $_POST['emp'];
    $default_password = "password123"; // fixed default

    // Update password and set reset flag
    $stmt = $conn->prepare("
        UPDATE employees
        SET password = ?,
            password_reset = 1
        WHERE emp_id = ?
    ");
    $stmt->bind_param("ss", $default_password, $emp_id);

    if ($stmt->execute()) {
        $message = "<div class='alert success'>✅ Password reset to default for <strong>$emp_id</strong>.</div>";
    } else {
        $message = "<div class='alert error'>❌ Reset failed. Please try again.</div>";
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>DayAway | Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="leave_management.css">
</head>
<body class="manager-layout">
    <aside id="sidebar">
        <div class="brand"><i class="fa-solid fa-layer-group"></i><span>DayAway</span></div>
        <ul class="nav-links">
            <li><a href="index.php"><i class="fa-solid fa-house"></i>Dashboard</a></li>
            <li><a href="requests.php"><i class="fa-solid fa-calendar-check"></i>Leave Requests</a></li>
            <li><a href="employees.php"><i class="fa-solid fa-users"></i>Employees</a></li>
            <li><a href="analytics.php"><i class="fa-solid fa-chart-pie"></i>Analytics</a></li>
            <li class="active"><a href="reset_password.php"><i class="fa-solid fa-rotate"></i>Reset Password</a></li>
            <li style="margin-top:auto;"><a href="logout.php" style="color:#ef4444;" onclick="return confirmLogout()"><i class="fa-solid fa-power-off"></i>Logout</a></li>
        </ul>
    </aside>

    <main>
        <header>
            <div>
                <h1>Reset User Passwords</h1>
                <p style="color:var(--text-muted);">Reset any user's password to the default <strong>password123</strong></p>
            </div>
        </header>

        <?= $message ?>

        <section class="neon-card">
            <div class="card-title"><i class="fa-solid fa-key" style="color:var(--accent-primary);"></i>All Employees</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Custom order: Admin → Manager → Employee, then by emp_id
                        $res = $conn->query("
                            SELECT emp_id, name, role
                            FROM employees
                            ORDER BY FIELD(role, 'Admin', 'Manager', 'Employee'), emp_id
                        ");
                        if ($res->num_rows > 0) {
                            while ($r = $res->fetch_assoc()) {
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['emp_id']) ?></td>
                                    <td><?= htmlspecialchars($r['name']) ?></td>
                                    <td><span class="badge <?= strtolower($r['role']) ?>"><?= htmlspecialchars($r['role']) ?></span></td>
                                    <td>
                                        <form method="POST" action="reset_password.php" style="display:flex; gap:10px;">
                                            <input type="hidden" name="emp" value="<?= $r['emp_id'] ?>">
                                            <button type="submit" name="reset_password" class="badge approved" style="border:none;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                                                <i class="fa-solid fa-rotate"></i> Reset to Default
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='4' style='text-align:center;'>No employees found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        function confirmLogout() {
            return confirm("Are you sure you want to log out?");
        }
    </script>
</body>
</html>