<?php
/**
 * Categories Management Page
 */
require_once '../config/config.php';
require_once '../config/auth.php';
require_once '../config/functions.php';

requireRole(['admin', 'staff']);

$pageTitle = 'Categories';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
            case 'edit':
                $id = $_POST['action'] === 'edit' ? intval($_POST['id']) : null;
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description'] ?? '');
                $status = sanitize($_POST['status'] ?? 'active');
                
                try {
                    if ($id) {
                        $stmt = $db->prepare("UPDATE categories SET name = ?, description = ?, status = ? WHERE id = ?");
                        $stmt->execute([$name, $description, $status, $id]);
                        setFlashMessage('success', 'Category updated successfully!');
                    } else {
                        $stmt = $db->prepare("INSERT INTO categories (name, description, status) VALUES (?, ?, ?)");
                        $stmt->execute([$name, $description, $status]);
                        setFlashMessage('success', 'Category added successfully!');
                    }
                    header('Location: ' . BASE_URL . 'pages/categories.php');
                    exit;
                } catch(PDOException $e) {
                    setFlashMessage('error', 'Error: ' . $e->getMessage());
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                try {
                    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
                    $stmt->execute([$id]);
                    setFlashMessage('success', 'Category deleted successfully!');
                } catch(PDOException $e) {
                    setFlashMessage('error', 'Error: ' . $e->getMessage());
                }
                header('Location: ' . BASE_URL . 'pages/categories.php');
                exit;
        }
    }
}

$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$editCategory = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $editCategory = $stmt->fetch();
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
    <h1 class="page-title">Vehicle Categories</h1>
    <p class="page-subtitle">Manage vehicle categories</p>
</div>

<?php if (isset($_GET['add']) || isset($_GET['edit'])): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo isset($_GET['edit']) ? 'Edit Category' : 'Add Category'; ?></h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo isset($_GET['edit']) ? 'edit' : 'add'; ?>">
                <?php if (isset($_GET['edit'])): ?>
                    <input type="hidden" name="id" value="<?php echo $editCategory['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label required" for="name">Category Name</label>
                    <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($editCategory['name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($editCategory['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="active" <?php echo ($editCategory && $editCategory['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($editCategory && $editCategory['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="card-footer">
                    <a href="<?php echo BASE_URL; ?>pages/categories.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><?php echo isset($_GET['edit']) ? 'Update' : 'Add'; ?> Category</button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Categories List</h3>
            <a href="<?php echo BASE_URL; ?>pages/categories.php?add=1" class="btn btn-primary">
                <i class="icon icon-add"></i> Add Category
            </a>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No categories found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($cat['description'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $cat['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($cat['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="<?php echo BASE_URL; ?>pages/categories.php?edit=<?php echo $cat['id']; ?>" class="action-btn action-btn-edit">
                                                <i class="icon icon-edit"></i>
                                            </a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirmDelete();">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
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
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

