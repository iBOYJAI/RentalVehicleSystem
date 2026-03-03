<?php

/**
 * Payments Management Page
 */
require_once '../config/config.php';
require_once '../config/auth.php';
require_once '../config/functions.php';

requireRole(['admin', 'staff']);

$pageTitle = 'Payments';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $bookingId = intval($_POST['booking_id']);
                $amount = floatval($_POST['amount']);
                $paymentType = sanitize($_POST['payment_type']);
                $paymentMethod = sanitize($_POST['payment_method']);
                $notes = sanitize($_POST['notes'] ?? '');

                $paymentNumber = generateUniqueCode('PAY-', 'payments', 'payment_number');

                try {
                    $db->beginTransaction();

                    $stmt = $db->prepare("INSERT INTO payments (payment_number, booking_id, payment_type, amount, payment_method, status, notes, created_by) VALUES (?, ?, ?, ?, ?, 'completed', ?, ?)");
                    $stmt->execute([$paymentNumber, $bookingId, $paymentType, $amount, $paymentMethod, $notes, $_SESSION['user_id']]);

                    // Update booking advance payment if advance type
                    if ($paymentType === 'advance') {
                        $db->prepare("UPDATE bookings SET advance_payment = advance_payment + ? WHERE id = ?")->execute([$amount, $bookingId]);
                    }

                    // Automated Workflow: Sync invoice status and amounts
                    syncInvoiceStatus($bookingId);

                    $db->commit();

                    setFlashMessage('success', 'Payment recorded and invoice synced!');
                    header('Location: ' . BASE_URL . 'pages/payments.php');
                    exit;
                } catch (PDOException $e) {
                    if ($db->inTransaction()) $db->rollBack();
                    setFlashMessage('error', 'Error: ' . $e->getMessage());
                }
                break;
        }
    }
}

$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$page = intval($_GET['page'] ?? 1);

$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(p.payment_number LIKE ? OR b.booking_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($statusFilter) {
    $where[] = "p.status = ?";
    $params[] = $statusFilter;
}

$whereClause = implode(' AND ', $where);
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM payments p JOIN bookings b ON p.booking_id = b.id WHERE $whereClause");
$countStmt->execute($params);
$totalItems = $countStmt->fetch()['total'];

$pagination = getPagination($page, $totalItems);
$offset = $pagination['offset'];

$stmt = $db->prepare("SELECT p.*, b.booking_number, b.total_amount, c.full_name as customer_name FROM payments p JOIN bookings b ON p.booking_id = b.id JOIN customers c ON b.customer_id = c.id WHERE $whereClause ORDER BY p.payment_date DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [ITEMS_PER_PAGE, $offset]));
$payments = $stmt->fetchAll();

// Get bookings for form
$bookings = $db->query("SELECT b.id, b.booking_number, b.total_amount, b.advance_payment, c.full_name FROM bookings b JOIN customers c ON b.customer_id = c.id WHERE b.status IN ('approved', 'active') ORDER BY b.created_at DESC")->fetchAll();

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
    <h1 class="page-title">Payments Management</h1>
    <p class="page-subtitle">Manage payment transactions</p>
</div>

<?php if (isset($_GET['add'])): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Record Payment</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg);">
                    <div class="form-group">
                        <label class="form-label required" for="booking_id">Booking</label>
                        <select class="form-control" id="booking_id" name="booking_id" required>
                            <option value="">Select Booking</option>
                            <?php foreach ($bookings as $booking): ?>
                                <option value="<?php echo $booking['id']; ?>" data-total="<?php echo $booking['total_amount']; ?>" data-advance="<?php echo $booking['advance_payment']; ?>">
                                    <?php echo htmlspecialchars($booking['booking_number'] . ' - ' . $booking['full_name'] . ' (' . formatCurrency($booking['total_amount']) . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="payment_type">Payment Type</label>
                        <select class="form-control" id="payment_type" name="payment_type" required>
                            <option value="advance">Advance</option>
                            <option value="partial">Partial</option>
                            <option value="full">Full Payment</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="amount">Amount</label>
                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="payment_method">Payment Method</label>
                        <select class="form-control" id="payment_method" name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="online">Online</option>
                        </select>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>

                <div class="card-footer">
                    <a href="<?php echo BASE_URL; ?>pages/payments.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Payments List</h3>
            <a href="<?php echo BASE_URL; ?>pages/payments.php?add=1" class="btn btn-primary">
                <i class="icon icon-add"></i> Record Payment
            </a>
        </div>
        <div class="card-body">
            <form method="GET" class="search-filter-bar">
                <div class="search-box">
                    <i class="icon icon-search"></i>
                    <input type="text" class="form-control" name="search" placeholder="Search payments..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <select class="form-control" name="status" style="width: 200px;">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?php echo BASE_URL; ?>pages/payments.php" class="btn btn-secondary">Reset</a>
            </form>

            <div class="table-container" style="margin-top: var(--spacing-lg);">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Payment #</th>
                            <th>Booking #</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Method</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No payments found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($payment['payment_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($payment['booking_number']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                                    <td><?php echo formatCurrency($payment['amount']); ?></td>
                                    <td><?php echo ucfirst($payment['payment_type']); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                    <td><?php echo formatDate($payment['payment_date'], 'd M Y, h:i A'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $payment['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
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