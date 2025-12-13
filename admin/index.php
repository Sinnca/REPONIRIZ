<?php
global $pdo;
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

// Custom function for colored status badges
function getColoredStatusBadge($status) {
    $statusConfig = [
        'pending' => ['class' => 'badge bg-warning text-dark', 'text' => 'Pending'],
        'verified' => ['class' => 'badge bg-primary', 'text' => 'Verified'],
        'ready_for_claim' => ['class' => 'badge bg-info text-dark', 'text' => 'Ready for Claim'],
        'claimed' => ['class' => 'badge bg-success', 'text' => 'Claimed'],
        'returned' => ['class' => 'badge bg-success', 'text' => 'Returned'],
        'rejected' => ['class' => 'badge bg-danger', 'text' => 'Rejected'],
        'archived' => ['class' => 'badge bg-secondary', 'text' => 'Archived'],
    ];
    
    $config = $statusConfig[$status] ?? ['class' => 'badge bg-secondary', 'text' => ucwords(str_replace('_', ' ', $status))];
    return '<span class="' . $config['class'] . '">' . $config['text'] . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
     <link rel="stylesheet" href="../assets/css/admin_index.css">
    
</head>
<body class="bg-light">

<?php include '../components/sidebar.php'; ?>

<main class="content-wrapper">

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="header-left">
            <h1>Admin Dashboard</h1>
            <p class="header-subtitle">Welcome back, <?php echo htmlspecialchars($userName); ?></p>
        </div>
    </div>

    <!-- Statistics Overview -->
    <section class="stats-section">
        <h2 class="section-title">System Overview</h2>
        <div class="stats-grid">
            <?php 
            $statIcons = [
                'total_lost' => 'search',
                'total_found' => 'bag-check',
                'pending_lost' => 'hourglass-split',
                'pending_found' => 'hourglass',
                'pending_claims' => 'journal-text',
                'ready_for_claim' => 'check-circle',
                'returned_items' => 'box-arrow-in-down',
                'total_users' => 'people'
            ];
            foreach ($stats as $label => $value): 
            ?>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-<?php echo $statIcons[$label] ?? 'graph-up'; ?>"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $value; ?></h3>
                        <p><?php echo ucwords(str_replace('_', ' ', $label)); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Two Column Layout -->
    <div class="row g-4">
        
        <!-- Left Column - Pending Items -->
        <div class="col-lg-8">
            
            <section class="content-section">
                <h2 class="section-title">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Actions Required
                </h2>

                <!-- Pending Lost Items -->
                <div class="card action-card">
                    <div class="card-header">
                        <div class="card-header-content">
                            <h3><i class="bi bi-search"></i> Pending Lost Items</h3>
                            <span class="count-badge"><?php echo $stats['pending_lost']; ?></span>
                        </div>
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
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($pendingLostItems as $item): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($item['submitter_name']); ?></td>
                                            <td><?php echo formatDate($item['date_lost']); ?></td>
                                            <td class="table-actions">
                                                <a class="btn btn-sm btn-success" href="verify_lost.php?id=<?php echo $item['id']; ?>">
                                                    <i class="bi bi-check-circle"></i> Verify
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer">
                                <a href="pending_lost.php" class="btn btn-primary btn-sm">
                                    <i class="bi bi-arrow-right"></i> View All Lost Items
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-check-circle"></i>
                                <p>No pending lost items</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pending Found Items -->
                <div class="card action-card">
                    <div class="card-header">
                        <div class="card-header-content">
                            <h3><i class="bi bi-bag-check"></i> Pending Found Items</h3>
                            <span class="count-badge"><?php echo $stats['pending_found']; ?></span>
                        </div>
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
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($pendingFoundItems as $item): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($item['finder_name']); ?></td>
                                            <td><?php echo formatDate($item['date_found']); ?></td>
                                            <td class="table-actions">
                                                <a class="btn btn-sm btn-success" href="verify_found.php?id=<?php echo $item['id']; ?>">
                                                    <i class="bi bi-check-circle"></i> Verify
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer">
                                <a href="pending_found.php" class="btn btn-primary btn-sm">
                                    <i class="bi bi-arrow-right"></i> View All Found Items
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-check-circle"></i>
                                <p>No pending found items</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pending Claim Requests -->
                <div class="card action-card">
                    <div class="card-header">
                        <div class="card-header-content">
                            <h3><i class="bi bi-journal-text"></i> Pending Claim Requests</h3>
                            <span class="count-badge"><?php echo $stats['pending_claims']; ?></span>
                        </div>
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
                                            <td><strong><?php echo htmlspecialchars($claim['item_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($claim['requester_name']); ?></td>
                                            <td><?php echo formatDate($claim['created_at']); ?></td>
                                            <td class="table-actions">
                                                <a class="btn btn-sm btn-warning" href="review_claim.php?id=<?php echo $claim['id']; ?>">
                                                    <i class="bi bi-clipboard-check"></i> Review
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer">
                                <a href="claim_requests.php" class="btn btn-primary btn-sm">
                                    <i class="bi bi-arrow-right"></i> View All Claims
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-check-circle"></i>
                                <p>No pending claim requests</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>

        <!-- Right Column - Activities & Quick Actions -->
        <div class="col-lg-4">
            
            <!-- Recent Activities -->
            <section class="content-section">
                <h2 class="section-title">
                    <i class="bi bi-clock-history"></i>
                    Recent Activities
                </h2>
                <div class="card activity-card">
                    <div class="card-body">
                        <?php if ($recentActivities): ?>
                            <div class="activity-list">
                                <?php foreach ($recentActivities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon <?php echo $activity['type']; ?>">
                                            <i class="bi bi-<?php echo $activity['type'] === 'lost' ? 'search' : 'bag-check'; ?>"></i>
                                        </div>
                                        <div class="activity-content">
                                            <p class="activity-title"><?php echo htmlspecialchars($activity['name']); ?></p>
                                            <div class="activity-meta">
                                                <span class="activity-type"><?php echo strtoupper($activity['type']); ?></span>
                                                <span class="activity-status"><?php echo getColoredStatusBadge($activity['status']); ?></span>
                                            </div>
                                            <p class="activity-date">
                                                <i class="bi bi-clock"></i>
                                                <?php echo formatDateTime($activity['created_at']); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <p>No recent activities</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Quick Actions -->
            <section class="content-section">
                <h2 class="section-title">
                    <i class="bi bi-lightning-charge-fill"></i>
                    Quick Actions
                </h2>
                <div class="quick-actions-grid">
                    <a class="quick-action-btn" href="pending_lost.php">
                        <i class="bi bi-search"></i>
                        <span>Review Lost Items</span>
                    </a>
                    <a class="quick-action-btn" href="pending_found.php">
                        <i class="bi bi-bag-check"></i>
                        <span>Review Found Items</span>
                    </a>
                    <a class="quick-action-btn" href="claim_requests.php">
                        <i class="bi bi-journal-text"></i>
                        <span>Review Claims</span>
                    </a>
                    <a class="quick-action-btn" href="all_items.php">
                        <i class="bi bi-collection"></i>
                        <span>All Items</span>
                    </a>
                    <a class="quick-action-btn" href="statistics.php">
                        <i class="bi bi-graph-up"></i>
                        <span>Statistics</span>
                    </a>
                </div>
            </section>
        </div>
    </div>

</main>

<footer>
    &copy; 2024 Campus Lost & Found System - Admin Panel
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>