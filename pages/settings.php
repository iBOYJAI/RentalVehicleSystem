<?php
/**
 * Settings Page
 */
require_once '../config/config.php';
require_once '../config/auth.php';
require_once '../config/functions.php';

requireLogin();

$pageTitle = 'Settings';
$db = getDB();
$currentUser = getCurrentUser();

$tab = $_GET['tab'] ?? 'profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $fullName = sanitize($_POST['full_name']);
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone'] ?? '');
                $address = sanitize($_POST['address'] ?? '');
                
                try {
                    $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                    $stmt->execute([$fullName, $email, $phone, $address, $_SESSION['user_id']]);
                    $_SESSION['user_name'] = $fullName;
                    $_SESSION['user_email'] = $email;
                    setFlashMessage('success', 'Profile updated successfully!');
                } catch(PDOException $e) {
                    setFlashMessage('error', 'Error: ' . $e->getMessage());
                }
                header('Location: ' . BASE_URL . 'pages/settings.php?tab=profile');
                exit;
                
            case 'change_password':
                $currentPassword = $_POST['current_password'];
                $newPassword = $_POST['new_password'];
                $confirmPassword = $_POST['confirm_password'];
                
                if ($newPassword !== $confirmPassword) {
                    setFlashMessage('error', 'New passwords do not match!');
                } else {
                    $user = $db->prepare("SELECT password FROM users WHERE id = ?");
                    $user->execute([$_SESSION['user_id']]);
                    $u = $user->fetch();
                    
                    if (!password_verify($currentPassword, $u['password'])) {
                        setFlashMessage('error', 'Current password is incorrect!');
                    } else {
                        $hashedPassword = hashPassword($newPassword);
                        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                        setFlashMessage('success', 'Password changed successfully!');
                    }
                }
                header('Location: ' . BASE_URL . 'pages/settings.php?tab=password');
                exit;
                
            case 'update_system_settings':
                if (!hasRole(['admin'])) {
                    setFlashMessage('error', 'Only administrators can update system settings!');
                } else {
                    $settings = [
                        'company_name' => sanitize($_POST['company_name'] ?? ''),
                        'company_address' => sanitize($_POST['company_address'] ?? ''),
                        'company_phone' => sanitize($_POST['company_phone'] ?? ''),
                        'company_email' => sanitize($_POST['company_email'] ?? ''),
                        'tax_rate' => floatval($_POST['tax_rate'] ?? 10),
                        'currency' => sanitize($_POST['currency'] ?? 'USD'),
                    ];
                    
                    foreach ($settings as $key => $value) {
                        setSetting($key, $value);
                    }
                    
                    setFlashMessage('success', 'System settings updated!');
                }
                header('Location: ' . BASE_URL . 'pages/settings.php?tab=system');
                exit;
        }
    }
}

// Get system settings
$systemSettings = [];
if (hasRole(['admin'])) {
    $settings = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
    foreach ($settings as $setting) {
        $systemSettings[$setting['setting_key']] = $setting['setting_value'];
    }
}

include '../includes/header.php';
?>

<?php
$successMsg = getFlashMessage('success');
$errorMsg = getFlashMessage('error');
if ($successMsg): ?>
    <div class="alert alert-success">
        <i class="icon icon-success"></i>
        <span><?php echo htmlspecialchars($successMsg); ?></span>
    </div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div class="alert alert-error">
        <i class="icon icon-error"></i>
        <span><?php echo htmlspecialchars($errorMsg); ?></span>
    </div>
<?php endif; ?>

<div class="page-header">
    <h1 class="page-title">Settings</h1>
    <p class="page-subtitle">Manage your account and system settings</p>
</div>

<!-- Tabs -->
<div style="display: flex; gap: var(--spacing-md); margin-bottom: var(--spacing-lg); border-bottom: 2px solid var(--border-color);">
    <a href="?tab=profile" style="padding: var(--spacing-md); text-decoration: none; color: var(--text-primary); border-bottom: 2px solid <?php echo $tab === 'profile' ? 'var(--primary-color)' : 'transparent'; ?>; margin-bottom: -2px;">
        Profile
    </a>
    <a href="?tab=password" style="padding: var(--spacing-md); text-decoration: none; color: var(--text-primary); border-bottom: 2px solid <?php echo $tab === 'password' ? 'var(--primary-color)' : 'transparent'; ?>; margin-bottom: -2px;">
        Change Password
    </a>
    <?php if (hasRole(['admin'])): ?>
        <a href="?tab=system" style="padding: var(--spacing-md); text-decoration: none; color: var(--text-primary); border-bottom: 2px solid <?php echo $tab === 'system' ? 'var(--primary-color)' : 'transparent'; ?>; margin-bottom: -2px;">
            System Settings
        </a>
    <?php endif; ?>
</div>

<!-- Profile Tab -->
<?php if ($tab === 'profile'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Profile Information</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg);">
                    <div class="form-group">
                        <label class="form-label required" for="full_name">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($currentUser['full_name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($currentUser['email']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($currentUser['username']); ?>" disabled>
                        <div class="form-help">Username cannot be changed</div>
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label" for="address">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($currentUser['address'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Password Tab -->
<?php if ($tab === 'password'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Change Password</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                
                <div style="max-width: 500px;">
                    <div class="form-group">
                        <label class="form-label required" for="current_password">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="new_password">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="confirm_password">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- System Settings Tab -->
<?php if ($tab === 'system' && hasRole(['admin'])): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">System Settings</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_system_settings">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg);">
                    <div class="form-group">
                        <label class="form-label" for="company_name">Company Name</label>
                        <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($systemSettings['company_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="company_phone">Company Phone</label>
                        <input type="text" class="form-control" id="company_phone" name="company_phone" value="<?php echo htmlspecialchars($systemSettings['company_phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label" for="company_address">Company Address</label>
                        <textarea class="form-control" id="company_address" name="company_address" rows="2"><?php echo htmlspecialchars($systemSettings['company_address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="company_email">Company Email</label>
                        <input type="email" class="form-control" id="company_email" name="company_email" value="<?php echo htmlspecialchars($systemSettings['company_email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="tax_rate">Tax Rate (%)</label>
                        <input type="number" class="form-control" id="tax_rate" name="tax_rate" step="0.1" min="0" max="100" value="<?php echo htmlspecialchars($systemSettings['tax_rate'] ?? 10); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="currency">Currency</label>
                        <input type="text" class="form-control" id="currency" name="currency" value="<?php echo htmlspecialchars($systemSettings['currency'] ?? 'USD'); ?>">
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Update Settings</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

