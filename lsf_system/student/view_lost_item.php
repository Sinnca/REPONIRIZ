<?php global $pdo;
/**
 * Student - View Lost Item Details
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
    header('Location: lost_items.php');
    exit;
}

/* ==========================
   FETCH LOST ITEM
========================== */
$stmt = $pdo->prepare("
    SELECT li.*, u.name AS owner_name, u.email AS owner_email
    FROM lost_items li
    JOIN users u ON li.user_id = u.id
    WHERE li.id = ?
");
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: lost_items.php');
    exit;
}

/* ==========================
   FETCH ALL MATCHED FOUND ITEMS
========================== */
$stmtFound = $pdo->prepare("
    SELECT fi.*, u.name AS finder_name
    FROM found_items fi
    JOIN users u ON fi.user_id = u.id
    WHERE fi.lost_item_id = ?
      AND fi.status IN ('verified','listed')
    ORDER BY fi.created_at DESC
");
$stmtFound->execute([$itemId]);
$matchedFoundItems = $stmtFound->fetchAll();

/* ==========================
   FETCH EXISTING CLAIMS (per found item)
========================== */
$stmtClaim = $pdo->prepare("
    SELECT *
    FROM claim_requests
    WHERE lost_item_id = ?
      AND requester_id = ?
");
$stmtClaim->execute([$itemId, $userId]);
$existingClaims = [];
while ($row = $stmtClaim->fetch()) {
    if ($row['found_item_id']) {
        $existingClaims[$row['found_item_id']] = $row;
    }
}

$isOwner = ($item['user_id'] == $userId);

/* ==========================
   HELPER: Get Found Item Name
========================== */
function getFoundItemName($foundItemId, $matchedFoundItems) {
    foreach ($matchedFoundItems as $fi) {
        if ($fi['id'] == $foundItemId) {
            return $fi['item_name'];
        }
    }
    return "Found Item #$foundItemId";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($item['item_name']); ?> - Lost Item Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">Lost & Found</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="report_lost.php">Report Lost</a></li>
                <li class="nav-item"><a class="nav-link" href="report_found.php">Report Found</a></li>
                <li class="nav-item"><a class="nav-link" href="lost_items.php">Browse Lost</a></li>
                <li class="nav-item"><a class="nav-link" href="found_items.php">Browse Found</a></li>
                <li class="nav-item"><a class="nav-link" href="my_items.php">My Items</a></li>
            </ul>
            <span class="navbar-text me-3">
                Welcome, <?= htmlspecialchars(getCurrentUserName()); ?>
            </span>
            <a class="btn btn-outline-light" href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<main class="container mb-5">
    <h1 class="mb-3"><?= htmlspecialchars($item['item_name']); ?> - Details</h1>
    <a href="lost_items.php" class="btn btn-secondary mb-3">&larr; Back to Lost Items</a>

    <div class="row g-4">

        <!-- LOST ITEM -->
        <div class="col-md-6">
            <div class="card">
                <?php if ($item['photo']): ?>
                    <img src="<?= getImageUrl($item['photo'], 'lost'); ?>" class="card-img-top" alt="Lost Item">
                <?php else: ?>
                    <div class="p-4 text-center text-muted">No Image Available</div>
                <?php endif; ?>

                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($item['item_name']); ?></h5>
                    <p><?= nl2br(htmlspecialchars($item['description'])); ?></p>
                    <p><strong>Date Lost:</strong> <?= formatDate($item['date_lost']); ?></p>
                    <p><strong>Reported By:</strong> <?= htmlspecialchars($item['owner_name']); ?></p>
                    <p><strong>Reported On:</strong> <?= formatDateTime($item['created_at']); ?></p>
                    <p><strong>Status:</strong> <?= getStatusBadge($item['status']); ?></p>
                </div>
            </div>
        </div>

        <!-- FOUND ITEMS + CLAIM STATUS -->
        <div class="col-md-6">

            <?php if (!empty($matchedFoundItems)): ?>
                <h4 class="mb-3">Matching Found Items</h4>

                <?php foreach ($matchedFoundItems as $found): ?>
                    <div class="card mb-3">
                        <?php if ($found['photo']): ?>
                            <img src="<?= getImageUrl($found['photo'], 'found'); ?>" class="card-img-top" alt="Found Item">
                        <?php endif; ?>

                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($found['item_name']); ?></h5>
                            <p><?= nl2br(htmlspecialchars($found['description'])); ?></p>
                            <p><strong>Date Found:</strong> <?= formatDate($found['date_found']); ?></p>
                            <p><strong>Found By:</strong> <?= htmlspecialchars($found['finder_name']); ?></p>

                            <!-- View Details Button -->
                            <a href="view_found_item.php?id=<?= $found['id']; ?>" class="btn btn-outline-primary mb-2">
                                View Details
                            </a>

                            <!-- SHOW CLAIM STATUS ONLY -->
                            <?php if ($isOwner): ?>
                                <?php if (isset($existingClaims[$found['id']])): ?>
                                    <span class="badge bg-warning mt-2">
                                        Claim Submitted - <?= getStatusBadge($existingClaims[$found['id']]['status']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary mt-2">No Claim Submitted</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>
                <p class="text-muted">No matching found items yet.</p>
            <?php endif; ?>

        </div>
    </div>

    <!-- OWNER CLAIMS SUMMARY -->
    <?php if ($isOwner): ?>
        <div class="card p-3 mt-4">
            <h5>Your Submitted Claims for this Item</h5>
            <?php if (!empty($existingClaims)): ?>
                <ul>
                    <?php foreach ($existingClaims as $claim): ?>
                        <li>
                            <?= htmlspecialchars(getFoundItemName($claim['found_item_id'], $matchedFoundItems)); ?>
                            - <?= getStatusBadge($claim['status']); ?>
                            <?php if ($claim['status'] === 'scheduled'): ?>
                                (Schedule: <?= formatDateTime($claim['schedule_date']); ?>)
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No claims submitted yet for this lost item.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</main>

<footer class="bg-dark text-white text-center py-3">
    &copy; 2024 Campus Lost & Found System
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
