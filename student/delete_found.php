<?php global $pdo;
/**
 * Student - Delete Found Item
 * Delete found item (only if status is pending)
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireStudent();

$userId = getCurrentUserId();
$itemId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$itemId) {
    header('Location: my_items.php');
    exit;
}

// Get found item details
$stmt = $pdo->prepare("SELECT * FROM found_items WHERE id = ? AND user_id = ?");
$stmt->execute([$itemId, $userId]);
$item = $stmt->fetch();

if (!$item) {
    redirect(BASE_URL . 'student/my_items.php', 'Item not found.', 'error');
}

// Only allow deletion if status is pending
if ($item['status'] !== 'pending') {
    redirect(BASE_URL . 'student/my_items.php', 'Cannot delete item that has been processed.', 'error');
}

// Delete the item
try {
    // Delete photo if exists
    if ($item['photo']) {
        deleteImage($item['photo'], 'found');
    }

    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM found_items WHERE id = ? AND user_id = ?");
    $stmt->execute([$itemId, $userId]);

    redirect(BASE_URL . 'student/my_items.php', 'Found item deleted successfully.', 'success');
} catch (PDOException $e) {
    redirect(BASE_URL . 'student/my_items.php', 'Failed to delete item. Please try again.', 'error');
}
?>