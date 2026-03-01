<?php
/**
 * General Helper Functions
 * Common utility functions used throughout the application
 */

require_once __DIR__ . '/config.php';

/**
 * Sanitize input data
 * @param mixed $data
 * @return mixed
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Format date for display
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = DISPLAY_DATE_FORMAT) {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    return date($format, strtotime($date));
}

/**
 * Format currency
 * @param float $amount
 * @param string|null $currency Optional currency code (e.g. USD, INR). If null, use system setting.
 * @return string
 */
function formatCurrency($amount, $currency = null) {
    // Get currency from settings if not provided
    if ($currency === null) {
        if (!function_exists('getSetting')) {
            // Fallback default
            $currency = 'INR';
        } else {
            $currency = getSetting('currency', 'INR');
        }
    }

    // Map currency code to symbol
    $symbol = $currency;
    switch (strtoupper($currency)) {
        case 'INR':
            $symbol = '₹';
            break;
        case 'USD':
            $symbol = 'USD';
            break;
        case 'EUR':
            $symbol = '€';
            break;
        case 'GBP':
            $symbol = '£';
            break;
        // Add more as needed
    }

    return $symbol . ' ' . number_format((float)$amount, 2);
}

/**
 * Generate unique code
 * @param string $prefix
 * @param string $table
 * @param string $column
 * @return string
 */
function generateUniqueCode($prefix, $table, $column) {
    $db = getDB();
    $code = '';
    $exists = true;
    
    while ($exists) {
        $code = $prefix . strtoupper(substr(uniqid(), -8));
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?");
        $stmt->execute([$code]);
        $result = $stmt->fetch();
        $exists = $result['count'] > 0;
    }
    
    return $code;
}

/**
 * Calculate rental cost
 * @param float $dailyRate
 * @param string $startDate
 * @param string $endDate
 * @param float $discount
 * @param float $taxRate
 * @return array
 */
function calculateRentalCost($dailyRate, $startDate, $endDate, $discount = 0, $taxRate = 10) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $interval = $start->diff($end);
    $totalDays = $interval->days + 1; // Include both start and end dates
    
    $subtotal = $dailyRate * $totalDays;
    $discountAmount = ($subtotal * $discount) / 100;
    $afterDiscount = $subtotal - $discountAmount;
    $taxAmount = ($afterDiscount * $taxRate) / 100;
    $totalAmount = $afterDiscount + $taxAmount;
    
    return [
        'total_days' => $totalDays,
        'daily_rate' => $dailyRate,
        'subtotal' => $subtotal,
        'discount' => $discountAmount,
        'tax' => $taxAmount,
        'total_amount' => $totalAmount
    ];
}

/**
 * Check vehicle availability
 * @param int $vehicleId
 * @param string $startDate
 * @param string $endDate
 * @param int $excludeBookingId
 * @return bool
 */
function checkVehicleAvailability($vehicleId, $startDate, $endDate, $excludeBookingId = null) {
    $db = getDB();
    
    $sql = "SELECT COUNT(*) as count FROM bookings 
            WHERE vehicle_id = ? 
            AND status IN ('approved', 'active') 
            AND (
                (start_date <= ? AND end_date >= ?) OR
                (start_date <= ? AND end_date >= ?) OR
                (start_date >= ? AND end_date <= ?)
            )";
    
    $params = [$vehicleId, $startDate, $startDate, $endDate, $endDate, $startDate, $endDate];
    
    if ($excludeBookingId) {
        $sql .= " AND id != ?";
        $params[] = $excludeBookingId;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    
    return $result['count'] == 0;
}

/**
 * Upload file
 * @param array $file $_FILES array element
 * @param string $directory
 * @param array $allowedTypes
 * @return array ['success' => bool, 'message' => string, 'filename' => string|null]
 */
function uploadFile($file, $directory = 'general', $allowedTypes = ALLOWED_IMAGE_TYPES) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file upload.', 'filename' => null];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error.', 'filename' => null];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds maximum limit.', 'filename' => null];
    }
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type.', 'filename' => null];
    }
    
    $uploadDir = UPLOAD_PATH . $directory . DIRECTORY_SEPARATOR;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'message' => 'Failed to move uploaded file.', 'filename' => null];
    }
    
    return ['success' => true, 'message' => 'File uploaded successfully.', 'filename' => $filename];
}

/**
 * Delete file
 * @param string $filename
 * @param string $directory
 * @return bool
 */
function deleteFile($filename, $directory = 'general') {
    if (empty($filename)) {
        return true;
    }
    
    $filepath = UPLOAD_PATH . $directory . DIRECTORY_SEPARATOR . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return true;
}

/**
 * Get file URL
 * @param string $filename
 * @param string $directory
 * @return string
 */
function getFileUrl($filename, $directory = 'general') {
    if (empty($filename)) {
        return ASSETS_URL . 'images/no-image.png';
    }
    return UPLOAD_URL . $directory . '/' . $filename;
}

/**
 * Set flash message
 * @param string $type success|error|warning|info
 * @param string $message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Get and clear flash message
 * @param string $type
 * @return string|null
 */
function getFlashMessage($type) {
    if (isset($_SESSION['flash_' . $type])) {
        $message = $_SESSION['flash_' . $type];
        unset($_SESSION['flash_' . $type]);
        return $message;
    }
    return null;
}

/**
 * Redirect with message
 * @param string $url
 * @param string $type
 * @param string $message
 */
function redirect($url, $type = null, $message = null) {
    if ($type && $message) {
        setFlashMessage($type, $message);
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Get pagination data
 * @param int $currentPage
 * @param int $totalItems
 * @param int $itemsPerPage
 * @return array
 */
function getPagination($currentPage, $totalItems, $itemsPerPage = ITEMS_PER_PAGE) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    return [
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'total_items' => $totalItems,
        'items_per_page' => $itemsPerPage,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

/**
 * Get setting value
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function getSetting($key, $default = null) {
    $db = getDB();
    try {
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch(PDOException $e) {
        error_log("Get Setting Error: " . $e->getMessage());
        return $default;
    }
}

/**
 * Set setting value
 * @param string $key
 * @param mixed $value
 * @return bool
 */
function setSetting($key, $value) {
    $db = getDB();
    try {
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                              ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = CURRENT_TIMESTAMP");
        return $stmt->execute([$key, $value, $value]);
    } catch(PDOException $e) {
        error_log("Set Setting Error: " . $e->getMessage());
        return false;
    }
}

