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
     <link rel="stylesheet" href="../assets/css/admin_claimreq.css"  
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