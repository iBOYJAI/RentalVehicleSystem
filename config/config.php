<?php
/**
 * Main Configuration File
 * Contains system-wide constants and settings
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL Configuration
define('BASE_URL', 'http://localhost/RentalVehicleSystem/');
define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// Directory Paths
define('UPLOAD_PATH', BASE_PATH . 'uploads' . DIRECTORY_SEPARATOR);
define('UPLOAD_URL', BASE_URL . 'uploads/');
define('ASSETS_PATH', BASE_PATH . 'assets' . DIRECTORY_SEPARATOR);
define('ASSETS_URL', BASE_URL . 'assets/');

// File Upload Settings
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']);
define('ALLOWED_DOC_TYPES', ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png']);

// Pagination
define('ITEMS_PER_PAGE', 10);

// Date Format
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'd M Y');
define('DISPLAY_DATETIME_FORMAT', 'd M Y, h:i A');

// User Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_STAFF', 'staff');
define('ROLE_CUSTOMER', 'customer');

// Booking Status
define('BOOKING_PENDING', 'pending');
define('BOOKING_APPROVED', 'approved');
define('BOOKING_ACTIVE', 'active');
define('BOOKING_COMPLETED', 'completed');
define('BOOKING_CANCELLED', 'cancelled');
define('BOOKING_REJECTED', 'rejected');

// Vehicle Status
define('VEHICLE_AVAILABLE', 'available');
define('VEHICLE_RENTED', 'rented');
define('VEHICLE_MAINTENANCE', 'maintenance');
define('VEHICLE_INACTIVE', 'inactive');

// Payment Status
define('PAYMENT_PENDING', 'pending');
define('PAYMENT_COMPLETED', 'completed');
define('PAYMENT_FAILED', 'failed');
define('PAYMENT_REFUNDED', 'refunded');

// Include database connection
require_once BASE_PATH . 'config' . DIRECTORY_SEPARATOR . 'database.php';

// Error Reporting (Set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('UTC');

