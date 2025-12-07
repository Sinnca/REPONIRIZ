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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .stats-card { 
            text-align: center; 
            padding: 20px; 
            border-radius: 12px; 
            background: white;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        .stats-card h3 {
            color: #1f2937;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .stats-card p {
            color: #6b7280;
            margin: 0;
            font-size: 14px;
        }
        .table-actions a { 
            margin-right: 5px; 
        }
        .content-wrapper {
            padding: 30px;
        }
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .card-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            color: #1f2937;
        }
    </style>
</head>
<body class="bg-light">

<?php include '../components/sidebar.php'; ?>

<main class="content-wrapper">

    <h1 class="mb-4">Admin Dashboard</h1>

    <!-- Statistics Overview -->
    <section class="mb-5">
        <h2 class="section-title">System Statistics</h2>
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
        <h2 class="section-title">Actions Required</h2>

        <!-- Pending Lost Items -->
        <div class="card mb-4">
            <div class="card-header">
                Pending Lost Items (<?php echo $stats['pending_lost']; ?>)
            </div>
            <div class="card-body">
                <?php if ($pendingLostItems): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
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
                    <div class="mt-3">
                        <a href="pending_lost.php" class="btn btn-primary btn-sm">View All</a>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No pending lost items.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pending Found Items -->
        <div class="card mb-4">
            <div class="card-header">
                Pending Found Items (<?php echo $stats['pending_found']; ?>)
            </div>
            <div class="card-body">
                <?php if ($pendingFoundItems): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
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
                    <div class="mt-3">
                        <a href="pending_found.php" class="btn btn-primary btn-sm">View All</a>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No pending found items.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pending Claim Requests -->
        <div class="card mb-4">
            <div class="card-header">
                Pending Claim Requests (<?php echo $stats['pending_claims']; ?>)
            </div>
            <div class="card-body">
                <?php if ($pendingClaims): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
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
                    <div class="mt-3">
                        <a href="claim_requests.php" class="btn btn-primary btn-sm">View All</a>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No pending claim requests.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Recent Activities -->
    <section class="mb-5">
        <h2 class="section-title">Recent System Activities</h2>
        <div class="card">
            <div class="card-body">
                <?php if ($recentActivities): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
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
                    <p class="text-muted mb-0">No recent activities.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Quick Links -->
    <section>
        <h2 class="section-title">Quick Actions</h2>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" href="pending_lost.php">
                <i class="bi bi-search me-1"></i> Review Pending Lost Items
            </a>
            <a class="btn btn-outline-primary" href="pending_found.php">
                <i class="bi bi-bag-check me-1"></i> Review Pending Found Items
            </a>
            <a class="btn btn-outline-primary" href="claim_requests.php">
                <i class="bi bi-journal-text me-1"></i> Review Claim Requests
            </a>
            <a class="btn btn-outline-primary" href="all_items.php">
                <i class="bi bi-collection me-1"></i> View All Items
            </a>
            <a class="btn btn-outline-primary" href="statistics.php">
                <i class="bi bi-graph-up me-1"></i> View Statistics
            </a>
        </div>
    </section>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>