<?php
/**
 * Admin - Statistics
 * System statistics and analytics
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

$userName = getCurrentUserName();

// Overall Statistics
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'")->fetch()['count'],
    'total_lost' => $pdo->query("SELECT COUNT(*) as count FROM lost_items")->fetch()['count'],
    'total_found' => $pdo->query("SELECT COUNT(*) as count FROM found_items")->fetch()['count'],
    'total_claims' => $pdo->query("SELECT COUNT(*) as count FROM claim_requests")->fetch()['count'],
    'items_returned' => $pdo->query("SELECT COUNT(*) as count FROM lost_items WHERE status = 'returned'")->fetch()['count'],
];

// Lost Items by Status
$lostByStatus = [
    'pending' => $pdo->query("SELECT COUNT(*) as count FROM lost_items WHERE status = 'pending'")->fetch()['count'],
    'listed' => $pdo->query("SELECT COUNT(*) as count FROM lost_items WHERE status = 'listed'")->fetch()['count'],
    'ready_for_claim' => $pdo->query("SELECT COUNT(*) as count FROM lost_items WHERE status = 'ready_for_claim'")->fetch()['count'],
    'returned' => $pdo->query("SELECT COUNT(*) as count FROM lost_items WHERE status = 'returned'")->fetch()['count'],
    'rejected' => $pdo->query("SELECT COUNT(*) as count FROM lost_items WHERE status = 'rejected'")->fetch()['count'],
];

// Found Items by Status
$foundByStatus = [
    'pending' => $pdo->query("SELECT COUNT(*) as count FROM found_items WHERE status = 'pending'")->fetch()['count'],
    'verified' => $pdo->query("SELECT COUNT(*) as count FROM found_items WHERE status = 'verified'")->fetch()['count'],
    'listed' => $pdo->query("SELECT COUNT(*) as count FROM found_items WHERE status = 'listed'")->fetch()['count'],
    'claimed' => $pdo->query("SELECT COUNT(*) as count FROM found_items WHERE status = 'claimed'")->fetch()['count'],
    'rejected' => $pdo->query("SELECT COUNT(*) as count FROM found_items WHERE status = 'rejected'")->fetch()['count'],
];

// Claims by Status
$claimsByStatus = [
    'pending' => $pdo->query("SELECT COUNT(*) as count FROM claim_requests WHERE status = 'pending'")->fetch()['count'],
    'approved' => $pdo->query("SELECT COUNT(*) as count FROM claim_requests WHERE status = 'approved'")->fetch()['count'],
    'scheduled' => $pdo->query("SELECT COUNT(*) as count FROM claim_requests WHERE status = 'scheduled'")->fetch()['count'],
    'completed' => $pdo->query("SELECT COUNT(*) as count FROM claim_requests WHERE status = 'completed'")->fetch()['count'],
    'rejected' => $pdo->query("SELECT COUNT(*) as count FROM claim_requests WHERE status = 'rejected'")->fetch()['count'],
];

// Recent Activity (last 30 days)
$thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
$recentStats = [
    'lost_items_30d' => $pdo->query("SELECT COUNT(*) as count FROM lost_items WHERE created_at >= '$thirtyDaysAgo'")->fetch()['count'],
    'found_items_30d' => $pdo->query("SELECT COUNT(*) as count FROM found_items WHERE created_at >= '$thirtyDaysAgo'")->fetch()['count'],
    'claims_30d' => $pdo->query("SELECT COUNT(*) as count FROM claim_requests WHERE created_at >= '$thirtyDaysAgo'")->fetch()['count'],
    'returned_30d' => $pdo->query("SELECT COUNT(*) as count FROM lost_items WHERE status = 'returned' AND created_at >= '$thirtyDaysAgo'")->fetch()['count'],
];

// Success Rate
$successRate = $stats['total_lost'] > 0 ? round(($stats['items_returned'] / $stats['total_lost']) * 100, 1) : 0;

// Most Active Users (Top 5 submitters)
$stmtTopUsers = $pdo->query("
    SELECT u.name, u.email, 
           COUNT(DISTINCT li.id) as lost_items,
           COUNT(DISTINCT fi.id) as found_items,
           (COUNT(DISTINCT li.id) + COUNT(DISTINCT fi.id)) as total_items
    FROM users u
    LEFT JOIN lost_items li ON u.id = li.user_id
    LEFT JOIN found_items fi ON u.id = fi.user_id
    WHERE u.role = 'student'
    GROUP BY u.id
    HAVING total_items > 0
    ORDER BY total_items DESC
    LIMIT 5
");
$topUsers = $stmtTopUsers->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin_statistics.css">
</head>
<body class="bg-light">

<?php include '../components/sidebar.php'; ?>

<main class="content-wrapper">

    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h1><i class="bi bi-graph-up-arrow"></i> Statistics & Analytics</h1>
            <p class="header-subtitle">System performance and insights</p>
        </div>
    
    </div>

    <!-- Overall Statistics -->
    <section class="stats-section">
        <div class="section-header">
            <h2><i class="bi bi-bar-chart-fill"></i> Overall System Statistics</h2>
        </div>
        <div class="stats-grid-main">
            <div class="stat-card-large">
                <div class="stat-icon blue">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_users']; ?></h3>
                    <p>Total Students</p>
                </div>
            </div>
            <div class="stat-card-large">
                <div class="stat-icon yellow">
                    <i class="bi bi-search"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_lost']; ?></h3>
                    <p>Total Lost Items</p>
                </div>
            </div>
            <div class="stat-card-large">
                <div class="stat-icon red">
                    <i class="bi bi-bag-check-fill"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_found']; ?></h3>
                    <p>Total Found Items</p>
                </div>
            </div>
            <div class="stat-card-large">
                <div class="stat-icon green">
                    <i class="bi bi-journal-text"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_claims']; ?></h3>
                    <p>Total Claims</p>
                </div>
            </div>
            <div class="stat-card-large">
                <div class="stat-icon purple">
                    <i class="bi bi-box-arrow-in-down"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['items_returned']; ?></h3>
                    <p>Items Returned</p>
                </div>
            </div>
            <div class="stat-card-large success-rate">
                <div class="stat-icon orange">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $successRate; ?>%</h3>
                    <p>Success Rate</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Activity (Last 30 Days) -->
    <section class="recent-section">
        <div class="section-header">
            <h2><i class="bi bi-clock-history"></i> Last 30 Days Activity</h2>
        </div>
        <div class="recent-stats-grid">
            <div class="recent-stat-card">
                <div class="recent-icon lost">
                    <i class="bi bi-search"></i>
                </div>
                <div class="recent-content">
                    <h4><?php echo $recentStats['lost_items_30d']; ?></h4>
                    <p>Lost Items Reported</p>
                </div>
            </div>
            <div class="recent-stat-card">
                <div class="recent-icon found">
                    <i class="bi bi-bag-check"></i>
                </div>
                <div class="recent-content">
                    <h4><?php echo $recentStats['found_items_30d']; ?></h4>
                    <p>Found Items Reported</p>
                </div>
            </div>
            <div class="recent-stat-card">
                <div class="recent-icon claims">
                    <i class="bi bi-journal-text"></i>
                </div>
                <div class="recent-content">
                    <h4><?php echo $recentStats['claims_30d']; ?></h4>
                    <p>Claims Submitted</p>
                </div>
            </div>
            <div class="recent-stat-card">
                <div class="recent-icon returned">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="recent-content">
                    <h4><?php echo $recentStats['returned_30d']; ?></h4>
                    <p>Items Returned</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Status Breakdown Tables -->
    <section class="breakdown-section">
        <div class="section-header">
            <h2><i class="bi bi-pie-chart-fill"></i> Status Breakdown</h2>
        </div>
        <div class="row g-4">
            
            <!-- Lost Items by Status -->
            <div class="col-lg-4">
                <div class="breakdown-card lost-card">
                    <div class="breakdown-header">
                        <i class="bi bi-search"></i>
                        <h3>Lost Items by Status</h3>
                    </div>
                    <div class="breakdown-body">
                        <div class="breakdown-list">
                            <?php foreach ($lostByStatus as $status => $count): ?>
                                <?php $percentage = $stats['total_lost'] > 0 ? round(($count / $stats['total_lost']) * 100, 1) : 0; ?>
                                <div class="breakdown-item">
                                    <div class="breakdown-label">
                                        <span class="status-name"><?php echo ucfirst(str_replace('_', ' ', $status)); ?></span>
                                        <span class="status-count"><?php echo $count; ?></span>
                                    </div>
                                    <div class="breakdown-progress">
                                        <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <span class="breakdown-percentage"><?php echo $percentage; ?>%</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Found Items by Status -->
            <div class="col-lg-4">
                <div class="breakdown-card found-card">
                    <div class="breakdown-header">
                        <i class="bi bi-bag-check"></i>
                        <h3>Found Items by Status</h3>
                    </div>
                    <div class="breakdown-body">
                        <div class="breakdown-list">
                            <?php foreach ($foundByStatus as $status => $count): ?>
                                <?php $percentage = $stats['total_found'] > 0 ? round(($count / $stats['total_found']) * 100, 1) : 0; ?>
                                <div class="breakdown-item">
                                    <div class="breakdown-label">
                                        <span class="status-name"><?php echo ucfirst(str_replace('_', ' ', $status)); ?></span>
                                        <span class="status-count"><?php echo $count; ?></span>
                                    </div>
                                    <div class="breakdown-progress">
                                        <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <span class="breakdown-percentage"><?php echo $percentage; ?>%</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Claims by Status -->
            <div class="col-lg-4">
                <div class="breakdown-card claims-card">
                    <div class="breakdown-header">
                        <i class="bi bi-journal-text"></i>
                        <h3>Claims by Status</h3>
                    </div>
                    <div class="breakdown-body">
                        <div class="breakdown-list">
                            <?php foreach ($claimsByStatus as $status => $count): ?>
                                <?php $percentage = $stats['total_claims'] > 0 ? round(($count / $stats['total_claims']) * 100, 1) : 0; ?>
                                <div class="breakdown-item">
                                    <div class="breakdown-label">
                                        <span class="status-name"><?php echo ucfirst($status); ?></span>
                                        <span class="status-count"><?php echo $count; ?></span>
                                    </div>
                                    <div class="breakdown-progress">
                                        <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <span class="breakdown-percentage"><?php echo $percentage; ?>%</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>

<footer>
    &copy; 2024 Campus Lost & Found System - Admin Panel
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>