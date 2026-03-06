<?php

/**
 * Login Page
 * User authentication entry point
 */
require_once 'config/config.php';
require_once 'config/auth.php';
require_once 'config/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $result = loginUser($username, $password);
        if ($result['success']) {
            header('Location: ' . BASE_URL . 'dashboard.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Get flash messages
$error = $error ?: getFlashMessage('error');
$success = $success ?: getFlashMessage('success');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rental Vehicle Management System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/icons/icons.css">
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">RV</div>
                <h1>Rental Vehicle Management System</h1>
                <p style="color: var(--text-secondary);">Sign in to your account</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="icon icon-error"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="icon icon-success"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" data-validate>
                <div class="form-group">
                    <label class="form-label required" for="username">Username or Email</label>
                    <input type="text"
                        class="form-control"
                        id="username"
                        name="username"
                        required
                        autofocus
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label required" for="password">Password</label>
                    <input type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        required>
                </div>

                <div class="form-group">
                    <button type="submit" name="login" class="btn btn-primary btn-block btn-lg">
                        Sign In
                    </button>
                </div>
            </form>

            <div style="text-align: center; margin-top: var(--spacing-lg); padding-top: var(--spacing-lg); border-top: 1px solid var(--border-color);">
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: var(--spacing-md);">
                    <strong>Quick Login</strong>
                </p>
                <div style="display: flex; gap: var(--spacing-sm); justify-content: center; flex-wrap: wrap;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="quickLogin('admin', 'password')">Admin</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="quickLogin('staff', 'password')">Staff</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="quickLogin('customer', 'password')">Customer</button>
                </div>
                <div style="margin-top: var(--spacing-md);">
                    <small style="color: var(--warning-color);">⚠️ Change password after first login!</small>
                </div>
            </div>
        </div>
    </div>

    <script>
        function quickLogin(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
            // Find and submit the form
            document.querySelector('form').submit();
        }
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>

</html>