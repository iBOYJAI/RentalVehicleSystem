<?php

/**
 * Browse Vehicles Page
 * Allows customers to browse bikes and other vehicles by category
 */
require_once '../config/config.php';
require_once '../config/auth.php';
require_once '../config/functions.php';

requireLogin();

$pageTitle = 'Browse Vehicles';
$db = getDB();

// Get categories for filtering
$categories = $db->query("SELECT id, name, icon FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();

// Handle filtering
$categoryFilter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

$query = "SELECT v.*, c.name as category_name 
          FROM vehicles v 
          JOIN categories c ON v.category_id = c.id 
          WHERE v.status = 'available'";
$params = [];

if ($categoryFilter) {
    $query .= " AND v.category_id = ?";
    $params[] = $categoryFilter;
}

if ($search) {
    $query .= " AND (v.vehicle_name LIKE ? OR v.brand LIKE ? OR v.model LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY v.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Browse Our Fleet</h1>
    <p class="page-subtitle">Find the perfect ride for your journey</p>
</div>

<!-- Search and Filter Bar -->
<div class="card" style="margin-bottom: var(--spacing-lg);">
    <div class="card-body">
        <form method="GET" style="display: flex; gap: var(--spacing-md); align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                <label class="form-label" for="search">Search</label>
                <div class="search-box">
                    <i class="icon icon-search"></i>
                    <input type="text" class="form-control" name="search" id="search" placeholder="Search by name, brand or model..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>

            <div class="form-group" style="width: 200px; margin-bottom: 0;">
                <label class="form-label" for="category">Category</label>
                <select class="form-control" name="category" id="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="height: 42px;">Apply Filters</button>
            <a href="browse-vehicles.php" class="btn btn-secondary" style="height: 42px; display: flex; align-items: center;">Reset</a>
        </form>
    </div>
</div>

<!-- Category Quick Select -->
<div style="display: flex; gap: var(--spacing-md); margin-bottom: var(--spacing-xl); overflow-x: auto; padding-bottom: var(--spacing-sm);">
    <a href="browse-vehicles.php" class="category-chip <?php echo !$categoryFilter ? 'active' : ''; ?>">
        All
    </a>
    <?php foreach ($categories as $cat): ?>
        <a href="?category=<?php echo $cat['id']; ?>" class="category-chip <?php echo $categoryFilter == $cat['id'] ? 'active' : ''; ?>">
            <?php echo htmlspecialchars($cat['name']); ?>
        </a>
    <?php endforeach; ?>
</div>

<style>
    .category-chip {
        padding: 8px 20px;
        background: var(--bg-secondary);
        border-radius: 50px;
        color: var(--text-primary);
        text-decoration: none;
        white-space: nowrap;
        transition: all 0.2s;
        border: 1px solid var(--border-color);
        font-weight: 500;
    }

    .category-chip:hover {
        background: var(--bg-tertiary);
    }

    .category-chip.active {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }

    .vehicle-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: var(--spacing-lg);
    }

    .vehicle-card {
        background: var(--card-bg);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .vehicle-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }

    .vehicle-image {
        height: 200px;
        width: 100%;
        object-fit: cover;
        background: var(--bg-tertiary);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .vehicle-info {
        padding: var(--spacing-lg);
    }

    .vehicle-category {
        font-size: 0.75rem;
        text-transform: uppercase;
        color: var(--primary-color);
        font-weight: 700;
        margin-bottom: var(--spacing-xs);
    }

    .vehicle-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: var(--spacing-sm);
    }

    .vehicle-meta {
        display: flex;
        gap: var(--spacing-md);
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin-bottom: var(--spacing-md);
    }

    .vehicle-price {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    .vehicle-price span {
        font-size: 0.85rem;
        font-weight: 400;
        color: var(--text-secondary);
    }
</style>

<!-- Vehicle Grid -->
<?php if (empty($vehicles)): ?>
    <div class="empty-state">
        <i class="icon icon-vehicle" style="font-size: 4rem; color: var(--text-secondary);"></i>
        <h3>No vehicles found</h3>
        <p>Try adjusting your search or filters.</p>
    </div>
<?php else: ?>
    <div class="vehicle-grid">
        <?php foreach ($vehicles as $vehicle): ?>
            <div class="vehicle-card">
                <?php if ($vehicle['image']): ?>
                    <img src="<?php echo getFileUrl($vehicle['image'], 'vehicles'); ?>" alt="<?php echo htmlspecialchars($vehicle['vehicle_name']); ?>" class="vehicle-image">
                <?php else: ?>
                    <div class="vehicle-image">
                        <i class="icon icon-vehicle" style="font-size: 3rem; color: var(--border-color);"></i>
                    </div>
                <?php endif; ?>

                <div class="vehicle-info">
                    <div class="vehicle-category"><?php echo htmlspecialchars($vehicle['category_name']); ?></div>
                    <div class="vehicle-title"><?php echo htmlspecialchars($vehicle['vehicle_name']); ?></div>

                    <div class="vehicle-meta">
                        <span><i class="icon icon-user"></i> <?php echo $vehicle['seating_capacity']; ?> Seats</span>
                        <span><i class="icon icon-category"></i> <?php echo ucfirst($vehicle['fuel_type']); ?></span>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: var(--spacing-lg);">
                        <div class="vehicle-price">
                            <?php echo formatCurrency($vehicle['daily_rate']); ?><span>/day</span>
                        </div>
                        <a href="my-bookings.php?vehicle_id=<?php echo $vehicle['id']; ?>" class="btn btn-primary">Book Now</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>