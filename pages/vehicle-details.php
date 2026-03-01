<?php
/**
 * Vehicle Details Page
 */
require_once '../config/config.php';
require_once '../config/auth.php';
require_once '../config/functions.php';

requireRole(['admin', 'staff']);

$pageTitle = 'Vehicle Details';
$db = getDB();

$id = intval($_GET['id'] ?? 0);

$vehicle = $db->prepare("SELECT v.*, c.name as category_name FROM vehicles v LEFT JOIN categories c ON v.category_id = c.id WHERE v.id = ?");
$vehicle->execute([$id]);
$vehicle = $vehicle->fetch();

if (!$vehicle) {
    header('Location: ' . BASE_URL . 'pages/vehicles.php');
    exit;
}

// Get booking history
$bookings = $db->prepare("SELECT b.*, c.full_name as customer_name FROM bookings b JOIN customers c ON b.customer_id = c.id WHERE b.vehicle_id = ? ORDER BY b.created_at DESC LIMIT 10");
$bookings->execute([$id]);
$bookings = $bookings->fetchAll();

// Get maintenance history
$maintenance = $db->prepare("SELECT * FROM maintenance WHERE vehicle_id = ? ORDER BY maintenance_date DESC LIMIT 10");
$maintenance->execute([$id]);
$maintenance = $maintenance->fetchAll();

include '../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Vehicle Details</h1>
    <p class="page-subtitle"><?php echo htmlspecialchars($vehicle['vehicle_name']); ?></p>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: var(--spacing-lg);">
    <!-- Vehicle Image & Info -->
    <div class="card">
        <div class="card-body">
            <?php if ($vehicle['image']): ?>
                <img src="<?php echo getFileUrl($vehicle['image'], 'vehicles'); ?>" alt="" style="width: 100%; border-radius: var(--radius); margin-bottom: var(--spacing-lg);">
            <?php else: ?>
                <div style="width: 100%; height: 300px; background: var(--bg-tertiary); border-radius: var(--radius); display: flex; align-items: center; justify-content: center; margin-bottom: var(--spacing-lg);">
                    <i class="icon icon-vehicle" style="font-size: 4rem; opacity: 0.3;"></i>
                </div>
            <?php endif; ?>
            
            <div style="display: flex; flex-direction: column; gap: var(--spacing-md);">
                <div>
                    <strong>Category:</strong> <?php echo htmlspecialchars($vehicle['category_name'] ?? 'N/A'); ?>
                </div>
                <div>
                    <strong>Registration:</strong> <?php echo htmlspecialchars($vehicle['registration_number']); ?>
                </div>
                <div>
                    <strong>Status:</strong> 
                    <span class="badge badge-<?php 
                        echo $vehicle['status'] === 'available' ? 'success' : 
                            ($vehicle['status'] === 'rented' ? 'warning' : 
                            ($vehicle['status'] === 'maintenance' ? 'danger' : 'secondary')); 
                    ?>">
                        <?php echo ucfirst($vehicle['status']); ?>
                    </span>
                </div>
                <div>
                    <strong>Daily Rate:</strong> <?php echo formatCurrency($vehicle['daily_rate']); ?>
                </div>
                <?php if ($vehicle['weekly_rate']): ?>
                    <div>
                        <strong>Weekly Rate:</strong> <?php echo formatCurrency($vehicle['weekly_rate']); ?>
                    </div>
                <?php endif; ?>
                <?php if ($vehicle['monthly_rate']): ?>
                    <div>
                        <strong>Monthly Rate:</strong> <?php echo formatCurrency($vehicle['monthly_rate']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Vehicle Details -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Vehicle Information</h3>
            <a href="<?php echo BASE_URL; ?>pages/vehicles.php?edit=<?php echo $vehicle['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md);">
                <div><strong>Brand:</strong> <?php echo htmlspecialchars($vehicle['brand'] ?? 'N/A'); ?></div>
                <div><strong>Model:</strong> <?php echo htmlspecialchars($vehicle['model'] ?? 'N/A'); ?></div>
                <div><strong>Year:</strong> <?php echo $vehicle['year'] ?: 'N/A'; ?></div>
                <div><strong>Color:</strong> <?php echo htmlspecialchars($vehicle['color'] ?? 'N/A'); ?></div>
                <div><strong>Fuel Type:</strong> <?php echo ucfirst($vehicle['fuel_type']); ?></div>
                <div><strong>Seating:</strong> <?php echo $vehicle['seating_capacity']; ?> seats</div>
                <div><strong>Chassis:</strong> <?php echo htmlspecialchars($vehicle['chassis_number'] ?? 'N/A'); ?></div>
                <div><strong>Engine:</strong> <?php echo htmlspecialchars($vehicle['engine_number'] ?? 'N/A'); ?></div>
                <div><strong>Mileage:</strong> <?php echo number_format($vehicle['mileage']); ?> km</div>
            </div>
            
            <?php if ($vehicle['description']): ?>
                <div style="margin-top: var(--spacing-lg); padding-top: var(--spacing-lg); border-top: 1px solid var(--border-color);">
                    <strong>Description:</strong>
                    <p><?php echo nl2br(htmlspecialchars($vehicle['description'])); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Booking History -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Booking History</h3>
    </div>
    <div class="card-body">
        <?php if (empty($bookings)): ?>
            <div class="empty-state">
                <i class="icon icon-booking"></i>
                <h3>No Bookings</h3>
                <p>This vehicle has no booking history.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Booking #</th>
                            <th>Customer</th>
                            <th>Dates</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['booking_number']); ?></td>
                                <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                <td><?php echo formatDate($booking['start_date']); ?> - <?php echo formatDate($booking['end_date']); ?></td>
                                <td><?php echo formatCurrency($booking['total_amount']); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $booking['status'] === 'approved' ? 'success' : 
                                            ($booking['status'] === 'pending' ? 'warning' : 'primary'); 
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

<!-- Maintenance History -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Maintenance History</h3>
    </div>
    <div class="card-body">
        <?php if (empty($maintenance)): ?>
            <div class="empty-state">
                <i class="icon icon-maintenance"></i>
                <h3>No Maintenance Records</h3>
                <p>This vehicle has no maintenance history.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Title</th>
                            <th>Cost</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($maintenance as $m): ?>
                            <tr>
                                <td><?php echo formatDate($m['maintenance_date']); ?></td>
                                <td><?php echo ucfirst($m['maintenance_type']); ?></td>
                                <td><?php echo htmlspecialchars($m['title']); ?></td>
                                <td><?php echo formatCurrency($m['cost']); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $m['status'] === 'completed' ? 'success' : 
                                            ($m['status'] === 'in_progress' ? 'info' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $m['status'])); ?>
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

<div style="margin-top: var(--spacing-lg);">
    <a href="<?php echo BASE_URL; ?>pages/vehicles.php" class="btn btn-secondary">Back to Vehicles</a>
</div>

<?php include '../includes/footer.php'; ?>

