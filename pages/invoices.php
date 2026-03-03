<?php

/**
 * Invoices Management Page
 */
require_once '../config/config.php';
require_once '../config/auth.php';
require_once '../config/functions.php';

requireRole(['admin', 'staff']);

$pageTitle = 'Invoices';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'generate':
                $bookingId = intval($_POST['booking_id']);

                // Check if invoice already exists
                $existing = $db->prepare("SELECT id FROM invoices WHERE booking_id = ?");
                $existing->execute([$bookingId]);
                if ($existing->fetch()) {
                    setFlashMessage('error', 'Invoice already exists for this booking!');
                    header('Location: ' . BASE_URL . 'pages/invoices.php');
                    exit;
                }

                // Get booking details
                $booking = $db->prepare("SELECT b.*, c.id as customer_id FROM bookings b JOIN customers c ON b.customer_id = c.id WHERE b.id = ?");
                $booking->execute([$bookingId]);
                $b = $booking->fetch();

                if (!$b) {
                    setFlashMessage('error', 'Booking not found!');
                    header('Location: ' . BASE_URL . 'pages/invoices.php');
                    exit;
                }

                $invoiceNumber = generateUniqueCode('INV-', 'invoices', 'invoice_number');
                $issueDate = date('Y-m-d');
                $dueDate = date('Y-m-d', strtotime('+30 days'));

                try {
                    $stmt = $db->prepare("INSERT INTO invoices (invoice_number, booking_id, customer_id, issue_date, due_date, subtotal, tax, discount, total_amount, paid_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $invoiceNumber,
                        $bookingId,
                        $b['customer_id'],
                        $issueDate,
                        $dueDate,
                        $b['subtotal'],
                        $b['tax'],
                        $b['discount'],
                        $b['total_amount'],
                        $b['advance_payment'],
                        'draft'
                    ]);
                    setFlashMessage('success', 'Invoice generated successfully!');
                    header('Location: ' . BASE_URL . 'pages/invoices.php');
                    exit;
                } catch (PDOException $e) {
                    setFlashMessage('error', 'Error: ' . $e->getMessage());
                }
                break;

            case 'update_status':
                $id = intval($_POST['id']);
                $status = sanitize($_POST['status']);
                try {
                    $stmt = $db->prepare("UPDATE invoices SET status = ? WHERE id = ?");
                    $stmt->execute([$status, $id]);
                    setFlashMessage('success', 'Invoice status updated!');
                } catch (PDOException $e) {
                    setFlashMessage('error', 'Error: ' . $e->getMessage());
                }
                header('Location: ' . BASE_URL . 'pages/invoices.php');
                exit;
        }
    }
}

$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$page = intval($_GET['page'] ?? 1);

$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(i.invoice_number LIKE ? OR b.booking_number LIKE ? OR c.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($statusFilter) {
    $where[] = "i.status = ?";
    $params[] = $statusFilter;
}

$whereClause = implode(' AND ', $where);
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM invoices i JOIN bookings b ON i.booking_id = b.id JOIN customers c ON i.customer_id = c.id WHERE $whereClause");
$countStmt->execute($params);
$totalItems = $countStmt->fetch()['total'];

$pagination = getPagination($page, $totalItems);
$offset = $pagination['offset'];

$stmt = $db->prepare("SELECT i.*, b.booking_number, c.full_name as customer_name FROM invoices i JOIN bookings b ON i.booking_id = b.id JOIN customers c ON i.customer_id = c.id WHERE $whereClause ORDER BY i.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [ITEMS_PER_PAGE, $offset]));
$invoices = $stmt->fetchAll();

// Get bookings without invoices for generation
$bookingsWithoutInvoice = $db->query("
    SELECT b.id, b.booking_number, b.total_amount, c.full_name as customer_name
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    WHERE b.status IN ('approved', 'active', 'completed')
    AND NOT EXISTS (SELECT 1 FROM invoices WHERE booking_id = b.id)
    ORDER BY b.created_at DESC
    LIMIT 50
")->fetchAll();

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
    <h1 class="page-title">Invoice Management</h1>
    <p class="page-subtitle">Generate and manage invoices</p>
</div>

<?php if (isset($_GET['generate'])): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Generate Invoice</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="generate">

                <div class="form-group">
                    <label class="form-label required" for="booking_id">Select Booking</label>
                    <select class="form-control" id="booking_id" name="booking_id" required>
                        <option value="">Select Booking</option>
                        <?php foreach ($bookingsWithoutInvoice as $booking): ?>
                            <option value="<?php echo $booking['id']; ?>">
                                <?php echo htmlspecialchars($booking['booking_number'] . ' - ' . $booking['customer_name'] . ' (' . formatCurrency($booking['total_amount']) . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-help">Only bookings without invoices are shown</div>
                </div>

                <div class="card-footer">
                    <a href="<?php echo BASE_URL; ?>pages/invoices.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Generate Invoice</button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Invoices List</h3>
            <a href="<?php echo BASE_URL; ?>pages/invoices.php?generate=1" class="btn btn-primary">
                <i class="icon icon-add"></i> Generate Invoice
            </a>
        </div>
        <div class="card-body">
            <form method="GET" class="search-filter-bar">
                <div class="search-box">
                    <i class="icon icon-search"></i>
                    <input type="text" class="form-control" name="search" placeholder="Search invoices..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <select class="form-control" name="status" style="width: 200px;">
                    <option value="">All Status</option>
                    <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="sent" <?php echo $statusFilter === 'sent' ? 'selected' : ''; ?>>Sent</option>
                    <option value="partially_paid" <?php echo $statusFilter === 'partially_paid' ? 'selected' : ''; ?>>Partially Paid</option>
                    <option value="paid" <?php echo $statusFilter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="overdue" <?php echo $statusFilter === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                    <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?php echo BASE_URL; ?>pages/invoices.php" class="btn btn-secondary">Reset</a>
            </form>

            <div class="table-container" style="margin-top: var(--spacing-lg);">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Booking #</th>
                            <th>Customer</th>
                            <th>Issue Date</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invoices)): ?>
                            <tr>
                                <td colspan="9" class="text-center">No invoices found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($invoice['booking_number']); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['customer_name']); ?></td>
                                    <td><?php echo formatDate($invoice['issue_date']); ?></td>
                                    <td><?php echo formatDate($invoice['due_date']); ?></td>
                                    <td><?php echo formatCurrency($invoice['total_amount']); ?></td>
                                    <td><?php echo formatCurrency($invoice['paid_amount']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php
                                                                    echo $invoice['status'] === 'paid' ? 'success' : ($invoice['status'] === 'overdue' ? 'danger' : ($invoice['status'] === 'sent' ? 'info' : ($invoice['status'] === 'partially_paid' ? 'warning' : ($invoice['status'] === 'draft' ? 'secondary' : 'warning'))));
                                                                    ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $invoice['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="<?php echo BASE_URL; ?>pages/invoice-view.php?id=<?php echo $invoice['id']; ?>" class="action-btn action-btn-view" target="_blank" data-tooltip="View/Print">
                                                <i class="icon icon-view"></i>
                                            </a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="id" value="<?php echo $invoice['id']; ?>">
                                                <select name="status" class="form-control" style="display: inline-block; width: auto; padding: 4px 8px; font-size: 0.85rem;" onchange="this.form.submit()">
                                                    <option value="draft" <?php echo $invoice['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                    <option value="sent" <?php echo $invoice['status'] === 'sent' ? 'selected' : ''; ?>>Sent</option>
                                                    <option value="partially_paid" <?php echo $invoice['status'] === 'partially_paid' ? 'selected' : ''; ?>>Partially Paid</option>
                                                    <option value="paid" <?php echo $invoice['status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                    <option value="overdue" <?php echo $invoice['status'] === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                                                    <option value="cancelled" <?php echo $invoice['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
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