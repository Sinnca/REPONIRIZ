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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-blue: #0066FF;
            --secondary-blue: #E6F0FF;
            --dark-blue: #003D99;
            --light-blue: #F0F7FF;
            --accent-blue: #3385FF;
            --text-dark: #1a1a2e;
            --text-light: #6B7280;
            --white: #FFFFFF;
            --border-color: #E5E7EB;
            --gold: #FDB813;
            --navy: #002D72;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #F5F5F5;
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            min-height: 100vh;
            font-size: 15px;
            line-height: 1.6;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                linear-gradient(135deg, rgba(0, 102, 255, 0.02) 0%, transparent 50%),
                repeating-linear-gradient(
                    0deg,
                    transparent,
                    transparent 2px,
                    rgba(0, 102, 255, 0.01) 2px,
                    rgba(0, 102, 255, 0.01) 4px
                );
            pointer-events: none;
            z-index: 0;
        }

        main, nav, footer {
            position: relative;
            z-index: 1;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        /* Navbar Styles */
        .navbar {
            background: var(--white);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 0.8rem 0;
            border-bottom: 3px solid var(--primary-blue);
        }

        .navbar-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--navy) !important;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            text-transform: uppercase;
        }

        .navbar-brand i {
            color: var(--primary-blue);
            margin-right: 0.5rem;
            font-size: 1.8rem;
        }

        .navbar-nav .nav-link {
            color: var(--text-dark) !important;
            font-weight: 600;
            font-size: 0.85rem;
            margin: 0 0.2rem;
            padding: 0.6rem 1rem !important;
            border-radius: 4px;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .navbar-nav .nav-link:hover {
            background: var(--light-blue);
            color: var(--primary-blue) !important;
        }

        .navbar-nav .nav-link.active {
            background: var(--primary-blue);
            color: var(--white) !important;
            font-weight: 700;
        }

        .navbar-text {
            color: var(--text-dark) !important;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .navbar-text strong {
            color: var(--primary-blue);
            font-weight: 700;
        }

        /* Card Styles */
        .card {
            border: 1px solid var(--border-color);
            border-radius: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            background: var(--white);
            margin-bottom: 24px;
            overflow: hidden;
            transition: all 0.2s ease;
            border-top: 4px solid var(--primary-blue);
        }

        .card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .card-header {
            background: var(--white);
            color: var(--navy);
            border-bottom: 2px solid var(--border-color);
            font-weight: 800;
            font-size: 1rem;
            padding: 1.2rem 1.5rem;
            font-family: 'Space Grotesk', sans-serif;
            letter-spacing: -0.02em;
            text-transform: uppercase;
        }

        .card-header i {
            color: var(--primary-blue);
            margin-right: 0.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Stat Cards */
        .stat-card {
            background: var(--white);
            border: 2px solid var(--border-color);
            border-left: 6px solid var(--primary-blue);
            border-radius: 0;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .stat-card::before {
            display: none;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            border-left-color: var(--gold);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .stat-card h3 {
            font-size: 3rem;
            font-weight: 900;
            color: var(--navy);
            margin-bottom: 0.5rem;
            font-family: 'Space Grotesk', sans-serif;
        }

        .stat-card p {
            color: var(--text-light);
            font-weight: 700;
            font-size: 0.85rem;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .stat-card p i {
            color: var(--primary-blue);
        }

        /* Table Styles */
        .table {
            border-radius: 0;
            overflow: hidden;
            margin-bottom: 0;
        }

        .table thead th {
            background: var(--navy);
            color: var(--white);
            border: none;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            padding: 1rem;
        }

        .table tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid var(--border-color);
        }

        .table tbody tr:hover {
            background: #FAFAFA;
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            font-size: 0.9rem;
        }

        /* Button Styles */
        .btn {
            border-radius: 4px;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 0.6rem 1.4rem;
            transition: all 0.2s ease;
            border: none;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .btn-primary {
            background: var(--primary-blue);
            box-shadow: 0 2px 4px rgba(0, 102, 255, 0.3);
        }

        .btn-primary:hover {
            background: var(--dark-blue);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 102, 255, 0.4);
        }

        .btn-success {
            background: #10B981;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.4);
        }

        .btn-secondary {
            background: #6B7280;
            box-shadow: 0 2px 4px rgba(107, 114, 128, 0.3);
        }

        .btn-secondary:hover {
            background: #4B5563;
            transform: translateY(-1px);
        }

        .btn-info {
            background: #0EA5E9;
            box-shadow: 0 2px 4px rgba(14, 165, 233, 0.3);
        }

        .btn-info:hover {
            background: #0284C7;
            transform: translateY(-1px);
        }

        .btn-warning {
            background: #F59E0B;
            box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
        }

        .btn-warning:hover {
            background: #D97706;
            transform: translateY(-1px);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-blue);
            color: var(--primary-blue);
            background: transparent;
            font-weight: 700;
        }

        .btn-outline-primary:hover {
            background: var(--primary-blue);
            color: var(--white);
            transform: translateY(-1px);
        }

        /* Badge Styles */
        .badge {
            border-radius: 20px;
            padding: 0.4rem 0.9rem;
            font-weight: 600;
            font-size: 0.75rem;
            letter-spacing: 0.3px;
        }

        /* Sidebar Styles */
        .sidebar {
            background: var(--white);
            padding: 1.8rem;
            border-radius: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--gold);
            position: sticky;
            top: 20px;
        }

        .sidebar h5 {
            color: var(--navy);
            font-weight: 800;
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            font-family: 'Space Grotesk', sans-serif;
            letter-spacing: -0.02em;
            text-transform: uppercase;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .sidebar h5 i {
            color: var(--gold);
        }

        .sidebar .btn {
            width: 100%;
            margin-bottom: 0.8rem;
            justify-content: flex-start;
            text-align: left;
            font-size: 0.85rem;
            padding: 0.8rem 1.2rem;
        }

        /* List Group Styles */
        .list-group-item {
            border: none;
            background: #FAFAFA;
            border-left: 3px solid var(--primary-blue);
            margin-bottom: 0.6rem;
            border-radius: 0;
            padding: 1rem 1.2rem;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .list-group-item:hover {
            background: var(--light-blue);
            transform: translateX(4px);
            border-left-color: var(--navy);
        }

        /* Section Title */
        .section-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--navy);
            margin-bottom: 1.5rem;
            text-align: center;
            font-family: 'Space Grotesk', sans-serif;
            letter-spacing: -0.02em;
            text-transform: uppercase;
        }

        .section-title i {
            color: var(--primary-blue);
        }

        /* Footer */
        footer {
            background: var(--navy);
            color: var(--white);
            font-weight: 500;
            padding: 2rem 0;
            margin-top: 3rem;
            box-shadow: 0 -1px 3px rgba(0, 0, 0, 0.1);
            border-top: 3px solid var(--primary-blue);
        }

        footer i {
            color: var(--gold);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                position: static;
                margin-bottom: 20px;
            }

            .stat-card h3 {
                font-size: 2.5rem;
            }

            .navbar-brand {
                font-size: 1.3rem;
            }

            .section-title {
                font-size: 1.6rem;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            animation: fadeInUp 0.5s ease-out;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php"><i class="bi bi-box-seam me-2"></i>Lost & Found</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link active" href="index.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="report_lost.php"><i class="bi bi-exclamation-circle me-1"></i>Report Lost</a></li>
                <li class="nav-item"><a class="nav-link" href="report_found.php"><i class="bi bi-check-circle me-1"></i>Report Found</a></li>
                <li class="nav-item"><a class="nav-link" href="lost_items.php"><i class="bi bi-search me-1"></i>Browse Lost</a></li>
                <li class="nav-item"><a class="nav-link" href="found_items.php"><i class="bi bi-archive me-1"></i>Browse Found</a></li>
                <li class="nav-item"><a class="nav-link" href="my_items.php"><i class="bi bi-person-lines-fill me-1"></i>My Items</a></li>
            </ul>
            <span class="navbar-text me-3"><i class="bi bi-person-circle me-2"></i>Welcome, <strong><?= htmlspecialchars($userName) ?></strong></span>
            <a class="btn btn-outline-primary btn-sm" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </div>
</nav>

<main class="container-fluid" style="padding: 2rem;">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 col-md-4 col-12 mb-4">
            <div class="sidebar">
                <h5><i class="bi bi-lightning-charge-fill me-2"></i>Quick Actions</h5>
                <a class="btn btn-success" href="report_lost.php"><i class="bi bi-exclamation-triangle-fill me-2"></i>Report Lost Item</a>
                <a class="btn btn-success" href="report_found.php"><i class="bi bi-bag-check-fill me-2"></i>Report Found Item</a>
                <a class="btn btn-secondary" href="lost_items.php"><i class="bi bi-list-ul me-2"></i>Browse Lost Items</a>
                <a class="btn btn-secondary" href="found_items.php"><i class="bi bi-inbox-fill me-2"></i>Browse Found Items</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9 col-md-8 col-12">
            <!-- Quick Stats -->
            <div class="row mb-4">
                <div class="col-12 mb-4">
                    <h2 class="section-title"><i class="bi bi-graph-up me-2"></i>Dashboard Overview</h2>
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="stat-card">
                        <h3><?= count($lostItems) ?></h3>
                        <p><i class="bi bi-exclamation-circle me-2"></i>Lost Items Reported</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="stat-card">
                        <h3><?= count($foundItems) ?></h3>
                        <p><i class="bi bi-check-circle me-2"></i>Found Items Reported</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-12 mb-3">
                    <div class="stat-card">
                        <h3><?= count($claimRequests) ?></h3>
                        <p><i class="bi bi-clipboard-check me-2"></i>Claim Requests</p>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-bell-fill me-2"></i>Recent Notifications</div>
                        <div class="card-body">
                            <?php if ($notifications): ?>
                                <ul class="list-group">
                                    <?php foreach ($notifications as $notification): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><?= htmlspecialchars($notification['message']) ?></span>
                                            <span class="badge bg-primary"><?= formatDateTime($notification['created_at']) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <p class="mb-0">No notifications yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Lost Items -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-exclamation-diamond-fill me-2"></i>My Lost Items</div>
                        <div class="card-body">
                            <?php if ($lostItems): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                        <tr><th>Name</th><th>Description</th><th>Date Lost</th><th>Status</th><th>Submitted</th><th>Actions</th></tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($lostItems as $item): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($item['item_name']) ?></strong></td>
                                                <td><?= htmlspecialchars(substr($item['description'],0,50)) ?>...</td>
                                                <td><?= formatDate($item['date_lost']) ?></td>
                                                <td><?= getStatusBadge($item['status']) ?></td>
                                                <td><?= formatDate($item['created_at']) ?></td>
                                                <td>
                                                    <a class="btn btn-sm btn-info" href="view_lost_item.php?id=<?= $item['id'] ?>"><i class="bi bi-eye me-1"></i>View</a>
                                                    <?php if ($item['status'] === 'pending'): ?>
                                                        <a class="btn btn-sm btn-warning" href="edit_lost_item.php?id=<?= $item['id'] ?>"><i class="bi bi-pencil me-1"></i>Edit</a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <p>You haven't reported any lost items yet.</p>
                                    <a class="btn btn-primary mt-2" href="report_lost.php"><i class="bi bi-plus-circle me-2"></i>Report Lost Item</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Found Items -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-patch-check-fill me-2"></i>Items I Found</div>
                        <div class="card-body">
                            <?php if ($foundItems): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                        <tr><th>Name</th><th>Description</th><th>Date Found</th><th>Status</th><th>Submitted</th><th>Actions</th></tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($foundItems as $item): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($item['item_name']) ?></strong></td>
                                                <td><?= htmlspecialchars(substr($item['description'],0,50)) ?>...</td>
                                                <td><?= formatDate($item['date_found']) ?></td>
                                                <td><?= getStatusBadge($item['status']) ?></td>
                                                <td><?= formatDate($item['created_at']) ?></td>
                                                <td><a class="btn btn-sm btn-info" href="view_found_item.php?id=<?= $item['id'] ?>"><i class="bi bi-eye me-1"></i>View</a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <p>You haven't reported any found items yet.</p>
                                    <a class="btn btn-primary mt-2" href="report_found.php"><i class="bi bi-plus-circle me-2"></i>Report Found Item</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Claim Requests -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-clipboard2-check-fill me-2"></i>My Claim Requests</div>
                        <div class="card-body">
                            <?php if ($claimRequests): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                        <tr><th>Name</th><th>Description</th><th>Status</th><th>Scheduled</th><th>Request Date</th><th>Actions</th></tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($claimRequests as $claim): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($claim['item_name']) ?></strong></td>
                                                <td><?= htmlspecialchars(substr($claim['description'],0,50)) ?>...</td>
                                                <td><?= getStatusBadge($claim['status']) ?></td>
                                                <td><?= formatDateTime($claim['schedule_date']) ?></td>
                                                <td><?= formatDate($claim['created_at']) ?></td>
                                                <td><a class="btn btn-sm btn-info" href="view_claim.php?id=<?= $claim['id'] ?>"><i class="bi bi-eye me-1"></i>View Details</a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <p class="mb-0">No claim requests yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<footer class="text-center">
    <div class="container">
        <p class="mb-0"><i class="bi bi-shield-check me-2"></i>&copy; 2024 Campus Lost & Found System. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>