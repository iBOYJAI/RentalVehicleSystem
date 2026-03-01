<?php
/**
 * Vehicles Management Page
 * List, Add, Edit, Delete vehicles
 */
require_once '../config/config.php';
require_once '../config/auth.php';
require_once '../config/functions.php';

requireRole(['admin', 'staff']);

$pageTitle = 'Vehicles';
$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
            case 'edit':
                $id = $_POST['action'] === 'edit' ? intval($_POST['id']) : null;
                $categoryId = intval($_POST['category_id']);
                $vehicleName = sanitize($_POST['vehicle_name']);
                $brand = sanitize($_POST['brand'] ?? '');
                $model = sanitize($_POST['model'] ?? '');
                $year = intval($_POST['year'] ?? 0);
                $color = sanitize($_POST['color'] ?? '');
                $registrationNumber = sanitize($_POST['registration_number']);
                $chassisNumber = sanitize($_POST['chassis_number'] ?? '');
                $engineNumber = sanitize($_POST['engine_number'] ?? '');
                $fuelType = sanitize($_POST['fuel_type'] ?? 'petrol');
                $seatingCapacity = intval($_POST['seating_capacity'] ?? 4);
                $dailyRate = floatval($_POST['daily_rate']);
                $weeklyRate = floatval($_POST['weekly_rate'] ?? 0);
                $monthlyRate = floatval($_POST['monthly_rate'] ?? 0);
                $description = sanitize($_POST['description'] ?? '');
                $status = sanitize($_POST['status'] ?? 'available');
                
                // Handle image upload
                $image = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadFile($_FILES['image'], 'vehicles', ALLOWED_IMAGE_TYPES);
                    if ($uploadResult['success']) {
                        $image = $uploadResult['filename'];
                        // Delete old image if editing
                        if ($id) {
                            $oldVehicle = $db->prepare("SELECT image FROM vehicles WHERE id = ?");
                            $oldVehicle->execute([$id]);
                            $old = $oldVehicle->fetch();
                            if ($old && $old['image']) {
                                deleteFile($old['image'], 'vehicles');
                            }
                        }
                    }
                } elseif ($id) {
                    // Keep existing image if not uploading new one
                    $oldVehicle = $db->prepare("SELECT image FROM vehicles WHERE id = ?");
                    $oldVehicle->execute([$id]);
                    $old = $oldVehicle->fetch();
                    $image = $old['image'] ?? null;
                }
                
                try {
                    if ($id) {
                        // Update
                        $stmt = $db->prepare("UPDATE vehicles SET category_id = ?, vehicle_name = ?, brand = ?, model = ?, year = ?, color = ?, registration_number = ?, chassis_number = ?, engine_number = ?, fuel_type = ?, seating_capacity = ?, daily_rate = ?, weekly_rate = ?, monthly_rate = ?, image = ?, description = ?, status = ? WHERE id = ?");
                        $stmt->execute([$categoryId, $vehicleName, $brand, $model, $year, $color, $registrationNumber, $chassisNumber, $engineNumber, $fuelType, $seatingCapacity, $dailyRate, $weeklyRate, $monthlyRate, $image, $description, $status, $id]);
                        setFlashMessage('success', 'Vehicle updated successfully!');
                    } else {
                        // Insert
                        $stmt = $db->prepare("INSERT INTO vehicles (category_id, vehicle_name, brand, model, year, color, registration_number, chassis_number, engine_number, fuel_type, seating_capacity, daily_rate, weekly_rate, monthly_rate, image, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$categoryId, $vehicleName, $brand, $model, $year, $color, $registrationNumber, $chassisNumber, $engineNumber, $fuelType, $seatingCapacity, $dailyRate, $weeklyRate, $monthlyRate, $image, $description, $status]);
                        setFlashMessage('success', 'Vehicle added successfully!');
                    }
                    header('Location: ' . BASE_URL . 'pages/vehicles.php');
                    exit;
                } catch(PDOException $e) {
                    setFlashMessage('error', 'Error saving vehicle: ' . $e->getMessage());
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                try {
                    // Get image to delete
                    $vehicle = $db->prepare("SELECT image FROM vehicles WHERE id = ?");
                    $vehicle->execute([$id]);
                    $v = $vehicle->fetch();
                    if ($v && $v['image']) {
                        deleteFile($v['image'], 'vehicles');
                    }
                    
                    $stmt = $db->prepare("DELETE FROM vehicles WHERE id = ?");
                    $stmt->execute([$id]);
                    setFlashMessage('success', 'Vehicle deleted successfully!');
                } catch(PDOException $e) {
                    setFlashMessage('error', 'Error deleting vehicle: ' . $e->getMessage());
                }
                header('Location: ' . BASE_URL . 'pages/vehicles.php');
                exit;
        }
    }
}

// Get vehicles list
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$page = intval($_GET['page'] ?? 1);

$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(vehicle_name LIKE ? OR registration_number LIKE ? OR brand LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($categoryFilter) {
    $where[] = "category_id = ?";
    $params[] = $categoryFilter;
}

if ($statusFilter) {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}

$whereClause = implode(' AND ', $where);

// Get total count
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM vehicles WHERE $whereClause");
$countStmt->execute($params);
$totalItems = $countStmt->fetch()['total'];

$pagination = getPagination($page, $totalItems);
$offset = $pagination['offset'];

// Get vehicles
$stmt = $db->prepare("SELECT v.*, c.name as category_name FROM vehicles v LEFT JOIN categories c ON v.category_id = c.id WHERE $whereClause ORDER BY v.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [ITEMS_PER_PAGE, $offset]));
$vehicles = $stmt->fetchAll();

// Get categories for filter
$categories = $db->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();

// Get vehicle for edit
$editVehicle = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $db->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([$id]);
    $editVehicle = $stmt->fetch();
}

include '../includes/header.php';
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

<div class="page-header">
    <h1 class="page-title">Vehicle Management</h1>
    <p class="page-subtitle">Manage your vehicle fleet</p>
</div>

<!-- Add/Edit Vehicle Form -->
<?php if (isset($_GET['add']) || isset($_GET['edit'])): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo isset($_GET['edit']) ? 'Edit Vehicle' : 'Add New Vehicle'; ?></h3>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" data-validate>
                <input type="hidden" name="action" value="<?php echo isset($_GET['edit']) ? 'edit' : 'add'; ?>">
                <?php if (isset($_GET['edit'])): ?>
                    <input type="hidden" name="id" value="<?php echo $editVehicle['id']; ?>">
                <?php endif; ?>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg);">
                    <div class="form-group">
                        <label class="form-label required" for="category_id">Category</label>
                        <select class="form-control" id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($editVehicle && $editVehicle['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="vehicle_name">Vehicle Name</label>
                        <input type="text" class="form-control" id="vehicle_name" name="vehicle_name" required value="<?php echo htmlspecialchars($editVehicle['vehicle_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="brand">Brand</label>
                        <input type="text" class="form-control" id="brand" name="brand" value="<?php echo htmlspecialchars($editVehicle['brand'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="model">Model</label>
                        <input type="text" class="form-control" id="model" name="model" value="<?php echo htmlspecialchars($editVehicle['model'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="year">Year</label>
                        <input type="number" class="form-control" id="year" name="year" min="1900" max="<?php echo date('Y') + 1; ?>" value="<?php echo $editVehicle['year'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="color">Color</label>
                        <input type="text" class="form-control" id="color" name="color" value="<?php echo htmlspecialchars($editVehicle['color'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="registration_number">Registration Number</label>
                        <input type="text" class="form-control" id="registration_number" name="registration_number" required value="<?php echo htmlspecialchars($editVehicle['registration_number'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="chassis_number">Chassis Number</label>
                        <input type="text" class="form-control" id="chassis_number" name="chassis_number" value="<?php echo htmlspecialchars($editVehicle['chassis_number'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="engine_number">Engine Number</label>
                        <input type="text" class="form-control" id="engine_number" name="engine_number" value="<?php echo htmlspecialchars($editVehicle['engine_number'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="fuel_type">Fuel Type</label>
                        <select class="form-control" id="fuel_type" name="fuel_type">
                            <option value="petrol" <?php echo ($editVehicle && $editVehicle['fuel_type'] === 'petrol') ? 'selected' : ''; ?>>Petrol</option>
                            <option value="diesel" <?php echo ($editVehicle && $editVehicle['fuel_type'] === 'diesel') ? 'selected' : ''; ?>>Diesel</option>
                            <option value="electric" <?php echo ($editVehicle && $editVehicle['fuel_type'] === 'electric') ? 'selected' : ''; ?>>Electric</option>
                            <option value="hybrid" <?php echo ($editVehicle && $editVehicle['fuel_type'] === 'hybrid') ? 'selected' : ''; ?>>Hybrid</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="seating_capacity">Seating Capacity</label>
                        <input type="number" class="form-control" id="seating_capacity" name="seating_capacity" min="1" value="<?php echo $editVehicle['seating_capacity'] ?? 4; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="daily_rate">Daily Rate</label>
                        <input type="number" class="form-control" id="daily_rate" name="daily_rate" step="0.01" min="0" required value="<?php echo $editVehicle['daily_rate'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="weekly_rate">Weekly Rate</label>
                        <input type="number" class="form-control" id="weekly_rate" name="weekly_rate" step="0.01" min="0" value="<?php echo $editVehicle['weekly_rate'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="monthly_rate">Monthly Rate</label>
                        <input type="number" class="form-control" id="monthly_rate" name="monthly_rate" step="0.01" min="0" value="<?php echo $editVehicle['monthly_rate'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="available" <?php echo ($editVehicle && $editVehicle['status'] === 'available') ? 'selected' : ''; ?>>Available</option>
                            <option value="rented" <?php echo ($editVehicle && $editVehicle['status'] === 'rented') ? 'selected' : ''; ?>>Rented</option>
                            <option value="maintenance" <?php echo ($editVehicle && $editVehicle['status'] === 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="inactive" <?php echo ($editVehicle && $editVehicle['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label" for="image">Vehicle Image</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*" data-preview="#imagePreview">
                        <?php if ($editVehicle && $editVehicle['image']): ?>
                            <img id="imagePreview" src="<?php echo getFileUrl($editVehicle['image'], 'vehicles'); ?>" style="max-width: 200px; margin-top: var(--spacing-md); border-radius: var(--radius);">
                        <?php else: ?>
                            <img id="imagePreview" src="" style="display: none; max-width: 200px; margin-top: var(--spacing-md); border-radius: var(--radius);">
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($editVehicle['description'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="card-footer">
                    <a href="<?php echo BASE_URL; ?>pages/vehicles.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><?php echo isset($_GET['edit']) ? 'Update Vehicle' : 'Add Vehicle'; ?></button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- Vehicles List -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Vehicles List</h3>
            <a href="<?php echo BASE_URL; ?>pages/vehicles.php?add=1" class="btn btn-primary">
                <i class="icon icon-add"></i> Add Vehicle
            </a>
        </div>
        <div class="card-body">
            <!-- Search and Filters -->
            <form method="GET" class="search-filter-bar">
                <div class="search-box">
                    <i class="icon icon-search"></i>
                    <input type="text" class="form-control" name="search" placeholder="Search vehicles..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <select class="form-control" name="category" style="width: 200px;">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select class="form-control" name="status" style="width: 200px;">
                    <option value="">All Status</option>
                    <option value="available" <?php echo $statusFilter === 'available' ? 'selected' : ''; ?>>Available</option>
                    <option value="rented" <?php echo $statusFilter === 'rented' ? 'selected' : ''; ?>>Rented</option>
                    <option value="maintenance" <?php echo $statusFilter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?php echo BASE_URL; ?>pages/vehicles.php" class="btn btn-secondary">Reset</a>
            </form>
            
            <!-- Vehicles Table -->
            <?php if (empty($vehicles)): ?>
                <div class="empty-state">
                    <i class="icon icon-vehicle"></i>
                    <h3>No Vehicles Found</h3>
                    <p>No vehicles match your search criteria.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Vehicle Name</th>
                                <th>Category</th>
                                <th>Registration</th>
                                <th>Daily Rate</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <tr>
                                    <td>
                                        <?php if ($vehicle['image']): ?>
                                            <img src="<?php echo getFileUrl($vehicle['image'], 'vehicles'); ?>" alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: var(--radius);">
                                        <?php else: ?>
                                            <div style="width: 60px; height: 60px; background: var(--bg-tertiary); border-radius: var(--radius); display: flex; align-items: center; justify-content: center;">
                                                <i class="icon icon-vehicle"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($vehicle['vehicle_name']); ?></strong><br>
                                        <small style="color: var(--text-secondary);"><?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($vehicle['category_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['registration_number']); ?></td>
                                    <td><?php echo formatCurrency($vehicle['daily_rate']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $vehicle['status'] === 'available' ? 'success' : 
                                                ($vehicle['status'] === 'rented' ? 'warning' : 
                                                ($vehicle['status'] === 'maintenance' ? 'danger' : 'secondary')); 
                                        ?>">
                                            <?php echo ucfirst($vehicle['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="<?php echo BASE_URL; ?>pages/vehicle-details.php?id=<?php echo $vehicle['id']; ?>" class="action-btn action-btn-view" data-tooltip="View">
                                                <i class="icon icon-view"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>pages/vehicles.php?edit=<?php echo $vehicle['id']; ?>" class="action-btn action-btn-edit" data-tooltip="Edit">
                                                <i class="icon icon-edit"></i>
                                            </a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Are you sure you want to delete this vehicle?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $vehicle['id']; ?>">
                                                <button type="submit" class="action-btn action-btn-delete" data-tooltip="Delete">
                                                    <i class="icon icon-delete"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="pagination">
                        <?php if ($pagination['has_prev']): ?>
                            <a href="?page=<?php echo $pagination['current_page'] - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($categoryFilter); ?>&status=<?php echo urlencode($statusFilter); ?>">Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <?php if ($i == $pagination['current_page']): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($categoryFilter); ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['has_next']): ?>
                            <a href="?page=<?php echo $pagination['current_page'] + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($categoryFilter); ?>&status=<?php echo urlencode($statusFilter); ?>">Next</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

