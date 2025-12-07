<?php
global $pdo;
/**
 * View Found Item Details
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
    header('Location: found_items.php');
    exit;
}

// Get found item details with safe defaults
$stmt = $pdo->prepare("
    SELECT 
        fi.id,
        fi.item_name,
        COALESCE(fi.description, '') AS description,
        COALESCE(fi.photo, '') AS photo,
        fi.date_found,
        fi.status,
        fi.user_id,
        COALESCE(fi.created_at, '') AS created_at,
        u.name AS finder_name,
        u.email AS finder_email
    FROM found_items fi
    JOIN users u ON fi.user_id = u.id
    WHERE fi.id = ?
");
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: found_items.php');
    exit;
}

// Check if current user is the finder
$isFinder = ($item['user_id'] == $userId);

// Check if current user already submitted a claim for this found item
$stmtClaim = $pdo->prepare("
    SELECT COUNT(*) FROM claim_requests 
    WHERE found_item_id = ? AND requester_id = ?
");
$stmtClaim->execute([$itemId, $userId]);
$existingClaimCount = $stmtClaim->fetchColumn();

// Allow claim if user didn't already submit
$canClaim = !$isFinder && $existingClaimCount == 0 && in_array($item['status'], ['verified','listed', 'pending']);
// Attempt to fetch related lost_item_id for this found item
$stmtLostLink = $pdo->prepare("SELECT lost_item_id FROM found_items WHERE id = ?");
$stmtLostLink->execute([$itemId]);
$lostLink = $stmtLostLink->fetch();
$lostItemIdForClaim = $lostLink['lost_item_id'] ?? null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($item['item_name']); ?> - Found Item Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">Lost & Found System</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="report_lost.php">Report Lost Item</a></li>
                <li class="nav-item"><a class="nav-link" href="report_found.php">Report Found Item</a></li>
                <li class="nav-item"><a class="nav-link" href="lost_items.php">Browse Lost Items</a></li>
                <li class="nav-item"><a class="nav-link active" href="found_items.php">Browse Found Items</a></li>
                <li class="nav-item"><a class="nav-link" href="my_items.php">My Items</a></li>
            </ul>
            <span class="navbar-text me-3">Welcome, <?= htmlspecialchars(getCurrentUserName()); ?></span>
            <a class="btn btn-outline-light" href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<main class="container mb-5">
    <h1 class="mb-3">Found Item Details</h1>
    <a href="found_items.php" class="btn btn-secondary mb-3">&larr; Back to Found Items</a>

    <div class="row g-4">
        <div class="col-md-5">
            <?php if (!empty($item['photo'])): ?>
                <img src="<?= getImageUrl($item['photo'], 'found'); ?>" class="img-fluid rounded shadow-sm" alt="<?= htmlspecialchars($item['item_name']); ?>">
            <?php else: ?>
                <div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="height:300px;">
                    No Image Available
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-7">
            <h2><?= htmlspecialchars($item['item_name']); ?></h2>
            <p><?= nl2br(htmlspecialchars($item['description'])); ?></p>

            <ul class="list-group mb-3">
                <li class="list-group-item"><strong>Date Found:</strong> <?= !empty($item['date_found']) ? formatDate($item['date_found']) : 'N/A'; ?></li>
                <li class="list-group-item"><strong>Found By:</strong> <?= htmlspecialchars($item['finder_name']); ?></li>
                <li class="list-group-item"><strong>Date Reported:</strong> <?= !empty($item['created_at']) ? formatDateTime($item['created_at']) : 'N/A'; ?></li>
                <li class="list-group-item"><strong>Status:</strong> <?= getStatusBadge($item['status']); ?></li>
            </ul>

            <?php if ($isFinder): ?>
                <div class="alert alert-info">
                    <h5>You Reported This Item</h5>
                    <p>This item is <strong><?= htmlspecialchars($item['status']); ?></strong>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Claim Section -->
    <?php if ($canClaim): ?>
        <section class="mt-5">
            <h3>Is This Your Item?</h3>
            <div class="card bg-light">
                <div class="card-body">
                    <p><strong>If this is your lost item, submit a claim request!</strong></p>

                    <form id="claimFoundItemForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="claim_photo" class="form-label">Upload Photo (Optional)</label>
                            <input type="file" name="claim_photo" id="claim_photo" class="form-control" accept="image/*">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Item Name (Auto-filled — editable)</label>
                            <input type="text" id="claim_item_name" name="item_name" class="form-control" value="<?= htmlspecialchars($item['item_name']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="claim_description" class="form-label">Description / Notes</label>
                            <textarea id="claim_description" name="description" class="form-control" rows="3"><?= htmlspecialchars($item['description']); ?></textarea>
                        </div>

                        <div id="claimAlertContainer"></div>

                        <button type="submit" class="btn btn-success">Request Claim</button>
                        <input type="hidden" name="lost_item_id" value="<?= $lostItemIdForClaim !== null ? $lostItemIdForClaim : '' ?>">
                        <input type="hidden" name="found_item_id" value="<?= $item['id'] ?>">


                    </form>
                </div>
            </div>
        </section>
    <?php elseif (!$isFinder && !$canClaim): ?>
        <div class="alert alert-warning mt-4">
            You already submitted a claim request for this item (Pending)
        </div>
    <?php endif; ?>

</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('claimFoundItemForm');
        if (!form) return;

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Submitting...';

            const formData = new FormData();
            formData.append('found_item_id', <?= $itemId; ?>);
            formData.append('item_name', document.getElementById('claim_item_name').value);
            formData.append('description', document.getElementById('claim_description').value);
            formData.append('lost_item_id', <?= $lostItemIdForClaim ?? 'null'; ?>);


            const fileInput = document.getElementById('claim_photo');
            if (fileInput.files[0]) {
                formData.append('claim_photo', fileInput.files[0]);
            }

            try {
                const response = await fetch('../api/claims/create_claim.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                const alertContainer = document.getElementById('claimAlertContainer');

                if (data.success) {
                    alertContainer.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    form.reset();
                } else {
                    alertContainer.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            } catch (err) {
                alert('An error occurred: ' + err.message);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    });
</script>

</body>
</html>
