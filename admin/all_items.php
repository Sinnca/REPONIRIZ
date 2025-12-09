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
        
        .filter-card {
            background: white;
            padding: 28px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            margin-bottom: 24px;
            border: 1px solid var(--border-color);
        }
        
        .filter-card h5 {
            color: var(--primary-blue);
            font-weight: 600;
            font-size: 1.125rem;
            margin-bottom: 20px;
        }
        
        .type-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        
        .type-tabs a {
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 8px;
            background: white;
            color: var(--text-primary);
            font-weight: 600;
            transition: all 0.2s ease;
            border: 2px solid var(--border-color);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
        }
        
        .type-tabs a:hover {
            background: #f9fafb;
            border-color: var(--primary-blue);
            transform: translateY(-1px);
        }
        
        .type-tabs a.active {
            background: linear-gradient(135deg, var(--primary-blue), #0052d4);
            color: white;
            border-color: var(--primary-blue);
            box-shadow: 0 2px 8px rgba(0, 61, 165, 0.25);
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
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-blue), #0052d4);
            border-bottom: none;
            color: white;
            padding: 18px 24px;
            font-size: 1rem;
            border-radius: 12px 12px 0 0;
        }
        
        .card-header h5 {
            margin: 0;
            font-weight: 600;
        }
        
        .card-body {
            padding: 0;
        }
        
        .card-body.p-3 {
            padding: 24px !important;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 8px;
            font-size: 0.9375rem;
        }
        
        .form-control, .form-select {
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 0.9375rem;
            transition: all 0.2s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(0, 61, 165, 0.1);
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
            padding: 10px 20px;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            font-size: 0.9375rem;
        }
        
        .btn-primary {
            background: var(--primary-blue);
            color: white;
            border-color: var(--primary-blue);
        }
        
        .btn-primary:hover {
            background: #003494;
            border-color: #003494;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 61, 165, 0.25);
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
        
        .btn-outline-secondary {
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
        }
        
        .btn-outline-secondary:hover {
            background: #f9fafb;
            border-color: var(--text-secondary);
            color: var(--text-secondary);
        }
        
        .alert {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            padding: 20px;
            background: white;
            margin: 24px;
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
        
        .guidelines-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--primary-blue);
        }
        
        .guidelines-card h5 {
            color: var(--primary-blue);
            font-weight: 600;
            font-size: 1.125rem;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .guidelines-card ul {
            margin-bottom: 0;
            padding-left: 24px;
        }
        
        .guidelines-card li {
            color: var(--text-primary);
            margin-bottom: 8px;
            line-height: 1.6;
        }
        
        .guidelines-card li::marker {
            color: var(--primary-blue);
        }
        
        .guidelines-card strong {
            color: var(--primary-blue);
            font-weight: 600;
        }
        
        /* Enhanced Status Badge Styles */
        .badge {
            padding: 7px 14px;
            font-weight: 600;
            font-size: 0.8125rem;
            border-radius: 6px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        
        .bg-warning {
            background-color: #fbbf24 !important;
        }
        
        .bg-primary {
            background-color: var(--primary-blue) !important;
            color: white !important;
        }
        
        .bg-info {
            background-color: #0ea5e9 !important;
        }
        
        .bg-cyan {
            background-color: #06b6d4 !important;
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
            
            .type-tabs a {
                padding: 10px 18px;
                font-size: 0.9375rem;
            }
            
            .filter-card {
                padding: 20px;
            }
        }
    </style>
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
    &copy; 2024 Campus Lost & Found System - Admin Panel
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>