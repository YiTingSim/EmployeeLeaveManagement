<?php
session_start();

// ============================================================
// 1. OTP VERIFICATION (POST request with verify_otp)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $otp = trim($_POST['otp_code']);

    if (empty($otp)) {
        $_SESSION['otp_error'] = "❌ Please enter the OTP code.";
        header("Location: otp_verify.php");
        exit();
    }

    // Fixed dummy OTP
    $valid_otp = '123456';

    if ($otp !== $valid_otp) {
        $_SESSION['otp_error'] = "❌ Invalid OTP.";
        header("Location: otp_verify.php");
        exit();
    }

    // OTP is correct – update the password
    $conn = new mysqli("localhost", "root", "", "leave_management");
    if ($conn->connect_error) {
        $_SESSION['otp_error'] = "❌ Database connection failed.";
        header("Location: otp_verify.php");
        exit();
    }

    // Ensure session data exists
    if (!isset($_SESSION['reset_emp']) || !isset($_SESSION['reset_password'])) {
        $_SESSION['otp_error'] = "❌ Session expired. Please restart the reset process.";
        header("Location: login.php#resetBox");
        exit();
    }

    $emp_id = $_SESSION['reset_emp'];
    $new_password = $_SESSION['reset_password'];

    // Update the password
    $stmt = $conn->prepare("UPDATE employees SET password = ? WHERE emp_id = ?");
    $stmt->bind_param("ss", $new_password, $emp_id);

    if ($stmt->execute()) {
        // Clear session data
        unset($_SESSION['reset_emp']);
        unset($_SESSION['reset_password']);
        $_SESSION['password_change_success'] = "✅ Your password has been successfully reset. Please log in with your new password.";
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['otp_error'] = "❌ Failed to update password. Please try again.";
        header("Location: otp_verify.php");
        exit();
    }
    $stmt->close();
    $conn->close();
}

// ============================================================
// 2. INITIAL RESET REQUEST (POST request with reset_emp, etc.)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_emp']) && isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
    $emp_id = trim($_POST['reset_emp']);
    $new_password = trim($_POST['new_password']);
    $confirm = trim($_POST['confirm_password']);

    // Validate empty fields
    if (empty($emp_id)) {
        $_SESSION['otp_error'] = "❌ Employee ID is required.";
        header("Location: login.php#resetBox");
        exit();
    }
    if (empty($new_password) || empty($confirm)) {
        $_SESSION['otp_error'] = "❌ Password fields are required.";
        header("Location: login.php#resetBox");
        exit();
    }

    // Validate password match
    if ($new_password !== $confirm) {
        $_SESSION['otp_error'] = "❌ Passwords do not match.";
        header("Location: login.php#resetBox");
        exit();
    }

    // Check if employee exists
    $conn = new mysqli("localhost", "root", "", "leave_management");
    if ($conn->connect_error) {
        $_SESSION['otp_error'] = "❌ Database connection failed.";
        header("Location: login.php#resetBox");
        exit();
    }

    $check_stmt = $conn->prepare("SELECT emp_id FROM employees WHERE emp_id = ?");
    $check_stmt->bind_param("s", $emp_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    if ($result->num_rows === 0) {
        $_SESSION['otp_error'] = "❌ Employee ID not found.";
        $check_stmt->close();
        $conn->close();
        header("Location: login.php#resetBox");
        exit();
    }
    $check_stmt->close();
    $conn->close();

    // Store data in session
    $_SESSION['reset_emp'] = $emp_id;
    $_SESSION['reset_password'] = $new_password;

    // Redirect to the OTP page (GET request) – display the form
    header("Location: otp_verify.php");
    exit();
}

// ============================================================
// 3. GET REQUEST – Display the OTP form (if session data exists)
// ============================================================
if (!isset($_SESSION['reset_emp']) || !isset($_SESSION['reset_password'])) {
    // No session data, redirect to login
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DayAway | OTP Verification</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="leave_management.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .otp-card { width: 100%; max-width: 420px; padding: 2.5rem; background: rgba(17, 24, 39, 0.5); border: 1px solid #1f2937; border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); }
        .otp-header { text-align: center; margin-bottom: 2rem; }
        .otp-header h1 { font-size: 1.8rem; font-weight: 700; color: #fff; letter-spacing: 2px; margin: 0; }
        .otp-header p { color: #9ca3af; font-size: 0.85rem; margin-top: 0.5rem; }
        .dummy-note { background: rgba(99, 102, 241, 0.1); padding: 0.75rem; border-radius: 8px; margin-top: 0.5rem; color: #c4b5fd; text-align: center; }
    </style>
</head>
<body>
    <div class="otp-card">
        <div class="otp-header">
            <h1>OTP Verification</h1>
            <p>Enter the 6-digit code</p>
        </div>

        <?php if (isset($_SESSION['otp_error'])): ?>
            <div class="alert error"><?= htmlspecialchars($_SESSION['otp_error']) ?></div>
            <?php unset($_SESSION['otp_error']); ?>
        <?php endif; ?>

        <form method="POST" action="otp_verify.php">
            <div class="form-group">
                <label>OTP Code</label>
                <input type="text" name="otp_code" class="form-control" placeholder="Enter 6-digit code" maxlength="6" required>
                <div class="dummy-note">
                    🔑 Your OTP is: <strong style="color: #ffffff; font-size: 1.2rem;">123456</strong>
                </div>
            </div>
            <button type="submit" name="verify_otp" class="btn-submit">Verify & Reset Password</button>
        </form>
    </div>
</body>
</html>