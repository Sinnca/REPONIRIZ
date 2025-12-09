<?php global $pdo;
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

// Get all pending lost items
$stmt = $pdo->prepare("
    SELECT li.*, u.name as submitter_name, u.email as submitter_email
    FROM lost_items li
    JOIN users u ON li.user_id = u.id
    WHERE li.status = 'pending'
    ORDER BY li.created_at DESC
");
$stmt->execute();
$pendingItems = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Lost Items - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin_pendinglost.css">

</head>
<body class="bg-light">

<?php include '../components/sidebar.php'; ?>

<main class="content-wrapper">

    <div class="page-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h2>
                    Pending Lost Items
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
                        <th>Date Lost</th>
                        <th>Submitted By</th>
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
                                    <img src="<?php echo getImageUrl($item['photo'], 'lost'); ?>" alt="Item Image" class="thumb">
                                <?php else: ?>
                                    <div class="no-image-placeholder">
                                        <i class="bi bi-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars(substr($item['description'], 0, 80)) . '...'; ?></td>
                            <td><?php echo formatDate($item['date_lost']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($item['submitter_name']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($item['submitter_email']); ?></small>
                            </td>
                            <td><?php echo formatDateTime($item['created_at']); ?></td>
                            <td>
                                <a href="verify_lost.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-success">
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
            No pending lost items to review.
        </div>
    <?php endif; ?>

    <section class="mt-4">
        <div class="guidelines-section">
            <h4>
                <i class="bi bi-clipboard-check"></i>
                Verification Guidelines
            </h4>
            <ul>
                <li>Review item description for completeness and clarity</li>
                <li>Check if photo is provided (if applicable)</li>
                <li>Verify date lost is reasonable</li>
                <li>Approve legitimate submissions</li>
                <li>Reject duplicate, vague, or inappropriate submissions</li>
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