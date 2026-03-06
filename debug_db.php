<?php
require_once 'config/config.php';
require_once 'config/database.php';

header('Content-Type: text/plain');
$db = getDB();

if (!isset($_SESSION['debug_visit'])) {
    $_SESSION['debug_visit'] = 0;
}
$_SESSION['debug_visit']++;

echo "DATABASE & SESSION DEBUG REPORT\n";
echo "=====================\n";
echo "Session ID: " . session_id() . "\n";
echo "Debug Visits: " . ($_SESSION['debug_visit'] ?? 'N/A') . "\n";
echo "Session status: " . session_status() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "Is Path Writable: " . (is_writable(session_save_path()) ? 'YES' : 'NO') . "\n";

try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Total Users: " . $result['count'] . "\n";

    $stmt = $db->query("SELECT username, password, role FROM users");
    while ($row = $stmt->fetch()) {
        $verify = password_verify('password', $row['password']);
        echo "User: " . $row['username'] . " | Pass Verify (password): " . ($verify ? 'SUCCESS' : 'FAILURE') . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
