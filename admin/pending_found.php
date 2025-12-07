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
    <style>
        img.thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
        .badge-info { background-color: #0dcaf0; color: #fff; }
    </style>
</head>
<body class="bg-light">

<!-- Navbar -->
<?php include '../components/sidebar.php'; ?>

<main class="container mb-5">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Pending Found Items (<?php echo count($pendingItems); ?>)</h2>
    </div>

    <?php if (count($pendingItems) > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-primary">
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
                        <td><?php echo $item['id']; ?></td>
                        <td>
                            <?php if ($item['photo']): ?>
                                <img src="<?php echo getImageUrl($item['photo'], 'found'); ?>" alt="Item Image" class="thumb">
                            <?php else: ?>
                                <span class="text-muted">No Image</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                        <td><?php echo htmlspecialchars(substr($item['description'], 0, 80)) . '...'; ?></td>
                        <td><?php echo formatDate($item['date_found']); ?></td>
                        <td>
                            <?php if ($item['lost_item_id']): ?>
                                <span class="badge badge-info">Linked: <?php echo htmlspecialchars($item['linked_item_name']); ?></span>
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
                            <a href="verify_found.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-success">Review</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No pending found items to review.</div>
    <?php endif; ?>

    <section class="mt-4">
        <h4>Verification Guidelines</h4>
        <ul>
            <li>Review item description for accuracy</li>
            <li>Verify photo matches description (if available)</li>
            <li>Check if linked to a lost item report</li>
            <li>If verified and linked, the lost item owner will be notified</li>
            <li>Approve legitimate submissions</li>
            <li>Reject duplicate or inappropriate submissions</li>
        </ul>
    </section>

</main>

<footer class="bg-dark text-white text-center py-3">
    &copy; 2024 Campus Lost & Found System - Admin Panel
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
