<?php
/**
 * Customers Management Page
 */
require_once '../config/config.php';
require_once '../config/auth.php';
require_once '../config/functions.php';

requireRole(['admin', 'staff']);

$pageTitle = 'Customers';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
            case 'edit':
                $id = $_POST['action'] === 'edit' ? intval($_POST['id']) : null;
                $customerCode = sanitize($_POST['customer_code']);
                $fullName = sanitize($_POST['full_name']);
                $email = sanitize($_POST['email'] ?? '');
                $phone = sanitize($_POST['phone']);
                $address = sanitize($_POST['address'] ?? '');
                $licenseNumber = sanitize($_POST['license_number'] ?? '');
                $licenseExpiry = sanitize($_POST['license_expiry'] ?? null);
                $status = sanitize($_POST['status'] ?? 'active');
                
                // Handle document uploads
                $idProof = null;
                $addressProof = null;
                
                if (isset($_FILES['id_proof']) && $_FILES['id_proof']['error'] === UPLOAD_ERR_OK) {
                    $upload = uploadFile($_FILES['id_proof'], 'documents', ALLOWED_DOC_TYPES);
                    if ($upload['success']) $idProof = $upload['filename'];
                } elseif ($id) {
                    $old = $db->prepare("SELECT id_proof FROM customers WHERE id = ?");
                    $old->execute([$id]);
                    $oldData = $old->fetch();
                    $idProof = $oldData['id_proof'] ?? null;
                }
                
                if (isset($_FILES['address_proof']) && $_FILES['address_proof']['error'] === UPLOAD_ERR_OK) {
                    $upload = uploadFile($_FILES['address_proof'], 'documents', ALLOWED_DOC_TYPES);
                    if ($upload['success']) $addressProof = $upload['filename'];
                } elseif ($id) {
                    $old = $db->prepare("SELECT address_proof FROM customers WHERE id = ?");
                    $old->execute([$id]);
                    $oldData = $old->fetch();
                    $addressProof = $oldData['address_proof'] ?? null;
                }
                
                try {
                    if ($id) {
                        $stmt = $db->prepare("UPDATE customers SET customer_code = ?, full_name = ?, email = ?, phone = ?, address = ?, license_number = ?, license_expiry = ?, id_proof = ?, address_proof = ?, status = ? WHERE id = ?");
                        $stmt->execute([$customerCode, $fullName, $email, $phone, $address, $licenseNumber, $licenseExpiry, $idProof, $addressProof, $status, $id]);
                        setFlashMessage('success', 'Customer updated!');
                    } else {
                        if (empty($customerCode)) {
                            $customerCode = generateUniqueCode('CUST', 'customers', 'customer_code');
                        }
                        $stmt = $db->prepare("INSERT INTO customers (customer_code, full_name, email, phone, address, license_number, license_expiry, id_proof, address_proof, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$customerCode, $fullName, $email, $phone, $address, $licenseNumber, $licenseExpiry, $idProof, $addressProof, $status]);
                        setFlashMessage('success', 'Customer added!');
                    }
                    header('Location: ' . BASE_URL . 'pages/customers.php');
                    exit;
                } catch(PDOException $e) {
                    setFlashMessage('error', 'Error: ' . $e->getMessage());
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                try {
                    $customer = $db->prepare("SELECT id_proof, address_proof FROM customers WHERE id = ?");
                    $customer->execute([$id]);
                    $c = $customer->fetch();
                    if ($c) {
                        if ($c['id_proof']) deleteFile($c['id_proof'], 'documents');
                        if ($c['address_proof']) deleteFile($c['address_proof'], 'documents');
                    }
                    $stmt = $db->prepare("DELETE FROM customers WHERE id = ?");
                    $stmt->execute([$id]);
                    setFlashMessage('success', 'Customer deleted!');
                } catch(PDOException $e) {
                    setFlashMessage('error', 'Error: ' . $e->getMessage());
                }
                header('Location: ' . BASE_URL . 'pages/customers.php');
                exit;
        }
    }
}

$search = $_GET['search'] ?? '';
$page = intval($_GET['page'] ?? 1);

$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(customer_code LIKE ? OR full_name LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(' AND ', $where);
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM customers WHERE $whereClause");
$countStmt->execute($params);
$totalItems = $countStmt->fetch()['total'];

$pagination = getPagination($page, $totalItems);
$offset = $pagination['offset'];

$stmt = $db->prepare("SELECT * FROM customers WHERE $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [ITEMS_PER_PAGE, $offset]));
$customers = $stmt->fetchAll();

$editCustomer = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    $editCustomer = $stmt->fetch();
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
    <h1 class="page-title">Customers Management</h1>
    <p class="page-subtitle">Manage customer information</p>
</div>

<?php if (isset($_GET['add']) || isset($_GET['edit'])): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo isset($_GET['edit']) ? 'Edit Customer' : 'Add Customer'; ?></h3>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo isset($_GET['edit']) ? 'edit' : 'add'; ?>">
                <?php if (isset($_GET['edit'])): ?>
                    <input type="hidden" name="id" value="<?php echo $editCustomer['id']; ?>">
                <?php endif; ?>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg);">
                    <div class="form-group">
                        <label class="form-label" for="customer_code">Customer Code</label>
                        <input type="text" class="form-control" id="customer_code" name="customer_code" value="<?php echo htmlspecialchars($editCustomer['customer_code'] ?? ''); ?>" <?php echo isset($_GET['edit']) ? '' : 'readonly'; ?>>
                        <div class="form-help">Auto-generated if left empty</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="full_name">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($editCustomer['full_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($editCustomer['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="phone">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" required value="<?php echo htmlspecialchars($editCustomer['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label" for="address">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($editCustomer['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="license_number">License Number</label>
                        <input type="text" class="form-control" id="license_number" name="license_number" value="<?php echo htmlspecialchars($editCustomer['license_number'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="license_expiry">License Expiry</label>
                        <input type="date" class="form-control" id="license_expiry" name="license_expiry" value="<?php echo $editCustomer['license_expiry'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="id_proof">ID Proof</label>
                        <input type="file" class="form-control" id="id_proof" name="id_proof" accept=".pdf,.jpg,.jpeg,.png">
                        <?php if ($editCustomer && $editCustomer['id_proof']): ?>
                            <a href="<?php echo getFileUrl($editCustomer['id_proof'], 'documents'); ?>" target="_blank" class="btn btn-sm btn-info" style="margin-top: var(--spacing-sm);">View Current</a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="address_proof">Address Proof</label>
                        <input type="file" class="form-control" id="address_proof" name="address_proof" accept=".pdf,.jpg,.jpeg,.png">
                        <?php if ($editCustomer && $editCustomer['address_proof']): ?>
                            <a href="<?php echo getFileUrl($editCustomer['address_proof'], 'documents'); ?>" target="_blank" class="btn btn-sm btn-info" style="margin-top: var(--spacing-sm);">View Current</a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="active" <?php echo ($editCustomer && $editCustomer['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($editCustomer && $editCustomer['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="blacklisted" <?php echo ($editCustomer && $editCustomer['status'] === 'blacklisted') ? 'selected' : ''; ?>>Blacklisted</option>
                        </select>
                    </div>
                </div>
                
                <div class="card-footer">
                    <a href="<?php echo BASE_URL; ?>pages/customers.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><?php echo isset($_GET['edit']) ? 'Update' : 'Add'; ?> Customer</button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Customers List</h3>
            <a href="<?php echo BASE_URL; ?>pages/customers.php?add=1" class="btn btn-primary">
                <i class="icon icon-add"></i> Add Customer
            </a>
        </div>
        <div class="card-body">
            <form method="GET" class="search-filter-bar">
                <div class="search-box">
                    <i class="icon icon-search"></i>
                    <input type="text" class="form-control" name="search" placeholder="Search customers..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="<?php echo BASE_URL; ?>pages/customers.php" class="btn btn-secondary">Reset</a>
            </form>
            
            <div class="table-container" style="margin-top: var(--spacing-lg);">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>License</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No customers found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($customer['customer_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($customer['license_number'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $customer['status'] === 'active' ? 'success' : 
                                                ($customer['status'] === 'blacklisted' ? 'danger' : 'secondary'); 
                                        ?>">
                                            <?php echo ucfirst($customer['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="<?php echo BASE_URL; ?>pages/customers.php?edit=<?php echo $customer['id']; ?>" class="action-btn action-btn-edit">
                                                <i class="icon icon-edit"></i>
                                            </a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirmDelete();">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $customer['id']; ?>">
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
                        <a href="?page=<?php echo $pagination['current_page'] - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <?php if ($i == $pagination['current_page']): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($pagination['has_next']): ?>
                        <a href="?page=<?php echo $pagination['current_page'] + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

