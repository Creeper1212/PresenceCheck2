<?php
// Start session
session_start();

// Include configuration and error handler
require_once 'Dashboard/config.php';
require_once 'Dashboard/error_handler.php';

// Check if user is already logged in
if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Redirect to the requested page or default to index
    $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'index.php';
    unset($_SESSION['redirect_after_login']); // Clear the stored redirect
    header("Location: $redirect");
    exit;
}

// Initialize variables
$error = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get admin credentials from database or configuration
    try {
        $stmt = $conn->prepare("SELECT password_hash FROM admin_users WHERE username = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        // Use a default admin username
        $admin_username = 'admin';
        $stmt->bind_param("s", $admin_username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // If no admin user exists, this might be first run - create default admin
            // For security, in production you should set up the admin separately
            $default_password = 'Bremen2025'; // Using the password from your existing system
            $password_hash = password_hash($default_password, PASSWORD_DEFAULT);
            
            $create_stmt = $conn->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
            $create_stmt->bind_param("ss", $admin_username, $password_hash);
            $create_stmt->execute();
            $create_stmt->close();
            
            // Now user exists, get the hash
            $stmt->execute();
            $result = $stmt->get_result();
        }
        
        if ($row = $result->fetch_assoc()) {
            $stored_hash = $row['password_hash'];
            $submitted_password = $_POST['password'] ?? '';
            
            if (password_verify($submitted_password, $stored_hash)) {
                // Password is correct
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $admin_username;
                $_SESSION['last_activity'] = time();
                
                // Redirect to the requested page or default to index
                $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'index.php';
                unset($_SESSION['redirect_after_login']); // Clear the stored redirect
                header("Location: $redirect");
                exit;
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "Authentication error";
        }
        
        $stmt->close();
    } catch (Exception $e) {
        handleError("Login Error", $e->getMessage(), null, true);
        $error = "A system error occurred. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Presence System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .login-logo img {
            max-width: 100px;
            height: auto;
        }
        .login-title {
            color: #d40612;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .btn-login {
            background-color: #d40612;
            border-color: #d40612;
            width: 100%;
        }
        .btn-login:hover {
            background-color: #b8050f;
            border-color: #b8050f;
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 0.85rem;
        }
        .error-message {
            color: #d40612;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body class="login-page">
    <div class="container">
        <div class="login-container">
            <div class="login-logo">
                <img src="assets/images/logo.png" alt="ASB Logo">
            </div>
            <h2 class="login-title">Admin Login</h2>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php echo displayError(); // Display any system errors ?>
            
            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" value="admin" readonly>
                    <div class="form-text">Default administrator account</div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-login">Log In</button>
            </form>
            
            <div class="login-footer">
                <p>Need to check in? <a href="checkin.php">Go to Check-in page</a></p>
                <p>© <?php echo date('Y'); ?> Presence System. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>

