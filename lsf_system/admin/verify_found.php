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

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="../assets/js/main.js" defer></script>
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="index.php">Lost & Found Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="pending_lost.php">Pending Lost</a></li>
                <li class="nav-item"><a class="nav-link" href="pending_found.php">Pending Found</a></li>
                <li class="nav-item"><a class="nav-link" href="claim_requests.php">Claim Requests</a></li>
                <li class="nav-item"><a class="nav-link" href="all_items.php">All Items</a></li>
                <li class="nav-item"><a class="nav-link" href="statistics.php">Statistics</a></li>
            </ul>
            <span class="text-white me-3">Admin: <?= htmlspecialchars(getCurrentUserName()); ?></span>
            <a href="../logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container">

    <a href="pending_found.php" class="btn btn-link mb-3">&larr; Back to Pending Found Items</a>

    <h1 class="mb-4">Verify Found Item</h1>

    <!-- Alerts -->
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <a href="pending_found.php" class="btn btn-primary mt-3">Back to Pending Items</a>
    <?php else: ?>

        <!-- Found Item Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">Found Item Details</div>
            <div class="card-body row">
                <div class="col-md-4">
                    <?php if ($item['photo']): ?>
                        <img src="<?= getImageUrl($item['photo'], 'found'); ?>" class="img-fluid rounded border">
                    <?php else: ?>
                        <div class="p-4 text-center border rounded bg-light">
                            <p class="text-muted">No Image Available</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Item Name:</dt><dd class="col-sm-8"><?= htmlspecialchars($item['item_name']); ?></dd>
                        <dt class="col-sm-4">Description:</dt><dd class="col-sm-8"><?= nl2br(htmlspecialchars($item['description'])); ?></dd>
                        <dt class="col-sm-4">Date Found:</dt><dd class="col-sm-8"><?= formatDate($item['date_found']); ?></dd>
                        <dt class="col-sm-4">Found By:</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($item['finder_name']); ?><br>
                            <small class="text-muted"><?= htmlspecialchars($item['finder_email']); ?></small></dd>
                        <dt class="col-sm-4">Date Submitted:</dt><dd class="col-sm-8"><?= formatDateTime($item['created_at']); ?></dd>
                        <dt class="col-sm-4">Current Status:</dt><dd class="col-sm-8"><?= getStatusBadge($item['status']); ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Linked Lost Item Card -->
        <?php if ($linkedLostItem): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">Linked Lost Item</div>
                <div class="card-body row">
                    <?php if ($linkedLostItem['photo']): ?>
                        <div class="col-md-4">
                            <img src="<?= getImageUrl($linkedLostItem['photo'], 'lost'); ?>" class="img-fluid rounded border">
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
            <!-- Action Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">Verification Action</div>
                <div class="card-body">
                    <form method="POST">
                        <button type="submit" name="action" value="approve" class="btn btn-success me-2">✔ Approve & Verify Item</button>
                        <button class="btn btn-danger" type="button" data-bs-toggle="collapse" data-bs-target="#rejectForm">✖ Reject Item</button>

                        <div class="collapse mt-3" id="rejectForm">
                            <div class="mb-3">
                                <label class="form-label">Reason for Rejection (Optional)</label>
                                <textarea name="reason" class="form-control" rows="3"></textarea>
                            </div>
                            <button type="submit" name="action" value="reject" class="btn btn-danger">Confirm Reject</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Checklist -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">Verification Checklist</div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>Item description matches photo (if available)</li>
                        <li>Date found is reasonable</li>
                        <li>If linked to lost item, verify they match</li>
                        <li>No duplicate submissions</li>
                        <li>Not inappropriate or fake</li>
                    </ul>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">This item has already been processed. Status: <?= getStatusBadge($item['status']); ?></div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
