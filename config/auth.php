<?php
/**
 * Authentication Helper Functions
 * Handles user authentication, session management, and access control
 */

require_once __DIR__ . '/config.php';

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 * @param string|array $roles Role(s) to check
 * @return bool
 */
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['user_role'] ?? '';
    
    if (is_array($roles)) {
        return in_array($userRole, $roles);
    }
    
    return $userRole === $roles;
}

/**
 * Require login - redirect to login if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

/**
 * Require specific role - redirect to dashboard if unauthorized
 * @param string|array $roles Required role(s)
 */
function requireRole($roles) {
    requireLogin();
    
    if (!hasRole($roles)) {
        $_SESSION['error'] = 'You do not have permission to access this page.';
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit;
    }
}

/**
 * Login user
 * @param string $username
 * @param string $password
 * @return array ['success' => bool, 'message' => string, 'user' => array|null]
 */
function loginUser($username, $password) {
    $db = getDB();
    
    try {
        $stmt = $db->prepare("SELECT id, username, email, password, full_name, role, status FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid username or password.', 'user' => null];
        }
        
        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Your account is inactive. Please contact administrator.', 'user' => null];
        }
        
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid username or password.', 'user' => null];
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        
        return ['success' => true, 'message' => 'Login successful!', 'user' => $user];
        
    } catch(PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred. Please try again.', 'user' => null];
    }
}

/**
 * Logout user
 */
function logoutUser() {
    $_SESSION = array();
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

/**
 * Get current user data
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    try {
        $stmt = $db->prepare("SELECT id, username, email, full_name, role, phone, address, status FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Get User Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Hash password
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

