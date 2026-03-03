<?php

/**
 * Invoice View/Print Page
 */
require_once '../config/config.php';
require_once '../config/auth.php';
require_once '../config/functions.php';

requireRole(['admin', 'staff']);

$db = getDB();
$id = intval($_GET['id'] ?? 0);

$invoice = $db->prepare("
    SELECT i.*, b.booking_number, b.start_date, b.end_date, b.total_days,
           v.vehicle_name, v.registration_number,
           c.full_name as customer_name, c.phone as customer_phone, c.email as customer_email, c.address as customer_address
    FROM invoices i
    JOIN bookings b ON i.booking_id = b.id
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN customers c ON i.customer_id = c.id
    WHERE i.id = ?
");
$invoice->execute([$id]);
$invoice = $invoice->fetch();

if (!$invoice) {
    header('Location: ' . BASE_URL . 'pages/invoices.php');
    exit;
}

// Get company settings
$companyName = getSetting('company_name', 'Rental Vehicle Management System');
$companyAddress = getSetting('company_address', '');
$companyPhone = getSetting('company_phone', '');
$companyEmail = getSetting('company_email', '');
$currency = getSetting('currency', 'INR');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo htmlspecialchars($invoice['invoice_number']); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <style>
        @media print {
            .no-print {
                display: none;
            }

            body {
                background: white;
            }

            .invoice-container {
                box-shadow: none;
            }
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: var(--shadow-lg);
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }

        .invoice-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .invoice-meta {
            text-align: right;
        }

        .invoice-body {
            margin-bottom: 40px;
        }

        .invoice-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }

        .invoice-section h3 {
            margin-bottom: 15px;
            color: var(--text-primary);
            font-size: 1.1rem;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }

        .invoice-table th,
        .invoice-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .invoice-table th {
            background: var(--bg-tertiary);
            font-weight: 600;
        }

        .invoice-totals {
            margin-top: 20px;
            text-align: right;
        }

        .invoice-totals table {
            width: 300px;
            margin-left: auto;
        }

        .invoice-totals td {
            padding: 8px 12px;
        }

        .invoice-totals .total-row {
            font-weight: 700;
            font-size: 1.2rem;
            border-top: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .invoice-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <div class="no-print" style="margin-bottom: 20px;">
            <a href="<?php echo BASE_URL; ?>pages/invoices.php" class="btn btn-secondary">Back to Invoices</a>
            <button onclick="window.print()" class="btn btn-primary" style="margin-left: 10px;">
                <i class="icon icon-print"></i> Print Invoice
            </button>
        </div>

        <div class="invoice-header">
            <div>
                <div class="invoice-title">INVOICE</div>
                <div style="color: var(--text-secondary); margin-top: 5px;"><?php echo htmlspecialchars($invoice['invoice_number']); ?></div>
            </div>
            <div class="invoice-meta">
                <div><strong>Issue Date:</strong> <?php echo formatDate($invoice['issue_date']); ?></div>
                <?php if ($invoice['due_date']): ?>
                    <div><strong>Due Date:</strong> <?php echo formatDate($invoice['due_date']); ?></div>
                <?php endif; ?>
                <div style="margin-top: 10px;">
                    <span class="badge badge-<?php
                                                echo $invoice['status'] === 'paid' ? 'success' : ($invoice['status'] === 'overdue' ? 'danger' : ($invoice['status'] === 'sent' ? 'info' : ($invoice['status'] === 'partially_paid' ? 'warning' : 'secondary')));
                                                ?>">
                        <?php echo ucwords(str_replace('_', ' ', $invoice['status'])); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="invoice-body">
            <div class="invoice-row">
                <div class="invoice-section">
                    <h3>From:</h3>
                    <div>
                        <strong><?php echo htmlspecialchars($companyName); ?></strong><br>
                        <?php if ($companyAddress): ?>
                            <?php echo nl2br(htmlspecialchars($companyAddress)); ?><br>
                        <?php endif; ?>
                        <?php if ($companyPhone): ?>
                            Phone: <?php echo htmlspecialchars($companyPhone); ?><br>
                        <?php endif; ?>
                        <?php if ($companyEmail): ?>
                            Email: <?php echo htmlspecialchars($companyEmail); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="invoice-section">
                    <h3>Bill To:</h3>
                    <div>
                        <strong><?php echo htmlspecialchars($invoice['customer_name']); ?></strong><br>
                        <?php if ($invoice['customer_address']): ?>
                            <?php echo nl2br(htmlspecialchars($invoice['customer_address'])); ?><br>
                        <?php endif; ?>
                        <?php if ($invoice['customer_phone']): ?>
                            Phone: <?php echo htmlspecialchars($invoice['customer_phone']); ?><br>
                        <?php endif; ?>
                        <?php if ($invoice['customer_email']): ?>
                            Email: <?php echo htmlspecialchars($invoice['customer_email']); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="invoice-section">
                <h3>Booking Details:</h3>
                <div style="margin-bottom: 20px;">
                    <strong>Booking Number:</strong> <?php echo htmlspecialchars($invoice['booking_number']); ?><br>
                    <strong>Vehicle:</strong> <?php echo htmlspecialchars($invoice['vehicle_name']); ?> (<?php echo htmlspecialchars($invoice['registration_number']); ?>)<br>
                    <strong>Rental Period:</strong> <?php echo formatDate($invoice['start_date']); ?> to <?php echo formatDate($invoice['end_date']); ?><br>
                    <strong>Total Days:</strong> <?php echo $invoice['total_days']; ?> days<br>
                    <div style="margin-top: 10px; padding: 8px; background: #f0fdf4; border-left: 4px solid #16a34a; font-size: 0.85rem;">
                        <i class="icon icon-success"></i> <strong>Payment Status:</strong>
                        <?php echo $invoice['total_amount'] <= $invoice['paid_amount'] ? 'Fully Matched' : 'Pending Verification'; ?>
                    </div>
                </div>
            </div>

            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Vehicle Rental (<?php echo $invoice['total_days']; ?> days)</td>
                        <td style="text-align: right;"><?php echo formatCurrency($invoice['subtotal'], $currency); ?></td>
                    </tr>
                    <?php if ($invoice['discount'] > 0): ?>
                        <tr>
                            <td>Discount</td>
                            <td style="text-align: right; color: var(--secondary-color);">- <?php echo formatCurrency($invoice['discount'], $currency); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($invoice['tax'] > 0): ?>
                        <tr>
                            <td>Tax</td>
                            <td style="text-align: right;"><?php echo formatCurrency($invoice['tax'], $currency); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="invoice-totals">
                <table>
                    <tr>
                        <td><strong>Subtotal:</strong></td>
                        <td style="text-align: right;"><?php echo formatCurrency($invoice['subtotal'], $currency); ?></td>
                    </tr>
                    <?php if ($invoice['discount'] > 0): ?>
                        <tr>
                            <td>Discount:</td>
                            <td style="text-align: right;">- <?php echo formatCurrency($invoice['discount'], $currency); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($invoice['tax'] > 0): ?>
                        <tr>
                            <td>Tax:</td>
                            <td style="text-align: right;"><?php echo formatCurrency($invoice['tax'], $currency); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td>Total Amount:</td>
                        <td style="text-align: right;"><?php echo formatCurrency($invoice['total_amount'], $currency); ?></td>
                    </tr>
                    <tr>
                        <td>Paid Amount:</td>
                        <td style="text-align: right;"><?php echo formatCurrency($invoice['paid_amount'], $currency); ?></td>
                    </tr>
                    <tr style="border-top: 1px solid var(--border-color);">
                        <td><strong>Balance Due:</strong></td>
                        <td style="text-align: right; font-weight: 600;">
                            <?php echo formatCurrency($invoice['total_amount'] - $invoice['paid_amount'], $currency); ?>
                        </td>
                    </tr>
                </table>
            </div>

            <?php if ($invoice['notes']): ?>
                <div style="margin-top: 30px; padding: 15px; background: var(--bg-secondary); border-radius: var(--radius);">
                    <strong>Notes:</strong>
                    <p style="margin: 10px 0 0 0;"><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="invoice-footer">
            <p>Thank you for your business!</p>
            <p>This is a computer-generated invoice. No signature required.</p>
        </div>
    </div>
</body>

</html>