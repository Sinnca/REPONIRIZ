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

    <!-- BOOTSTRAP & ICONS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary-yellow: #F7C506;
            --primary-blue: #003DA5;
            --primary-red: #C8102E;
            --bg-primary: #f8f9fa;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
        }

        body {
            background: var(--bg-primary);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: var(--text-primary);
        }

        .content-wrapper {
            padding: 40px 50px;
            max-width: 1200px;
            margin: 0 auto;
        }

        h2, h1 {
            color: var(--primary-blue);
            font-weight: 700;
            margin-bottom: 24px;
        }

        .card {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            background: white;
            margin-bottom: 24px;
        }

        .card-header {
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
            font-size: 1rem;
        }

        img.thumb, .img-fluid {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            object-fit: cover;
        }

        .btn {
            border-radius: 6px;
            font-weight: 500;
            padding: 7px 16px;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            font-size: 0.875rem;
        }

        .btn-success {
            background: var(--primary-yellow);
            color: #1f2937;
            border-color: var(--primary-yellow);
        }

        .btn-success:hover {
            background: #e6b505;
            border-color: #e6b505;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(247,197,6,0.25);
        }

        .alert {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            padding: 20px;
            background: white;
        }

        .alert-info {
            border-left: 4px solid var(--primary-blue);
            background: #f0f7ff;
        }

        .text-muted {
            color: var(--text-secondary) !important;
        }

        .guidelines-section {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary-blue);
        }

        .guidelines-section h4 {
            color: var(--primary-blue);
            font-weight: 600;
            font-size: 1.125rem;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .guidelines-section ul {
            margin-bottom: 0;
            padding-left: 24px;
        }

        .guidelines-section li {
            color: var(--text-primary);
            margin-bottom: 8px;
            line-height: 1.6;
        }

        .guidelines-section li::marker {
            color: var(--primary-blue);
        }

        .no-image-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 200px;
            background: #f3f4f6;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        footer {
            background: var(--primary-blue);
            color: white;
            text-align: center;
            padding: 24px;
            margin-top: 48px;
            font-size: 0.9375rem;
        }
    </style>

    <script src="../assets/js/main.js" defer></script>
</head>
<body>

<?php include '../components/sidebar.php'; ?>

<main class="content-wrapper">

    <h1>Verify Lost Item</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <a href="pending_lost.php" class="btn btn-success mt-3">Back to Pending Items</a>
    <?php else: ?>

        <!-- ITEM DETAILS -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                Item Details
            </div>
            <div class="card-body row">
                <div class="col-md-4">
                    <?php if ($item['photo']): ?>
                        <img src="<?= getImageUrl($item['photo'], 'lost'); ?>" class="img-fluid">
                    <?php else: ?>
                        <div class="no-image-placeholder">No Image Available</div>
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
            <div class="card">
                <div class="card-header bg-dark text-white">Verification Action</div>
                <div class="card-body">
                    <form method="POST" id="verifyForm">
                        <button type="submit" name="action" value="approve" class="btn btn-success me-2">
                            <i class="bi bi-check-circle me-1"></i>Approve & List Item
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

            <!-- CHECKLIST -->
            <section class="guidelines-section">
                <h4><i class="bi bi-clipboard-check"></i>Verification Checklist</h4>
                <ul>
                    <li>Item description is clear and detailed</li>
                    <li>Date lost is valid and reasonable</li>
                    <li>No duplicate submission</li>
                    <li>Not inappropriate or fake</li>
                    <li>Photo is clear (if provided)</li>
                </ul>
            </section>
        <?php else: ?>
            <div class="alert alert-info">
                This item has already been processed.
                Status: <?= getStatusBadge($item['status']); ?>
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
