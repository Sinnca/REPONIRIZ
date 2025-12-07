<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

$userName = getCurrentUserName();

// Get statistics
$statsQueries = [
        'total_lost' => "SELECT COUNT(*) as count FROM lost_items",
        'total_found' => "SELECT COUNT(*) as count FROM found_items",
        'pending_lost' => "SELECT COUNT(*) as count FROM lost_items WHERE status = 'pending'",
        'pending_found' => "SELECT COUNT(*) as count FROM found_items WHERE status = 'pending'",
        'pending_claims' => "SELECT COUNT(*) as count FROM claim_requests WHERE status = 'pending'",
        'ready_for_claim' => "SELECT COUNT(*) as count FROM lost_items WHERE status = 'ready_for_claim'",
        'returned_items' => "SELECT COUNT(*) as count FROM lost_items WHERE status = 'returned'",
        'total_users' => "SELECT COUNT(*) as count FROM users WHERE role = 'student'"
];

$stats = [];
foreach ($statsQueries as $key => $query) {
    $result = $pdo->query($query)->fetch();
    $stats[$key] = $result['count'];
}

// Pending Lost Items
$stmtPendingLost = $pdo->prepare("
    SELECT li.*, u.name as submitter_name 
    FROM lost_items li
    JOIN users u ON li.user_id = u.id
    WHERE li.status = 'pending'
    ORDER BY li.created_at DESC
    LIMIT 5
");
$stmtPendingLost->execute();
$pendingLostItems = $stmtPendingLost->fetchAll();

// Pending Found Items
$stmtPendingFound = $pdo->prepare("
    SELECT fi.*, u.name as finder_name 
    FROM found_items fi
    JOIN users u ON fi.user_id = u.id
    WHERE fi.status = 'pending'
    ORDER BY fi.created_at DESC
    LIMIT 5
");
$stmtPendingFound->execute();
$pendingFoundItems = $stmtPendingFound->fetchAll();

// Pending Claims
$stmtPendingClaims = $pdo->prepare("
    SELECT cr.*, u.name as requester_name
    FROM claim_requests cr
    LEFT JOIN users u ON cr.requester_id = u.id
    WHERE cr.status = 'pending'
    ORDER BY cr.created_at DESC
    LIMIT 5
");

$stmtPendingClaims->execute();
$pendingClaims = $stmtPendingClaims->fetchAll();

// Recent Activities
$stmtRecentActivities = $pdo->prepare("
    (SELECT 'lost' as type, id, item_name as name, created_at, status 
     FROM lost_items ORDER BY created_at DESC LIMIT 5)
    UNION ALL
    (SELECT 'found' as type, id, item_name as name, created_at, status 
     FROM found_items ORDER BY created_at DESC LIMIT 5)
    ORDER BY created_at DESC
    LIMIT 10
");
$stmtRecentActivities->execute();
$recentActivities = $stmtRecentActivities->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .stats-card { text-align: center; padding: 20px; border-radius: 8px; background: #f8f9fa; margin-bottom: 15px; }
        .table-actions a { margin-right: 5px; }
    </style>
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">Admin Dashboard</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" href="index.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="pending_lost.php">Pending Lost Items</a></li>
                <li class="nav-item"><a class="nav-link" href="pending_found.php">Pending Found Items</a></li>
                <li class="nav-item"><a class="nav-link" href="claim_requests.php">Claim Requests</a></li>
                <li class="nav-item"><a class="nav-link" href="all_items.php">All Items</a></li>
                <li class="nav-item"><a class="nav-link" href="statistics.php">Statistics</a></li>
            </ul>
            <span class="navbar-text me-3">Admin: <?php echo htmlspecialchars($userName); ?></span>
            <a class="btn btn-outline-light" href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<main class="container mb-5">

    <h1 class="mb-4">Admin Dashboard</h1>

    <!-- Statistics Overview -->
    <section class="mb-5">
        <h2>System Statistics</h2>
        <div class="row">
            <?php foreach ($stats as $label => $value): ?>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card">
                        <h3><?php echo $value; ?></h3>
                        <p><?php echo ucwords(str_replace('_', ' ', $label)); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Pending Actions -->
    <section class="mb-5">
        <h2>Actions Required</h2>

        <!-- Pending Lost Items -->
        <div class="mb-4">
            <h4>Pending Lost Items (<?php echo $stats['pending_lost']; ?>)</h4>
            <?php if ($pendingLostItems): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                        <tr>
                            <th>Item Name</th>
                            <th>Submitted By</th>
                            <th>Date Lost</th>
                            <th>Date Submitted</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pendingLostItems as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['submitter_name']); ?></td>
                                <td><?php echo formatDate($item['date_lost']); ?></td>
                                <td><?php echo formatDate($item['created_at']); ?></td>
                                <td class="table-actions">
                                    <a class="btn btn-sm btn-success" href="verify_lost.php?id=<?php echo $item['id']; ?>">Verify</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <a href="pending_lost.php" class="btn btn-primary btn-sm">View All</a>
            <?php else: ?>
                <p>No pending lost items.</p>
            <?php endif; ?>
        </div>

        <!-- Pending Found Items -->
        <div class="mb-4">
            <h4>Pending Found Items (<?php echo $stats['pending_found']; ?>)</h4>
            <?php if ($pendingFoundItems): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                        <tr>
                            <th>Item Name</th>
                            <th>Found By</th>
                            <th>Date Found</th>
                            <th>Date Submitted</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pendingFoundItems as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['finder_name']); ?></td>
                                <td><?php echo formatDate($item['date_found']); ?></td>
                                <td><?php echo formatDate($item['created_at']); ?></td>
                                <td class="table-actions">
                                    <a class="btn btn-sm btn-success" href="verify_found.php?id=<?php echo $item['id']; ?>">Verify</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <a href="pending_found.php" class="btn btn-primary btn-sm">View All</a>
            <?php else: ?>
                <p>No pending found items.</p>
            <?php endif; ?>
        </div>

        <!-- Pending Claim Requests -->
        <div class="mb-4">
            <h4>Pending Claim Requests (<?php echo $stats['pending_claims']; ?>)</h4>
            <?php if ($pendingClaims): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                        <tr>
                            <th>Item Name</th>
                            <th>Requested By</th>
                            <th>Request Date</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pendingClaims as $claim): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($claim['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($claim['requester_name']); ?></td>
                                <td><?php echo formatDate($claim['created_at']); ?></td>
                                <td class="table-actions">
                                    <a class="btn btn-sm btn-warning" href="review_claim.php?id=<?php echo $claim['id']; ?>">Review</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <a href="claim_requests.php" class="btn btn-primary btn-sm">View All</a>
            <?php else: ?>
                <p>No pending claim requests.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Recent Activities -->
    <section class="mb-5">
        <h2>Recent System Activities</h2>
        <?php if ($recentActivities): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                    <tr>
                        <th>Type</th>
                        <th>Item Name</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentActivities as $activity): ?>
                        <tr>
                            <td><?php echo strtoupper($activity['type']); ?></td>
                            <td><?php echo htmlspecialchars($activity['name']); ?></td>
                            <td><?php echo getStatusBadge($activity['status']); ?></td>
                            <td><?php echo formatDateTime($activity['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No recent activities.</p>
        <?php endif; ?>
    </section>

    <!-- Quick Links -->
    <section>
        <h2>Quick Actions</h2>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" href="pending_lost.php">Review Pending Lost Items</a>
            <a class="btn btn-outline-primary" href="pending_found.php">Review Pending Found Items</a>
            <a class="btn btn-outline-primary" href="claim_requests.php">Review Claim Requests</a>
            <a class="btn btn-outline-primary" href="all_items.php">View All Items</a>
            <a class="btn btn-outline-primary" href="statistics.php">View Statistics</a>
        </div>
    </section>

</main>

<footer class="bg-dark text-white text-center py-3">
    &copy; 2024 Campus Lost & Found System - Admin Panel
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
