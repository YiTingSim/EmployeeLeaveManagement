<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "leave_management";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { 
    die("<div class='alert error'>Terminal Linkage Failed: " . $conn->connect_error . "</div>"); 
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $emp_id = htmlspecialchars(trim($_POST['emp_id']));
    $password_input = trim($_POST['password']);

    if (!empty($emp_id) && !empty($password_input)) {
        $stmt = $conn->prepare("SELECT emp_id, name, role, password FROM employees WHERE emp_id = ?");
        $stmt->bind_param("s", $emp_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Standard string comparison for local debugging. 
            if ($password_input === $user['password']) {
                $_SESSION['user_id'] = $user['emp_id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                
                header("Location: index.php");
                exit();
            } else {
                $error = "❌ Security authentication mismatch: Invalid password.";
            }
        } else {
            $error = "❌ Identity unregistered: Employee ID not found.";
        }
        $stmt->close();
    } else {
        $error = "⚠️ Operational parameter error: Missing input parameters.";
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DayAway | Kiosk Gateway Terminal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="leave_management.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0;}
		.video-bg { position: fixed; right: 0; bottom: 0; min-width: 100%; min-height: 100%; z-index:-100;}
        .login-card { width: 100%; max-width: 420px; padding: 2.5rem; background: #111827; border: 1px solid #1f2937; border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); }
        .login-header { text-align: center; margin-bottom: 2rem; }
        .login-header h1 { font-size: 1.8rem; font-weight: 700; color: #fff; letter-spacing: 2px; margin: 0; }
        .login-header p { color: #9ca3af; font-size: 0.85rem; margin-top: 0.5rem; }
    </style>
</head>
<body>
	<!--Plays in line to avoid fullscreen mode-->
	<video autoplay loop playsinline class="video-bg">
		<source src="login-bg.mp4">
	</video>

    <div class="login-card">
        <div class="login-header">
            <h1>DayAway SYSTEM</h1>
            <p>Please authenticate at this kiosk node to manage leaves</p>
        </div>
        
        <?php if(!empty($error)) echo "<div class='alert error' style='margin-bottom:1.5rem;'>$error</div>"; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label>Employee ID Code</label>
                <input type="text" name="emp_id" class="form-control" placeholder="e.g., EMP-2026" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>Kiosk Pin / Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" name="login" class="btn-submit" style="margin-top: 1.5rem;">Access Dashboard</button>
        </form>
    </div>
</body>
</html>