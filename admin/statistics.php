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
    <link rel="stylesheet" href="../assets/css/admin_statistics.css">

    <style>
        .content-wrapper {
            padding: 30px;
        }
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
            font-size: 2rem;
        }
        .stats-card p {
            color: #6b7280;
            margin: 0;
            font-size: 14px;
        }
    </style>
</head>
<body class="bg-light">

<?php include '../components/sidebar.php'; ?>

<main class="content-wrapper">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>System Statistics & Analytics</h2>
    </div>

    <!-- Overall Statistics -->
    <section class="mb-5">
        <h4 class="mb-3">Overall Statistics</h4>
        <div class="row">
            <div class="col-md-2 col-sm-6">
                <div class="stats-card">
                    <h3><?php echo $stats['total_users']; ?></h3>
                    <p>Total Students</p>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="stats-card">
                    <h3><?php echo $stats['total_lost']; ?></h3>
                    <p>Total Lost Items</p>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="stats-card">
                    <h3><?php echo $stats['total_found']; ?></h3>
                    <p>Total Found Items</p>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="stats-card">
                    <h3><?php echo $stats['total_claims']; ?></h3>
                    <p>Total Claims</p>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="stats-card">
                    <h3><?php echo $stats['items_returned']; ?></h3>
                    <p>Items Returned</p>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="stats-card">
                    <h3><?php echo $successRate; ?>%</h3>
                    <p>Success Rate</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Activity (Last 30 Days) -->
    <section class="mb-5">
        <h4 class="mb-3">Last 30 Days Activity</h4>
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <h3><?php echo $recentStats['lost_items_30d']; ?></h3>
                    <p>Lost Items Reported</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <h3><?php echo $recentStats['found_items_30d']; ?></h3>
                    <p>Found Items Reported</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <h3><?php echo $recentStats['claims_30d']; ?></h3>
                    <p>Claims Submitted</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <h3><?php echo $recentStats['returned_30d']; ?></h3>
                    <p>Items Returned</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Status Breakdown Tables -->
    <div class="row mb-5">
        <!-- Lost Items by Status -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Lost Items by Status</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Status</th>
                                <th class="text-center">Count</th>
                                <th class="text-center">Percentage</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($lostByStatus as $status => $count): ?>
                                <tr>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $status)); ?></td>
                                    <td class="text-center"><strong><?php echo $count; ?></strong></td>
                                    <td class="text-center">
                                        <?php
                                        $percentage = $stats['total_lost'] > 0 ? round(($count / $stats['total_lost']) * 100, 1) : 0;
                                        ?>
                                        <span class="badge bg-primary"><?php echo $percentage; ?>%</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Found Items by Status -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Found Items by Status</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Status</th>
                                <th class="text-center">Count</th>
                                <th class="text-center">Percentage</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($foundByStatus as $status => $count): ?>
                                <tr>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $status)); ?></td>
                                    <td class="text-center"><strong><?php echo $count; ?></strong></td>
                                    <td class="text-center">
                                        <?php
                                        $percentage = $stats['total_found'] > 0 ? round(($count / $stats['total_found']) * 100, 1) : 0;
                                        ?>
                                        <span class="badge bg-success"><?php echo $percentage; ?>%</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Claims by Status -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Claims by Status</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Status</th>
                                <th class="text-center">Count</th>
                                <th class="text-center">Percentage</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($claimsByStatus as $status => $count): ?>
                                <tr>
                                    <td><?php echo ucfirst($status); ?></td>
                                    <td class="text-center"><strong><?php echo $count; ?></strong></td>
                                    <td class="text-center">
                                        <?php
                                        $percentage = $stats['total_claims'] > 0 ? round(($count / $stats['total_claims']) * 100, 1) : 0;
                                        ?>
                                        <span class="badge bg-warning text-dark"><?php echo $percentage; ?>%</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Active Users -->
    <section>
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Most Active Users (Top 5)</h5>
            </div>
            <div class="card-body p-0">
                <?php if (count($topUsers) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Rank</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th class="text-center">Lost Items</th>
                                <th class="text-center">Found Items</th>
                                <th class="text-center">Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php 
                            $rank = 1;
                            foreach ($topUsers as $user): 
                            ?>
                                <tr>
                                    <td>
                                        <?php if ($rank == 1): ?>
                                            <span class="badge bg-warning text-dark fs-6">🥇 <?php echo $rank; ?></span>
                                        <?php elseif ($rank == 2): ?>
                                            <span class="badge bg-secondary fs-6">🥈 <?php echo $rank; ?></span>
                                        <?php elseif ($rank == 3): ?>
                                            <span class="badge text-white fs-6" style="background: #cd7f32;">🥉 <?php echo $rank; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark"><?php echo $rank; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small></td>
                                    <td class="text-center">
                                        <span class="badge bg-danger"><?php echo $user['lost_items']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success"><?php echo $user['found_items']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <strong class="text-primary fs-5"><?php echo $user['total_items']; ?></strong>
                                    </td>
                                </tr>
                            <?php 
                            $rank++;
                            endforeach; 
                            ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info m-3">
                        <i class="bi bi-info-circle"></i> No activity data available yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

</main>

<footer class="bg-dark text-white text-center py-3">
    &copy; 2024 Campus Lost & Found System - Admin Panel
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>