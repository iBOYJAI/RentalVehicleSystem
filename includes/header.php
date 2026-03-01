<?php
/**
 * Header Component
 * Includes sidebar navigation and top header
 */
requireLogin();
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>RVMS</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/icons/icons.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">RV</div>
            <div class="sidebar-title">RVMS</div>
        </div>
        <nav class="sidebar-menu">
            <a href="<?php echo BASE_URL; ?>dashboard.php" class="menu-item <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="icon icon-dashboard"></i> Dashboard
            </a>
            
            <?php if (hasRole(['admin', 'staff'])): ?>
            <div class="menu-group-title">Management</div>
            <a href="<?php echo BASE_URL; ?>pages/vehicles.php" class="menu-item <?php echo $currentPage === 'vehicles.php' ? 'active' : ''; ?>">
                <i class="icon icon-vehicle"></i> Vehicles
            </a>
            <a href="<?php echo BASE_URL; ?>pages/categories.php" class="menu-item <?php echo $currentPage === 'categories.php' ? 'active' : ''; ?>">
                <i class="icon icon-category"></i> Categories
            </a>
            <a href="<?php echo BASE_URL; ?>pages/bookings.php" class="menu-item <?php echo $currentPage === 'bookings.php' ? 'active' : ''; ?>">
                <i class="icon icon-booking"></i> Bookings
            </a>
            <a href="<?php echo BASE_URL; ?>pages/customers.php" class="menu-item <?php echo $currentPage === 'customers.php' ? 'active' : ''; ?>">
                <i class="icon icon-customer"></i> Customers
            </a>
            <a href="<?php echo BASE_URL; ?>pages/payments.php" class="menu-item <?php echo $currentPage === 'payments.php' ? 'active' : ''; ?>">
                <i class="icon icon-payment"></i> Payments
            </a>
            <a href="<?php echo BASE_URL; ?>pages/invoices.php" class="menu-item <?php echo $currentPage === 'invoices.php' ? 'active' : ''; ?>">
                <i class="icon icon-file"></i> Invoices
            </a>
            <a href="<?php echo BASE_URL; ?>pages/maintenance.php" class="menu-item <?php echo $currentPage === 'maintenance.php' ? 'active' : ''; ?>">
                <i class="icon icon-maintenance"></i> Maintenance
            </a>
            <?php endif; ?>
            
            <?php if (hasRole(['admin', 'staff'])): ?>
            <div class="menu-group-title">Reports</div>
            <a href="<?php echo BASE_URL; ?>pages/reports.php" class="menu-item <?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">
                <i class="icon icon-report"></i> Reports
            </a>
            <?php endif; ?>
            
            <?php if (hasRole(['admin'])): ?>
            <div class="menu-group-title">Administration</div>
            <a href="<?php echo BASE_URL; ?>pages/users.php" class="menu-item <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">
                <i class="icon icon-user"></i> Users
            </a>
            <a href="<?php echo BASE_URL; ?>pages/backup.php" class="menu-item <?php echo $currentPage === 'backup.php' ? 'active' : ''; ?>">
                <i class="icon icon-download"></i> Database Backup
            </a>
            <?php endif; ?>
            
            <div class="menu-group-title">Account</div>
            
            <?php if (hasRole(ROLE_CUSTOMER)): ?>
            <a href="<?php echo BASE_URL; ?>pages/my-bookings.php" class="menu-item <?php echo $currentPage === 'my-bookings.php' ? 'active' : ''; ?>">
                <i class="icon icon-booking"></i> My Bookings
            </a>
            <?php endif; ?>

            <a href="<?php echo BASE_URL; ?>pages/settings.php" class="menu-item <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
                <i class="icon icon-settings"></i> Settings
            </a>
            <a href="<?php echo BASE_URL; ?>logout.php" class="menu-item">
                <i class="icon icon-logout"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <button class="btn btn-icon menu-toggle d-none" id="menuToggle" style="display: none;">
                    <i class="icon icon-menu"></i>
                </button>
                <h2 style="margin: 0; font-size: 1.5rem;"><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></h2>
            </div>
            <div class="header-right">
                <div class="user-menu">
                    <div class="user-avatar"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></div>
                    <div>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary);">
                            <?php echo isset($pageTitle) ? $pageTitle : ucfirst($currentUser['role']); ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="content">

