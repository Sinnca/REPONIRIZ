<?php
/**
 * Admin - Verify Found Item
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

$itemId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';

if (!$itemId) {
    header('Location: pending_found.php');
    exit;
}

// Get found item
$stmt = $pdo->prepare("
    SELECT fi.*, u.name as finder_name, u.email as finder_email
    FROM found_items fi
    JOIN users u ON fi.user_id = u.id
    WHERE fi.id = ?
");
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: pending_found.php');
    exit;
}

// Get linked lost item
$linkedLostItem = null;
if ($item['lost_item_id']) {
    $stmtLost = $pdo->prepare("
        SELECT li.*, u.name as owner_name, u.email as owner_email
        FROM lost_items li
        JOIN users u ON li.user_id = u.id
        WHERE li.id = ?
    ");
    $stmtLost->execute([$item['lost_item_id']]);
    $linkedLostItem = $stmtLost->fetch();
}

// Handle form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'approve') {
        try {
            $pdo->prepare("UPDATE found_items SET status = ? WHERE id = ?")
                    ->execute([FOUND_STATUS_VERIFIED, $itemId]);

            if ($item['lost_item_id']) {
                $pdo->prepare("UPDATE lost_items SET status = ? WHERE id = ?")
                        ->execute([LOST_STATUS_READY_FOR_CLAIM, $item['lost_item_id']]);
                if ($linkedLostItem) {
                    createNotification(
                            $pdo,
                            $linkedLostItem['user_id'],
                            "Good news! A matching found item for '{$linkedLostItem['item_name']}' has been verified. You can now request a claim.",
                            'success'
                    );
                }
            }

            createNotification($pdo, $item['user_id'], "Your found item '{$item['item_name']}' has been verified.", 'success');
            $success = 'Found item approved and verified successfully!';
        } catch (PDOException $e) {
            $error = 'Failed to approve item. Please try again.';
        }
    }

    elseif ($action === 'reject') {
        $reason = sanitize($_POST['reason'] ?? '');
        try {
            $pdo->prepare("UPDATE found_items SET status = ? WHERE id = ?")
                    ->execute([FOUND_STATUS_REJECTED, $itemId]);

            $message = "Your found item '{$item['item_name']}' was rejected.";
            if (!empty($reason)) $message .= " Reason: $reason";

            createNotification($pdo, $item['user_id'], $message, 'warning');
            $success = 'Found item rejected.';
        } catch (PDOException $e) {
            $error = 'Failed to reject item. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Found Item - Admin</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin_verifyfound.css">



<script src="../assets/js/main.js" defer></script>
</head>
<body>

<?php include '../components/sidebar.php'; ?>

<main class="content-wrapper">

<div class="page-header-with-close">
        <h1>Verify Found Item</h1>
        <a href="pending_lost.php" class="close-btn" title="Back to Pending Items">
            <i class="bi bi-x-lg"></i>
        </a>
    </div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <a href="pending_found.php" class="btn btn-success mt-3">Back to Pending Items</a>
<?php else: ?>

    <!-- Found Item Details -->
    <div class="card">
        <div class="card-header bg-primary text-white">Found Item Details</div>
        <div class="card-body row">
            <div class="col-md-4">
                <?php if ($item['photo']): ?>
                    <img src="<?= getImageUrl($item['photo'], 'found'); ?>" class="img-fluid">
                <?php else: ?>
                    <div class="no-image-placeholder">No Image Available</div>
                <?php endif; ?>
            </div>
            <div class="col-md-8">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Item Name:</dt><dd class="col-sm-8"><?= htmlspecialchars($item['item_name']); ?></dd>
                    <dt class="col-sm-4">Description:</dt><dd class="col-sm-8"><?= nl2br(htmlspecialchars($item['description'])); ?></dd>
                    <dt class="col-sm-4">Date Found:</dt><dd class="col-sm-8"><?= formatDate($item['date_found']); ?></dd>
                    <dt class="col-sm-4">Found By:</dt><dd class="col-sm-8"><?= htmlspecialchars($item['finder_name']); ?><br><small class="text-muted"><?= htmlspecialchars($item['finder_email']); ?></small></dd>
                    <dt class="col-sm-4">Date Submitted:</dt><dd class="col-sm-8"><?= formatDateTime($item['created_at']); ?></dd>
                    <dt class="col-sm-4">Current Status:</dt><dd class="col-sm-8"><?= getStatusBadge($item['status']); ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Linked Lost Item -->
    <?php if ($linkedLostItem): ?>
        <div class="card">
            <div class="card-header bg-secondary text-white">Linked Lost Item</div>
            <div class="card-body row">
                <?php if ($linkedLostItem['photo']): ?>
                    <div class="col-md-4">
                        <img src="<?= getImageUrl($linkedLostItem['photo'], 'lost'); ?>" class="img-fluid">
                    </div>
                <?php endif; ?>
                <div class="col-md-8">
                    <h5><?= htmlspecialchars($linkedLostItem['item_name']); ?></h5>
                    <p><?= nl2br(htmlspecialchars($linkedLostItem['description'])); ?></p>
                    <p><strong>Date Lost:</strong> <?= formatDate($linkedLostItem['date_lost']); ?></p>
                    <p><strong>Lost By:</strong> <?= htmlspecialchars($linkedLostItem['owner_name']); ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($linkedLostItem['owner_email']); ?></p>
                    <p><strong>Status:</strong> <?= getStatusBadge($linkedLostItem['status']); ?></p>
                    <p class="text-muted"><small>Approving this found item notifies the lost item owner.</small></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($item['status'] === 'pending'): ?>
        <!-- Verification Action -->
        <div class="card">
            <div class="card-header bg-dark text-white">Verification Action</div>
            <div class="card-body">
                <form method="POST">
                    <button type="submit" name="action" value="approve" class="btn btn-success me-2">
                        <i class="bi bi-check-circle me-1"></i>Approve & Verify Item
                    </button>
                    <button class="btn btn-danger" type="button" data-bs-toggle="collapse" data-bs-target="#rejectForm">
                        <i class="bi bi-x-circle me-1"></i>Reject Item
                    </button>

                    <div class="collapse mt-3" id="rejectForm">
                        <div class="mb-3">
                            <label class="form-label">Reason for Rejection (Optional)</label>
                            <textarea name="reason" class="form-control" rows="3"></textarea>
                        </div>
                        <button type="submit" name="action" value="reject" class="btn btn-danger">
                            Confirm Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Checklist -->
        <section class="guidelines-section">
            <h4><i class="bi bi-clipboard-check"></i>Verification Checklist</h4>
            <ul>
                <li>Item description matches photo (if available)</li>
                <li>Date found is reasonable</li>
                <li>If linked to lost item, verify they match</li>
                <li>No duplicate submissions</li>
                <li>Not inappropriate or fake</li>
            </ul>
        </section>
    <?php else: ?>
        <div class="alert alert-info">
    This item has already been processed. Status: 
    <?php
        $statusClass = match($item['status']) {
            'pending' => 'status-pending',
            'verified', 'listed' => 'status-verified',
            'rejected' => 'status-rejected',
            default => 'status-pending'
        };
    ?>
    <span class="status-badge <?= $statusClass ?>"><?= ucfirst($item['status']); ?></span>
</div>

    <?php endif; ?>

<?php endif; ?>

</main>

<footer>
    &copy; 2024 Campus Lost & Found System - Admin Panel
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
