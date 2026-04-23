<?php
require_once 'config.php';
require_once 'db.php';

// Cookie security configuration
if (SECURE_MODE) {
    session_set_cookie_params([
        'httponly' => true,  // Prevent JavaScript access to cookies
        'secure' => true,    // Only send over HTTPS
        'samesite' => 'Strict'
    ]);
} else {
    session_set_cookie_params([
        'httponly' => false // VULNERABLE: HttpOnly flag is disabled, making cookies accessible via JavaScript
    ]);
}

session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (SECURE_MODE) {
        // SECURE: Using prepared statements with parameterized queries
        $sql = "SELECT * FROM users WHERE username = ? AND password = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // VULNERABLE: Direct string interpolation (SQL Injection)
        $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
        $result = $conn->query($sql);
    }

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Login | V-Bank</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

</head>
<body>

    <div class="container">
        <div class="box"></div>

        <div class="login-container">
            <div class="login-box">
                <header class="login-header">
                    <h1>Welcome to V-Bank</h1>
                    <p>Sign in to your account to continue.</p>
                </header>
                
                <?php if (isset($error) && $error): ?>
                    <div class="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <div class="icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <input type="text" id="username" name="username" placeholder="Enter username" required autocomplete="username">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <div class="icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                            <div class="toggle-password" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <span>Sign In</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
                
                <div class="footer-links">
                    <p>New here? <a href="#">Create account</a></p>
                    
                    <div class="sandbox">
                        <div class="sandbox-header">
                            <i class="fas fa-shield-alt"></i>
                            Dev Lab
                        </div>
                        <code>' OR '1'='1</code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
