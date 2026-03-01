<?php
/**
 * Database Backup Page
 * Admin only - Export database as SQL file
 */
require_once '../config/config.php';
require_once '../config/auth.php';
require_once '../config/functions.php';

requireRole(['admin']);

$pageTitle = 'Database Backup';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['backup'])) {
    $backupType = $_POST['backup_type'] ?? 'full';
    
    // Get database name from config
    $dbName = 'rvms_db';
    
    // Generate backup filename
    $filename = 'rvms_backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    // Set headers for download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Start output
    echo "-- Rental Vehicle Management System Database Backup\n";
    echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    echo "-- Database: {$dbName}\n\n";
    echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    echo "SET AUTOCOMMIT = 0;\n";
    echo "START TRANSACTION;\n";
    echo "SET time_zone = \"+00:00\";\n\n";
    
    // Get all tables
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        // Skip if structure only
        if ($backupType === 'structure') {
            echo "-- Table structure for table `{$table}`\n";
            $createTable = $db->query("SHOW CREATE TABLE `{$table}`")->fetch();
            echo $createTable['Create Table'] . ";\n\n";
            continue;
        }
        
        // Full backup - structure and data
        echo "-- Table structure for table `{$table}`\n";
        $createTable = $db->query("SHOW CREATE TABLE `{$table}`")->fetch();
        echo $createTable['Create Table'] . ";\n\n";
        
        // Get table data
        $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            echo "-- Dumping data for table `{$table}`\n";
            echo "INSERT INTO `{$table}` VALUES\n";
            
            $values = [];
            foreach ($rows as $row) {
                $rowValues = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $rowValues[] = 'NULL';
                    } else {
                        $rowValues[] = "'" . addslashes($value) . "'";
                    }
                }
                $values[] = "(" . implode(", ", $rowValues) . ")";
            }
            
            echo implode(",\n", $values) . ";\n\n";
        }
    }
    
    echo "COMMIT;\n";
    exit;
}

include '../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Database Backup</h1>
    <p class="page-subtitle">Export database for backup</p>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Create Database Backup</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="icon icon-info"></i>
            <span>
                <strong>Backup Instructions:</strong><br>
                • Full Backup: Includes table structure and all data<br>
                • Structure Only: Includes only table structure (no data)<br>
                • Backup file will be downloaded automatically<br>
                • Store backups in a safe location<br>
                • Regular backups are recommended
            </span>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="backup_type">Backup Type</label>
                <select class="form-control" id="backup_type" name="backup_type" required>
                    <option value="full" selected>Full Backup (Structure + Data)</option>
                    <option value="structure">Structure Only (No Data)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Database Information</label>
                <div style="background: var(--bg-tertiary); padding: var(--spacing-md); border-radius: var(--radius);">
                    <div><strong>Database Name:</strong> rvms_db</div>
                    <div><strong>Total Tables:</strong> 
                        <?php 
                        $tableCount = $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'rvms_db'")->fetchColumn();
                        echo $tableCount;
                        ?>
                    </div>
                    <div><strong>Backup Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></div>
                </div>
            </div>
            
            <div class="card-footer">
                <button type="submit" name="backup" class="btn btn-primary">
                    <i class="icon icon-download"></i> Download Backup
                </button>
                <a href="<?php echo BASE_URL; ?>pages/settings.php?tab=system" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Restore Database</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <i class="icon icon-warning"></i>
            <span>
                <strong>Restore Instructions:</strong><br>
                To restore a backup, use phpMyAdmin:<br>
                1. Open phpMyAdmin: http://localhost/phpmyadmin<br>
                2. Select "rvms_db" database<br>
                3. Click "Import" tab<br>
                4. Choose your backup SQL file<br>
                5. Click "Go" to import<br><br>
                <strong>Warning:</strong> Restoring will overwrite existing data!
            </span>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

