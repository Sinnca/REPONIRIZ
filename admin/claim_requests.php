<?php
global $pdo;
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

// Filter parameter
$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : 'pending';

// Build WHERE clause
$whereClause = '';
if ($filter === 'pending') $whereClause = "WHERE cr.status = 'pending'";
elseif ($filter === 'scheduled') $whereClause = "WHERE cr.status = 'scheduled'";
elseif ($filter === 'completed') $whereClause = "WHERE cr.status = 'completed'";
elseif ($filter === 'all') $whereClause = '';

// Fetch claim requests (LEFT JOIN for lost_item_id to allow null)
$stmt = $pdo->prepare("
SELECT cr.*, u.name as requester_name, u.email as requester_email
FROM claim_requests cr
LEFT JOIN users u ON cr.requester_id = u.id
$whereClause
ORDER BY cr.created_at DESC
");
$stmt->execute();
$claimRequests = $stmt->fetchAll();

// Counts for tabs
$counts = [
        'pending' => $pdo->query("SELECT COUNT(*) FROM claim_requests WHERE status = 'pending'")->fetchColumn(),
        'scheduled' => $pdo->query("SELECT COUNT(*) FROM claim_requests WHERE status = 'scheduled'")->fetchColumn(),
        'completed' => $pdo->query("SELECT COUNT(*) FROM claim_requests WHERE status = 'completed'")->fetchColumn(),
        'all' => $pdo->query("SELECT COUNT(*) FROM claim_requests")->fetchColumn()
];

// Custom function for colored status badges
function getColoredStatusBadge($status) {
    $statusConfig = [
        'pending' => ['class' => 'badge bg-warning text-dark', 'text' => 'Pending', 'icon' => 'clock-history'],
        'scheduled' => ['class' => 'badge bg-info text-dark', 'text' => 'Scheduled', 'icon' => 'calendar-check'],
        'completed' => ['class' => 'badge bg-success', 'text' => 'Completed', 'icon' => 'check-circle-fill'],
        'rejected' => ['class' => 'badge bg-danger', 'text' => 'Rejected', 'icon' => 'x-circle-fill'],
        'cancelled' => ['class' => 'badge bg-secondary', 'text' => 'Cancelled', 'icon' => 'dash-circle-fill'],
    ];
    
    $config = $statusConfig[$status] ?? ['class' => 'badge bg-secondary', 'text' => ucwords(str_replace('_', ' ', $status)), 'icon' => 'circle-fill'];
    return '<span class="' . $config['class'] . '"><i class="bi bi-' . $config['icon'] . ' me-1"></i>' . $config['text'] . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Requests - Admin</title>
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
        
        h2 {
            color: var(--primary-blue);
            font-weight: 700;
            font-size: 2rem;
            letter-spacing: -0.5px;
            margin-bottom: 0;
        }
        
        .page-header {
            margin-bottom: 24px;
        }
        
        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }
        
        .filter-tabs a {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9375rem;
            transition: all 0.2s ease;
            background: white;
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .filter-tabs a:hover {
            background: #f9fafb;
            border-color: var(--primary-blue);
            transform: translateY(-1px);
        }
        
        .filter-tabs a.active {
            background: var(--primary-blue);
            color: white;
            border-color: var(--primary-blue);
            box-shadow: 0 2px 8px rgba(0, 61, 165, 0.25);
        }
        
        .filter-tabs a .count-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8125rem;
            font-weight: 600;
        }
        
        .filter-tabs a.active .count-badge {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .filter-tabs a:not(.active) .count-badge {
            background: #f3f4f6;
            color: var(--text-secondary);
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
        
        img.thumb { 
            width: 60px; 
            height: 60px; 
            object-fit: cover; 
            border-radius: 8px;
            border: 1px solid var(--border-color);
            transition: transform 0.2s ease;
        }
        
        img.thumb:hover {
            transform: scale(1.5);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            cursor: pointer;
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
        
        .table-responsive {
            border-radius: 8px;
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
        
        .alert {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            padding: 20px;
            background: white;
        }
        
        .alert-info {
            border-left: 4px solid var(--primary-blue);
            background: #f0f7ff;
            color: var(--text-primary);
        }
        
        .text-muted {
            color: var(--text-secondary) !important;
            font-size: 0.875rem;
        }
        
        .guidelines-section {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--primary-blue);
        }
        
        .guidelines-section h4 {
            color: var(--primary-blue);
            font-weight: 600;
            font-size: 1.125rem;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .guidelines-section ul {
            margin-bottom: 0;
            padding-left: 24px;
        }
        
        .guidelines-section li {
            color: var(--text-primary);
            margin-bottom: 8px;
            line-height: 1.6;
        }
        
        .guidelines-section li::marker {
            color: var(--primary-blue);
        }
        
        .guidelines-section strong {
            color: var(--primary-blue);
            font-weight: 600;
        }
        
        footer {
            background: var(--primary-blue);
            color: white;
            text-align: center;
            padding: 24px;
            margin-top: 48px;
            font-size: 0.9375rem;
        }
        
        .no-image-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            background: #f3f4f6;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-size: 0.75rem;
        }
        
        /* Enhanced Status Badge Styles */
        .badge {
            padding: 7px 14px;
            font-weight: 600;
            font-size: 0.8125rem;
            border-radius: 6px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .badge i {
    display: none !important;
}

        
        .bg-warning {
            background-color: #fbbf24 !important;
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
            
            h2 {
                font-size: 1.5rem;
            }
            
            .filter-tabs {
                gap: 6px;
            }
            
            .filter-tabs a {
                padding: 8px 14px;
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body class="bg-light">

<?php include '../components/sidebar.php'; ?>

<main class="content-wrapper">

    <div class="page-header">
        <h2>Claim Requests</h2>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="?filter=pending" class="<?= $filter === 'pending' ? 'active' : ''; ?>">
            <i class="bi bi-clock-history"></i>
            Pending
            <span class="count-badge"><?= $counts['pending']; ?></span>
        </a>
        <a href="?filter=scheduled" class="<?= $filter === 'scheduled' ? 'active' : ''; ?>">
            <i class="bi bi-calendar-check"></i>
            Scheduled
            <span class="count-badge"><?= $counts['scheduled']; ?></span>
        </a>
        <a href="?filter=completed" class="<?= $filter === 'completed' ? 'active' : ''; ?>">
            <i class="bi bi-check-circle"></i>
            Completed
            <span class="count-badge"><?= $counts['completed']; ?></span>
        </a>
        <a href="?filter=all" class="<?= $filter === 'all' ? 'active' : ''; ?>">
            <i class="bi bi-list-ul"></i>
            All
            <span class="count-badge"><?= $counts['all']; ?></span>
        </a>
    </div>

    <!-- Claim Requests Table -->
    <?php if (count($claimRequests) > 0): ?>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Photo</th>
                        <th>Item Name</th>
                        <th>Description</th>
                        <th>Requested By</th>
                        <th>Status</th>
                        <th>Scheduled Date</th>
                        <th>Request Date</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($claimRequests as $claim): ?>
                        <tr>
                            <td><strong>#<?= $claim['id']; ?></strong></td>
                            <td>
                                <?php if ($claim['photo']): ?>
                                    <img src="<?= getImageUrl($claim['photo'], 'claim'); ?>" class="thumb" alt="Item Photo">
                                <?php else: ?>
                                    <div class="no-image-placeholder">
                                        <i class="bi bi-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($claim['item_name']); ?></strong></td>
                            <td><?= htmlspecialchars(substr($claim['item_description'], 0, 60)) . '...'; ?></td>
                            <td>
                                <?= htmlspecialchars($claim['requester_name']); ?><br>
                                <small class="text-muted"><?= htmlspecialchars($claim['requester_email']); ?></small>
                            </td>
                            <td><?= getColoredStatusBadge($claim['status']); ?></td>
                            <td>
                                <?= $claim['schedule_date'] ? formatDateTime($claim['schedule_date']) : '<span class="text-muted">Not scheduled</span>'; ?>
                            </td>
                            <td><?= formatDateTime($claim['created_at']); ?></td>
                            <td>
                                <a href="review_claim.php?id=<?= $claim['id']; ?>" class="btn btn-sm btn-success">
                                    <i class="bi bi-<?php
                                    if ($claim['status'] === 'pending') echo 'eye';
                                    elseif ($claim['status'] === 'scheduled') echo 'check-circle';
                                    else echo 'file-text';
                                    ?> me-1"></i>
                                    <?php
                                    if ($claim['status'] === 'pending') echo 'Review';
                                    elseif ($claim['status'] === 'scheduled') echo 'Complete';
                                    else echo 'View';
                                    ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            No <?= $filter === 'all' ? '' : $filter; ?> claim requests found.
        </div>
    <?php endif; ?>

    <!-- Guidelines -->
    <section class="mt-4">
        <div class="guidelines-section">
            <h4>
                <i class="bi bi-clipboard-check"></i>
                Claim Management Guidelines
            </h4>
            <ul>
                <li><strong>Pending:</strong> Review ownership details and approve/reject claims</li>
                <li><strong>Scheduled:</strong> Claims approved and scheduled for pickup</li>
                <li><strong>Completed:</strong> Items successfully claimed and returned</li>
                <li>Verify student ID and proof of ownership during claiming</li>
                <li>Update claim status after item is verified</li>
            </ul>
        </div>
    </section>

</main>

<footer>
    &copy; 2024 Campus Lost & Found System - Admin Panel
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>