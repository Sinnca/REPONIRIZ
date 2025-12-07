<?php
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

    if ($status !== 'all') {
        $query .= " AND li.status = :status";
    }

    if (!empty($search)) {
        $query .= " AND (li.item_name LIKE :search OR li.description LIKE :search)";
    }

    $query .= " ORDER BY li.created_at DESC";

    $stmt = $pdo->prepare($query);

    if ($status !== 'all') {
        $stmt->bindValue(':status', $status);
    }
    if (!empty($search)) {
        $stmt->bindValue(':search', "%$search%");
    }

    $stmt->execute();
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

    if ($status !== 'all') {
        $query .= " AND fi.status = :status";
    }

    if (!empty($search)) {
        $query .= " AND (fi.item_name LIKE :search OR fi.description LIKE :search)";
    }

    $query .= " ORDER BY fi.created_at DESC";

    $stmt = $pdo->prepare($query);

    if ($status !== 'all') {
        $stmt->bindValue(':status', $status);
    }
    if (!empty($search)) {
        $stmt->bindValue(':search', "%$search%");
    }

    $stmt->execute();
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Items - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .content-wrapper {
            padding: 30px;
        }
        img.thumb { 
            width: 60px; 
            height: 60px; 
            object-fit: cover; 
            border-radius: 4px; 
        }
        .filter-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .filter-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .filter-tabs a { 
            text-decoration: none; 
            padding: 8px 16px; 
            border-radius: 6px;
            background: #f8f9fa;
            color: #495057;
            transition: all 0.2s;
        }
        .filter-tabs a:hover {
            background: #e9ecef;
        }
        .filter-tabs a.active { 
            background-color: #0d6efd; 
            color: #fff; 
        }
        .type-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .type-tabs a {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            background: #f8f9fa;
            color: #495057;
            font-weight: 500;
            transition: all 0.2s;
        }
        .type-tabs a:hover {
            background: #e9ecef;
        }
        .type-tabs a.active {
            background: #198754;
            color: white;
        }
    </style>
</head>
<body class="bg-light">

<?php include '../components/sidebar.php'; ?>

<main class="content-wrapper">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>All Items Management</h2>
    </div>

    <!-- Type Tabs -->
    <div class="type-tabs">
        <a href="?type=lost&status=<?= $status; ?>&search=<?= urlencode($search); ?>" 
           class="<?= $type === 'lost' ? 'active' : ''; ?>">
            Lost Items
        </a>
        <a href="?type=found&status=<?= $status; ?>&search=<?= urlencode($search); ?>" 
           class="<?= $type === 'found' ? 'active' : ''; ?>">
            Found Items
        </a>
    </div>

    <!-- Filters Card -->
    <div class="filter-card">
        <h5 class="mb-3">Filter Items</h5>
        
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
                <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
            </div>
        </form>

        <?php if (!empty($search) || $status !== 'all'): ?>
            <div class="mt-3">
                <a href="all_items.php?type=<?= $type; ?>" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Items List -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><?= ucfirst($type); ?> Items (<?= count($items); ?>)</h5>
        </div>
        <div class="card-body p-0">
            <?php if (count($items) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
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
                                <td><?= $item['id']; ?></td>
                                <td>
                                    <?php if ($item['photo']): ?>
                                        <img
                                            src="<?= getImageUrl($item['photo'], $type); ?>"
                                            alt="<?= htmlspecialchars($item['item_name']); ?>"
                                            class="thumb"
                                        >
                                    <?php else: ?>
                                        <span class="text-muted">No Image</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($item['item_name']); ?></td>
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
                                <td><?= getStatusBadge($item['status']); ?></td>
                                <td><?= formatDate($item['created_at']); ?></td>
                                <td>
                                    <?php if ($type === 'lost'): ?>
                                        <a href="verify_lost.php?id=<?= $item['id']; ?>" class="btn btn-sm btn-success">View</a>
                                    <?php else: ?>
                                        <a href="verify_found.php?id=<?= $item['id']; ?>" class="btn btn-sm btn-success">View</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info m-3">
                    <i class="bi bi-info-circle"></i> No items found matching your filters.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Guidelines -->
    <section class="mt-4">
        <div class="card">
            <div class="card-body">
                <h5>Item Management Guidelines</h5>
                <ul class="mb-0">
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
        </div>
    </section>

</main>

<footer class="bg-dark text-white text-center py-3">
    &copy; 2024 Campus Lost & Found System - Admin Panel
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>