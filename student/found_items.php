<?php global $pdo;
/**
 * Browse Found Items
 * List of all verified/listed found items for students to browse
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireStudent();

$userId = getCurrentUserId();

// 1. Get all verified/listed found items
$stmt = $pdo->prepare("
    SELECT fi.*, u.name as finder_name
    FROM found_items fi
    JOIN users u ON fi.user_id = u.id
    WHERE fi.status IN ('verified','listed')
    ORDER BY fi.created_at DESC
");
$stmt->execute();
$foundItems = $stmt->fetchAll();

// 2. Get all active claim requests made by this user (pending or scheduled)
$claimStmt = $pdo->prepare("
    SELECT found_item_id
    FROM claim_requests
    WHERE requester_id = :userId
      AND status IN ('pending', 'scheduled')
");
$claimStmt->execute(['userId' => $userId]);
$claimsByUser = $claimStmt->fetchAll(PDO::FETCH_COLUMN);

// 3. Get recent rejected claims (last 7 days) to show feedback
$rejectedClaimStmt = $pdo->prepare("
    SELECT found_item_id, notes
    FROM claim_requests
    WHERE requester_id = :userId
      AND status = 'rejected'
      AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$rejectedClaimStmt->execute(['userId' => $userId]);
$rejectedClaims = $rejectedClaimStmt->fetchAll(PDO::FETCH_KEY_PAIR); // key: found_item_id, value: notes
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Found Items - Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">Lost & Found System</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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
    <h1 class="mb-3">Browse Found Items</h1>
    <p>Check if your lost item has been found by someone.</p>

    <?php if (count($foundItems) > 0): ?>
        <div class="row row-cols-1 row-cols-md-3 g-4 mt-3">
            <?php foreach ($foundItems as $item): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <?php if ($item['photo']): ?>
                            <img src="<?= getImageUrl($item['photo'], 'found'); ?>"
                                 class="card-img-top" alt="<?= htmlspecialchars($item['item_name']); ?>">
                        <?php else: ?>
                            <div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="height:200px;">
                                No Image Available
                            </div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($item['item_name']); ?></h5>
                            <p class="card-text"><?= htmlspecialchars(substr($item['description'], 0, 100)) . '...'; ?></p>
                            <p class="mb-1"><strong>Date Found:</strong> <?= formatDate($item['date_found']); ?></p>
                            <p><?= getStatusBadge($item['status']); ?></p>
                            <div class="mt-auto">
                                <a href="view_found_item.php?id=<?= $item['id']; ?>" class="btn btn-primary btn-sm me-2 mb-2">View Details</a>

                                <?php
                                // Claim status display logic
                                if (in_array($item['id'], $claimsByUser)): ?>
                                    <span class="badge bg-warning mb-2">Pending Claim</span>
                                <?php elseif (isset($rejectedClaims[$item['id']])): ?>
                                    <span class="badge bg-danger mb-2">Claim Rejected</span>
                                    <?php if (!empty($rejectedClaims[$item['id']])): ?>
                                        <small class="text-muted d-block"><?= htmlspecialchars($rejectedClaims[$item['id']]); ?></small>
                                    <?php endif; ?>
                                <?php elseif ($item['user_id'] != $userId): ?>
                                    <a href="view_found_item.php?id=<?= $item['id']; ?>" class="btn btn-outline-success btn-sm mb-2">Claim This Item</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info mt-4">No found items currently available.</div>
    <?php endif; ?>

    <section class="mt-5">
        <h3>Is This Your Item?</h3>
        <p>If you see an item that belongs to you:</p>
        <ol>
            <li>Make sure you have reported it as lost first</li>
            <li>View the item details to verify it matches your lost item</li>
            <li>Admin will link it to your lost item report</li>
            <li>You can then request a claim from your dashboard</li>
        </ol>
        <p>Haven't reported your lost item yet? <a href="report_lost.php">Report it now</a></p>
    </section>

</main>

<footer class="bg-dark text-white text-center py-3 mt-5">
    &copy; 2024 Campus Lost & Found System
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
