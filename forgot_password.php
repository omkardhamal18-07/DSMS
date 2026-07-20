<?php
include("database/db.php");

session_start();

$error_msg = "";
$success_msg = "";
$step = 1; // 1: Enter Email, 2: Reset Password

if (isset($_POST['verify_email'])) {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error_msg = "Email address cannot be empty.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Invalid email format.";
    } else {
        // Prevent SQL Injection using prepared statement
        $stmt = $conn->prepare("SELECT user_id, email, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $_SESSION['reset_user_id'] = $user['user_id'];
            $_SESSION['reset_email'] = $user['email'];
            $_SESSION['reset_role'] = $user['role'];
            $step = 2;
        } else {
            $error_msg = "The email address is not registered in our system.";
        }
        $stmt->close();
    }
}

if (isset($_POST['reset_password'])) {
    if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_email'])) {
        $error_msg = "Session expired. Please try again.";
        $step = 1;
    } else {
        $step = 2; // Stay on step 2 if there's an error
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($password) || empty($confirm_password)) {
            $error_msg = "Please fill in all password fields.";
        } elseif ($password !== $confirm_password) {
            $error_msg = "Passwords do not match.";
        } elseif (strlen($password) < 6) {
            $error_msg = "Password must be at least 6 characters long.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $user_id = $_SESSION['reset_user_id'];

            // Update database securely
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $success_msg = "Password reset successfully! Redirecting to login page...";
                // Clear reset session variables
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_role']);
                $step = 1;
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 3000);
                </script>";
            } else {
                $error_msg = "Failed to update password. Please try again.";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - DSMS</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-image: url("background-1.jfif");
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            background-attachment: fixed;
        }

        .login-box {
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            width: 340px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-box h2 {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 10px;
            color: #333;
        }

        .login-box p.subtitle {
            text-align: center;
            color: #3d444e;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .input-box {
            margin-bottom: 15px;
            position: relative;
        }

        .input-box i.input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #555;
        }

        .input-box input {
            width: 100%;
            padding: 10px 10px 10px 38px;
            border: 1px solid #000000;
            border-radius: 5px;
            outline: none;
            background: rgba(0, 0, 0, 0.05);
            color: #000000;
        }

        .input-box input:focus {
            border-color: #007BFF;
            background: rgba(255, 255, 255, 0.9);
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #333;
            user-select: none;
        }

        .btn {
            width: 100%;
            padding: 10px;
            background: #4facfe;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #008cff;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            text-decoration: none;
            color: #4facfe;
            font-size: 0.9rem;
        }

        .links a:hover {
            text-decoration: underline;
        }

        /* Alert styles */
        .alert {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 0.85rem;
            text-align: center;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        /* Loader Animation */
        .loader {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
            display: none;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <div class="login-box">
        <img src="zeal-logo.png" height="100" style="display: block; margin: 0 auto;">
        <h2>Reset Password</h2>
        
        <?php if ($step === 1): ?>
            <p class="subtitle">Enter your registered email address</p>
        <?php else: ?>
            <p class="subtitle">Set a secure password for your account</p>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-1"></i> <?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle me-1"></i> <?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <form action="" method="POST" id="emailForm" onsubmit="showLoading('emailBtn')">
                <div class="input-box">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" placeholder="Registered Email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <button type="submit" name="verify_email" id="emailBtn" class="btn">
                    <span>Verify Email</span>
                    <div class="loader" id="emailBtnLoader"></div>
                </button>
            </form>
        <?php else: ?>
            <form action="" method="POST" id="resetForm" onsubmit="showLoading('resetBtn')">
                <div class="input-box">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" placeholder="New Password" required minlength="6">
                    <span class="toggle-password" onclick="togglePasswordVisibility('password')">&#128065;</span>
                </div>
                <div class="input-box">
                    <i class="fas fa-key input-icon"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                    <span class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">&#128065;</span>
                </div>
                <button type="submit" name="reset_password" id="resetBtn" class="btn">
                    <span>Reset Password</span>
                    <div class="loader" id="resetBtnLoader"></div>
                </button>
            </form>
        <?php endif; ?>

        <div class="links">
            <a href="login.php"><i class="fas fa-arrow-left me-1"></i> Back to Login</a>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            if (field.type === "password") {
                field.type = "text";
            } else {
                field.type = "password";
            }
        }

        function showLoading(btnId) {
            const btn = document.getElementById(btnId);
            const span = btn.querySelector('span');
            const loader = btn.querySelector('.loader');
            
            // Show loader, update text status (non-blocking simulation)
            loader.style.display = 'inline-block';
            btn.style.opacity = '0.8';
            btn.style.pointerEvents = 'none';
        }
    </script>
</body>
</html>
