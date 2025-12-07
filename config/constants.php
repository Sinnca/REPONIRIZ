<?php
/**
 * Application Constants
 * Define paths, statuses, and app-wide settings
 */

// Base URL (adjust based on your setup)

define('BASE_URL', 'http://localhost/lsf_system/');
// Upload paths
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('LOST_ITEMS_PATH', UPLOAD_PATH . 'lost_items/');
define('FOUND_ITEMS_PATH', UPLOAD_PATH . 'found_items/');

// Upload URL paths
define('UPLOAD_URL', BASE_URL . 'assets/uploads/');
define('LOST_ITEMS_URL', UPLOAD_URL . 'lost_items/');
define('FOUND_ITEMS_URL', UPLOAD_URL . 'found_items/');

// File upload settings
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Lost item statuses
define('LOST_STATUS_PENDING', 'pending');
define('LOST_STATUS_LISTED', 'listed');
define('LOST_STATUS_READY_FOR_CLAIM', 'ready_for_claim');
define('LOST_STATUS_RETURNED', 'returned');
define('LOST_STATUS_REJECTED', 'rejected');

// Found item statuses
define('FOUND_STATUS_PENDING', 'pending');
define('FOUND_STATUS_VERIFIED', 'verified');
define('FOUND_STATUS_LISTED', 'listed');
define('FOUND_STATUS_CLAIMED', 'claimed');
define('FOUND_STATUS_REJECTED', 'rejected');
define('FOUND_STATUS_RETURNED', 'returned'); // New


// Claim request statuses
define('CLAIM_STATUS_PENDING', 'pending');
define('CLAIM_STATUS_APPROVED', 'approved');
define('CLAIM_STATUS_SCHEDULED', 'scheduled');
define('CLAIM_STATUS_COMPLETED', 'completed');
define('CLAIM_STATUS_REJECTED', 'rejected');

// User roles
define('ROLE_STUDENT', 'student');
define('ROLE_ADMIN', 'admin');

// Session settings
define('SESSION_LIFETIME', 3600); // 1 hour in seconds
?>