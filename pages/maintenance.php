<?php
/**
 * Maintenance/Damage Log Page
 */
require_once '../config/config.php';
require_once '../config/auth.php';
require_once '../config/functions.php';

requireRole(['admin', 'staff']);

$pageTitle = 'Maintenance';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
            case 'edit':
                $id = $_POST['action'] === 'edit' ? intval($_POST['id']) : null;
                $vehicleId = intval($_POST['vehicle_id']);
                $bookingId = !empty($_POST['booking_id']) ? intval($_POST['booking_id']) : null;
                $maintenanceType = sanitize($_POST['maintenance_type']);
                $title = sanitize($_POST['title']);
                $description = sanitize($_POST['description'] ?? '');
                $cost = floatval($_POST['cost'] ?? 0);
                $maintenanceDate = sanitize($_POST['maintenance_date']);
                $completedDate = !empty($_POST['completed_date']) ? sanitize($_POST['completed_date']) : null;
                $status = sanitize($_POST['status'] ?? 'pending');
                $serviceProvider = sanitize($_POST['service_provider'] ?? '');
                $nextServiceDate = !empty($_POST['next_service_date']) ? sanitize($_POST['next_service_date']) : null;
                $mileageAtService = intval($_POST['mileage_at_service'] ?? 0);
                
                try {
                    if ($id) {
                        $stmt = $db->prepare("UPDATE maintenance SET vehicle_id = ?, booking_id = ?, maintenance_type = ?, title = ?, description = ?, cost = ?, maintenance_date = ?, completed_date = ?, status = ?, service_provider = ?, next_service_date = ?, mileage_at_service = ? WHERE id = ?");
                        $stmt->execute([$vehicleId, $bookingId, $maintenanceType, $title, $description, $cost, $maintenanceDate, $completedDate, $status, $serviceProvider, $nextServiceDate, $mileageAtService, $id]);
                        setFlashMessage('success', 'Maintenance record updated!');
                    } else {
                        $stmt = $db->prepare("INSERT INTO maintenance (vehicle_id, booking_id, maintenance_type, title, description, cost, maintenance_date, completed_date, status, service_provider, next_service_date, mileage_at_service, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$vehicleId, $bookingId, $maintenanceType, $title, $description, $cost, $maintenanceDate, $completedDate, $status, $serviceProvider, $nextServiceDate, $mileageAtService, $_SESSION['user_id']]);
                        
                        // Update vehicle status if maintenance
                        if ($maintenanceType === 'service' || $maintenanceType === 'repair') {
                            $db->prepare("UPDATE vehicles SET status = 'maintenance' WHERE id = ?")->execute([$vehicleId]);
                        }
                        
                        setFlashMessage('success', 'Maintenance record added!');
                    }
                    header('Location: ' . BASE_URL . 'pages/maintenance.php');
                    exit;
                } catch(PDOException $e) {
                    setFlashMessage('error', 'Error: ' . $e->getMessage());
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                try {
                    $stmt = $db->prepare("DELETE FROM maintenance WHERE id = ?");
                    $stmt->execute([$id]);
                    setFlashMessage('success', 'Maintenance record deleted!');
                } catch(PDOException $e) {
                    setFlashMessage('error', 'Error: ' . $e->getMessage());
                }
                header('Location: ' . BASE_URL . 'pages/maintenance.php');
                exit;
        }
    }
}

$search = $_GET['search'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$page = intval($_GET['page'] ?? 1);

$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(m.title LIKE ? OR v.vehicle_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($typeFilter) {
    $where[] = "m.maintenance_type = ?";
    $params[] = $typeFilter;
}

if ($statusFilter) {
    $where[] = "m.status = ?";
    $params[] = $statusFilter;
}

$whereClause = implode(' AND ', $where);
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM maintenance m JOIN vehicles v ON m.vehicle_id = v.id WHERE $whereClause");
$countStmt->execute($params);
$totalItems = $countStmt->fetch()['total'];

$pagination = getPagination($page, $totalItems);
$offset = $pagination['offset'];

$stmt = $db->prepare("SELECT m.*, v.vehicle_name, v.registration_number FROM maintenance m JOIN vehicles v ON m.vehicle_id = v.id WHERE $whereClause ORDER BY m.maintenance_date DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [ITEMS_PER_PAGE, $offset]));
$maintenanceRecords = $stmt->fetchAll();

$vehicles = $db->query("SELECT id, vehicle_name, registration_number FROM vehicles ORDER BY vehicle_name")->fetchAll();
$bookings = $db->query("SELECT id, booking_number FROM bookings ORDER BY created_at DESC LIMIT 50")->fetchAll();

$editRecord = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $db->prepare("SELECT * FROM maintenance WHERE id = ?");
    $stmt->execute([$id]);
    $editRecord = $stmt->fetch();
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
    <h1 class="page-title">Maintenance & Damage Log</h1>
    <p class="page-subtitle">Track vehicle maintenance and repairs</p>
</div>

<?php if (isset($_GET['add']) || isset($_GET['edit'])): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo isset($_GET['edit']) ? 'Edit Maintenance Record' : 'Add Maintenance Record'; ?></h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo isset($_GET['edit']) ? 'edit' : 'add'; ?>">
                <?php if (isset($_GET['edit'])): ?>
                    <input type="hidden" name="id" value="<?php echo $editRecord['id']; ?>">
                <?php endif; ?>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg);">
                    <div class="form-group">
                        <label class="form-label required" for="vehicle_id">Vehicle</label>
                        <select class="form-control" id="vehicle_id" name="vehicle_id" required>
                            <option value="">Select Vehicle</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?php echo $vehicle['id']; ?>" <?php echo ($editRecord && $editRecord['vehicle_id'] == $vehicle['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($vehicle['vehicle_name'] . ' - ' . $vehicle['registration_number']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="booking_id">Related Booking (Optional)</label>
                        <select class="form-control" id="booking_id" name="booking_id">
                            <option value="">None</option>
                            <?php foreach ($bookings as $booking): ?>
                                <option value="<?php echo $booking['id']; ?>" <?php echo ($editRecord && $editRecord['booking_id'] == $booking['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($booking['booking_number']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="maintenance_type">Type</label>
                        <select class="form-control" id="maintenance_type" name="maintenance_type" required>
                            <option value="service" <?php echo ($editRecord && $editRecord['maintenance_type'] === 'service') ? 'selected' : ''; ?>>Service</option>
                            <option value="repair" <?php echo ($editRecord && $editRecord['maintenance_type'] === 'repair') ? 'selected' : ''; ?>>Repair</option>
                            <option value="damage" <?php echo ($editRecord && $editRecord['maintenance_type'] === 'damage') ? 'selected' : ''; ?>>Damage</option>
                            <option value="inspection" <?php echo ($editRecord && $editRecord['maintenance_type'] === 'inspection') ? 'selected' : ''; ?>>Inspection</option>
                            <option value="other" <?php echo ($editRecord && $editRecord['maintenance_type'] === 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="title">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required value="<?php echo htmlspecialchars($editRecord['title'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($editRecord['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="cost">Cost</label>
                        <input type="number" class="form-control" id="cost" name="cost" step="0.01" min="0" value="<?php echo $editRecord['cost'] ?? 0; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="maintenance_date">Maintenance Date</label>
                        <input type="date" class="form-control" id="maintenance_date" name="maintenance_date" required value="<?php echo $editRecord['maintenance_date'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="completed_date">Completed Date</label>
                        <input type="date" class="form-control" id="completed_date" name="completed_date" value="<?php echo $editRecord['completed_date'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="pending" <?php echo ($editRecord && $editRecord['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo ($editRecord && $editRecord['status'] === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo ($editRecord && $editRecord['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo ($editRecord && $editRecord['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="service_provider">Service Provider</label>
                        <input type="text" class="form-control" id="service_provider" name="service_provider" value="<?php echo htmlspecialchars($editRecord['service_provider'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="next_service_date">Next Service Date</label>
                        <input type="date" class="form-control" id="next_service_date" name="next_service_date" value="<?php echo $editRecord['next_service_date'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="mileage_at_service">Mileage at Service</label>
                        <input type="number" class="form-control" id="mileage_at_service" name="mileage_at_service" min="0" value="<?php echo $editRecord['mileage_at_service'] ?? 0; ?>">
                    </div>
                </div>
                
                <div class="card-footer">
                    <a href="<?php echo BASE_URL; ?>pages/maintenance.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><?php echo isset($_GET['edit']) ? 'Update' : 'Add'; ?> Record</button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Maintenance Records</h3>
            <a href="<?php echo BASE_URL; ?>pages/maintenance.php?add=1" class="btn btn-primary">
                <i class="icon icon-add"></i> Add Record
            </a>
        </div>
        <div class="card-body">
            <form method="GET" class="search-filter-bar">
                <div class="search-box">
                    <i class="icon icon-search"></i>
                    <input type="text" class="form-control" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <select class="form-control" name="type" style="width: 200px;">
                    <option value="">All Types</option>
                    <option value="service" <?php echo $typeFilter === 'service' ? 'selected' : ''; ?>>Service</option>
                    <option value="repair" <?php echo $typeFilter === 'repair' ? 'selected' : ''; ?>>Repair</option>
                    <option value="damage" <?php echo $typeFilter === 'damage' ? 'selected' : ''; ?>>Damage</option>
                </select>
                <select class="form-control" name="status" style="width: 200px;">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?php echo BASE_URL; ?>pages/maintenance.php" class="btn btn-secondary">Reset</a>
            </form>
            
            <div class="table-container" style="margin-top: var(--spacing-lg);">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Vehicle</th>
                            <th>Type</th>
                            <th>Title</th>
                            <th>Cost</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($maintenanceRecords)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No records found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($maintenanceRecords as $record): ?>
                                <tr>
                                    <td><?php echo formatDate($record['maintenance_date']); ?></td>
                                    <td><?php echo htmlspecialchars($record['vehicle_name']); ?></td>
                                    <td><?php echo ucfirst($record['maintenance_type']); ?></td>
                                    <td><?php echo htmlspecialchars($record['title']); ?></td>
                                    <td><?php echo formatCurrency($record['cost']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $record['status'] === 'completed' ? 'success' : 
                                                ($record['status'] === 'in_progress' ? 'info' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="<?php echo BASE_URL; ?>pages/maintenance.php?edit=<?php echo $record['id']; ?>" class="action-btn action-btn-edit">
                                                <i class="icon icon-edit"></i>
                                            </a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirmDelete();">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $record['id']; ?>">
                                                <button type="submit" class="action-btn action-btn-delete">
                                                    <i class="icon icon-delete"></i>
                                                </button>
                                            </form>
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
                        <a href="?page=<?php echo $pagination['current_page'] - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($typeFilter); ?>&status=<?php echo urlencode($statusFilter); ?>">Previous</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <?php if ($i == $pagination['current_page']): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($typeFilter); ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($pagination['has_next']): ?>
                        <a href="?page=<?php echo $pagination['current_page'] + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($typeFilter); ?>&status=<?php echo urlencode($statusFilter); ?>">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

