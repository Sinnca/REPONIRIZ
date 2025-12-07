<?php
/**
 * Session Management
 * Secure session handling and user authentication checks
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === ROLE_ADMIN;
}

/**
 * Check if user is student
 */
function isStudent() {
    return isLoggedIn() && $_SESSION['role'] === ROLE_STUDENT;
}

/**
 * Redirect to login if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

/**
 * Redirect to login if not admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . 'student/index.php');
        exit;
    }
}

/**
 * Redirect to login if not student
 */
function requireStudent() {
    requireLogin();
    if (!isStudent()) {
        header('Location: ' . BASE_URL . 'admin/index.php');
        exit;
    }
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 */
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Get current user name
 */
function getCurrentUserName() {
    return $_SESSION['user_name'] ?? null;
}

/**
 * Destroy session and logout
 */
function logout() {
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}
?>