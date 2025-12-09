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
    <style>
        :root {
            --primary-yellow: #F7C506;
            --primary-blue: #003DA5;
            --primary-red: #C8102E;
            --bg-primary: #f8f9fa;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
        }
        
        body {
            background: var(--bg-primary);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: var(--text-primary);
        }
        
        .content-wrapper {
            padding: 40px 50px;
            max-width: 1600px;
            margin: 0 auto;
        }
        
        h1 {
            color: var(--primary-blue);
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }
        
        .stats-card { 
            text-align: center; 
            padding: 28px 24px; 
            border-radius: 12px; 
            background: white;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            transition: all 0.2s ease;
            border: 1px solid var(--border-color);
            position: relative;
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary-blue);
            border-radius: 12px 12px 0 0;
        }
        
        .stats-card:nth-child(4n+1)::before {
            background: var(--primary-blue);
        }
        
        .stats-card:nth-child(4n+2)::before {
            background: var(--primary-yellow);
        }
        
        .stats-card:nth-child(4n+3)::before {
            background: var(--primary-red);
        }
        
        .stats-card:nth-child(4n+4)::before {
            background: linear-gradient(90deg, var(--primary-blue), var(--primary-yellow));
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }
        
        .stats-card h3 {
            color: var(--text-primary);
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 2.25rem;
            line-height: 1;
        }
        
        .stats-card p {
            color: var(--text-secondary);
            margin: 0;
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .table-actions a { 
            margin-right: 5px; 
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .card {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            background: white;
            transition: all 0.2s ease;
            margin-bottom: 24px;
        }
        
        .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
            color: var(--primary-blue);
            padding: 18px 24px;
            font-size: 1rem;
            border-radius: 12px 12px 0 0;
        }
        
        .card-body {
            padding: 24px;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            border-bottom: 2px solid var(--border-color);
            border-top: none;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 14px 12px;
            background: #f9fafb;
        }
        
        .table tbody tr {
            transition: background 0.15s ease;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .table tbody tr:hover {
            background: #f9fafb;
        }
        
        .table tbody tr:last-child {
            border-bottom: none;
        }
        
        .table tbody td {
            padding: 16px 12px;
            vertical-align: middle;
            color: var(--text-primary);
            font-size: 0.9375rem;
        }
        
        .btn {
            border-radius: 6px;
            font-weight: 500;
            padding: 7px 16px;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            font-size: 0.875rem;
        }
        
        .btn-success {
            background: var(--primary-yellow);
            color: #1f2937;
            border-color: var(--primary-yellow);
        }
        
        .btn-success:hover {
            background: #e6b505;
            border-color: #e6b505;
            color: #1f2937;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(247, 197, 6, 0.25);
        }
        
        .btn-warning {
            background: var(--primary-red);
            color: white;
            border-color: var(--primary-red);
        }
        
        .btn-warning:hover {
            background: #b00e29;
            border-color: #b00e29;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(200, 16, 46, 0.25);
        }
        
        .btn-primary {
            background: var(--primary-blue);
            color: white;
            border-color: var(--primary-blue);
        }
        
        .btn-primary:hover {
            background: #003494;
            border-color: #003494;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 61, 165, 0.25);
        }
        
        .btn-outline-primary {
            border: 1px solid var(--primary-blue);
            color: var(--primary-blue);
            background: white;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-blue);
            color: white;
            border-color: var(--primary-blue);
            transform: translateY(-1px);
        }
        
        .text-muted {
            color: var(--text-secondary) !important;
        }
        
        .table-responsive {
            border-radius: 8px;
        }
        
        .d-flex.gap-2 {
            gap: 12px !important;
        }
        
        section {
            margin-bottom: 48px;
        }
        
        .mb-4 {
            margin-bottom: 2rem !important;
        }
        
        .mb-5 {
            margin-bottom: 3rem !important;
        }
        
        /* Enhanced Status Badge Styles */
        .badge {
            padding: 6px 12px;
            font-weight: 600;
            font-size: 0.8125rem;
            border-radius: 6px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        
        .bg-warning {
            background-color: #fbbf24 !important;
        }
        
        .bg-primary {
            background-color: var(--primary-blue) !important;
            color: white !important;
        }
        
        .bg-info {
            background-color: #0ea5e9 !important;
        }
        
        .bg-success {
            background-color: #10b981 !important;
            color: white !important;
        }
        
        .bg-danger {
            background-color: var(--primary-red) !important;
            color: white !important;
        }
        
        .bg-secondary {
            background-color: #6b7280 !important;
            color: white !important;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .content-wrapper {
                padding: 24px 20px;
            }
            
            h1 {
                font-size: 1.75rem;
            }
            
            .stats-card h3 {
                font-size: 1.75rem;
            }
        }
        
        /* Print styles for professional reporting */
        @media print {
            .btn, .table-actions {
                display: none;
            }
            
            .card {
                break-inside: avoid;
                box-shadow: none;
                border: 1px solid #ddd;
            }
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
                                    <td><?php echo getColoredStatusBadge($activity['status']); ?></td>
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