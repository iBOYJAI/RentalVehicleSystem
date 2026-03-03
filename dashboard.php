<?php

/**
 * Dashboard Page
 * Main dashboard with statistics and charts
 */
require_once 'config/config.php';
require_once 'config/auth.php';
require_once 'config/functions.php';

requireLogin();

$pageTitle = 'Dashboard';
$db = getDB();

// Detect role
$currentUser = getCurrentUser();
$isCustomer = $currentUser && $currentUser['role'] === 'customer';

if ($isCustomer) {
    // Customer-specific: show only this customer's bookings, no global revenue/admin stats
    try {
        // Try to find matching customer record by email
        $customerId = null;
        if (!empty($currentUser['email'])) {
            $stmt = $db->prepare("SELECT id FROM customers WHERE email = ? LIMIT 1");
            $stmt->execute([$currentUser['email']]);
            $cust = $stmt->fetch();
            if ($cust) {
                $customerId = $cust['id'];
            }
        }

        if ($customerId) {
            // Stats for this customer only
            $stats = [
                'my_total_bookings' => 0,
                'my_active_bookings' => 0,
                'my_total_spent' => 0,
            ];

            $statStmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_bookings,
                    SUM(CASE WHEN status IN ('pending','approved','active') THEN 1 ELSE 0 END) as active_bookings,
                    COALESCE(SUM(total_amount), 0) as total_spent
                FROM bookings
                WHERE customer_id = ?
            ");
            $statStmt->execute([$customerId]);
            $row = $statStmt->fetch();
            if ($row) {
                $stats['my_total_bookings'] = (int)$row['total_bookings'];
                $stats['my_active_bookings'] = (int)$row['active_bookings'];
                $stats['my_total_spent'] = (float)$row['total_spent'];
            }

            // Recent bookings for this customer
            $recentBookings = $db->prepare("
                SELECT b.*, v.vehicle_name, v.registration_number
                FROM bookings b
                JOIN vehicles v ON b.vehicle_id = v.id
                WHERE b.customer_id = ?
                ORDER BY b.created_at DESC
                LIMIT 5
            ");
            $recentBookings->execute([$customerId]);
            $recentBookings = $recentBookings->fetchAll();
        } else {
            // No linked customer record
            $stats = ['my_total_bookings' => 0, 'my_active_bookings' => 0, 'my_total_spent' => 0];
            $recentBookings = [];
        }
    } catch (PDOException $e) {
        error_log("Customer Dashboard Error: " . $e->getMessage());
        $stats = ['my_total_bookings' => 0, 'my_active_bookings' => 0, 'my_total_spent' => 0];
        $recentBookings = [];
    }

    // No charts or top-vehicles for customers
    $chartLabels = $chartRevenue = $vehicleStatusData = $vehicleStatusLabels = $topVehicles = [];
} else {
    // Admin/Staff dashboard with full system-wide statistics
    try {
        $stats = $db->query("SELECT * FROM dashboard_stats")->fetch();

        // Get recent bookings
        $recentBookings = $db->query("
            SELECT b.*, v.vehicle_name, v.registration_number, c.full_name as customer_name
            FROM bookings b
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN customers c ON b.customer_id = c.id
            ORDER BY b.created_at DESC
            LIMIT 5
        ")->fetchAll();

        // Get revenue data for chart (last 7 days)
        $revenueData = $db->query("
            SELECT DATE(created_at) as date, COALESCE(SUM(total_amount), 0) as revenue
            FROM bookings
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            AND status IN ('approved', 'active', 'completed')
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ")->fetchAll();

        // Get vehicle status distribution
        $vehicleStatus = $db->query("
            SELECT status, COUNT(*) as count
            FROM vehicles
            GROUP BY status
        ")->fetchAll();

        // Get top rented vehicles
        $topVehicles = $db->query("
            SELECT v.vehicle_name, v.registration_number, COUNT(b.id) as booking_count
            FROM vehicles v
            LEFT JOIN bookings b ON v.id = b.vehicle_id AND b.status IN ('approved', 'active', 'completed')
            GROUP BY v.id
            ORDER BY booking_count DESC
            LIMIT 5
        ")->fetchAll();
    } catch (PDOException $e) {
        error_log("Dashboard Error: " . $e->getMessage());
        $stats = ['available_vehicles' => 0, 'rented_vehicles' => 0, 'active_bookings' => 0, 'active_customers' => 0, 'today_revenue' => 0, 'month_revenue' => 0];
        $recentBookings = [];
        $revenueData = [];
        $vehicleStatus = [];
        $topVehicles = [];
    }

    // Prepare chart data
    $chartLabels = [];
    $chartRevenue = [];
    foreach ($revenueData as $row) {
        $chartLabels[] = date('M d', strtotime($row['date']));
        $chartRevenue[] = floatval($row['revenue']);
    }

    $vehicleStatusData = [];
    $vehicleStatusLabels = [];
    foreach ($vehicleStatus as $row) {
        $vehicleStatusLabels[] = ucfirst($row['status']);
        $vehicleStatusData[] = intval($row['count']);
    }
}

include 'includes/header.php';
?>

<!-- Flash Messages -->
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

<?php if ($isCustomer): ?>
    <!-- Customer Dashboard: personal summary only -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="icon icon-booking"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['my_active_bookings']); ?></div>
            <div class="stat-label">My Active Bookings</div>
        </div>

        <div class="stat-card success">
            <div class="stat-icon">
                <i class="icon icon-booking"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['my_total_bookings']); ?></div>
            <div class="stat-label">My Total Bookings</div>
        </div>

        <div class="stat-card info">
            <div class="stat-icon">
                <i class="icon icon-money"></i>
            </div>
            <div class="stat-value"><?php echo formatCurrency($stats['my_total_spent']); ?></div>
            <div class="stat-label">Total Amount Spent</div>
        </div>
    </div>

    <!-- Recent bookings for this customer -->
    <div style="margin-top: var(--spacing-xl);">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">My Recent Bookings</h3>
            </div>
            <div class="card-body">
                <?php if (empty($recentBookings)): ?>
                    <div class="empty-state">
                        <i class="icon icon-booking"></i>
                        <h3>No Bookings Yet</h3>
                        <p>You don't have any bookings in the system.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Booking #</th>
                                    <th>Vehicle</th>
                                    <th>Dates</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBookings as $booking): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['booking_number']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['vehicle_name']); ?></td>
                                        <td><?php echo formatDate($booking['start_date']); ?> - <?php echo formatDate($booking['end_date']); ?></td>
                                        <td><?php echo formatCurrency($booking['total_amount']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php
                                                                        echo $booking['status'] === 'approved' ? 'success' : ($booking['status'] === 'pending' ? 'warning' : ($booking['status'] === 'active' ? 'info' : 'secondary'));
                                                                        ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Admin/Staff Dashboard: full system statistics -->
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="icon icon-vehicle"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['available_vehicles']); ?></div>
            <div class="stat-label">Available Vehicles</div>
        </div>

        <div class="stat-card success">
            <div class="stat-icon">
                <i class="icon icon-booking"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['active_bookings']); ?></div>
            <div class="stat-label">Active Bookings</div>
        </div>

        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="icon icon-customer"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['active_customers']); ?></div>
            <div class="stat-label">Active Customers</div>
        </div>

        <div class="stat-card info">
            <div class="stat-icon">
                <i class="icon icon-money"></i>
            </div>
            <div class="stat-value"><?php echo formatCurrency($stats['today_revenue']); ?></div>
            <div class="stat-label">Today's Revenue</div>
        </div>

        <div class="stat-card danger">
            <div class="stat-icon">
                <i class="icon icon-invoice"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['pending_invoices']); ?></div>
            <div class="stat-label">Pending Invoices</div>
        </div>

        <div class="stat-card success">
            <div class="stat-icon">
                <i class="icon icon-money"></i>
            </div>
            <div class="stat-value"><?php echo formatCurrency($stats['month_revenue']); ?></div>
            <div class="stat-label">Monthly Revenue</div>
        </div>

        <div class="stat-card danger">
            <div class="stat-icon">
                <i class="icon icon-vehicle"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['rented_vehicles']); ?></div>
            <div class="stat-label">Rented Vehicles</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--spacing-lg); margin-bottom: var(--spacing-xl);">
        <!-- Revenue Chart -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Revenue Trend (Last 7 Days)</h3>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" width="400" height="200"
                    data-chart="bar"
                    data-chart-data='{"labels": <?php echo json_encode($chartLabels); ?>, "datasets": [{"label": "Revenue", "data": <?php echo json_encode($chartRevenue); ?>, "borderColor": "#6366f1", "backgroundColor": "rgba(99, 102, 241, 0.8)"}]}'>
                </canvas>
            </div>
        </div>

        <!-- Vehicle Status Chart -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Vehicle Status</h3>
            </div>
            <div class="card-body">
                <canvas id="vehicleStatusChart" width="300" height="200"
                    data-chart="doughnut"
                    data-chart-data='{"labels": <?php echo json_encode($vehicleStatusLabels); ?>, "datasets": [{"data": <?php echo json_encode($vehicleStatusData); ?>, "backgroundColor": ["#6366f1", "#10b981", "#f59e0b", "#ef4444"]}]}'>
                </canvas>
            </div>
        </div>
    </div>

    <!-- Recent Bookings & Top Vehicles -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--spacing-lg);">
        <!-- Recent Bookings -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Bookings</h3>
                <a href="<?php echo BASE_URL; ?>pages/bookings.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentBookings)): ?>
                    <div class="empty-state">
                        <i class="icon icon-booking"></i>
                        <h3>No Recent Bookings</h3>
                        <p>No bookings found in the system.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Booking #</th>
                                    <th>Customer</th>
                                    <th>Vehicle</th>
                                    <th>Dates</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBookings as $booking): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['booking_number']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['vehicle_name']); ?></td>
                                        <td><?php echo formatDate($booking['start_date']); ?> - <?php echo formatDate($booking['end_date']); ?></td>
                                        <td><?php echo formatCurrency($booking['total_amount']); ?></td>
                                        <td><span class="badge badge-<?php echo $booking['status'] === 'approved' ? 'success' : ($booking['status'] === 'pending' ? 'warning' : 'primary'); ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Vehicles -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Top Rented Vehicles</h3>
            </div>
            <div class="card-body">
                <?php if (empty($topVehicles)): ?>
                    <div class="empty-state">
                        <i class="icon icon-vehicle"></i>
                        <h3>No Data</h3>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: var(--spacing-md);">
                        <?php foreach ($topVehicles as $index => $vehicle): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--spacing-md); background: var(--bg-secondary); border-radius: var(--radius);">
                                <div>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($vehicle['vehicle_name']); ?></div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);"><?php echo htmlspecialchars($vehicle['registration_number']); ?></div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);"><?php echo $vehicle['booking_count']; ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary);">bookings</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>