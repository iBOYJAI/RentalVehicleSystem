<?php
/**
 * 404 Error Page
 */
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/icons/icons.css">
</head>
<body>
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--primary-color), var(--primary-light));">
        <div style="text-align: center; background: white; padding: var(--spacing-2xl); border-radius: var(--radius-xl); box-shadow: var(--shadow-xl); max-width: 500px;">
            <div style="font-size: 6rem; font-weight: 700; color: var(--primary-color); margin-bottom: var(--spacing-lg);">404</div>
            <h1 style="margin-bottom: var(--spacing-md);">Page Not Found</h1>
            <p style="color: var(--text-secondary); margin-bottom: var(--spacing-xl);">The page you are looking for does not exist.</p>
            <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn btn-primary">Go to Dashboard</a>
        </div>
    </div>
</body>
</html>

