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
            margin-bottom: 32px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .count-badge {
            display: inline-block;
            background: var(--primary-yellow);
            color: #1f2937;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9375rem;
            margin-left: 12px;
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
        
        .badge-info { 
            background: linear-gradient(135deg, var(--primary-blue), #0052d4);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.8125rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .badge-linked {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.8125rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
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
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .content-wrapper {
                padding: 24px 20px;
            }
            
            h2 {
                font-size: 1.5rem;
            }
            
            .count-badge {
                display: block;
                margin-left: 0;
                margin-top: 8px;
                width: fit-content;
            }
        }
    </style>
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