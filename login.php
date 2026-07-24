<?php
include("database/db.php");

session_start();

$error_msg = "";

if(isset($_POST['login']))
{
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $role = mysqli_real_escape_string($conn, $_POST['role'] ?? '');

    if(empty($role))
    {
        $error_msg = "Please select a role (Faculty or Admin).";
    }
    else
    {
        // Step 1: Check if email exists for the selected role
        $sql = "SELECT * FROM users WHERE email='$email' AND role='$role'";
        $result = mysqli_query($conn, $sql);

        if(mysqli_num_rows($result) == 1)
        {
            $user = mysqli_fetch_assoc($result);
            
            // Step 2: Secure password check supporting hashed passwords with plain text fallback
            if(password_verify($password, $user['password']) || $user['password'] === $password)
            {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                $dashboard = strtolower($user['role']) . "_dashboard.php";
                
                echo "<script>
                    alert('Login Successful');
                    window.location.href = '$dashboard';
                </script>";
                exit();
            }
            else
            {
                $error_msg = "Wrong password entered.";
            }
        }
        else
        {
            // Email+role combo not found — check if email exists at all
            $sql2 = "SELECT * FROM users WHERE email='$email'";
            $result2 = mysqli_query($conn, $sql2);
            
            if(mysqli_num_rows($result2) > 0)
            {
                // Email exists but under a different role
                $error_msg = "Wrong username.";
            }
            else
            {
                // Email doesn't exist at all
                $error_msg = "Invalid credentials.";
            }
        }
    }
}

if(!empty($error_msg)) {
    echo "<script>alert('" . addslashes($error_msg) . "');</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="theme.css">
    <script src="theme.js"></script>

    <style>
        :root {
            --bg-image: url("background-1.jfif");
            --glass-blur: blur(5px);
            --transition-speed: 0.35s;
        }

        body.dark-theme {
            --bg-image: linear-gradient(rgba(17, 24, 39, 0.85), rgba(17, 24, 39, 0.95)), url("background-1.jfif");
            --glass-blur: blur(12px);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
            transition: background-color var(--transition-speed) ease, 
                        color var(--transition-speed) ease, 
                        border-color var(--transition-speed) ease, 
                        box-shadow var(--transition-speed) ease;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-image: var(--bg-image);
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            background-attachment: fixed;
            transition: background-image var(--transition-speed) ease;
        }

        .login-box {
            background: var(--dsms-card-bg);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            width: 320px;
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
        }
        .portal {
             display: flex;
             justify-content: center;
             margin-bottom: 15px;
        }

        .login-box h2 {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 10px;
            color: var(--dsms-heading);
        }
        .login-box p {
            text-align: center;
            color: var(--dsms-text);
        }
        
        .card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 70px;
            height: 70px;
            margin: 8px;
            border: 2px solid var(--dsms-border);
            border-radius: 12px;
            cursor: pointer;
            background: transparent;
            transition: all var(--transition-speed) ease;
        }

        .card img {
            height: 32px;
            transition: transform 0.3s ease;
        }
        .card p {
            color: var(--dsms-text);
            font-size: 0.52rem;
            margin-top: 0.2rem;
        }
        .card:hover {
            transform: scale(1.08);
            box-shadow: 0 4px 12px rgba(59,130,246,.35);
        }

        .card.selected {
            border: 2px solid var(--dsms-primary);
            background: rgba(59, 130, 246, 0.15);
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.5);
            transform: scale(1.08);
        }

        .card.selected p {
            color: var(--dsms-primary);
            font-weight: bold;
        }
        
        .input-box {
            margin-bottom: 15px;
            position: relative;
        }

        .input-box input {
            width: 100%;
            padding: 10px;
            padding-right: 40px; /* extra space for the toggle eye icon */
            border: 1px solid var(--dsms-border);
            border-radius: 5px;
            outline: none;
            background: var(--dsms-bg);
            color: var(--dsms-text);
        }

        .input-box input:focus {
            border-color: var(--dsms-primary);
            box-shadow: 0 0 5px rgba(59, 130, 246, 0.5);
        }

        .btn {
            width: 100%;
            padding: 10px;
            background: var(--dsms-primary);
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all var(--transition-speed) ease;
        }

        .btn:hover {
            background: #2563EB;
            transform: translateY(-1px);
        }

        .links {
            text-align: center;
            margin-top: 15px;
        }

        .links a {
            text-decoration: none;
            color: var(--dsms-primary);
        }

        .links a:hover {
            text-decoration: underline;
        }

        #togglePassword {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            user-select: none;
            color: var(--dsms-text);
        }
    </style>
</head>
<body>

    <div class="login-box">
        <h2>StationaryHub</h2>
        <p style="margin-bottom: 15px;">Sign in to access your portal</p>

        <div class="portal">
            <div class="card" data-role="FACULTY">
                <img src="faculty.png" alt="Faculty">
                <p>Faculty</p>
            </div>
            <div class="card" data-role="ADMIN">
                <img src="HOD.png" alt="Admin">
                <p>Admin</p>
            </div>
        </div>
        
        <form action="" method="POST" id="loginForm">
            <!-- Hidden input to store selected role -->
            <input type="hidden" name="role" id="selectedRoleInput" value="">

            <div class="input-box">
                <input type="email" name="email" placeholder="Email" required>
            </div>

            <div class="input-box">
                <input type="password" id="password" name="password" placeholder="Password" required>
                <span id="togglePassword">&#128065;</span>
            </div>

            <button type="submit" name="login" class="btn">Login</button>

            <div class="links">
                <p><a href="forgot_password.php">Forgot Password?</a></p>
            </div>
        </form>
    </div>

<script>
    const cards = document.querySelectorAll(".card");
    const selectedRoleInput = document.getElementById("selectedRoleInput");

    cards.forEach(card => {
        card.addEventListener("click", () => {
            cards.forEach(c => c.classList.remove("selected"));
            card.classList.add("selected");
            selectedRoleInput.value = card.getAttribute("data-role");
        });
    });

    const togglePassword = document.getElementById("togglePassword");
    const passwordField = document.getElementById("password");

    togglePassword.addEventListener("click", function () {
        if (passwordField.type === "password") {
            passwordField.type = "text";
        } else {
            passwordField.type = "password";
        }
    });

    // Role-based form validation
    document.getElementById("loginForm").addEventListener("submit", function(e) {
        if (!selectedRoleInput.value) {
            e.preventDefault();
            alert("Please select a role (Faculty or Admin) before logging in.");
        }
    });
</script>
</body>
</html>
