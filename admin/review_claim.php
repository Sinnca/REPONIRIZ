<?php
global $pdo;
/**
 * Admin - Review Claim Request
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

$claimId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';

if (!$claimId) {
    header('Location: claim_requests.php');
    exit;
}

// Fetch claim request details
$stmt = $pdo->prepare("
    SELECT cr.*, 
           cr.photo AS claim_photo,             
           li.item_name AS lost_item_name, 
           li.description AS lost_item_description, 
           li.photo AS lost_photo, 
           li.date_lost,
           fi.item_name AS found_item_name, 
           fi.description AS found_description, 
           fi.photo AS found_photo, 
           fi.date_found,
           u.name AS requester_name, 
           u.email AS requester_email
    FROM claim_requests cr
    LEFT JOIN lost_items li ON cr.lost_item_id = li.id
    LEFT JOIN found_items fi ON cr.found_item_id = fi.id
    JOIN users u ON cr.requester_id = u.id
    WHERE cr.id = ?
");
$stmt->execute([$claimId]);
$claim = $stmt->fetch();

if (!$claim) {
    header('Location: claim_requests.php');
    exit;
}

// Determine item name & description
$itemName = $claim['lost_item_id'] ? $claim['lost_item_name'] : $claim['item_name'];
$itemDescription = $claim['lost_item_id'] ? $claim['lost_item_description'] : $claim['item_description'];

// --- FIX: Define $itemType and $itemId for approve logic ---
$itemId = $claim['lost_item_id'] ?? $claim['found_item_id'];
$itemType = $claim['lost_item_id'] ? 'lost' : 'found';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'approve') {
            $scheduleDate = sanitize($_POST['schedule_date'] ?? '');
            $scheduleTime = sanitize($_POST['schedule_time'] ?? '');
            $adminNotes = sanitize($_POST['admin_notes'] ?? '');

            if (empty($scheduleDate) || empty($scheduleTime)) {
                $error = 'Schedule date and time are required.';
            } else {
                $scheduleDateTime = $scheduleDate . ' ' . $scheduleTime;

                // Update all pending claims for this item
                if ($itemType === 'lost') {
                    $stmtUpdate = $pdo->prepare("
                        UPDATE claim_requests 
                        SET status = ?, schedule_date = ?, notes = ?, admin_id = ?
                        WHERE lost_item_id = ? AND status = 'pending'
                    ");
                    $stmtUpdate->execute([CLAIM_STATUS_SCHEDULED, $scheduleDateTime, $adminNotes, getCurrentUserId(), $claim['lost_item_id']]);
                } else {
                    $stmtUpdate = $pdo->prepare("
                        UPDATE claim_requests 
                        SET status = ?, schedule_date = ?, notes = ?, admin_id = ?
                        WHERE found_item_id = ? AND status = 'pending'
                    ");
                    $stmtUpdate->execute([CLAIM_STATUS_SCHEDULED, $scheduleDateTime, $adminNotes, getCurrentUserId(), $claim['found_item_id']]);
                }

                // Notify all requesters for this item
                $stmtNoti = $pdo->prepare("
                    SELECT requester_id 
                    FROM claim_requests 
                    WHERE " . ($itemType === 'lost' ? "lost_item_id" : "found_item_id") . " = ?
                ");
                $stmtNoti->execute([$itemId]);
                $requesters = $stmtNoti->fetchAll();

                foreach ($requesters as $r) {
                    createNotification($pdo, $r['requester_id'], "Your claim for '{$itemName}' has been approved! Scheduled on " . formatDateTime($scheduleDateTime), 'success');
                }

                $success = 'Claim(s) approved and scheduled successfully!';
            }
        }
        elseif ($action === 'reject') {
            $rejectReason = sanitize($_POST['reject_reason'] ?? '');

            $stmtUpdate = $pdo->prepare("UPDATE claim_requests SET status=?, notes=?, admin_id=? WHERE id=?");
            $stmtUpdate->execute([CLAIM_STATUS_REJECTED, $rejectReason, getCurrentUserId(), $claimId]);

            // Update lost item if exists
            if ($claim['lost_item_id']) {
                $stmtLost = $pdo->prepare("UPDATE lost_items SET status=? WHERE id=?");
                $stmtLost->execute([LOST_STATUS_LISTED, $claim['lost_item_id']]);
            }

            $message = "Your claim for '{$itemName}' was rejected." . (!empty($rejectReason) ? " Reason: $rejectReason" : '');
            createNotification($pdo, $claim['requester_id'], $message, 'warning');
            $success = 'Claim rejected.';

        } elseif ($action === 'complete') {
            $pdo->beginTransaction();

            // Complete this claim
            $stmtComplete = $pdo->prepare("UPDATE claim_requests SET status=? WHERE id=?");
            $stmtComplete->execute([CLAIM_STATUS_COMPLETED, $claimId]);

            // Update lost item if exists
            if ($claim['lost_item_id']) {
                $stmtLost = $pdo->prepare("UPDATE lost_items SET status=? WHERE id=?");
                $stmtLost->execute([LOST_STATUS_RETURNED, $claim['lost_item_id']]);
            }

            // Update found item if exists
            if ($claim['found_item_id']) {
                $stmtFound = $pdo->prepare("UPDATE found_items SET status=? WHERE id=?");
                $stmtFound->execute([FOUND_STATUS_CLAIMED, $claim['found_item_id']]);
            }


            // --- Reject other claims (existing) ---
            $stmtOther = $pdo->prepare("
                SELECT cr.id, cr.requester_id, cr.found_item_id, u.name AS requester_name
                FROM claim_requests cr
                JOIN users u ON cr.requester_id = u.id
                WHERE cr.id != ?
                  AND (cr.lost_item_id = ? OR (cr.found_item_id = ? AND cr.found_item_id IS NOT NULL))
                  AND cr.status IN ('pending','scheduled')
            ");
            $stmtOther->execute([$claimId, $claim['lost_item_id'], $claim['found_item_id']]);
            $otherClaims = $stmtOther->fetchAll();

            $stmtReject = $pdo->prepare("UPDATE claim_requests SET status='rejected' WHERE id=?");
            $stmtRelease = $pdo->prepare("UPDATE found_items SET status='listed', lost_item_id=NULL WHERE id=?");

            foreach ($otherClaims as $other) {
                $stmtReject->execute([$other['id']]);
                // Only release found items that are not the one we just completed
                if ($other['found_item_id'] && $other['found_item_id'] != $claim['found_item_id']) {
                    $stmtRelease->execute([$other['found_item_id']]);
                }
                $message = "Hello {$other['requester_name']}, your claim for '{$itemName}' was not successful because another claim was completed.";
                createNotification($pdo, $other['requester_id'], $message, 'warning');
            }

            // --- Release other found items linked to this lost item (not part of claim) ---
            $stmtOtherFound = $pdo->prepare("
                SELECT id, user_id
                FROM found_items
                WHERE lost_item_id = ?
                  AND status != ?
                  AND id != ?
            ");
            $stmtOtherFound->execute([$claim['lost_item_id'], FOUND_STATUS_CLAIMED, $claim['found_item_id']]);
            $otherFoundItems = $stmtOtherFound->fetchAll();

            $stmtReleaseFound = $pdo->prepare("UPDATE found_items SET status='listed' WHERE id=?");

            foreach ($otherFoundItems as $foundItem) {
                $stmtReleaseFound->execute([$foundItem['id']]);
                createNotification($pdo, $foundItem['user_id'],
                        "The found item you reported for '{$itemName}' was not the correct match and is now available for others.",
                        'warning');
            }

            // Notify original claimer
            createNotification($pdo, $claim['requester_id'], "Your item '{$itemName}' has been returned!", 'success');

            $pdo->commit();
            $success = 'Claim completed successfully. Other claims rejected, found items released, and notifications sent!';
        }


    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Claim Request - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/admin_reviewclaim.css" rel="stylesheet">
</head>
<body>
<?php include '../components/sidebar.php'; ?>

<main class="content-wrapper">
    <div class="page-header-with-close">
        <h1>Review Claim Request</h1>
        <a href="pending_lost.php" class="close-btn" title="Back to Pending Items">
            <i class="bi bi-x-lg"></i>
        </a>
    </div>
   
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- Claim Details -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Claim Request Details</h5>
            <p><strong>Claim ID:</strong> <?= $claim['id'] ?></p>
            <p><strong>Status:</strong> <?= getStatusBadge($claim['status']) ?></p>
            <p><strong>Requested By:</strong> <?= htmlspecialchars($claim['requester_name']) ?> (<?= htmlspecialchars($claim['requester_email']) ?>)</p>
            <p><strong>Request Date:</strong> <?= formatDateTime($claim['created_at']) ?></p>
            <?php if ($claim['schedule_date']): ?>
                <p><strong>Scheduled:</strong> <?= formatDateTime($claim['schedule_date']) ?></p>
            <?php endif; ?>
            <?php if ($claim['notes']): ?>
                <p><strong>Notes:</strong> <?= nl2br(htmlspecialchars($claim['notes'])) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Claimer Info & Photo -->
    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <img src="<?= $claim['claim_photo'] ? getImageUrl($claim['claim_photo'], 'claim') : 'https://via.placeholder.com/300x300?text=No+Photo' ?>" 
                         class="img-fluid rounded" 
                         alt="Claimer Photo">
                </div>
                <div class="col-md-8">
                    <h5>Claimer Information</h5>
                    <h4><strong>Item Name: </strong><?= htmlspecialchars($claim['found_item_name']) ?></h4>
                    <p><strong>Name:</strong> <?= htmlspecialchars($claim['requester_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($claim['requester_email']) ?></p>

                    <?php if (!empty($claim['item_description'])): ?>
                        <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($claim['item_description'])) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($claim['date_found'])): ?>
                        <p><strong>Date Found:</strong> <?= formatDate($claim['date_found']) ?></p>
                    <?php endif; ?>

                    
                </div>

            </div>
        </div>
    </div>

    <!-- Found Item -->
    <?php if ($claim['found_item_id']): ?>
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <?php if ($claim['found_photo']): ?>
                            <img src="<?= getImageUrl($claim['found_photo'], 'found') ?>" 
                                 class="img-fluid rounded" 
                                 alt="Found Item Photo">
                        <?php else: ?>
                            <div class="no-image-placeholder">No Photo Available</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-8">
                        <h5>Item Information</h5>
                        <h4><strong>Item Name: </strong><?= htmlspecialchars($claim['found_item_name']) ?></h4>

                        <?php if (!empty($claim['found_description'])): ?>
                            <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($claim['found_description'])) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($claim['date_found'])): ?>
                            <p><strong>Date Found:</strong> <?= formatDate($claim['date_found']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($claim['found_by'])): ?>
                            <p><strong>Found By:</strong> <?= htmlspecialchars($claim['found_by']) ?></p>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Action Forms -->
    <?php if (!$success): ?>
        <?php if ($claim['status'] === 'pending' || $claim['status'] === 'scheduled'): ?>
            <div class="row g-4">
                <?php if ($claim['status'] === 'pending'): ?>
                    <div class="col-md-6">
                        <div class="action-card">
                            <h5>Approve & Schedule</h5>
                            <form method="POST" id="approveForm">
                                <input type="hidden" name="action" value="approve">
                                <div class="mb-3">
                                    <label class="form-label">Date</label>
                                    <input type="date" 
                                           name="schedule_date" 
                                           class="form-control" 
                                           min="<?= date('Y-m-d') ?>" 
                                           required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Time</label>
                                    <input type="time" 
                                           name="schedule_time" 
                                           class="form-control" 
                                           required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Notes (Optional)</label>
                                    <textarea name="admin_notes" 
                                              class="form-control" 
                                              rows="3"
                                              placeholder="Add any additional information for the claimer..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-success">Approve & Schedule</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="col-md-6">
                    <div class="action-card">
                        <h5><?= $claim['status'] === 'pending' ? 'Reject Claim' : 'Reject Scheduled Claim' ?></h5>
                        <form method="POST" id="rejectForm">
                            <input type="hidden" name="action" value="reject">
                            <div class="mb-3">
                                <label class="form-label">Reason</label>
                                <textarea name="reject_reason" 
                                          class="form-control" 
                                          rows="3" 
                                          placeholder="Provide a reason for rejection..."
                                          required></textarea>
                            </div>
                            <button type="button" 
                                    class="btn btn-danger" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#rejectModal">
                                Reject
                            </button>
                        </form>
                    </div>
                </div>

                <?php if ($claim['status'] === 'scheduled'): ?>
                    <div class="col-12">
                        <div class="action-card">
                            <h5>Complete Claim</h5>
                            <p><strong>Scheduled:</strong> <?= formatDateTime($claim['schedule_date']) ?></p>
                            
                            <div class="verification-checklist">
                                <strong>Verification Checklist:</strong>
                                <ul>
                                    <li>Verify student ID</li>
                                    <li>Verify ownership</li>
                                    <li>Item matches description</li>
                                </ul>
                            </div>
                            
                            <form method="POST" id="completeForm">
                                <input type="hidden" name="action" value="complete">
                                <button type="button" 
                                        class="btn btn-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#completeModal">
                                    Complete Claim
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                This claim has been <?= $claim['status'] ?>.
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<!-- Reject Confirmation Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">Confirm Rejection</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to reject this claim?</p>
                <p class="text-muted mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmReject">Reject Claim</button>
            </div>
        </div>
    </div>
</div>

<!-- Complete Confirmation Modal -->
<div class="modal fade" id="completeModal" tabindex="-1" aria-labelledby="completeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="completeModalLabel">Complete Claim</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to mark this claim as completed?</p>
                <p class="text-muted mb-0">This will update the item status and notify all relevant parties.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmComplete">Complete Claim</button>
            </div>
        </div>
    </div>
</div>

<footer>
    &copy; 2025 Campus Lost & Found System - Admin Panel
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Handle reject confirmation
    document.getElementById('confirmReject').addEventListener('click', function() {
        document.getElementById('rejectForm').submit();
    });

    // Handle complete confirmation
    document.getElementById('confirmComplete').addEventListener('click', function() {
        document.getElementById('completeForm').submit();
    });
</script>
</body>
</html>