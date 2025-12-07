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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Requests - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        img.thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
        .filter-tabs a { margin-right: 10px; text-decoration: none; padding: 6px 12px; border-radius: 4px; }
        .filter-tabs a.active { background-color: #0d6efd; color: #fff; }
    </style>
</head>
<body class="bg-light">

<?php include '../components/sidebar.php'; ?>

<main class="container mb-5">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Claim Requests</h2>
        
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs mb-3">
        <a href="?filter=pending" class="<?= $filter === 'pending' ? 'active' : ''; ?>">Pending (<?= $counts['pending']; ?>)</a>
        <a href="?filter=scheduled" class="<?= $filter === 'scheduled' ? 'active' : ''; ?>">Scheduled (<?= $counts['scheduled']; ?>)</a>
        <a href="?filter=completed" class="<?= $filter === 'completed' ? 'active' : ''; ?>">Completed (<?= $counts['completed']; ?>)</a>
        <a href="?filter=all" class="<?= $filter === 'all' ? 'active' : ''; ?>">All (<?= $counts['all']; ?>)</a>
    </div>

    <!-- Claim Requests Table -->
    <?php if (count($claimRequests) > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-primary">
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
                        <td><?= $claim['id']; ?></td>
                        <td>
                            <?php if ($claim['photo']): ?>
                                <img src="<?= getImageUrl($claim['photo'], 'claim'); ?>" class="thumb" alt="Item Photo">
                            <?php else: ?>
                                <span class="text-muted">No Image</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($claim['item_name']); ?></td>
                        <td><?= htmlspecialchars(substr($claim['item_description'], 0, 60)) . '...'; ?></td>
                        <td>
                            <?= htmlspecialchars($claim['requester_name']); ?><br>
                            <small class="text-muted"><?= htmlspecialchars($claim['requester_email']); ?></small>
                        </td>
                        <td><?= getStatusBadge($claim['status']); ?></td>
                        <td>
                            <?= $claim['schedule_date'] ? formatDateTime($claim['schedule_date']) : '<span class="text-muted">Not scheduled</span>'; ?>
                        </td>
                        <td><?= formatDateTime($claim['created_at']); ?></td>
                        <td>
                            <a href="review_claim.php?id=<?= $claim['id']; ?>" class="btn btn-sm btn-success">
                                <?php
                                if ($claim['status'] === 'pending') echo 'Review & Approve';
                                elseif ($claim['status'] === 'scheduled') echo 'View/Complete';
                                else echo 'View Details';
                                ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No <?= $filter === 'all' ? '' : $filter; ?> claim requests found.</div>
    <?php endif; ?>

    <!-- Guidelines -->
    <section class="mt-4">
        <h4>Claim Management Guidelines</h4>
        <ul>
            <li><strong>Pending:</strong> Review ownership details and approve/reject claims</li>
            <li><strong>Scheduled:</strong> Claims approved and scheduled for pickup</li>
            <li><strong>Completed:</strong> Items successfully claimed and returned</li>
            <li>Verify student ID and proof of ownership during claiming</li>
            <li>Update claim status after item is verified</li>
        </ul>
    </section>

</main>

<footer class="bg-dark text-white text-center py-3">
    &copy; 2024 Campus Lost & Found System - Admin Panel
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
