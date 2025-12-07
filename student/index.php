<?php global $pdo;
/**
 * Student Dashboard
 * Main dashboard for student showing their items and notifications
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Require student login
requireStudent();

$userId = getCurrentUserId();
$userName = getCurrentUserName();

// Fetch student's lost items
$stmtLost = $pdo->prepare("SELECT * FROM lost_items WHERE user_id = ? ORDER BY created_at DESC");
$stmtLost->execute([$userId]);
$lostItems = $stmtLost->fetchAll();

// Fetch student's found items
$stmtFound = $pdo->prepare("SELECT * FROM found_items WHERE user_id = ? ORDER BY created_at DESC");
$stmtFound->execute([$userId]);
$foundItems = $stmtFound->fetchAll();

// Fetch student's claim requests (LEFT JOIN to handle missing lost_item_id)
$stmtClaims = $pdo->prepare("
    SELECT cr.*, li.item_name AS lost_item_name, li.description AS lost_item_description
    FROM claim_requests cr
    LEFT JOIN lost_items li ON cr.lost_item_id = li.id
    WHERE cr.requester_id = ?
    ORDER BY cr.created_at DESC
");
$stmtClaims->execute([$userId]);
$claimRequestsRaw = $stmtClaims->fetchAll();

// Normalize claim requests so item_name and description are always set
$claimRequests = [];
foreach ($claimRequestsRaw as $c) {
    $claimRequests[] = [
            'id' => $c['id'],
            'item_name' => !empty($c['lost_item_name']) ? $c['lost_item_name'] : ($c['item_name'] ?? 'N/A'),
            'description' => !empty($c['lost_item_description']) ? $c['lost_item_description'] : ($c['item_description'] ?? 'No description'),
            'status' => $c['status'] ?? 'pending',
            'schedule_date' => $c['schedule_date'] ?? null,
            'created_at' => $c['created_at'] ?? null
    ];
}

// Fetch latest notifications
$stmtNotifications = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmtNotifications->execute([$userId]);
$notifications = $stmtNotifications->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">Lost & Found System</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" href="index.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="report_lost.php">Report Lost Item</a></li>
                <li class="nav-item"><a class="nav-link" href="report_found.php">Report Found Item</a></li>
                <li class="nav-item"><a class="nav-link" href="lost_items.php">Browse Lost Items</a></li>
                <li class="nav-item"><a class="nav-link" href="found_items.php">Browse Found Items</a></li>
                <li class="nav-item"><a class="nav-link" href="my_items.php">My Items</a></li>
            </ul>
            <span class="navbar-text me-3">Welcome, <?= htmlspecialchars($userName) ?></span>
            <a class="btn btn-outline-light" href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<main class="container">

    <!-- Notifications -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">Recent Notifications</div>
                <div class="card-body">
                    <?php if ($notifications): ?>
                        <ul class="list-group">
                            <?php foreach ($notifications as $notification): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars($notification['message']) ?>
                                    <span class="badge bg-primary rounded-pill"><?= formatDateTime($notification['created_at']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="mb-0">No notifications yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4 text-center">
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm"><div class="card-body"><h3><?= count($lostItems) ?></h3><p class="mb-0">Lost Items Reported</p></div></div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm"><div class="card-body"><h3><?= count($foundItems) ?></h3><p class="mb-0">Found Items Reported</p></div></div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm"><div class="card-body"><h3><?= count($claimRequests) ?></h3><p class="mb-0">Claim Requests</p></div></div>
        </div>
    </div>

    <!-- My Lost Items -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">My Lost Items</div>
                <div class="card-body">
                    <?php if ($lostItems): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-dark">
                                <tr><th>Name</th><th>Description</th><th>Date Lost</th><th>Status</th><th>Submitted</th><th>Actions</th></tr>
                                </thead>
                                <tbody>
                                <?php foreach ($lostItems as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td><?= htmlspecialchars(substr($item['description'],0,50)) ?>...</td>
                                        <td><?= formatDate($item['date_lost']) ?></td>
                                        <td><?= getStatusBadge($item['status']) ?></td>
                                        <td><?= formatDate($item['created_at']) ?></td>
                                        <td>
                                            <a class="btn btn-sm btn-info" href="view_lost_item.php?id=<?= $item['id'] ?>">View</a>
                                            <?php if ($item['status'] === 'pending'): ?>
                                                <a class="btn btn-sm btn-warning" href="edit_lost_item.php?id=<?= $item['id'] ?>">Edit</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>You haven't reported any lost items yet.</p>
                        <a class="btn btn-primary" href="report_lost.php">Report Lost Item</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- My Found Items -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">Items I Found</div>
                <div class="card-body">
                    <?php if ($foundItems): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-dark">
                                <tr><th>Name</th><th>Description</th><th>Date Found</th><th>Status</th><th>Submitted</th><th>Actions</th></tr>
                                </thead>
                                <tbody>
                                <?php foreach ($foundItems as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td><?= htmlspecialchars(substr($item['description'],0,50)) ?>...</td>
                                        <td><?= formatDate($item['date_found']) ?></td>
                                        <td><?= getStatusBadge($item['status']) ?></td>
                                        <td><?= formatDate($item['created_at']) ?></td>
                                        <td><a class="btn btn-sm btn-info" href="view_found_item.php?id=<?= $item['id'] ?>">View</a></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>You haven't reported any found items yet.</p>
                        <a class="btn btn-primary" href="report_found.php">Report Found Item</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- My Claim Requests -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">My Claim Requests</div>
                <div class="card-body">
                    <?php if ($claimRequests): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-light">
                                <tr><th>Name</th><th>Description</th><th>Status</th><th>Scheduled</th><th>Request Date</th><th>Actions</th></tr>
                                </thead>
                                <tbody>
                                <?php foreach ($claimRequests as $claim): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($claim['item_name']) ?></td>
                                        <td><?= htmlspecialchars(substr($claim['description'],0,50)) ?>...</td>
                                        <td><?= getStatusBadge($claim['status']) ?></td>
                                        <td><?= formatDateTime($claim['schedule_date']) ?></td>
                                        <td><?= formatDate($claim['created_at']) ?></td>
                                        <td><a class="btn btn-sm btn-info" href="view_claim.php?id=<?= $claim['id'] ?>">View Details</a></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No claim requests yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4 text-center">
        <div class="col">
            <a class="btn btn-success me-2 mb-2" href="report_lost.php">Report Lost Item</a>
            <a class="btn btn-success me-2 mb-2" href="report_found.php">Report Found Item</a>
            <a class="btn btn-secondary me-2 mb-2" href="lost_items.php">Browse Lost Items</a>
            <a class="btn btn-secondary me-2 mb-2" href="found_items.php">Browse Found Items</a>
        </div>
    </div>

</main>

<footer class="bg-dark text-white text-center py-3 mt-4">&copy; 2024 Campus Lost & Found System</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
