<?php global $pdo;
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

// Get all pending found items
$stmt = $pdo->prepare("
    SELECT fi.*, u.name as finder_name, u.email as finder_email,
           li.item_name as linked_item_name
    FROM found_items fi
    JOIN users u ON fi.user_id = u.id
    LEFT JOIN lost_items li ON fi.lost_item_id = li.id
    WHERE fi.status = 'pending'
    ORDER BY fi.created_at DESC
");
$stmt->execute();
$pendingItems = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Found Items - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin_pendingfound.css">
    
</head>
<body class="bg-light">

<?php include '../components/sidebar.php'; ?>

<main class="content-wrapper">

    <div class="page-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h2>
                    Pending Found Items
                    <span class="count-badge"><?php echo count($pendingItems); ?> Items</span>
                </h2>
            </div>
        </div>
    </div>

    <?php if (count($pendingItems) > 0): ?>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Photo</th>
                        <th>Item Name</th>
                        <th>Description</th>
                        <th>Date Found</th>
                        <th>Linked Lost Item</th>
                        <th>Found By</th>
                        <th>Date Submitted</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pendingItems as $item): ?>
                        <tr>
                            <td><strong>#<?php echo $item['id']; ?></strong></td>
                            <td>
                                <?php if ($item['photo']): ?>
                                    <img src="<?php echo getImageUrl($item['photo'], 'found'); ?>" alt="Item Image" class="thumb">
                                <?php else: ?>
                                    <div class="no-image-placeholder">
                                        <i class="bi bi-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars(substr($item['description'], 0, 80)) . '...'; ?></td>
                            <td><?php echo formatDate($item['date_found']); ?></td>
                            <td>
                                <?php if ($item['lost_item_id']): ?>
                                    <span class="badge-linked">
                                        <i class="bi bi-link-45deg"></i>
                                        <?php echo htmlspecialchars($item['linked_item_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">Not Linked</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($item['finder_name']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($item['finder_email']); ?></small>
                            </td>
                            <td><?php echo formatDateTime($item['created_at']); ?></td>
                            <td>
                                <a href="verify_found.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-success">
                                    <i class="bi bi-check-circle me-1"></i>Review
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
            No pending found items to review.
        </div>
    <?php endif; ?>

    <section class="mt-4">
        <div class="guidelines-section">
            <h4>
                <i class="bi bi-clipboard-check"></i>
                Verification Guidelines
            </h4>
            <ul>
                <li>Review item description for accuracy</li>
                <li>Verify photo matches description (if available)</li>
                <li>Check if linked to a lost item report</li>
                <li>If verified and linked, the lost item owner will be notified</li>
                <li>Approve legitimate submissions</li>
                <li>Reject duplicate or inappropriate submissions</li>
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