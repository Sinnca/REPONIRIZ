<?php
global $pdo;
/**
 * API - Create Found Item
 * Handle found item submission via AJAX
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Check authentication
if (!isLoggedIn() || !isStudent()) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

$userId = getCurrentUserId();
$itemName = sanitize($_POST['item_name'] ?? '');
$description = sanitize($_POST['description'] ?? '');
$dateFound = sanitize($_POST['date_found'] ?? '');
$lostItemId = isset($_POST['lost_item_id']) ? intval($_POST['lost_item_id']) : null;

// Validation
if (empty($itemName) || empty($description) || empty($dateFound)) {
    echo json_encode([
        'success' => false,
        'message' => 'All fields are required'
    ]);
    exit;
}

if (!isValidDate($dateFound)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid date format'
    ]);
    exit;
}

// Check daily submission limit for found items
if (!canUserSubmitItem($pdo, $userId, 'found')) {
    echo json_encode([
        'success' => false,
        'message' => 'You have reached your daily limit of 2 found items.'
    ]);
    exit;
}

// Handle photo upload
$photoFilename = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $photoFilename = uploadImage($_FILES['photo'], 'found');
    if ($photoFilename === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Photo upload failed. Please check file size and format'
        ]);
        exit;
    }
}

// Insert into database
try {
    $stmt = $pdo->prepare("
        INSERT INTO found_items (user_id, lost_item_id, item_name, description, photo, date_found, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $userId,
        $lostItemId,
        $itemName,
        $description,
        $photoFilename,
        $dateFound,
        FOUND_STATUS_PENDING
    ]);

    $itemId = $pdo->lastInsertId();

    // Notify first admin
    $adminStmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $admin = $adminStmt->fetch();
    if ($admin) {
        createNotification($pdo, $admin['id'], "New found item submitted: $itemName");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Found item reported successfully! Waiting for admin verification.',
        'item_id' => $itemId
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to submit found item. Please try again.'
    ]);
}
?>
