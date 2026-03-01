<?php
/**
 * Reports Page
 */
require_once '../config/config.php';
require_once '../config/auth.php';
require_once '../config/functions.php';

requireRole(['admin', 'staff']);

$pageTitle = 'Reports';
$db = getDB();

$reportType = $_GET['type'] ?? 'daily';
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Get report data
$reports = [];
$summary = [];

try {
    switch ($reportType) {
        case 'daily':
            $stmt = $db->prepare("
                SELECT DATE(created_at) as date, 
                       COUNT(*) as total_bookings,
                       SUM(total_amount) as total_revenue,
                       COUNT(DISTINCT customer_id) as unique_customers
                FROM bookings
                WHERE DATE(created_at) BETWEEN ? AND ?
                AND status IN ('approved', 'active', 'completed')
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            $stmt->execute([$startDate, $endDate]);
            $reports = $stmt->fetchAll();
            break;
            
        case 'weekly':
            $stmt = $db->prepare("
                SELECT YEARWEEK(created_at) as week,
                       COUNT(*) as total_bookings,
                       SUM(total_amount) as total_revenue,
                       COUNT(DISTINCT customer_id) as unique_customers
                FROM bookings
                WHERE DATE(created_at) BETWEEN ? AND ?
                AND status IN ('approved', 'active', 'completed')
                GROUP BY YEARWEEK(created_at)
                ORDER BY week DESC
            ");
            $stmt->execute([$startDate, $endDate]);
            $reports = $stmt->fetchAll();
            break;
            
        case 'monthly':
            $stmt = $db->prepare("
                SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                       COUNT(*) as total_bookings,
                       SUM(total_amount) as total_revenue,
                       COUNT(DISTINCT customer_id) as unique_customers
                FROM bookings
                WHERE DATE(created_at) BETWEEN ? AND ?
                AND status IN ('approved', 'active', 'completed')
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month DESC
            ");
            $stmt->execute([$startDate, $endDate]);
            $reports = $stmt->fetchAll();
            break;
            
        case 'vehicles':
            $stmt = $db->query("
                SELECT v.vehicle_name, v.registration_number,
                       COUNT(b.id) as booking_count,
                       SUM(b.total_amount) as total_revenue,
                       AVG(b.total_days) as avg_days
                FROM vehicles v
                LEFT JOIN bookings b ON v.id = b.vehicle_id AND b.status IN ('approved', 'active', 'completed')
                GROUP BY v.id
                ORDER BY booking_count DESC
            ");
            $reports = $stmt->fetchAll();
            break;
    }
    
    // Get summary
    $summaryStmt = $db->prepare("
        SELECT 
            COUNT(*) as total_bookings,
            SUM(total_amount) as total_revenue,
            COUNT(DISTINCT customer_id) as total_customers,
            AVG(total_amount) as avg_booking_amount
        FROM bookings
        WHERE DATE(created_at) BETWEEN ? AND ?
        AND status IN ('approved', 'active', 'completed')
    ");
    $summaryStmt->execute([$startDate, $endDate]);
    $summary = $summaryStmt->fetch();
    
} catch(PDOException $e) {
    error_log("Reports Error: " . $e->getMessage());
    $reports = [];
    $summary = [];
}

include '../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Reports & Analytics</h1>
    <p class="page-subtitle">View detailed reports and statistics</p>
</div>

<!-- Summary Cards -->
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-icon">
            <i class="icon icon-booking"></i>
        </div>
        <div class="stat-value"><?php echo number_format($summary['total_bookings'] ?? 0); ?></div>
        <div class="stat-label">Total Bookings</div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-icon">
            <i class="icon icon-money"></i>
        </div>
        <div class="stat-value"><?php echo formatCurrency($summary['total_revenue'] ?? 0); ?></div>
        <div class="stat-label">Total Revenue</div>
    </div>
    
    <div class="stat-card info">
        <div class="stat-icon">
            <i class="icon icon-customer"></i>
        </div>
        <div class="stat-value"><?php echo number_format($summary['total_customers'] ?? 0); ?></div>
        <div class="stat-label">Total Customers</div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-icon">
            <i class="icon icon-money"></i>
        </div>
        <div class="stat-value"><?php echo formatCurrency($summary['avg_booking_amount'] ?? 0); ?></div>
        <div class="stat-label">Avg Booking Amount</div>
    </div>
</div>

<!-- Filters -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Report Filters</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="search-filter-bar">
            <div class="form-group" style="margin: 0;">
                <label class="form-label" for="type">Report Type</label>
                <select class="form-control" id="type" name="type">
                    <option value="daily" <?php echo $reportType === 'daily' ? 'selected' : ''; ?>>Daily</option>
                    <option value="weekly" <?php echo $reportType === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                    <option value="monthly" <?php echo $reportType === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                    <option value="vehicles" <?php echo $reportType === 'vehicles' ? 'selected' : ''; ?>>Top Vehicles</option>
                </select>
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label" for="start_date">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label" for="end_date">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
            </div>
            
            <div class="form-group" style="margin: 0; align-self: flex-end;">
                <button type="submit" class="btn btn-primary">Generate Report</button>
                <a href="<?php echo BASE_URL; ?>pages/reports.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Report Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?php echo ucfirst($reportType); ?> Report</h3>
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="icon icon-print"></i> Print
        </button>
    </div>
    <div class="card-body">
        <?php if (empty($reports)): ?>
            <div class="empty-state">
                <i class="icon icon-report"></i>
                <h3>No Data Available</h3>
                <p>No reports found for the selected criteria.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <?php if ($reportType === 'daily'): ?>
                                <th>Date</th>
                            <?php elseif ($reportType === 'weekly'): ?>
                                <th>Week</th>
                            <?php elseif ($reportType === 'monthly'): ?>
                                <th>Month</th>
                            <?php else: ?>
                                <th>Vehicle</th>
                                <th>Registration</th>
                            <?php endif; ?>
                            <th>Bookings</th>
                            <th>Revenue</th>
                            <?php if ($reportType !== 'vehicles'): ?>
                                <th>Customers</th>
                            <?php else: ?>
                                <th>Avg Days</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <?php if ($reportType === 'daily'): ?>
                                    <td><?php echo formatDate($report['date']); ?></td>
                                <?php elseif ($reportType === 'weekly'): ?>
                                    <td>Week <?php echo $report['week']; ?></td>
                                <?php elseif ($reportType === 'monthly'): ?>
                                    <td><?php echo date('M Y', strtotime($report['month'] . '-01')); ?></td>
                                <?php else: ?>
                                    <td><?php echo htmlspecialchars($report['vehicle_name']); ?></td>
                                    <td><?php echo htmlspecialchars($report['registration_number']); ?></td>
                                <?php endif; ?>
                                <td><?php echo number_format($report['total_bookings'] ?? $report['booking_count']); ?></td>
                                <td><?php echo formatCurrency($report['total_revenue'] ?? 0); ?></td>
                                <?php if ($reportType !== 'vehicles'): ?>
                                    <td><?php echo number_format($report['unique_customers']); ?></td>
                                <?php else: ?>
                                    <td><?php echo number_format($report['avg_days'] ?? 0, 1); ?> days</td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

