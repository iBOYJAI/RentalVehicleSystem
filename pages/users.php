<?php
/**
 * Users Management Page
 */
require_once '../config/config.php';
require_once '../config/auth.php';
require_once '../config/functions.php';

requireRole(['admin']);

$pageTitle = 'Users';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
            case 'edit':
                $id = $_POST['action'] === 'edit' ? intval($_POST['id']) : null;
                $username = sanitize($_POST['username']);
                $email = sanitize($_POST['email']);
                $fullName = sanitize($_POST['full_name']);
                $role = sanitize($_POST['role']);
                $phone = sanitize($_POST['phone'] ?? '');
                $address = sanitize($_POST['address'] ?? '');
                $status = sanitize($_POST['status'] ?? 'active');
                
                try {
                    if ($id) {
                        // Update
                        if (!empty($_POST['password'])) {
                            $password = hashPassword($_POST['password']);
                            $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, password = ?, full_name = ?, role = ?, phone = ?, address = ?, status = ? WHERE id = ?");
                            $stmt->execute([$username, $email, $password, $fullName, $role, $phone, $address, $status, $id]);
                        } else {
                            $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, role = ?, phone = ?, address = ?, status = ? WHERE id = ?");
                            $stmt->execute([$username, $email, $fullName, $role, $phone, $address, $status, $id]);
                        }
                        setFlashMessage('success', 'User updated!');
                    } else {
                        // Insert
                        $password = hashPassword($_POST['password']);
                        $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, role, phone, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$username, $email, $password, $fullName, $role, $phone, $address, $status]);
                        setFlashMessage('success', 'User added!');
                    }
                    header('Location: ' . BASE_URL . 'pages/users.php');
                    exit;
                } catch(PDOException $e) {
                    setFlashMessage('error', 'Error: ' . $e->getMessage());
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                if ($id == $_SESSION['user_id']) {
                    setFlashMessage('error', 'You cannot delete your own account!');
                } else {
                    try {
                        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                        $stmt->execute([$id]);
                        setFlashMessage('success', 'User deleted!');
                    } catch(PDOException $e) {
                        setFlashMessage('error', 'Error: ' . $e->getMessage());
                    }
                }
                header('Location: ' . BASE_URL . 'pages/users.php');
                exit;
        }
    }
}

$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$page = intval($_GET['page'] ?? 1);

$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($roleFilter) {
    $where[] = "role = ?";
    $params[] = $roleFilter;
}

$whereClause = implode(' AND ', $where);
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE $whereClause");
$countStmt->execute($params);
$totalItems = $countStmt->fetch()['total'];

$pagination = getPagination($page, $totalItems);
$offset = $pagination['offset'];

$stmt = $db->prepare("SELECT id, username, email, full_name, role, phone, status, created_at FROM users WHERE $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [ITEMS_PER_PAGE, $offset]));
$users = $stmt->fetchAll();

$editUser = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $editUser = $stmt->fetch();
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
    <h1 class="page-title">Users Management</h1>
    <p class="page-subtitle">Manage system users</p>
</div>

<?php if (isset($_GET['add']) || isset($_GET['edit'])): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo isset($_GET['edit']) ? 'Edit User' : 'Add User'; ?></h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo isset($_GET['edit']) ? 'edit' : 'add'; ?>">
                <?php if (isset($_GET['edit'])): ?>
                    <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
                <?php endif; ?>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg);">
                    <div class="form-group">
                        <label class="form-label required" for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required value="<?php echo htmlspecialchars($editUser['username'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($editUser['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label <?php echo isset($_GET['edit']) ? '' : 'required'; ?>" for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" <?php echo isset($_GET['edit']) ? '' : 'required'; ?>>
                        <?php if (isset($_GET['edit'])): ?>
                            <div class="form-help">Leave blank to keep current password</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="full_name">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($editUser['full_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="role">Role</label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="admin" <?php echo ($editUser && $editUser['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="staff" <?php echo ($editUser && $editUser['role'] === 'staff') ? 'selected' : ''; ?>>Staff</option>
                            <option value="customer" <?php echo ($editUser && $editUser['role'] === 'customer') ? 'selected' : ''; ?>>Customer</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($editUser['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label" for="address">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($editUser['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="active" <?php echo ($editUser && $editUser['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($editUser && $editUser['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="card-footer">
                    <a href="<?php echo BASE_URL; ?>pages/users.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><?php echo isset($_GET['edit']) ? 'Update' : 'Add'; ?> User</button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Users List</h3>
            <a href="<?php echo BASE_URL; ?>pages/users.php?add=1" class="btn btn-primary">
                <i class="icon icon-add"></i> Add User
            </a>
        </div>
        <div class="card-body">
            <form method="GET" class="search-filter-bar">
                <div class="search-box">
                    <i class="icon icon-search"></i>
                    <input type="text" class="form-control" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <select class="form-control" name="role" style="width: 200px;">
                    <option value="">All Roles</option>
                    <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="staff" <?php echo $roleFilter === 'staff' ? 'selected' : ''; ?>>Staff</option>
                    <option value="customer" <?php echo $roleFilter === 'customer' ? 'selected' : ''; ?>>Customer</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?php echo BASE_URL; ?>pages/users.php" class="btn btn-secondary">Reset</a>
            </form>
            
            <div class="table-container" style="margin-top: var(--spacing-lg);">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No users found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $user['role'] === 'admin' ? 'danger' : 
                                                ($user['role'] === 'staff' ? 'info' : 'secondary'); 
                                        ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="<?php echo BASE_URL; ?>pages/users.php?edit=<?php echo $user['id']; ?>" class="action-btn action-btn-edit">
                                                <i class="icon icon-edit"></i>
                                            </a>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirmDelete();">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="action-btn action-btn-delete">
                                                        <i class="icon icon-delete"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
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
                        <a href="?page=<?php echo $pagination['current_page'] - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>">Previous</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <?php if ($i == $pagination['current_page']): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($pagination['has_next']): ?>
                        <a href="?page=<?php echo $pagination['current_page'] + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

