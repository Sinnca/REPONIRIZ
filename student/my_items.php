<?php global $pdo;
/**
 * My Items
 * View and manage all items reported by the student
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireStudent();

$userId = getCurrentUserId();

// Get all lost items by user
$stmtLost = $pdo->prepare("SELECT * FROM lost_items WHERE user_id = ? ORDER BY created_at DESC");
$stmtLost->execute([$userId]);
$lostItems = $stmtLost->fetchAll();

// Get all found items by user
$stmtFound = $pdo->prepare("SELECT * FROM found_items WHERE user_id = ? ORDER BY created_at DESC");
$stmtFound->execute([$userId]);
$foundItems = $stmtFound->fetchAll();

// Get all claim requests
$stmtClaims = $pdo->prepare("
    SELECT cr.*,
           li.id AS lost_item_id,
           fi.id AS found_item_id,
           COALESCE(li.item_name, fi.item_name) AS item_name,
           COALESCE(li.description, fi.description) AS description,
           li.photo AS lost_photo,
           fi.photo AS found_photo
    FROM claim_requests cr
    LEFT JOIN lost_items li ON cr.lost_item_id = li.id
    LEFT JOIN found_items fi ON cr.found_item_id = fi.id
    WHERE cr.requester_id = ?
    ORDER BY cr.created_at DESC
");
$stmtClaims->execute([$userId]);
$claimRequests = $stmtClaims->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Items - Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="../assets/js/main.js" defer></script>
    <style>
        .item-img {
            width: 55px;
            height: 55px;
            object-fit: cover;
            border-radius: 5px;
        }
        .navbar-brand { font-weight: 700; letter-spacing: .5px; }
        table td { vertical-align: middle; }
    </style>
</head>
<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg bg-dark navbar-dark px-3 mb-4">
    <a class="navbar-brand" href="#">Lost & Found System</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navMenu">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
        <ul class="navbar-nav me-auto">
            <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="report_lost.php">Report Lost Item</a></li>
            <li class="nav-item"><a class="nav-link" href="report_found.php">Report Found Item</a></li>
            <li class="nav-item"><a class="nav-link" href="lost_items.php">Browse Lost Items</a></li>
            <li class="nav-item"><a class="nav-link" href="found_items.php">Browse Found Items</a></li>
            <li class="nav-item"><a class="nav-link active" href="my_items.php">My Items</a></li>
        </ul>
        <span class="navbar-text text-white me-3">
            Welcome, <?= htmlspecialchars(getCurrentUserName()) ?>
        </span>
        <a href="../logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
</nav>

<div class="container mb-5">

    <h1 class="mb-3">My Items</h1>
    <p class="text-muted mb-4">View and manage all your submitted items and claim requests.</p>

    <!-- LOST ITEMS -->
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">My Lost Items (<?= count($lostItems) ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (count($lostItems) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                        <tr>
                            <th>Photo</th>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th>Date Lost</th>
                            <th>Status</th>
                            <th>Date Reported</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($lostItems as $item): ?>
                            <tr>
                                <td>
                                    <?php if ($item['photo']): ?>
                                        <img src="<?= getImageUrl($item['photo'], 'lost') ?>" class="item-img">
                                    <?php else: ?>
                                        <span class="text-muted">No Image</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($item['item_name']) ?></td>
                                <td><?= htmlspecialchars(substr($item['description'],0,50)) ?>...</td>
                                <td><?= formatDate($item['date_lost']) ?></td>
                                <td><?= getStatusBadge($item['status']) ?></td>
                                <td><?= formatDate($item['created_at']) ?></td>
                                <td>
                                    <a href="view_lost_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary">View</a>
                                    <?php if ($item['status']==='pending'): ?>
                                        <a href="edit_lost_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <button class="btn btn-sm btn-danger" onclick="deleteLostItem(<?= $item['id'] ?>)">Delete</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">You haven't reported any lost items yet.</p>
                <a href="report_lost.php" class="btn btn-primary">Report Lost Item</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- FOUND ITEMS -->
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Items I Found (<?= count($foundItems) ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (count($foundItems) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                        <tr>
                            <th>Photo</th>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th>Date Found</th>
                            <th>Status</th>
                            <th>Date Reported</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($foundItems as $item): ?>
                            <tr>
                                <td>
                                    <?php if ($item['photo']): ?>
                                        <img src="<?= getImageUrl($item['photo'], 'found') ?>" class="item-img">
                                    <?php else: ?>
                                        <span class="text-muted">No Image</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($item['item_name']) ?></td>
                                <td><?= htmlspecialchars(substr($item['description'],0,50)) ?>...</td>
                                <td><?= formatDate($item['date_found']) ?></td>
                                <td><?= getStatusBadge($item['status']) ?></td>
                                <td><?= formatDate($item['created_at']) ?></td>
                                <td>
                                    <a href="view_found_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary">View</a>
                                    <?php if ($item['status']==='pending'): ?>
                                        <button class="btn btn-sm btn-danger" onclick="deleteFoundItem(<?= $item['id'] ?>)">Delete</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">You haven't reported any found items yet.</p>
                <a href="report_found.php" class="btn btn-success">Report Found Item</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- CLAIM REQUESTS -->
    <div class="card shadow-sm">
        <div class="card-header bg-warning">
            <h5 class="mb-0">My Claim Requests (<?= count($claimRequests) ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (count($claimRequests) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                        <tr>
                            <th>Photo</th>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Scheduled Date</th>
                            <th>Request Date</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($claimRequests as $claim): ?>
                            <tr>
                                <td>
                                    <?php if ($claim['photo']): ?>
                                        <img src="<?= getImageUrl($claim['photo'], 'claim') ?>" class="item-img">
                                    <?php else: ?>
                                        <span class="text-muted">No Image</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($claim['item_name']) ?></td>
                                <td><?= htmlspecialchars(substr($claim['description'],0,50)) ?>...</td>
                                <td><?= getStatusBadge($claim['status']) ?></td>
                                <td><?= $claim['schedule_date'] ? formatDateTime($claim['schedule_date']) : 'Not scheduled yet' ?></td>
                                <td><?= formatDate($claim['created_at']) ?></td>
                                <td>
                                    <a href="view_claim.php?id=<?= $claim['id'] ?>" class="btn btn-sm btn-secondary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No claim requests yet.</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    async function deleteLostItem(id){ await LSF.FormHandlers.deleteItem(id,'lost'); }
    async function deleteFoundItem(id){ await LSF.FormHandlers.deleteItem(id,'found'); }
</script>

<footer class="text-center py-3 text-muted">
    &copy; 2024 Campus Lost & Found System
</footer>

</body>
</html>
