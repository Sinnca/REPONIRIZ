<?php global $pdo;
/**
 * Admin - Verify Lost Item
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
    header('Location: pending_lost.php');
    exit;
}

// Fetch item
$stmt = $pdo->prepare("
    SELECT li.*, u.name as submitter_name, u.email as submitter_email
    FROM lost_items li
    JOIN users u ON li.user_id = u.id
    WHERE li.id = ?
");
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: pending_lost.php');
    exit;
}

// Handle form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'approve') {
        try {
            $pdo->prepare("UPDATE lost_items SET status = ? WHERE id = ?")
                    ->execute([LOST_STATUS_LISTED, $itemId]);

            createNotification($pdo, $item['user_id'],
                    "Your lost item '{$item['item_name']}' has been verified and listed.",
                    'success'
            );

            $success = 'Lost item approved and listed successfully!';
        } catch (PDOException $e) {
            $error = 'Failed to approve item. Please try again.';
        }
    }

    elseif ($action === 'reject') {
        $reason = sanitize($_POST['reason'] ?? '');

        try {
            $pdo->prepare("UPDATE lost_items SET status = ? WHERE id = ?")
                    ->execute([LOST_STATUS_REJECTED, $itemId]);

            $message = "Your lost item '{$item['item_name']}' was rejected.";
            if (!empty($reason)) $message .= " Reason: $reason";

            createNotification($pdo, $item['user_id'], $message, 'warning');

            $success = 'Lost item rejected.';
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
    <title>Verify Lost Item - Admin</title>

    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <script src="../assets/js/main.js" defer></script>

</head>
<body class="bg-light">


<?php include '../components/sidebar.php'; ?>

<div class="container py-4">

    <h1 class="mb-4">Verify Lost Item</h1>

    <!-- Alerts -->
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <a href="pending_lost.php" class="btn btn-primary mt-3">Back to Pending Items</a>
    <?php else: ?>

        <!-- ITEM DETAILS -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Item Details</h5>
            </div>

            <div class="card-body row">

                <div class="col-md-4">
                    <?php if ($item['photo']): ?>
                        <img src="<?= getImageUrl($item['photo'], 'lost'); ?>"
                             class="img-fluid rounded border">
                    <?php else: ?>
                        <div class="p-4 text-center border rounded bg-light">
                            <p class="text-muted">No Image Available</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-8">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Item Name:</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($item['item_name']); ?></dd>

                        <dt class="col-sm-4">Description:</dt>
                        <dd class="col-sm-8"><?= nl2br(htmlspecialchars($item['description'])); ?></dd>

                        <dt class="col-sm-4">Date Lost:</dt>
                        <dd class="col-sm-8"><?= formatDate($item['date_lost']); ?></dd>

                        <dt class="col-sm-4">Submitted By:</dt>
                        <dd class="col-sm-8">
                            <?= htmlspecialchars($item['submitter_name']); ?><br>
                            <small class="text-muted"><?= htmlspecialchars($item['submitter_email']); ?></small>
                        </dd>

                        <dt class="col-sm-4">Date Submitted:</dt>
                        <dd class="col-sm-8"><?= formatDateTime($item['created_at']); ?></dd>

                        <dt class="col-sm-4">Current Status:</dt>
                        <dd class="col-sm-8"><?= getStatusBadge($item['status']); ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <?php if ($item['status'] === 'pending'): ?>

            <!-- ACTION SECTION -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Verification Action</h5>
                </div>

                <div class="card-body">
                    <form method="POST" id="verifyForm">

                        <button type="submit" name="action" value="approve"
                                class="btn btn-success me-2">
                            ✔ Approve & List Item
                        </button>

                        <button class="btn btn-danger" type="button"
                                data-bs-toggle="collapse" data-bs-target="#rejectForm">
                            ✖ Reject Item
                        </button>

                        <!-- Collapse Reject Form -->
                        <div class="collapse mt-3" id="rejectForm">
                            <div class="mb-3">
                                <label class="form-label">Reason for Rejection (Optional)</label>
                                <textarea name="reason" class="form-control" rows="3"></textarea>
                            </div>

                            <button type="submit" name="action" value="reject"
                                    class="btn btn-danger">
                                Confirm Reject
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- CHECKLIST -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Verification Checklist</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>Item description is clear and detailed</li>
                        <li>Date lost is valid and reasonable</li>
                        <li>No duplicate submission</li>
                        <li>Not inappropriate or fake</li>
                        <li>Photo is clear (if provided)</li>
                    </ul>
                </div>
            </div>

        <?php else: ?>

            <div class="alert alert-info">
                This item has already been processed.
                Status: <?= getStatusBadge($item['status']); ?>
            </div>

        <?php endif; ?>

    <?php endif; ?>

</div>

<!-- BOOTSTRAP JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
