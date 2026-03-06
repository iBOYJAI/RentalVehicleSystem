<?php

/**
 * Customer "My Bookings" Page
 * Allows customers to view and create their own bookings
 */
require_once '../config/config.php';
require_once '../config/auth.php';
require_once '../config/functions.php';

requireRole(ROLE_CUSTOMER);

$pageTitle = 'My Bookings';
$db = getDB();
$currentUser = getCurrentUser();

// Resolve or auto-create linked customer record for this user
$customerId = null;
try {
    if ($currentUser) {
        // Try to find by email first (if available), otherwise by full name
        if (!empty($currentUser['email'])) {
            $stmt = $db->prepare("SELECT id FROM customers WHERE email = ? LIMIT 1");
            $stmt->execute([$currentUser['email']]);
            $cust = $stmt->fetch();
        } else {
            $stmt = $db->prepare("SELECT id FROM customers WHERE full_name = ? LIMIT 1");
            $stmt->execute([$currentUser['full_name']]);
            $cust = $stmt->fetch();
        }

        if ($cust) {
            $customerId = (int)$cust['id'];
        } else {
            // Auto-create a customer profile linked to this user
            $customerCode = generateUniqueCode('CUST', 'customers', 'customer_code');
            $fullName = $currentUser['full_name'] ?? 'Customer';
            $email = $currentUser['email'] ?? null;
            $phone = $currentUser['phone'] ?? '';
            $address = $currentUser['address'] ?? '';

            $insert = $db->prepare("
                INSERT INTO customers (customer_code, full_name, email, phone, address, license_number, license_expiry, status)
                VALUES (?, ?, ?, ?, ?, NULL, NULL, 'active')
            ");
            $insert->execute([$customerCode, $fullName, $email, $phone, $address]);
            $customerId = (int)$db->lastInsertId();
        }
    }
} catch (PDOException $e) {
    error_log('MyBookings: resolve/create customer error - ' . $e->getMessage());
}

if (!$customerId) {
    // Fallback: still allow page to load but without booking actions
    $bookings = [];
    $vehicles = [];
} else {
    // Handle new booking submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
        $vehicleId = intval($_POST['vehicle_id']);
        $startDate = sanitize($_POST['start_date']);
        $endDate = sanitize($_POST['end_date']);
        $pickupLocation = sanitize($_POST['pickup_location'] ?? '');
        $dropoffLocation = sanitize($_POST['dropoff_location'] ?? '');
        $paymentMethod = sanitize($_POST['payment_method'] ?? 'cash');
        $notes = sanitize($_POST['notes'] ?? '');

        if ($paymentMethod === 'cash') {
            $notes = "[Payment: Cash/Hand] " . $notes;
        } else {
            $notes = "[Payment: Online] " . $notes;
        }

        // Basic validation
        if (!$vehicleId || !$startDate || !$endDate) {
            setFlashMessage('error', 'Please fill all required fields.');
            header('Location: ' . BASE_URL . 'pages/my-bookings.php');
            exit;
        }

        // Check availability
        if (!checkVehicleAvailability($vehicleId, $startDate, $endDate)) {
            setFlashMessage('error', 'Selected vehicle is not available for the chosen dates.');
            header('Location: ' . BASE_URL . 'pages/my-bookings.php');
            exit;
        }

        // Get vehicle daily rate
        try {
            $vehicleStmt = $db->prepare("SELECT daily_rate FROM vehicles WHERE id = ? AND status = ?");
            $vehicleStmt->execute([$vehicleId, VEHICLE_AVAILABLE]);
            $vehicle = $vehicleStmt->fetch();
            if (!$vehicle) {
                setFlashMessage('error', 'Selected vehicle is not available.');
                header('Location: ' . BASE_URL . 'pages/my-bookings.php');
                exit;
            }
            $dailyRate = (float)$vehicle['daily_rate'];

            // Calculate cost
            $cost = calculateRentalCost($dailyRate, $startDate, $endDate);

            // Generate booking number
            $bookingNumber = generateUniqueCode(getSetting('booking_prefix', 'BK-'), 'bookings', 'booking_number');

            // Insert booking (status: pending)
            $stmt = $db->prepare("
                INSERT INTO bookings 
                    (booking_number, customer_id, vehicle_id, start_date, end_date, pickup_location, dropoff_location, 
                     daily_rate, total_days, subtotal, tax, total_amount, notes, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $bookingNumber,
                $customerId,
                $vehicleId,
                $startDate,
                $endDate,
                $pickupLocation,
                $dropoffLocation,
                $dailyRate,
                $cost['total_days'],
                $cost['subtotal'],
                $cost['tax'],
                $cost['total_amount'],
                $notes,
                BOOKING_PENDING
            ]);

            setFlashMessage('success', 'Booking request submitted successfully! It will be reviewed by the team.');
            header('Location: ' . BASE_URL . 'pages/my-bookings.php');
            exit;
        } catch (PDOException $e) {
            error_log('MyBookings: add booking error - ' . $e->getMessage());
            setFlashMessage('error', 'An error occurred while creating your booking. Please try again.');
            header('Location: ' . BASE_URL . 'pages/my-bookings.php');
            exit;
        }
    }

    // Fetch this customer's bookings
    try {
        $bookingsStmt = $db->prepare("
            SELECT b.*, v.vehicle_name, v.registration_number
            FROM bookings b
            JOIN vehicles v ON b.vehicle_id = v.id
            WHERE b.customer_id = ?
            ORDER BY b.created_at DESC
        ");
        $bookingsStmt->execute([$customerId]);
        $bookings = $bookingsStmt->fetchAll();

        // Vehicles available to book
        $vehicles = $db->query("
            SELECT id, vehicle_name, registration_number, daily_rate 
            FROM vehicles 
            WHERE status = 'available'
            ORDER BY vehicle_name
        ")->fetchAll();
    } catch (PDOException $e) {
        error_log('MyBookings: fetch data error - ' . $e->getMessage());
        $bookings = [];
        $vehicles = [];
    }
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
    <h1 class="page-title">My Bookings</h1>
    <p class="page-subtitle">Create and view your bookings</p>
</div>

<?php if ($customerId): ?>
    <!-- New Booking Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">New Booking Request</h3>
        </div>
        <div class="card-body">
            <?php if (empty($vehicles)): ?>
                <div class="empty-state">
                    <i class="icon icon-vehicle"></i>
                    <h3>No vehicles available</h3>
                    <p>Please check back later.</p>
                </div>
            <?php else: ?>
                <form method="POST" data-rental-calc>
                    <input type="hidden" name="action" value="add">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg);">
                        <div class="form-group">
                            <label class="form-label required" for="vehicle_id">Vehicle</label>
                            <select class="form-control" id="vehicle_id" name="vehicle_id" required>
                                <option value="">Select Vehicle</option>
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <option value="<?php echo $vehicle['id']; ?>" data-rate="<?php echo $vehicle['daily_rate']; ?>">
                                        <?php echo htmlspecialchars($vehicle['vehicle_name'] . ' - ' . $vehicle['registration_number'] . ' (' . formatCurrency($vehicle['daily_rate']) . '/day)'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label required" for="start_date">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label required" for="end_date">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="pickup_location">Pickup Location</label>
                            <input type="text" class="form-control" id="pickup_location" name="pickup_location">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="dropoff_location">Dropoff Location</label>
                            <input type="text" class="form-control" id="dropoff_location" name="dropoff_location">
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label required" for="payment_method">Payment Method</label>
                            <select class="form-control" id="payment_method" name="payment_method" required>
                                <option value="cash">Hand / Offline Payment (Pay at Store)</option>
                                <option value="online">Online Payment</option>
                            </select>
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label" for="notes">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any special instructions or notes"></textarea>
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <div style="background: var(--bg-tertiary); padding: var(--spacing-lg); border-radius: var(--radius);">
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--spacing-md);">
                                    <div>
                                        <label style="color: var(--text-secondary); font-size: 0.85rem;">Daily Rate</label>
                                        <div id="display_daily_rate" style="font-size: 1.25rem; font-weight: 600;">-</div>
                                    </div>
                                    <div>
                                        <label style="color: var(--text-secondary); font-size: 0.85rem;">Total Days</label>
                                        <div data-display="total_days" style="font-size: 1.25rem; font-weight: 600;">-</div>
                                    </div>
                                    <div>
                                        <label style="color: var(--text-secondary); font-size: 0.85rem;">Estimated Total</label>
                                        <div data-display="total_amount" style="font-size: 1.25rem; font-weight: 600; color: var(--primary-color);">-</div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="daily_rate" id="daily_rate">
                            <input type="hidden" name="total_days" id="total_days">
                            <input type="hidden" name="total_amount" id="total_amount">
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Submit Booking Request</button>
                    </div>
                </form>

                <script>
                    document.getElementById('vehicle_id').addEventListener('change', function() {
                        const option = this.options[this.selectedIndex];
                        const rate = option.getAttribute('data-rate') || 0;
                        document.getElementById('daily_rate').value = rate;
                        document.getElementById('display_daily_rate').textContent = formatCurrency(rate);
                        calculateRentalCost(document.querySelector('form[data-rental-calc]'));
                    });

                    document.getElementById('start_date').addEventListener('change', function() {
                        calculateRentalCost(document.querySelector('form[data-rental-calc]'));
                    });

                    document.getElementById('end_date').addEventListener('change', function() {
                        calculateRentalCost(document.querySelector('form[data-rental-calc]'));
                    });

                    // Handle pre-selected vehicle
                    window.addEventListener('load', function() {
                        const urlParams = new URLSearchParams(window.location.search);
                        const vehicleId = urlParams.get('vehicle_id');
                        if (vehicleId) {
                            const select = document.getElementById('vehicle_id');
                            select.value = vehicleId;
                            // Trigger change event to update rates
                            const event = new Event('change');
                            select.dispatchEvent(event);
                        }
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>

    <!-- My Bookings List -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">My Bookings</h3>
        </div>
        <div class="card-body">
            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <i class="icon icon-booking"></i>
                    <h3>No bookings found</h3>
                    <p>You have not created any bookings yet.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Booking #</th>
                                <th>Vehicle</th>
                                <th>Dates</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['booking_number']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['vehicle_name']); ?></td>
                                    <td><?php echo formatDate($booking['start_date']); ?> - <?php echo formatDate($booking['end_date']); ?></td>
                                    <td><?php echo formatCurrency($booking['total_amount']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php
                                                                    echo $booking['status'] === BOOKING_APPROVED ? 'success' : ($booking['status'] === BOOKING_PENDING ? 'warning' : ($booking['status'] === BOOKING_ACTIVE ? 'info' : ($booking['status'] === BOOKING_COMPLETED ? 'primary' : 'secondary')));
                                                                    ?>">
                                            <?php echo ucfirst($booking['status']); ?>
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
<?php endif; ?>

<?php include '../includes/footer.php'; ?>