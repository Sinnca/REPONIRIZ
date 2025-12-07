<?php
global $pdo;
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Only admin
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate claim_id
$claimId = isset($_POST['claim_id']) ? intval($_POST['claim_id']) : 0;
if (!$claimId) {
    echo json_encode(['success' => false, 'message' => 'Invalid claim ID']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Fetch claim details (scheduled only)
    $stmt = $pdo->prepare("
        SELECT cr.id, cr.found_item_id, cr.lost_item_id, cr.requester_id,
               COALESCE(li.item_name, cr.item_name) AS item_name
        FROM claim_requests cr
        LEFT JOIN lost_items li ON cr.lost_item_id = li.id
        WHERE cr.id = ? AND cr.status = 'scheduled'
    ");
    $stmt->execute([$claimId]);
    $claim = $stmt->fetch();

    if (!$claim) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Claim not found or not in scheduled status']);
        exit;
    }

    // 1. Mark this claim as completed
    $stmtComplete = $pdo->prepare("UPDATE claim_requests SET status = 'completed' WHERE id = ?");
    $stmtComplete->execute([$claimId]);

    // 2. Update lost item status if exists
    if ($claim['lost_item_id']) {
        $stmtLost = $pdo->prepare("UPDATE lost_items SET status = ? WHERE id = ?");
        $stmtLost->execute([LOST_STATUS_RETURNED, $claim['lost_item_id']]);
    }

    // 3. Update found item status if exists
    if ($claim['found_item_id']) {
        $stmtFound = $pdo->prepare("UPDATE found_items SET status = ? WHERE id = ?");
        $stmtFound->execute([FOUND_STATUS_CLAIMED, $claim['found_item_id']]);
    }

    // 4. Reject all other claims for the same lost or found items
    $stmtReject = $pdo->prepare("
        UPDATE claim_requests
        SET status = 'rejected'
        WHERE id != ?
          AND (lost_item_id = ? OR found_item_id = ?)
          AND status IN ('pending','scheduled')
    ");
    $stmtReject->execute([$claimId, $claim['lost_item_id'], $claim['found_item_id']]);

    // 5. Notify the original claimer
    createNotification($pdo, $claim['requester_id'], "Your item '{$claim['item_name']}' has been returned!", 'success');

    // Commit transaction
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Claim completed successfully. Other claims rejected.']);
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
