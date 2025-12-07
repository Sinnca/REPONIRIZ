<?php global $pdo;
/**
 * API - Admin Verify Item
 * Approve or reject lost/found items
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Check admin authentication
if (!isLoggedIn() || !isAdmin()) {
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
$action = sanitize($input['action'] ?? ''); // 'approve' or 'reject'
$reason = sanitize($input['reason'] ?? '');

if (!$itemId || !in_array($type, ['lost', 'found']) || !in_array($action, ['approve', 'reject'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid parameters'
    ]);
    exit;
}

try {
    if ($type === 'lost') {
        // Get lost item
        $stmt = $pdo->prepare("
            SELECT li.*, u.name as owner_name 
            FROM lost_items li 
            JOIN users u ON li.user_id = u.id 
            WHERE li.id = ?
        ");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();

        if (!$item) {
            echo json_encode([
                'success' => false,
                'message' => 'Item not found'
            ]);
            exit;
        }

        if ($action === 'approve') {
            // Update to listed
            $stmt = $pdo->prepare("UPDATE lost_items SET status = ? WHERE id = ?");
            $stmt->execute([LOST_STATUS_LISTED, $itemId]);

            // Notify student
            createNotification(
                $pdo,
                $item['user_id'],
                "Your lost item '{$item['item_name']}' has been verified and listed.",
                'success'
            );

            $message = 'Lost item approved and listed successfully';

        } else {
            // Update to rejected
            $stmt = $pdo->prepare("UPDATE lost_items SET status = ? WHERE id = ?");
            $stmt->execute([LOST_STATUS_REJECTED, $itemId]);

            // Notify student
            $notifMessage = "Your lost item '{$item['item_name']}' was rejected.";
            if (!empty($reason)) {
                $notifMessage .= " Reason: $reason";
            }
            createNotification($pdo, $item['user_id'], $notifMessage, 'warning');

            $message = 'Lost item rejected';
        }

    } else {
        // Get found item
        $stmt = $pdo->prepare("
            SELECT fi.*, u.name as finder_name 
            FROM found_items fi 
            JOIN users u ON fi.user_id = u.id 
            WHERE fi.id = ?
        ");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();

        if (!$item) {
            echo json_encode([
                'success' => false,
                'message' => 'Item not found'
            ]);
            exit;
        }

        if ($action === 'approve') {
            // Update to verified
            $stmt = $pdo->prepare("UPDATE found_items SET status = ? WHERE id = ?");
            $stmt->execute([FOUND_STATUS_VERIFIED, $itemId]);

            // If linked to lost item, update lost item status
            if ($item['lost_item_id']) {
                $stmtLost = $pdo->prepare("UPDATE lost_items SET status = ? WHERE id = ?");
                $stmtLost->execute([LOST_STATUS_READY_FOR_CLAIM, $item['lost_item_id']]);

                // Notify lost item owner
                $stmtOwner = $pdo->prepare("SELECT user_id, item_name FROM lost_items WHERE id = ?");
                $stmtOwner->execute([$item['lost_item_id']]);
                $lostItem = $stmtOwner->fetch();

                if ($lostItem) {
                    createNotification(
                        $pdo,
                        $lostItem['user_id'],
                        "Good news! A matching found item for '{$lostItem['item_name']}' has been verified. You can now request a claim.",
                        'success'
                    );
                }
            }

            // Notify finder
            createNotification(
                $pdo,
                $item['user_id'],
                "Your found item '{$item['item_name']}' has been verified.",
                'success'
            );

            $message = 'Found item approved and verified successfully';

        } else {
            // Update to rejected
            $stmt = $pdo->prepare("UPDATE found_items SET status = ? WHERE id = ?");
            $stmt->execute([FOUND_STATUS_REJECTED, $itemId]);

            // Notify finder
            $notifMessage = "Your found item '{$item['item_name']}' was rejected.";
            if (!empty($reason)) {
                $notifMessage .= " Reason: $reason";
            }
            createNotification($pdo, $item['user_id'], $notifMessage, 'warning');

            $message = 'Found item rejected';
        }
    }

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process item verification'
    ]);
}
?>