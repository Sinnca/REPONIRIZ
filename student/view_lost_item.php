<?php global $pdo;
/**
 * Student - View Lost Item Details
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireStudent();

$userId = getCurrentUserId();
$itemId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$itemId) {
    header('Location: lost_items.php');
    exit;
}

/* ==========================
   FETCH LOST ITEM
========================== */
$stmt = $pdo->prepare("
    SELECT li.*, u.name AS owner_name, u.email AS owner_email
    FROM lost_items li
    JOIN users u ON li.user_id = u.id
    WHERE li.id = ?
");
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: lost_items.php');
    exit;
}

/* ==========================
   FETCH ALL MATCHED FOUND ITEMS
========================== */
$stmtFound = $pdo->prepare("
    SELECT fi.*, u.name AS finder_name
    FROM found_items fi
    JOIN users u ON fi.user_id = u.id
    WHERE fi.lost_item_id = ?
      AND fi.status IN ('verified','listed')
    ORDER BY fi.created_at DESC
");
$stmtFound->execute([$itemId]);
$matchedFoundItems = $stmtFound->fetchAll();

/* ==========================
   FETCH EXISTING CLAIMS (per found item)
========================== */
$stmtClaim = $pdo->prepare("
    SELECT *
    FROM claim_requests
    WHERE lost_item_id = ?
      AND requester_id = ?
");
$stmtClaim->execute([$itemId, $userId]);
$existingClaims = [];
while ($row = $stmtClaim->fetch()) {
    if ($row['found_item_id']) {
        $existingClaims[$row['found_item_id']] = $row;
    }
}

$isOwner = ($item['user_id'] == $userId);

/* ==========================
   HELPER: Get Found Item Name
========================== */
function getFoundItemName($foundItemId, $matchedFoundItems) {
    foreach ($matchedFoundItems as $fi) {
        if ($fi['id'] == $foundItemId) {
            return $fi['item_name'];
        }
    }
    return "Found Item #$foundItemId";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($item['item_name']); ?> - Lost Item Details</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* ========== CSS VARIABLES - UNIVERSITY THEME ========== */
        :root {
            --university-blue: #003366;
            --university-gold: #FFB81C;
            --navy: #002D72;
            --light-blue: #E3F2FD;
            --white: #FFFFFF;
            --text-dark: #1a1a2e;
            --text-light: #6B7280;
            --border-color: #E5E7EB;
            --success: #10B981;
            --info: #0EA5E9;
            --warning: #F59E0B;
            --danger: #DC2626;
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

        /* Background Pattern */
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

        /* ========== NAVBAR STYLES - MATCHING STUDENT DASHBOARD ========== */
        .navbar {
            background: linear-gradient(135deg, var(--university-blue) 0%, var(--navy) 100%);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            padding: 1rem 0;
            border-bottom: 4px solid var(--university-gold);
        }

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

        .navbar-brand i {
            color: var(--university-gold);
            margin-right: 0.6rem;
            font-size: 2rem;
        }

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

        .navbar-nav .nav-link:hover {
            background: rgba(255, 184, 28, 0.2);
            color: var(--university-gold) !important;
        }

        .navbar-nav .nav-link.active {
            background: var(--university-gold);
            color: var(--university-blue) !important;
            font-weight: 700;
        }

        .navbar-text {
            color: var(--white) !important;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .navbar-text strong {
            color: var(--university-gold);
            font-weight: 700;
        }

        .btn-outline-light {
            border: 2px solid var(--university-gold);
            color: var(--university-gold);
            background: transparent;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
        }

        .btn-outline-light:hover {
            background: var(--university-gold);
            color: var(--university-blue);
            border-color: var(--university-gold);
            transform: translateY(-1px);
        }
        /* ========== END OF NAVBAR STYLES ========== */

        /* Main Content */
        main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
            animation: fadeInUp 0.6s ease-out;
        }

        /* Page Header */
        .page-header {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            border-left: 6px solid var(--danger);
            border-bottom: 1px solid var(--border-color);
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--navy);
            margin: 0;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
        }

        .page-header h1 i {
            margin-right: 1rem;
            font-size: 2rem;
            color: var(--danger);
        }

        /* Back Link */
        .back-link {
            display: inline-flex;
            align-items: center;
            color: var(--university-blue);
            font-weight: 600;
            text-decoration: none;
            margin-bottom: 2rem;
            padding: 0.6rem 1.2rem;
            background: var(--white);
            border-radius: 4px;
            border: 2px solid var(--border-color);
            transition: all 0.2s ease;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
        }

        .back-link i {
            margin-right: 0.5rem;
            font-size: 1.2rem;
        }

        .back-link:hover {
            background: var(--university-blue);
            color: var(--white);
            border-color: var(--university-blue);
            transform: translateX(-4px);
        }

        /* Cards */
        .card {
            border: 1px solid var(--border-color);
            border-radius: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            background: var(--white);
            margin-bottom: 1.5rem;
            border-top: 4px solid var(--university-blue);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--university-blue) 0%, var(--university-gold) 100%);
        }

        .card-img-top {
            max-height: 300px;
            object-fit: cover;
            border-bottom: 2px solid var(--border-color);
        }

        .card-body {
            padding: 2rem;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--navy);
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: -0.02em;
            border-bottom: 3px solid var(--university-gold);
            padding-bottom: 0.8rem;
        }

        .card-body p {
            margin-bottom: 1rem;
            line-height: 1.8;
            color: var(--text-dark);
        }

        .card-body p strong {
            color: var(--navy);
            font-weight: 700;
            display: inline-block;
            min-width: 130px;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
        }

        .no-image-text {
            background: linear-gradient(135deg, var(--university-blue) 0%, var(--navy) 100%);
            color: var(--white);
            padding: 4rem 2rem;
            text-align: center;
            font-weight: 700;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .no-image-text i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--university-gold);
        }

        /* Section Headers */
        .section-header {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--navy);
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            background: var(--white);
            border-left: 6px solid var(--university-gold);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .section-header i {
            margin-right: 1rem;
            color: var(--university-gold);
            font-size: 1.8rem;
        }

        /* Buttons */
        .btn {
            border-radius: 4px;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 0.7rem 1.5rem;
            transition: all 0.2s ease;
            border: none;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .btn i {
            margin-right: 0.5rem;
        }

        .btn-outline-primary {
            border: 2px solid var(--university-blue);
            color: var(--university-blue);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: var(--university-blue);
            color: var(--white);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 51, 102, 0.3);
        }

        .btn-secondary {
            background: #6B7280;
            box-shadow: 0 2px 4px rgba(107, 114, 128, 0.3);
        }

        .btn-secondary:hover {
            background: #4B5563;
            transform: translateY(-1px);
        }

        /* Badges */
        .badge {
            border-radius: 4px;
            padding: 0.5rem 1rem;
            font-weight: 700;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .bg-warning {
            background: var(--warning) !important;
            color: var(--text-dark) !important;
        }

        .bg-secondary {
            background: #6B7280 !important;
        }

        /* Claims Summary Card */
        .claims-summary-card {
            background: linear-gradient(135deg, #F0F9FF 0%, #E0F2FE 100%);
            border: 2px solid var(--border-color);
            border-left: 6px solid var(--info);
            border-radius: 0;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .claims-summary-card h5 {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--navy);
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
        }

        .claims-summary-card h5 i {
            margin-right: 0.8rem;
            color: var(--university-gold);
            font-size: 1.6rem;
        }

        .claims-summary-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .claims-summary-card ul li {
            padding: 1rem 1.2rem;
            background: var(--white);
            margin-bottom: 0.8rem;
            border-radius: 4px;
            border-left: 4px solid var(--university-blue);
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .claims-summary-card ul li:hover {
            transform: translateX(4px);
            border-left-color: var(--university-gold);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .claims-summary-card p {
            color: var(--text-light);
            font-style: italic;
            margin: 0;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            background: #FAFAFA;
            border-radius: 4px;
            border: 2px dashed var(--border-color);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--text-light);
            font-size: 1.1rem;
            margin: 0;
        }

        /* Footer */
        footer {
            background: linear-gradient(135deg, var(--university-blue) 0%, var(--navy) 100%);
            color: var(--white);
            padding: 2rem 0;
            margin-top: 3rem;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.2);
            border-top: 4px solid var(--university-gold);
            text-align: center;
        }

        footer p {
            margin: 0;
            font-weight: 500;
            font-size: 0.9rem;
            letter-spacing: 0.02em;
        }

        footer i {
            margin-right: 0.5rem;
            color: var(--university-gold);
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

        /* Responsive */
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.3rem;
            }

            .page-header h1 {
                font-size: 1.6rem;
            }

            .card-body {
                padding: 1.5rem;
            }

            .section-header {
                font-size: 1.3rem;
                padding: 0.8rem 1rem;
            }

            .claims-summary-card {
                padding: 1.5rem;
            }
        }

        /* Lost Item Main Card */
        .lost-item-card {
            border-top-color: var(--danger);
        }

        .lost-item-card::before {
            background: linear-gradient(180deg, var(--danger) 0%, var(--university-gold) 100%);
        }

        /* Found Item Cards */
        .found-item-card {
            border-top-color: var(--success);
        }

        .found-item-card::before {
            background: linear-gradient(180deg, var(--success) 0%, var(--university-gold) 100%);
        }

        /* Status Badge Color Styles - Add this to your existing CSS */

/* Yellow for pending status */
.badge.badge-warning {
    color: #F59E0B !important; /* Yellow */
    background-color: transparent !important;
    font-weight: 700 !important;
}

/* Gray for listed status */
.badge.badge-info {
    color: #6B7280 !important; /* Gray */
    background-color: transparent !important;
    font-weight: 700 !important;
}

/* Green for verified, ready_for_claim, and approved statuses */
.badge.badge-success {
    color: #10B981 !important; /* Green */
    background-color: transparent !important;
    font-weight: 700 !important;
}

/* Dark Gray for returned, claimed, and completed statuses */
.badge.badge-secondary {
    color: #4B5563 !important; /* Dark Gray */
    background-color: transparent !important;
    font-weight: 700 !important;
}

/* Red for rejected status */
.badge.badge-danger {
    color: #DC2626 !important; /* Red */
    background-color: transparent !important;
    font-weight: 700 !important;
}

/* Blue for scheduled status */
.badge.badge-primary {
    color: #0EA5E9 !important; /* Blue */
    background-color: transparent !important;
    font-weight: 700 !important;
}

/* Light Gray for unknown/default statuses */
.badge.badge-light {
    color: #9CA3AF !important; /* Light Gray */
    background-color: transparent !important;
    font-weight: 700 !important;
}
    </style>
</head>
<body>

<!-- ========== NAVBAR - MATCHING STUDENT DASHBOARD ========== -->
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php"><i class="bi bi-box-seam"></i>Lost & Found</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="report_lost.php"><i class="bi bi-exclamation-circle me-1"></i>Report Lost</a></li>
                <li class="nav-item"><a class="nav-link" href="report_found.php"><i class="bi bi-check-circle me-1"></i>Report Found</a></li>
                <li class="nav-item"><a class="nav-link active" href="lost_items.php"><i class="bi bi-search me-1"></i>Browse Lost</a></li>
                <li class="nav-item"><a class="nav-link" href="found_items.php"><i class="bi bi-archive me-1"></i>Browse Found</a></li>
                <li class="nav-item"><a class="nav-link" href="my_items.php"><i class="bi bi-person-lines-fill me-1"></i>My Items</a></li>
                <li class="nav-item"><a class="nav-link" href="faq.php"><i class="bi bi-question-circle me-1"></i>FAQ</a></li>
            </ul>
            <span class="navbar-text me-3">
                <i class="bi bi-person-circle me-2"></i>Welcome, <strong><?= htmlspecialchars(getCurrentUserName()); ?></strong>
            </span>
            <a class="btn btn-outline-light btn-sm" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </div>
</nav>
<!-- ========== END OF NAVBAR ========== -->

<main class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="bi bi-exclamation-diamond-fill"></i><?= htmlspecialchars($item['item_name']); ?> - Details</h1>
    </div>

    <!-- Back Link -->
    <a href="lost_items.php" class="back-link">
        <i class="bi bi-arrow-left-circle-fill"></i>Back to Lost Items
    </a>

    <div class="row g-4">

        <!-- LOST ITEM SECTION -->
        <div class="col-md-6">
            <div class="card lost-item-card">
                <?php if ($item['photo']): ?>
                    <img src="<?= getImageUrl($item['photo'], 'lost'); ?>" class="card-img-top" alt="Lost Item">
                <?php else: ?>
                    <div class="no-image-text">
                        <i class="bi bi-image"></i>
                        No Image Available
                    </div>
                <?php endif; ?>

                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-tag-fill me-2"></i><?= htmlspecialchars($item['item_name']); ?></h5>
                    <p style="background: #FAFAFA; padding: 1rem; border-left: 4px solid var(--danger); border-radius: 4px;">
                        <?= nl2br(htmlspecialchars($item['description'])); ?>
                    </p>
                    <p><strong><i class="bi bi-calendar-x me-2"></i>Date Lost:</strong> <?= formatDate($item['date_lost']); ?></p>
                    <p><strong><i class="bi bi-person-fill me-2"></i>Reported By:</strong> <?= htmlspecialchars($item['owner_name']); ?></p>
                    <p><strong><i class="bi bi-calendar-plus me-2"></i>Reported On:</strong> <?= formatDateTime($item['created_at']); ?></p>
                    <p><strong><i class="bi bi-flag-fill me-2"></i>Status:</strong> <?= getStatusBadge($item['status']); ?></p>
                </div>
            </div>
        </div>

        <!-- MATCHING FOUND ITEMS SECTION -->
        <div class="col-md-6">

            <?php if (!empty($matchedFoundItems)): ?>
                <h4 class="section-header">
                    <i class="bi bi-search"></i>Matching Found Items
                </h4>

                <?php foreach ($matchedFoundItems as $found): ?>
                    <div class="card found-item-card">
                        <?php if ($found['photo']): ?>
                            <img src="<?= getImageUrl($found['photo'], 'found'); ?>" class="card-img-top" alt="Found Item">
                        <?php endif; ?>

                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($found['item_name']); ?></h5>
                            <p style="background: #FAFAFA; padding: 1rem; border-left: 4px solid var(--success); border-radius: 4px;">
                                <?= nl2br(htmlspecialchars($found['description'])); ?>
                            </p>
                            <p><strong><i class="bi bi-calendar-check me-2"></i>Date Found:</strong> <?= formatDate($found['date_found']); ?></p>
                            <p><strong><i class="bi bi-person-fill me-2"></i>Found By:</strong> <?= htmlspecialchars($found['finder_name']); ?></p>

                            <!-- View Details Button -->
                            <a href="view_found_item.php?id=<?= $found['id']; ?>" class="btn btn-outline-primary mb-2">
                                <i class="bi bi-eye-fill"></i>View Details
                            </a>

                            <!-- SHOW CLAIM STATUS ONLY -->
                            <?php if ($isOwner): ?>
                                <?php if (isset($existingClaims[$found['id']])): ?>
                                    <span class="badge bg-warning mt-2">
                                        <i class="bi bi-clock-fill me-1"></i>Claim Submitted - <?= getStatusBadge($existingClaims[$found['id']]['status']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary mt-2">
                                        <i class="bi bi-dash-circle me-1"></i>No Claim Submitted
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>No matching found items yet.</p>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- OWNER CLAIMS SUMMARY -->
    <?php if ($isOwner): ?>
        <div class="claims-summary-card">
            <h5><i class="bi bi-clipboard2-check-fill"></i>Your Submitted Claims for this Item</h5>
            <?php if (!empty($existingClaims)): ?>
                <ul>
                    <?php foreach ($existingClaims as $claim): ?>
                        <li>
                            <i class="bi bi-arrow-right-circle-fill me-2" style="color: var(--university-gold);"></i>
                            <strong><?= htmlspecialchars(getFoundItemName($claim['found_item_id'], $matchedFoundItems)); ?></strong>
                            - <?= getStatusBadge($claim['status']); ?>
                            <?php if ($claim['status'] === 'scheduled'): ?>
                                <span style="color: var(--success); font-weight: 700;">
                                    <i class="bi bi-calendar-event ms-2 me-1"></i>(Schedule: <?= formatDateTime($claim['schedule_date']); ?>)
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p><i class="bi bi-info-circle me-2"></i>No claims submitted yet for this lost item.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</main>

<!-- Footer -->
<footer>
    <div class="container">
        <p><i class="bi bi-shield-check"></i>&copy; 2025 Campus Lost & Found System. All rights reserved.</p>
    </div>
</footer>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>