<?php global $pdo;
/**
 * Admin - All Items Management
 * View all lost and found items in the system
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

$userName = getCurrentUserName();

// Get filter parameters
$type = isset($_GET['type']) ? sanitize($_GET['type']) : 'lost';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query for lost items
if ($type === 'lost') {
    $query = "
        SELECT li.*, u.name as submitter_name, u.email as submitter_email
        FROM lost_items li
        JOIN users u ON li.user_id = u.id
        WHERE 1=1
    ";

    $params = [];

    if ($status !== 'all') {
        $query .= " AND li.status = :status";
        $params['status'] = $status;
    }

    if (!empty($search)) {
        $query .= " AND (li.item_name LIKE :search_name OR li.description LIKE :search_desc)";
        $params['search_name'] = "%{$search}%";
        $params['search_desc'] = "%{$search}%";
    }

    $query .= " ORDER BY li.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $items = $stmt->fetchAll();
}
// Build query for found items
else {
    $query = "
        SELECT fi.*, u.name as submitter_name, u.email as submitter_email
        FROM found_items fi
        JOIN users u ON fi.user_id = u.id
        WHERE 1=1
    ";

    $params = [];

    if ($status !== 'all') {
        $query .= " AND fi.status = :status";
        $params['status'] = $status;
    }

    if (!empty($search)) {
        $query .= " AND (fi.item_name LIKE :search_name OR fi.description LIKE :search_desc)";
        $params['search_name'] = "%{$search}%";
        $params['search_desc'] = "%{$search}%";
    }

    $query .= " ORDER BY fi.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $items = $stmt->fetchAll();
}


// Get counts for different statuses
$counts = [];
if ($type === 'lost') {
    $counts = [
        'all' => $pdo->query("SELECT COUNT(*) FROM lost_items")->fetchColumn(),
        'pending' => $pdo->query("SELECT COUNT(*) FROM lost_items WHERE status = 'pending'")->fetchColumn(),
        'listed' => $pdo->query("SELECT COUNT(*) FROM lost_items WHERE status = 'listed'")->fetchColumn(),
        'ready_for_claim' => $pdo->query("SELECT COUNT(*) FROM lost_items WHERE status = 'ready_for_claim'")->fetchColumn(),
        'returned' => $pdo->query("SELECT COUNT(*) FROM lost_items WHERE status = 'returned'")->fetchColumn(),
        'rejected' => $pdo->query("SELECT COUNT(*) FROM lost_items WHERE status = 'rejected'")->fetchColumn(),
    ];
} else {
    $counts = [
        'all' => $pdo->query("SELECT COUNT(*) FROM found_items")->fetchColumn(),
        'pending' => $pdo->query("SELECT COUNT(*) FROM found_items WHERE status = 'pending'")->fetchColumn(),
        'verified' => $pdo->query("SELECT COUNT(*) FROM found_items WHERE status = 'verified'")->fetchColumn(),
        'listed' => $pdo->query("SELECT COUNT(*) FROM found_items WHERE status = 'listed'")->fetchColumn(),
        'claimed' => $pdo->query("SELECT COUNT(*) FROM found_items WHERE status = 'claimed'")->fetchColumn(),
        'rejected' => $pdo->query("SELECT COUNT(*) FROM found_items WHERE status = 'rejected'")->fetchColumn(),
    ];
}

// Custom function for colored status badges
function getColoredStatusBadge($status) {
    $statusConfig = [
        'pending' => ['class' => 'badge bg-warning text-dark', 'text' => 'Pending'],
        'verified' => ['class' => 'badge bg-primary', 'text' => 'Verified'],
        'listed' => ['class' => 'badge bg-info text-dark', 'text' => 'Listed'],
        'ready_for_claim' => ['class' => 'badge bg-cyan text-dark', 'text' => 'Ready for Claim'],
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
    <title>All Items - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
     <link rel="stylesheet" href="../assets/css/admin_allitems.css"
    
</head>
<body class="bg-light">

<?php include '../components/sidebar.php'; ?>

<main class="content-wrapper">

    <div class="page-header">
        <h2>All Items Management</h2>
    </div>

    <!-- Type Tabs -->
    <div class="type-tabs">
        <a href="?type=lost&status=<?= $status; ?>&search=<?= urlencode($search); ?>" 
           class="<?= $type === 'lost' ? 'active' : ''; ?>">
            <i class="bi bi-search"></i>
            Lost Items
        </a>
        <a href="?type=found&status=<?= $status; ?>&search=<?= urlencode($search); ?>" 
           class="<?= $type === 'found' ? 'active' : ''; ?>">
            <i class="bi bi-bag-check"></i>
            Found Items
        </a>
    </div>

    <!-- Filters Card -->
    <div class="filter-card">
        <h5><i class="bi bi-funnel me-2"></i>Filter Items</h5>
        
        <form method="GET" action="" class="row g-3">
            <input type="hidden" name="type" value="<?= $type; ?>">
            
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="all" <?= $status === 'all' ? 'selected' : ''; ?>>All Statuses (<?= $counts['all']; ?>)</option>

                    <?php if ($type === 'lost'): ?>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : ''; ?>>Pending (<?= $counts['pending']; ?>)</option>
                        <option value="listed" <?= $status === 'listed' ? 'selected' : ''; ?>>Listed (<?= $counts['listed']; ?>)</option>
                        <option value="ready_for_claim" <?= $status === 'ready_for_claim' ? 'selected' : ''; ?>>Ready for Claim (<?= $counts['ready_for_claim']; ?>)</option>
                        <option value="returned" <?= $status === 'returned' ? 'selected' : ''; ?>>Returned (<?= $counts['returned']; ?>)</option>
                        <option value="rejected" <?= $status === 'rejected' ? 'selected' : ''; ?>>Rejected (<?= $counts['rejected']; ?>)</option>
                    <?php else: ?>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : ''; ?>>Pending (<?= $counts['pending']; ?>)</option>
                        <option value="verified" <?= $status === 'verified' ? 'selected' : ''; ?>>Verified (<?= $counts['verified']; ?>)</option>
                        <option value="listed" <?= $status === 'listed' ? 'selected' : ''; ?>>Listed (<?= $counts['listed']; ?>)</option>
                        <option value="claimed" <?= $status === 'claimed' ? 'selected' : ''; ?>>Claimed (<?= $counts['claimed']; ?>)</option>
                        <option value="rejected" <?= $status === 'rejected' ? 'selected' : ''; ?>>Rejected (<?= $counts['rejected']; ?>)</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="search" class="form-label">Search</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    class="form-control"
                    placeholder="Search item name or description..."
                    value="<?= htmlspecialchars($search); ?>"
                >
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Apply
                </button>
            </div>
        </form>

        <?php if (!empty($search) || $status !== 'all'): ?>
            <div class="mt-3">
                <a href="all_items.php?type=<?= $type; ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear Filters
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Items List -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-collection me-2"></i><?= ucfirst($type); ?> Items (<?= count($items); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (count($items) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Photo</th>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th>Date <?= $type === 'lost' ? 'Lost' : 'Found'; ?></th>
                            <th>Submitted By</th>
                            <th>Status</th>
                            <th>Date Submitted</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><strong>#<?= $item['id']; ?></strong></td>
                                <td>
                                    <?php if ($item['photo']): ?>
                                        <img
                                            src="<?= getImageUrl($item['photo'], $type); ?>"
                                            alt="<?= htmlspecialchars($item['item_name']); ?>"
                                            class="thumb"
                                        >
                                    <?php else: ?>
                                        <div class="no-image-placeholder">
                                            <i class="bi bi-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($item['item_name']); ?></strong></td>
                                <td><?= htmlspecialchars(substr($item['description'], 0, 60)) . '...'; ?></td>
                                <td>
                                    <?php
                                    $dateField = $type === 'lost' ? 'date_lost' : 'date_found';
                                    echo formatDate($item[$dateField]);
                                    ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($item['submitter_name']); ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($item['submitter_email']); ?></small>
                                </td>
                                <td><?= getColoredStatusBadge($item['status']); ?></td>
                                <td><?= formatDate($item['created_at']); ?></td>
                                <td>
                                    <?php if ($type === 'lost'): ?>
                                        <a href="verify_lost.php?id=<?= $item['id']; ?>" class="btn btn-sm btn-success">
                                            <i class="bi bi-eye me-1"></i>View
                                        </a>
                                    <?php else: ?>
                                        <a href="verify_found.php?id=<?= $item['id']; ?>" class="btn btn-sm btn-success">
                                            <i class="bi bi-eye me-1"></i>View
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>No items found matching your filters.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Guidelines -->
    <section class="mt-4">
        <div class="guidelines-card">
            <h5>
                <i class="bi bi-clipboard-check"></i>
                Item Management Guidelines
            </h5>
            <ul>
                <li><strong>Pending:</strong> Items awaiting admin verification</li>
                <li><strong>Listed:</strong> Verified items visible to students</li>
                <?php if ($type === 'lost'): ?>
                    <li><strong>Ready for Claim:</strong> Lost items with approved claim requests</li>
                    <li><strong>Returned:</strong> Items successfully claimed and returned to owner</li>
                <?php else: ?>
                    <li><strong>Verified:</strong> Found items verified by admin</li>
                    <li><strong>Claimed:</strong> Found items claimed by owner</li>
                <?php endif; ?>
                <li><strong>Rejected:</strong> Items that don't meet criteria</li>
            </ul>
        </div>
    </section>

</main>

<footer>
    &copy; 2025 Campus Lost & Found System - Admin Panel
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>