<?php global $pdo;
/**
 * API - Admin Approve/Reject/Complete Claim
 * Handle claim request actions
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

$claimId = intval($input['claim_id'] ?? 0);
$action = sanitize($input['action'] ?? ''); // 'approve', 'reject', 'complete'
$scheduleDate = sanitize($input['schedule_date'] ?? '');
$scheduleTime = sanitize($input['schedule_time'] ?? '');
$notes = sanitize($input['notes'] ?? '');
$reason = sanitize($input['reason'] ?? '');

if (!$claimId || !in_array($action, ['approve', 'reject', 'complete'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid parameters'
    ]);
    exit;
}

try {
    // Get claim details
    $stmt = $pdo->prepare("
        SELECT cr.*, li.item_name, li.user_id as owner_id
        FROM claim_requests cr
        JOIN lost_items li ON cr.lost_item_id = li.id
        WHERE cr.id = ?
    ");
    $stmt->execute([$claimId]);
    $claim = $stmt->fetch();

    if (!$claim) {
        echo json_encode([
            'success' => false,
            'message' => 'Claim not found'
        ]);
        exit;
    }

    $adminId = getCurrentUserId();

    if ($action === 'approve') {
        if (empty($scheduleDate) || empty($scheduleTime)) {
            echo json_encode([
                'success' => false,
                'message' => 'Schedule date and time are required'
            ]);
            exit;
        }

        $scheduleDateTime = $scheduleDate . ' ' . $scheduleTime;

        // Update claim to scheduled
        $stmt = $pdo->prepare("
            UPDATE claim_requests 
            SET status = ?, schedule_date = ?, notes = ?, admin_id = ?
            WHERE id = ?
        ");
        $stmt->execute([CLAIM_STATUS_SCHEDULED, $scheduleDateTime, $notes, $adminId, $claimId]);

        // Notify student
        $message = "Your claim request for '{$claim['item_name']}' has been approved! Scheduled on " .
            formatDateTime($scheduleDateTime) . ". Please bring valid ID and proof of ownership.";
        createNotification($pdo, $claim['requester_id'], $message);

        $responseMessage = 'Claim approved and scheduled successfully';

    } elseif ($action === 'reject') {
        // Update claim to rejected
        $stmt = $pdo->prepare("
            UPDATE claim_requests 
            SET status = ?, notes = ?, admin_id = ?
            WHERE id = ?
        ");
        $stmt->execute([CLAIM_STATUS_REJECTED, $reason, $adminId, $claimId]);

        // Update lost item back to listed
        $stmtLost = $pdo->prepare("UPDATE lost_items SET status = ? WHERE id = ?");
        $stmtLost->execute([LOST_STATUS_LISTED, $claim['lost_item_id']]);

        // Notify student
        $message = "Your claim request for '{$claim['item_name']}' was rejected.";
        if (!empty($reason)) {
            $message .= " Reason: $reason";
        }
        createNotification($pdo, $claim['requester_id'], $message);

        $responseMessage = 'Claim rejected';

    } elseif ($action === 'complete') {
        // Update claim to completed
        $stmt = $pdo->prepare("UPDATE claim_requests SET status = ? WHERE id = ?");
        $stmt->execute([CLAIM_STATUS_COMPLETED, $claimId]);

        // Update lost item to returned
        $stmtLost = $pdo->prepare("UPDATE lost_items SET status = ? WHERE id = ?");
        $stmtLost->execute([LOST_STATUS_RETURNED, $claim['lost_item_id']]);

        // Update found item to claimed (if exists)
        if ($claim['found_item_id']) {
            $stmtFound = $pdo->prepare("UPDATE found_items SET status = ? WHERE id = ?");
            $stmtFound->execute([FOUND_STATUS_CLAIMED, $claim['found_item_id']]);
        }

        // Notify student
        createNotification(
            $pdo,
            $claim['requester_id'],
            "Your item '{$claim['item_name']}' has been successfully claimed and returned!"
        );

        $responseMessage = 'Claim completed successfully';
    }

    echo json_encode([
        'success' => true,
        'message' => $responseMessage
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process claim action'
    ]);
}
?>