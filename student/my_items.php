<?php global $pdo;
/**
 * My Items
 * View and manage all items reported by the student
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireStudent();

$userId = getCurrentUserId();

// Get all lost items by user
$stmtLost = $pdo->prepare("SELECT * FROM lost_items WHERE user_id = ? ORDER BY created_at DESC");
$stmtLost->execute([$userId]);
$lostItems = $stmtLost->fetchAll();

// Get all found items by user
$stmtFound = $pdo->prepare("SELECT * FROM found_items WHERE user_id = ? ORDER BY created_at DESC");
$stmtFound->execute([$userId]);
$foundItems = $stmtFound->fetchAll();

// Get all claim requests
$stmtClaims = $pdo->prepare("
    SELECT cr.*,
           li.id AS lost_item_id,
           fi.id AS found_item_id,
           COALESCE(li.item_name, fi.item_name) AS item_name,
           COALESCE(li.description, fi.description) AS description,
           li.photo AS lost_photo,
           fi.photo AS found_photo
    FROM claim_requests cr
    LEFT JOIN lost_items li ON cr.lost_item_id = li.id
    LEFT JOIN found_items fi ON cr.found_item_id = fi.id
    WHERE cr.requester_id = ?
    ORDER BY cr.created_at DESC
");
$stmtClaims->execute([$userId]);
$claimRequests = $stmtClaims->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Items - Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-blue: #0066FF;
            --secondary-blue: #E6F0FF;
            --dark-blue: #003D99;
            --light-blue: #F0F7FF;
            --accent-blue: #3385FF;
            --text-dark: #1a1a2e;
            --text-light: #6B7280;
            --white: #FFFFFF;
            --border-color: #E5E7EB;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #DC2626;
            --gold: #FDB813;
            --navy: #002D72;
            --university-blue: #003366;
            --university-gold: #FFB81C;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #F5F5F5;
            color: var(--text-dark);
            min-height: 100vh;
            font-size: 15px;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                linear-gradient(135deg, rgba(0, 102, 255, 0.02) 0%, transparent 50%),
                repeating-linear-gradient(
                    0deg,
                    transparent,
                    transparent 2px,
                    rgba(0, 102, 255, 0.01) 2px,
                    rgba(0, 102, 255, 0.01) 4px
                );
            pointer-events: none;
            z-index: 0;
        }

        main, nav, footer {
            position: relative;
            z-index: 1;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        /* Navbar */
         /* ========== NAVBAR STYLES - UPDATED TO MATCH STUDENT DASHBOARD ========== */
        /* Changed from white background to blue gradient */
        .navbar {
            background: linear-gradient(135deg, var(--university-blue) 0%, var(--navy) 100%);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            padding: 1rem 0;
            border-bottom: 4px solid var(--university-gold);
        }

        /* Changed brand text color from navy to white */
        .navbar-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 800;
            font-size: 1.6rem;
            color: var(--white) !important;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            text-transform: uppercase;
        }

        /* Changed brand icon color from primary-blue to university-gold */
        .navbar-brand i {
            color: var(--university-gold);
            margin-right: 0.6rem;
            font-size: 2rem;
        }

        /* Changed nav link color from text-dark to white */
        .navbar-nav .nav-link {
            color: var(--white) !important;
            font-weight: 600;
            font-size: 0.85rem;
            margin: 0 0.2rem;
            padding: 0.6rem 1rem !important;
            border-radius: 4px;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        /* Changed hover background from light-blue to gold with transparency */
        .navbar-nav .nav-link:hover {
            background: rgba(255, 184, 28, 0.2);
            color: var(--university-gold) !important;
        }

        /* Changed active state from primary-blue to university-gold */
        .navbar-nav .nav-link.active {
            background: var(--university-gold);
            color: var(--university-blue) !important;
            font-weight: 700;
        }

        /* Changed navbar text color from text-dark to white */
        .navbar-text {
            color: var(--white) !important;
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* Changed strong text color from primary-blue to university-gold */
        .navbar-text strong {
            color: var(--university-gold);
            font-weight: 700;
        }
        
        /* Changed button border and text from primary-blue to university-gold */
        .btn-outline-primary {
            border: 2px solid var(--university-gold);
            color: var(--university-gold);
            background: transparent;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
        }

        /* Changed button hover from primary-blue to university-gold */
        .btn-outline-primary:hover {
            background: var(--university-gold);
            color: var(--university-blue);
            border-color: var(--university-gold);
            transform: translateY(-1px);
        }
        /* ========== END OF NAVBAR STYLES UPDATE ========== */

        /* Main Container */
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem 1rem 4rem;
            animation: fadeInUp 0.6s ease-out;
        }

        .page-header {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            margin-bottom: 2.5rem;
            border-left: 6px solid var(--primary-blue);
            border-bottom: 1px solid var(--border-color);
        }

        .page-header h1 {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--navy);
            margin: 0 0 0.5rem 0;
            letter-spacing: -0.02em;
            text-transform: uppercase;
        }

        .page-header p {
            color: var(--text-light);
            margin: 0;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.6;
        }

        /* Section Cards */
        .card {
            border: 1px solid var(--border-color);
            border-radius: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            background: var(--white);
            margin-bottom: 2.5rem;
            border-top: 5px solid var(--primary-blue);
        }

        .card-header {
            background: var(--white);
            border-bottom: 2px solid var(--border-color);
            padding: 1.5rem;
        }

        .card-header h5 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--navy);
            text-transform: uppercase;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
        }

        .card-header h5 i {
            margin-right: 0.8rem;
            font-size: 1.5rem;
        }

        .card-header.lost-items {
            border-top-color: var(--primary-blue);
        }

        .card-header.lost-items h5 i {
            color: var(--primary-blue);
        }

        .card-header.found-items {
            border-top-color: var(--success);
        }

        .card-header.found-items h5 i {
            color: var(--success);
        }

        .card-header.claim-items {
            border-top-color: var(--warning);
        }

        .card-header.claim-items h5 i {
            color: var(--warning);
        }

        .card-body {
            padding: 2rem;
        }

        /* Table Styles */
        .table {
            margin-bottom: 0;
            font-size: 0.9rem;
        }

        .table thead th {
            background: var(--navy);
            color: var(--white);
            border: none;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            padding: 1rem;
            vertical-align: middle;
        }

        .table tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid var(--border-color);
        }

        .table tbody tr:hover {
            background: #FAFAFA;
        }

        .table tbody td {
            padding: 1.2rem 1rem;
            vertical-align: middle;
            color: var(--text-dark);
        }

        .item-img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 4px;
            border: 2px solid var(--border-color);
        }

        /* Buttons */
        .btn {
            border-radius: 4px;
            font-weight: 700;
            font-size: 0.75rem;
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
            border: none;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.7rem;
        }

        .btn-primary {
            background: var(--primary-blue);
            color: var(--white);
            box-shadow: 0 2px 4px rgba(0, 102, 255, 0.3);
        }

        .btn-primary:hover {
            background: var(--dark-blue);
            transform: translateY(-1px);
            color: var(--white);
        }

        .btn-warning {
            background: var(--warning);
            color: #78350F;
            box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
        }

        .btn-warning:hover {
            background: #D97706;
            transform: translateY(-1px);
            color: #78350F;
        }

        .btn-danger {
            background: var(--danger);
            color: var(--white);
            box-shadow: 0 2px 4px rgba(220, 38, 38, 0.3);
        }

        .btn-danger:hover {
            background: #B91C1C;
            transform: translateY(-1px);
        }

        .btn-success {
            background: var(--success);
            color: var(--white);
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #6B7280;
            color: var(--white);
            box-shadow: 0 2px 4px rgba(107, 114, 128, 0.3);
        }

        .btn-secondary:hover {
            background: #4B5563;
            transform: translateY(-1px);
        }

        /* Badge Styles */
        .badge {
            padding: 0.4rem 0.8rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-radius: 0;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }

        .empty-state p {
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        /* Footer */
        footer {
            background: var(--navy);
            color: var(--white);
            padding: 2rem 0;
            margin-top: 3rem;
            box-shadow: 0 -1px 3px rgba(0, 0, 0, 0.1);
            border-top: 3px solid var(--primary-blue);
        }

        footer i {
            color: var(--gold);
            margin-right: 0.5rem;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Count Badge */
        .count-badge {
            display: inline-block;
            background: var(--secondary-blue);
            color: var(--primary-blue);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-weight: 800;
            font-size: 0.85rem;
            margin-left: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                padding: 1.5rem;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .card-body {
                padding: 1.5rem;
            }

            .table {
                font-size: 0.85rem;
            }

            .item-img {
                width: 50px;
                height: 50px;
            }
        }

        /* Status badges colors */
        .badge.bg-info {
            background: #0EA5E9 !important;
            color: var(--white) !important;
        }

        .badge.bg-success {
            background: var(--success) !important;
            color: var(--white) !important;
        }

        .badge.bg-danger {
            background: var(--danger) !important;
            color: var(--white) !important;
        }

        .badge.bg-primary {
            background: var(--primary-blue) !important;
            color: var(--white) !important;
        }

        .badge.bg-warning {
            background: var(--warning) !important;
            color: #78350F !important;
        }

        .badge.bg-secondary {
            background: #6B7280 !important;
            color: var(--white) !important;
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php"><i class="bi bi-box-seam me-2"></i>Lost & Found</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="report_lost.php"><i class="bi bi-exclamation-circle me-1"></i>Report Lost</a></li>
                <li class="nav-item"><a class="nav-link" href="report_found.php"><i class="bi bi-check-circle me-1"></i>Report Found</a></li>
                <li class="nav-item"><a class="nav-link" href="lost_items.php"><i class="bi bi-search me-1"></i>Browse Lost</a></li>
                <li class="nav-item"><a class="nav-link" href="found_items.php"><i class="bi bi-archive me-1"></i>Browse Found</a></li>
                <li class="nav-item"><a class="nav-link active" href="my_items.php"><i class="bi bi-person-lines-fill me-1"></i>My Items</a></li>
                <li class="nav-item"><a class="nav-link" href="faq.php"><i class="bi bi-question-circle me-1"></i>FAQ</a></li>
            </ul>
            <span class="navbar-text me-3">
                <i class="bi bi-person-circle me-2"></i>Welcome, <strong><?= htmlspecialchars(getCurrentUserName()) ?></strong>
            </span>
            <a href="../logout.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="bi bi-folder2-open me-2"></i>My Items</h1>
        <p>View and manage all your submitted items and claim requests in one place.</p>
    </div>

    <!-- LOST ITEMS -->
    <div class="card">
        <div class="card-header lost-items">
            <h5>
                <i class="bi bi-exclamation-diamond-fill"></i>
                My Lost Items
                <span class="count-badge"><?= count($lostItems) ?></span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (count($lostItems) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th>Date Lost</th>
                            <th>Status</th>
                            <th>Date Reported</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($lostItems as $item): ?>
                            <tr>
                                <td>
                                    <?php if ($item['photo']): ?>
                                        <img src="<?= getImageUrl($item['photo'], 'lost') ?>" class="item-img" alt="Item photo">
                                    <?php else: ?>
                                        <div class="item-img d-flex align-items-center justify-content-center" style="background: #E5E7EB;">
                                            <i class="bi bi-image" style="font-size: 1.5rem; color: #9CA3AF;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($item['item_name']) ?></strong></td>
                                <td><?= htmlspecialchars(substr($item['description'],0,50)) ?>...</td>
                                <td><?= formatDate($item['date_lost']) ?></td>
                                <td><?= getStatusBadge($item['status']) ?></td>
                                <td><?= formatDate($item['created_at']) ?></td>
                                <td>
                                    <a href="view_lost_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary mb-1"><i class="bi bi-eye me-1"></i>View</a>
                                    <?php if ($item['status']==='pending'): ?>
                                        <a href="edit_lost_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-warning mb-1"><i class="bi bi-pencil me-1"></i>Edit</a>
                                        <button class="btn btn-sm btn-danger mb-1" onclick="deleteLostItem(<?= $item['id'] ?>)"><i class="bi bi-trash me-1"></i>Delete</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>You haven't reported any lost items yet.</p>
                    <a href="report_lost.php" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Report Lost Item</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- FOUND ITEMS -->
    <div class="card">
        <div class="card-header found-items">
            <h5>
                <i class="bi bi-patch-check-fill"></i>
                Items I Found
                <span class="count-badge" style="background: #D1FAE5; color: #047857;"><?= count($foundItems) ?></span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (count($foundItems) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th>Date Found</th>
                            <th>Status</th>
                            <th>Date Reported</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($foundItems as $item): ?>
                            <tr>
                                <td>
                                    <?php if ($item['photo']): ?>
                                        <img src="<?= getImageUrl($item['photo'], 'found') ?>" class="item-img" alt="Item photo">
                                    <?php else: ?>
                                        <div class="item-img d-flex align-items-center justify-content-center" style="background: #E5E7EB;">
                                            <i class="bi bi-image" style="font-size: 1.5rem; color: #9CA3AF;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($item['item_name']) ?></strong></td>
                                <td><?= htmlspecialchars(substr($item['description'],0,50)) ?>...</td>
                                <td><?= formatDate($item['date_found']) ?></td>
                                <td><?= getStatusBadge($item['status']) ?></td>
                                <td><?= formatDate($item['created_at']) ?></td>
                                <td>
                                    <a href="view_found_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary mb-1"><i class="bi bi-eye me-1"></i>View</a>
                                    <?php if ($item['status']==='pending'): ?>
                                        <button class="btn btn-sm btn-danger mb-1" onclick="deleteFoundItem(<?= $item['id'] ?>)"><i class="bi bi-trash me-1"></i>Delete</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>You haven't reported any found items yet.</p>
                    <a href="report_found.php" class="btn btn-success"><i class="bi bi-plus-circle me-2"></i>Report Found Item</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- CLAIM REQUESTS -->
    <div class="card">
        <div class="card-header claim-items">
            <h5>
                <i class="bi bi-clipboard2-check-fill"></i>
                My Claim Requests
                <span class="count-badge" style="background: #FEF3C7; color: #92400E;"><?= count($claimRequests) ?></span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (count($claimRequests) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Scheduled Date</th>
                            <th>Request Date</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($claimRequests as $claim): ?>
                            <tr>
                                <td>
                                    <?php if ($claim['photo']): ?>
                                        <img src="<?= getImageUrl($claim['photo'], 'claim') ?>" class="item-img" alt="Item photo">
                                    <?php else: ?>
                                        <div class="item-img d-flex align-items-center justify-content-center" style="background: #E5E7EB;">
                                            <i class="bi bi-image" style="font-size: 1.5rem; color: #9CA3AF;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($claim['item_name']) ?></strong></td>
                                <td><?= htmlspecialchars(substr($claim['description'],0,50)) ?>...</td>
                                <td><?= getStatusBadge($claim['status']) ?></td>
                                <td><?= $claim['schedule_date'] ? formatDateTime($claim['schedule_date']) : '<span class="text-muted">Not scheduled yet</span>' ?></td>
                                <td><?= formatDate($claim['created_at']) ?></td>
                                <td>
                                    <a href="view_claim.php?id=<?= $claim['id'] ?>" class="btn btn-sm btn-secondary mb-1"><i class="bi bi-eye me-1"></i>View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>No claim requests yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- LOAD MAIN.JS FIRST - BEFORE ANY INLINE SCRIPTS -->
<script src="../assets/js/main.js"></script>

<!-- NOW THE DELETE FUNCTIONS WILL WORK BECAUSE LSF IS LOADED -->
<script>
    async function deleteLostItem(id){ 
        await LSF.FormHandlers.deleteItem(id,'lost'); 
    }
    async function deleteFoundItem(id){ 
        await LSF.FormHandlers.deleteItem(id,'found'); 
    }
</script>

<footer class="text-center">
    <div class="container">
        <p><i class="bi bi-shield-check"></i>&copy; 2025 Campus Lost & Found System. All rights reserved.</p>
    </div>
</footer>

</body>
</html>