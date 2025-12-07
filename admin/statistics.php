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
</head>
<body>

<header>
    <nav>
        <h1>Lost & Found System - Admin</h1>
        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="pending_lost.php">Pending Lost Items</a></li>
            <li><a href="pending_found.php">Pending Found Items</a></li>
            <li><a href="claim_requests.php">Claim Requests</a></li>
            <li><a href="all_items.php">All Items</a></li>
            <li><a href="statistics.php">Statistics</a></li>
        </ul>
        <div>
            <span>Admin: <?php echo htmlspecialchars(getCurrentUserName()); ?></span>
            <a href="../logout.php">Logout</a>
        </div>
    </nav>
</header>

<main>

    <h1>System Statistics</h1>

    <a href="index.php">&larr; Back to Dashboard</a>

    <!-- Overall Statistics -->
    <section>
        <h2>Overall Statistics</h2>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats['total_users']; ?></h3>
                <p>Total Students</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['total_lost']; ?></h3>
                <p>Total Lost Items</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['total_found']; ?></h3>
                <p>Total Found Items</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['total_claims']; ?></h3>
                <p>Total Claims</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['items_returned']; ?></h3>
                <p>Items Returned</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $successRate; ?>%</h3>
                <p>Success Rate</p>
            </div>
        </div>
    </section>

    <!-- Recent Activity (Last 30 Days) -->
    <section>
        <h2>Last 30 Days Activity</h2>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $recentStats['lost_items_30d']; ?></h3>
                <p>Lost Items Reported</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $recentStats['found_items_30d']; ?></h3>
                <p>Found Items Reported</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $recentStats['claims_30d']; ?></h3>
                <p>Claims Submitted</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $recentStats['returned_30d']; ?></h3>
                <p>Items Returned</p>
            </div>
        </div>
    </section>

    <!-- Lost Items by Status -->
    <section>
        <h2>Lost Items by Status</h2>

        <table>
            <thead>
            <tr>
                <th>Status</th>
                <th>Count</th>
                <th>Percentage</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($lostByStatus as $status => $count): ?>
                <tr>
                    <td><?php echo ucfirst(str_replace('_', ' ', $status)); ?></td>
                    <td><?php echo $count; ?></td>
                    <td>
                        <?php
                        $percentage = $stats['total_lost'] > 0 ? round(($count / $stats['total_lost']) * 100, 1) : 0;
                        echo $percentage . '%';
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- Found Items by Status -->
    <section>
        <h2>Found Items by Status</h2>

        <table>
            <thead>
            <tr>
                <th>Status</th>
                <th>Count</th>
                <th>Percentage</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($foundByStatus as $status => $count): ?>
                <tr>
                    <td><?php echo ucfirst(str_replace('_', ' ', $status)); ?></td>
                    <td><?php echo $count; ?></td>
                    <td>
                        <?php
                        $percentage = $stats['total_found'] > 0 ? round(($count / $stats['total_found']) * 100, 1) : 0;
                        echo $percentage . '%';
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- Claims by Status -->
    <section>
        <h2>Claims by Status</h2>

        <table>
            <thead>
            <tr>
                <th>Status</th>
                <th>Count</th>
                <th>Percentage</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($claimsByStatus as $status => $count): ?>
                <tr>
                    <td><?php echo ucfirst($status); ?></td>
                    <td><?php echo $count; ?></td>
                    <td>
                        <?php
                        $percentage = $stats['total_claims'] > 0 ? round(($count / $stats['total_claims']) * 100, 1) : 0;
                        echo $percentage . '%';
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- Top Active Users -->
    <section>
        <h2>Most Active Users (Top 5)</h2>

        <?php if (count($topUsers) > 0): ?>
            <table>
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Lost Items Reported</th>
                    <th>Found Items Reported</th>
                    <th>Total</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($topUsers as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo $user['lost_items']; ?></td>
                        <td><?php echo $user['found_items']; ?></td>
                        <td><strong><?php echo $user['total_items']; ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No activity data available yet.</p>
        <?php endif; ?>
    </section>

</main>

<footer>
    <p>&copy; 2024 Campus Lost & Found System - Admin Panel</p>
</footer>

</body>
</html>