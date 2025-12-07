<?php global $pdo;
/**
 * API - Delete Item
 * Delete lost or found item via AJAX
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

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$itemId = intval($input['item_id'] ?? 0);
$type = sanitize($input['type'] ?? ''); // 'lost' or 'found'
$userId = getCurrentUserId();

if (!$itemId || !in_array($type, ['lost', 'found'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid parameters'
    ]);
    exit;
}

try {
    if ($type === 'lost') {
        // Get item
        $stmt = $pdo->prepare("SELECT * FROM lost_items WHERE id = ? AND user_id = ?");
        $stmt->execute([$itemId, $userId]);
        $item = $stmt->fetch();

        if (!$item) {
            echo json_encode([
                'success' => false,
                'message' => 'Item not found'
            ]);
            exit;
        }

        // Only allow deletion if pending
        if ($item['status'] !== 'pending') {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot delete item that has been processed'
            ]);
            exit;
        }

        // Delete photo
        if ($item['photo']) {
            deleteImage($item['photo'], 'lost');
        }

        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM lost_items WHERE id = ? AND user_id = ?");
        $stmt->execute([$itemId, $userId]);

    } else {
        // Get item
        $stmt = $pdo->prepare("SELECT * FROM found_items WHERE id = ? AND user_id = ?");
        $stmt->execute([$itemId, $userId]);
        $item = $stmt->fetch();

        if (!$item) {
            echo json_encode([
                'success' => false,
                'message' => 'Item not found'
            ]);
            exit;
        }

        // Only allow deletion if pending
        if ($item['status'] !== 'pending') {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot delete item that has been processed'
            ]);
            exit;
        }

        // Delete photo
        if ($item['photo']) {
            deleteImage($item['photo'], 'found');
        }

        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM found_items WHERE id = ? AND user_id = ?");
        $stmt->execute([$itemId, $userId]);
    }

    echo json_encode([
        'success' => true,
        'message' => ucfirst($type) . ' item deleted successfully'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete item'
    ]);
}
?>