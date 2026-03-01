<?php
/**
 * Bookings Management Page
 */
require_once '../config/config.php';
require_once '../config/auth.php';
require_once '../config/functions.php';

requireRole(['admin', 'staff']);

$pageTitle = 'Bookings';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $customerId = intval($_POST['customer_id']);
                $vehicleId = intval($_POST['vehicle_id']);
                $startDate = sanitize($_POST['start_date']);
                $endDate = sanitize($_POST['end_date']);
                $pickupLocation = sanitize($_POST['pickup_location'] ?? '');
                $dropoffLocation = sanitize($_POST['dropoff_location'] ?? '');
                $notes = sanitize($_POST['notes'] ?? '');
                
                // Check availability
                if (!checkVehicleAvailability($vehicleId, $startDate, $endDate)) {
                    setFlashMessage('error', 'Vehicle is not available for the selected dates.');
                    header('Location: ' . BASE_URL . 'pages/bookings.php?add=1');
                    exit;
                }
                
                // Get vehicle daily rate
                $vehicle = $db->prepare("SELECT daily_rate FROM vehicles WHERE id = ?");
                $vehicle->execute([$vehicleId]);
                $v = $vehicle->fetch();
                $dailyRate = $v['daily_rate'];
                
                // Calculate cost
                $cost = calculateRentalCost($dailyRate, $startDate, $endDate);
                
                // Generate booking number
                $bookingNumber = generateUniqueCode('BK-', 'bookings', 'booking_number');
                
                try {
                    $stmt = $db->prepare("INSERT INTO bookings (booking_number, customer_id, vehicle_id, start_date, end_date, pickup_location, dropoff_location, daily_rate, total_days, subtotal, tax, total_amount, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $bookingNumber, $customerId, $vehicleId, $startDate, $endDate,
                        $pickupLocation, $dropoffLocation, $dailyRate, $cost['total_days'],
                        $cost['subtotal'], $cost['tax'], $cost['total_amount'], $notes, 'pending'
                    ]);
                    
                    // Update vehicle status
                    $db->prepare("UPDATE vehicles SET status = 'rented' WHERE id = ?")->execute([$vehicleId]);
                    
                    setFlashMessage('success', 'Booking created successfully!');
                    header('Location: ' . BASE_URL . 'pages/bookings.php');
                    exit;
                } catch(PDOException $e) {
                    setFlashMessage('error', 'Error: ' . $e->getMessage());
                }
                break;
                
            case 'approve':
                $id = intval($_POST['id']);
                try {
                    $stmt = $db->prepare("UPDATE bookings SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id'], $id]);
                    setFlashMessage('success', 'Booking approved!');
                } catch(PDOException $e) {
                    setFlashMessage('error', 'Error: ' . $e->getMessage());
                }
                header('Location: ' . BASE_URL . 'pages/bookings.php');
                exit;
                
            case 'reject':
                $id = intval($_POST['id']);
                try {
                    $stmt = $db->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?");
                    $stmt->execute([$id]);
                    // Free vehicle
                    $booking = $db->prepare("SELECT vehicle_id FROM bookings WHERE id = ?");
                    $booking->execute([$id]);
                    $b = $booking->fetch();
                    if ($b) {
                        $db->prepare("UPDATE vehicles SET status = 'available' WHERE id = ?")->execute([$b['vehicle_id']]);
                    }
                    setFlashMessage('success', 'Booking rejected!');
                } catch(PDOException $e) {
                    setFlashMessage('error', 'Error: ' . $e->getMessage());
                }
                header('Location: ' . BASE_URL . 'pages/bookings.php');
                exit;
                
            case 'complete':
                $id = intval($_POST['id']);
                try {
                    $stmt = $db->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?");
                    $stmt->execute([$id]);
                    // Free vehicle
                    $booking = $db->prepare("SELECT vehicle_id FROM bookings WHERE id = ?");
                    $booking->execute([$id]);
                    $b = $booking->fetch();
                    if ($b) {
                        $db->prepare("UPDATE vehicles SET status = 'available' WHERE id = ?")->execute([$b['vehicle_id']]);
                    }
                    setFlashMessage('success', 'Booking completed!');
                } catch(PDOException $e) {
                    setFlashMessage('error', 'Error: ' . $e->getMessage());
                }
                header('Location: ' . BASE_URL . 'pages/bookings.php');
                exit;
        }
    }
}

$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = intval($_GET['page'] ?? 1);

$where = ['1=1'];
$params = [];

if ($statusFilter) {
    $where[] = "b.status = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $where[] = "(b.booking_number LIKE ? OR c.full_name LIKE ? OR v.vehicle_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(' AND ', $where);

$countStmt = $db->prepare("SELECT COUNT(*) as total FROM bookings b WHERE $whereClause");
$countStmt->execute($params);
$totalItems = $countStmt->fetch()['total'];

$pagination = getPagination($page, $totalItems);
$offset = $pagination['offset'];

$stmt = $db->prepare("SELECT b.*, v.vehicle_name, v.registration_number, c.full_name as customer_name, c.phone as customer_phone FROM bookings b JOIN vehicles v ON b.vehicle_id = v.id JOIN customers c ON b.customer_id = c.id WHERE $whereClause ORDER BY b.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [ITEMS_PER_PAGE, $offset]));
$bookings = $stmt->fetchAll();

// Get data for form
$customers = $db->query("SELECT id, full_name, customer_code FROM customers WHERE status = 'active' ORDER BY full_name")->fetchAll();
$vehicles = $db->query("SELECT id, vehicle_name, registration_number, daily_rate FROM vehicles WHERE status = 'available' ORDER BY vehicle_name")->fetchAll();

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
    <h1 class="page-title">Bookings Management</h1>
    <p class="page-subtitle">Manage vehicle bookings</p>
</div>

<?php if (isset($_GET['add'])): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Create New Booking</h3>
        </div>
        <div class="card-body">
            <form method="POST" data-rental-calc>
                <input type="hidden" name="action" value="add">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg);">
                    <div class="form-group">
                        <label class="form-label required" for="customer_id">Customer</label>
                        <select class="form-control" id="customer_id" name="customer_id" required>
                            <option value="">Select Customer</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>">
                                    <?php echo htmlspecialchars($customer['full_name'] . ' (' . $customer['customer_code'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="vehicle_id">Vehicle</label>
                        <select class="form-control" id="vehicle_id" name="vehicle_id" required>
                            <option value="">Select Vehicle</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?php echo $vehicle['id']; ?>" data-rate="<?php echo $vehicle['daily_rate']; ?>">
                                    <?php echo htmlspecialchars($vehicle['vehicle_name'] . ' - ' . $vehicle['registration_number'] . ' (' . formatCurrency($vehicle['daily_rate']) . '/day)'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="start_date">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="end_date">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="pickup_location">Pickup Location</label>
                        <input type="text" class="form-control" id="pickup_location" name="pickup_location">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="dropoff_location">Dropoff Location</label>
                        <input type="text" class="form-control" id="dropoff_location" name="dropoff_location">
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <div style="background: var(--bg-tertiary); padding: var(--spacing-lg); border-radius: var(--radius);">
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--spacing-md);">
                                <div>
                                    <label style="color: var(--text-secondary); font-size: 0.85rem;">Daily Rate</label>
                                    <div id="display_daily_rate" style="font-size: 1.25rem; font-weight: 600;">-</div>
                                </div>
                                <div>
                                    <label style="color: var(--text-secondary); font-size: 0.85rem;">Total Days</label>
                                    <div id="display_total_days" style="font-size: 1.25rem; font-weight: 600;">-</div>
                                </div>
                                <div>
                                    <label style="color: var(--text-secondary); font-size: 0.85rem;">Total Amount</label>
                                    <div id="display_total_amount" style="font-size: 1.25rem; font-weight: 600; color: var(--primary-color);">-</div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="daily_rate" id="daily_rate">
                        <input type="hidden" name="total_days" id="total_days">
                        <input type="hidden" name="total_amount" id="total_amount">
                    </div>
                </div>
                
                <div class="card-footer">
                    <a href="<?php echo BASE_URL; ?>pages/bookings.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Booking</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    document.getElementById('vehicle_id').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const rate = option.getAttribute('data-rate') || 0;
        document.getElementById('daily_rate').value = rate;
        document.getElementById('display_daily_rate').textContent = formatCurrency(rate);
        calculateRentalCost(document.querySelector('form'));
    });
    
    document.getElementById('start_date').addEventListener('change', function() {
        calculateRentalCost(document.querySelector('form'));
    });
    
    document.getElementById('end_date').addEventListener('change', function() {
        calculateRentalCost(document.querySelector('form'));
    });
    </script>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Bookings List</h3>
            <a href="<?php echo BASE_URL; ?>pages/bookings.php?add=1" class="btn btn-primary">
                <i class="icon icon-add"></i> New Booking
            </a>
        </div>
        <div class="card-body">
            <form method="GET" class="search-filter-bar">
                <div class="search-box">
                    <i class="icon icon-search"></i>
                    <input type="text" class="form-control" name="search" placeholder="Search bookings..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <select class="form-control" name="status" style="width: 200px;">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?php echo BASE_URL; ?>pages/bookings.php" class="btn btn-secondary">Reset</a>
            </form>
            
            <div class="table-container" style="margin-top: var(--spacing-lg);">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Booking #</th>
                            <th>Customer</th>
                            <th>Vehicle</th>
                            <th>Dates</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No bookings found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($booking['booking_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['vehicle_name']); ?></td>
                                    <td><?php echo formatDate($booking['start_date']); ?> - <?php echo formatDate($booking['end_date']); ?></td>
                                    <td><?php echo formatCurrency($booking['total_amount']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $booking['status'] === 'approved' ? 'success' : 
                                                ($booking['status'] === 'pending' ? 'warning' : 
                                                ($booking['status'] === 'active' ? 'info' : 
                                                ($booking['status'] === 'completed' ? 'primary' : 'secondary'))); 
                                        ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($booking['status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="id" value="<?php echo $booking['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="id" value="<?php echo $booking['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirmDelete();">Reject</button>
                                                </form>
                                            <?php elseif ($booking['status'] === 'active'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="complete">
                                                    <input type="hidden" name="id" value="<?php echo $booking['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary">Complete</button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="<?php echo BASE_URL; ?>pages/booking-details.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-info">View</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="pagination">
                    <?php if ($pagination['has_prev']): ?>
                        <a href="?page=<?php echo $pagination['current_page'] - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>">Previous</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <?php if ($i == $pagination['current_page']): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($pagination['has_next']): ?>
                        <a href="?page=<?php echo $pagination['current_page'] + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

