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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #F0F7FF 0%, #FFFFFF 50%, #E6F0FF 100%);
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            min-height: 100vh;
            font-size: 15px;
            line-height: 1.6;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
        }

        /* Navbar Styles */
        .navbar {
            background: var(--white);
            box-shadow: 0 2px 20px rgba(0, 102, 255, 0.08);
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .navbar-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 800;
            font-size: 1.6rem;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }

        .navbar-nav .nav-link {
            color: var(--text-dark) !important;
            font-weight: 500;
            font-size: 0.9rem;
            margin: 0 0.3rem;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            background: var(--light-blue);
            color: var(--primary-blue) !important;
        }

        .navbar-nav .nav-link.active {
            background: var(--secondary-blue);
            color: var(--primary-blue) !important;
            font-weight: 600;
        }

        .navbar-text {
            color: var(--text-light) !important;
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* Card Styles */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 102, 255, 0.06);
            background: var(--white);
            margin-bottom: 24px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 32px rgba(0, 102, 255, 0.12);
            transform: translateY(-2px);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            color: var(--white);
            border: none;
            font-weight: 700;
            font-size: 1.1rem;
            padding: 1.2rem 1.5rem;
            font-family: 'Space Grotesk', sans-serif;
            letter-spacing: -0.3px;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Stat Cards */
        .stat-card {
            background: linear-gradient(135deg, var(--white) 0%, var(--light-blue) 100%);
            border: 2px solid var(--secondary-blue);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0, 102, 255, 0.1) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            border-color: var(--primary-blue);
            box-shadow: 0 12px 40px rgba(0, 102, 255, 0.2);
        }

        .stat-card h3 {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            font-family: 'Space Grotesk', sans-serif;
        }

        .stat-card p {
            color: var(--text-light);
            font-weight: 600;
            font-size: 0.95rem;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Table Styles */
        .table {
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            color: var(--white);
            border: none;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            padding: 1rem;
        }

        .table tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid var(--border-color);
        }

        .table tbody tr:hover {
            background: var(--light-blue);
            transform: scale(1.01);
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            font-size: 0.9rem;
        }

        /* Button Styles */
        .btn {
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 0.6rem 1.4rem;
            transition: all 0.3s ease;
            border: none;
            letter-spacing: 0.3px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            box-shadow: 0 4px 12px rgba(0, 102, 255, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary-blue) 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 102, 255, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6B7280 0%, #4B5563 100%);
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #4B5563 0%, #374151 100%);
            transform: translateY(-2px);
        }

        .btn-info {
            background: linear-gradient(135deg, #0EA5E9 0%, #0284C7 100%);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
        }

        .btn-info:hover {
            background: linear-gradient(135deg, #0284C7 0%, #0369A1 100%);
            transform: translateY(-2px);
        }

        .btn-warning {
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #D97706 0%, #B45309 100%);
            transform: translateY(-2px);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-blue);
            color: var(--primary-blue);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: var(--primary-blue);
            color: var(--white);
            transform: translateY(-2px);
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
            border-radius: 20px;
            box-shadow: 0 4px 24px rgba(0, 102, 255, 0.08);
            border: 1px solid var(--border-color);
            position: sticky;
            top: 20px;
        }

        .sidebar h5 {
            color: var(--primary-blue);
            font-weight: 800;
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            font-family: 'Space Grotesk', sans-serif;
            letter-spacing: -0.5px;
        }

        .sidebar .btn {
            width: 100%;
            margin-bottom: 0.8rem;
            justify-content: flex-start;
            text-align: left;
            font-size: 0.95rem;
            padding: 0.8rem 1.2rem;
        }

        /* List Group Styles */
        .list-group-item {
            border: none;
            background: var(--light-blue);
            margin-bottom: 0.6rem;
            border-radius: 12px;
            padding: 1rem 1.2rem;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .list-group-item:hover {
            background: var(--secondary-blue);
            transform: translateX(4px);
        }

        /* Section Title */
        .section-title {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
            text-align: center;
            font-family: 'Space Grotesk', sans-serif;
            letter-spacing: -0.8px;
        }

        /* Footer */
        footer {
            background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary-blue) 100%);
            color: var(--white);
            font-weight: 500;
            padding: 1.5rem 0;
            margin-top: 3rem;
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