<?php
/**
 * Booking Details Page
 */
require_once '../config/config.php';
require_once '../config/auth.php';
require_once '../config/functions.php';

requireRole(['admin', 'staff']);

$pageTitle = 'Booking Details';
$db = getDB();

$id = intval($_GET['id'] ?? 0);

$booking = $db->prepare("SELECT b.*, v.vehicle_name, v.registration_number, v.daily_rate, c.full_name as customer_name, c.phone as customer_phone, c.email as customer_email FROM bookings b JOIN vehicles v ON b.vehicle_id = v.id JOIN customers c ON b.customer_id = c.id WHERE b.id = ?");
$booking->execute([$id]);
$booking = $booking->fetch();

if (!$booking) {
    header('Location: ' . BASE_URL . 'pages/bookings.php');
    exit;
}

// Get payments
$payments = $db->prepare("SELECT * FROM payments WHERE booking_id = ? ORDER BY payment_date DESC");
$payments->execute([$id]);
$payments = $payments->fetchAll();

include '../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Booking Details</h1>
    <p class="page-subtitle"><?php echo htmlspecialchars($booking['booking_number']); ?></p>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg);">
    <!-- Booking Info -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Booking Information</h3>
        </div>
        <div class="card-body">
            <div style="display: flex; flex-direction: column; gap: var(--spacing-md);">
                <div>
                    <strong>Booking Number:</strong> <?php echo htmlspecialchars($booking['booking_number']); ?>
                </div>
                <div>
                    <strong>Status:</strong> 
                    <span class="badge badge-<?php 
                        echo $booking['status'] === 'approved' ? 'success' : 
                            ($booking['status'] === 'pending' ? 'warning' : 
                            ($booking['status'] === 'active' ? 'info' : 'primary')); 
                    ?>">
                        <?php echo ucfirst($booking['status']); ?>
                    </span>
                </div>
                <div>
                    <strong>Start Date:</strong> <?php echo formatDate($booking['start_date']); ?>
                </div>
                <div>
                    <strong>End Date:</strong> <?php echo formatDate($booking['end_date']); ?>
                </div>
                <div>
                    <strong>Total Days:</strong> <?php echo $booking['total_days']; ?> days
                </div>
                <?php if ($booking['pickup_location']): ?>
                    <div>
                        <strong>Pickup Location:</strong> <?php echo htmlspecialchars($booking['pickup_location']); ?>
                    </div>
                <?php endif; ?>
                <?php if ($booking['dropoff_location']): ?>
                    <div>
                        <strong>Dropoff Location:</strong> <?php echo htmlspecialchars($booking['dropoff_location']); ?>
                    </div>
                <?php endif; ?>
                <?php if ($booking['notes']): ?>
                    <div style="margin-top: var(--spacing-md); padding-top: var(--spacing-md); border-top: 1px solid var(--border-color);">
                        <strong>Notes:</strong>
                        <p><?php echo nl2br(htmlspecialchars($booking['notes'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Customer & Vehicle Info -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Customer & Vehicle</h3>
        </div>
        <div class="card-body">
            <div style="margin-bottom: var(--spacing-lg);">
                <h4 style="margin-bottom: var(--spacing-sm);">Customer</h4>
                <div>
                    <strong>Name:</strong> <?php echo htmlspecialchars($booking['customer_name']); ?><br>
                    <strong>Phone:</strong> <?php echo htmlspecialchars($booking['customer_phone']); ?><br>
                    <?php if ($booking['customer_email']): ?>
                        <strong>Email:</strong> <?php echo htmlspecialchars($booking['customer_email']); ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="padding-top: var(--spacing-lg); border-top: 1px solid var(--border-color);">
                <h4 style="margin-bottom: var(--spacing-sm);">Vehicle</h4>
                <div>
                    <strong>Vehicle:</strong> <?php echo htmlspecialchars($booking['vehicle_name']); ?><br>
                    <strong>Registration:</strong> <?php echo htmlspecialchars($booking['registration_number']); ?><br>
                    <strong>Daily Rate:</strong> <?php echo formatCurrency($booking['daily_rate']); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Summary -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Payment Summary</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--spacing-lg); margin-bottom: var(--spacing-lg);">
            <div>
                <div style="color: var(--text-secondary); font-size: 0.85rem;">Subtotal</div>
                <div style="font-size: 1.5rem; font-weight: 600;"><?php echo formatCurrency($booking['subtotal']); ?></div>
            </div>
            <div>
                <div style="color: var(--text-secondary); font-size: 0.85rem;">Tax</div>
                <div style="font-size: 1.5rem; font-weight: 600;"><?php echo formatCurrency($booking['tax']); ?></div>
            </div>
            <div>
                <div style="color: var(--text-secondary); font-size: 0.85rem;">Advance Paid</div>
                <div style="font-size: 1.5rem; font-weight: 600;"><?php echo formatCurrency($booking['advance_payment']); ?></div>
            </div>
            <div>
                <div style="color: var(--text-secondary); font-size: 0.85rem;">Total Amount</div>
                <div style="font-size: 1.5rem; font-weight: 600; color: var(--primary-color);"><?php echo formatCurrency($booking['total_amount']); ?></div>
            </div>
        </div>
        
        <?php if (!empty($payments)): ?>
            <h4 style="margin-bottom: var(--spacing-md);">Payment History</h4>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Payment #</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['payment_number']); ?></td>
                                <td><?php echo ucfirst($payment['payment_type']); ?></td>
                                <td><?php echo formatCurrency($payment['amount']); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                <td><?php echo formatDate($payment['payment_date'], 'd M Y, h:i A'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $payment['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($payment['status']); ?>
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
    <a href="<?php echo BASE_URL; ?>pages/bookings.php" class="btn btn-secondary">Back to Bookings</a>
    <?php if ($booking['status'] === 'pending'): ?>
        <form method="POST" action="<?php echo BASE_URL; ?>pages/bookings.php" style="display: inline;">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="id" value="<?php echo $booking['id']; ?>">
            <button type="submit" class="btn btn-success">Approve Booking</button>
        </form>
    <?php endif; ?>
    <?php 
    // Check if invoice exists for this booking
    $invoiceCheck = $db->prepare("SELECT id FROM invoices WHERE booking_id = ?");
    $invoiceCheck->execute([$id]);
    $hasInvoice = $invoiceCheck->fetch();
    if ($hasInvoice): ?>
        <a href="<?php echo BASE_URL; ?>pages/invoices.php?search=<?php echo urlencode($booking['booking_number']); ?>" class="btn btn-info">View Invoice</a>
    <?php elseif (in_array($booking['status'], ['approved', 'active', 'completed'])): ?>
        <a href="<?php echo BASE_URL; ?>pages/invoices.php?generate=1" class="btn btn-primary">Generate Invoice</a>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

