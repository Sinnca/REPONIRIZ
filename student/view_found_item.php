<?php
global $pdo;
/**
 * View Found Item Details
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
    header('Location: found_items.php');
    exit;
}

// Get found item details with safe defaults
$stmt = $pdo->prepare("
    SELECT 
        fi.id,
        fi.item_name,
        COALESCE(fi.description, '') AS description,
        COALESCE(fi.photo, '') AS photo,
        fi.date_found,
        fi.status,
        fi.user_id,
        COALESCE(fi.created_at, '') AS created_at,
        u.name AS finder_name,
        u.email AS finder_email
    FROM found_items fi
    JOIN users u ON fi.user_id = u.id
    WHERE fi.id = ?
");
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: found_items.php');
    exit;
}

// Check if current user is the finder
$isFinder = ($item['user_id'] == $userId);

// Check if current user already submitted a claim for this found item
$stmtClaim = $pdo->prepare("
    SELECT COUNT(*) FROM claim_requests 
    WHERE found_item_id = ? AND requester_id = ?
");
$stmtClaim->execute([$itemId, $userId]);
$existingClaimCount = $stmtClaim->fetchColumn();

// Allow claim if user didn't already submit
$canClaim = !$isFinder && $existingClaimCount == 0 && in_array($item['status'], ['verified','listed', 'pending']);
// Attempt to fetch related lost_item_id for this found item
$stmtLostLink = $pdo->prepare("SELECT lost_item_id FROM found_items WHERE id = ?");
$stmtLostLink->execute([$itemId]);
$lostLink = $stmtLostLink->fetch();
$lostItemIdForClaim = $lostLink['lost_item_id'] ?? null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($item['item_name']); ?> - Found Item Details</title>
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
            border-left: 6px solid var(--success);
            border-bottom: 1px solid var(--border-color);
        }

        .page-header h1 {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--navy);
            margin: 0;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            text-transform: uppercase;
        }

        .page-header h1 i {
            margin-right: 1rem;
            font-size: 2rem;
            color: var(--success);
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

        /* Item Details Section */
        .item-details-card {
            background: var(--white);
            border-radius: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            padding: 2.5rem;
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
            border-top: 4px solid var(--success);
            position: relative;
        }

        .item-details-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--success) 0%, var(--university-gold) 100%);
        }

        /* Image Display */
        .item-image-container {
            background: #FAFAFA;
            padding: 1.5rem;
            border-radius: 4px;
            border: 2px solid var(--border-color);
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .item-image-container img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .no-image-placeholder {
            background: linear-gradient(135deg, var(--university-blue) 0%, var(--navy) 100%);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 400px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .no-image-placeholder i {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
            color: var(--university-gold);
        }

        /* Item Info */
        .item-info h2 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--navy);
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: -0.02em;
            border-bottom: 3px solid var(--university-gold);
            padding-bottom: 1rem;
        }

        .item-info p {
            font-size: 1rem;
            line-height: 1.8;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            background: #FAFAFA;
            border-left: 4px solid var(--success);
            border-radius: 4px;
        }

        /* List Group */
        .list-group {
            border-radius: 0;
        }

        .list-group-item {
            border: none;
            background: #FAFAFA;
            margin-bottom: 0.5rem;
            border-radius: 0;
            border-left: 3px solid var(--university-blue);
            padding: 1rem 1.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .list-group-item:hover {
            background: var(--light-blue);
            transform: translateX(4px);
            border-left-color: var(--success);
        }

        .list-group-item strong {
            color: var(--navy);
            font-weight: 700;
            display: inline-block;
            min-width: 150px;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
        }

        /* Alert Messages */
        .alert {
            border-radius: 0;
            border: none;
            padding: 1.5rem;
            margin-bottom: 2rem;
            font-weight: 500;
            animation: slideIn 0.5s ease-out;
            border-left: 5px solid;
        }

        .alert h5 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            text-transform: uppercase;
        }

        .alert h5 i {
            margin-right: 0.8rem;
            font-size: 1.5rem;
        }

        .alert-info {
            background: #DBEAFE;
            color: #1E40AF;
            border-left-color: var(--info);
        }

        .alert-warning {
            background: #FEF3C7;
            color: #92400E;
            border-left-color: var(--warning);
        }

        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border-left-color: var(--success);
        }

        .alert-danger {
            background: #FEE2E2;
            color: #991B1B;
            border-left-color: #DC2626;
        }

        /* Claim Section */
        .claim-section {
            background: var(--white);
            border-radius: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            padding: 2.5rem;
            border: 1px solid var(--border-color);
            margin-top: 3rem;
            border-top: 4px solid var(--university-gold);
            position: relative;
        }

        .claim-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--university-gold) 0%, var(--success) 100%);
        }

        .claim-section h3 {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--navy);
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
        }

        .claim-section h3 i {
            margin-right: 1rem;
            color: var(--university-gold);
            font-size: 2rem;
        }

        /* Form Styles */
        .form-label {
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 0.6rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-label i {
            margin-right: 0.6rem;
            color: var(--university-gold);
            font-size: 1rem;
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 4px;
            padding: 0.85rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            font-weight: 500;
            background: var(--white);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--university-blue);
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
            outline: none;
            background: var(--white);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
            font-family: 'Inter', sans-serif;
        }

        /* File Input */
        .form-control[type="file"] {
            padding: 0.75rem 1rem;
        }

        .form-control[type="file"]::file-selector-button {
            background: var(--university-blue);
            color: var(--white);
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 4px;
            font-weight: 700;
            margin-right: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
        }

        .form-control[type="file"]::file-selector-button:hover {
            background: var(--navy);
            transform: translateY(-1px);
        }

        /* Buttons */
        .btn {
            border-radius: 4px;
            font-weight: 700;
            font-size: 0.9rem;
            padding: 0.75rem 1.8rem;
            transition: all 0.2s ease;
            border: none;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .btn i {
            margin-right: 0.5rem;
        }

        .btn-success {
            background: var(--success);
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.4);
        }

        .btn-secondary {
            background: #6B7280;
            box-shadow: 0 2px 4px rgba(107, 114, 128, 0.3);
        }

        .btn-secondary:hover {
            background: #4B5563;
            transform: translateY(-1px);
        }

        /* Card with Light Background */
        .card.bg-light {
            background: linear-gradient(135deg, #F0F9FF 0%, #E0F2FE 100%) !important;
            border: 2px solid var(--border-color);
            border-left: 4px solid var(--info);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .card-body p {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
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

        @keyframes slideIn {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.3rem;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .item-details-card {
                padding: 1.5rem;
            }

            .claim-section {
                padding: 1.5rem;
            }

            .list-group-item strong {
                display: block;
                margin-bottom: 0.3rem;
            }
        }

        .mb-3 {
            margin-bottom: 1.5rem !important;
        }

        /* Info Box in Claim Section */
        .info-box {
            background: #F0F9FF;
            border-left: 4px solid var(--info);
            padding: 1.2rem 1.5rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }

        .info-box p {
            margin: 0;
            font-weight: 600;
            color: var(--navy);
            display: flex;
            align-items: center;
        }

        .info-box p i {
            margin-right: 0.8rem;
            color: var(--university-gold);
            font-size: 1.5rem;
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
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="report_lost.php"><i class="bi bi-exclamation-circle me-1"></i>Report Lost</a></li>
                <li class="nav-item"><a class="nav-link" href="report_found.php"><i class="bi bi-check-circle me-1"></i>Report Found</a></li>
                <li class="nav-item"><a class="nav-link" href="lost_items.php"><i class="bi bi-search me-1"></i>Browse Lost</a></li>
                <li class="nav-item"><a class="nav-link active" href="found_items.php"><i class="bi bi-archive me-1"></i>Browse Found</a></li>
                <li class="nav-item"><a class="nav-link" href="my_items.php"><i class="bi bi-person-lines-fill me-1"></i>My Items</a></li>
            </ul>
            <span class="navbar-text me-3"><i class="bi bi-person-circle me-2"></i>Welcome, <strong><?= htmlspecialchars(getCurrentUserName()); ?></strong></span>
            <a class="btn btn-outline-light btn-sm" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </div>
</nav>
<!-- ========== END OF NAVBAR ========== -->

<main class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="bi bi-eye-fill"></i>Found Item Details</h1>
    </div>

    <!-- Back Link -->
    <a href="found_items.php" class="back-link">
        <i class="bi bi-arrow-left-circle-fill"></i>Back to Found Items
    </a>

    <!-- Item Details -->
    <div class="item-details-card">
        <div class="row g-4">
            <!-- Image Section -->
            <div class="col-md-5">
                <div class="item-image-container">
                    <?php if (!empty($item['photo'])): ?>
                        <img src="<?= getImageUrl($item['photo'], 'found'); ?>" alt="<?= htmlspecialchars($item['item_name']); ?>">
                    <?php else: ?>
                        <div class="no-image-placeholder">
                            <div>
                                <i class="bi bi-image d-block"></i>
                                No Image Available
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info Section -->
            <div class="col-md-7 item-info">
                <h2><?= htmlspecialchars($item['item_name']); ?></h2>
                <p><?= nl2br(htmlspecialchars($item['description'])); ?></p>

                <ul class="list-group mb-3">
                    <li class="list-group-item">
                        <strong><i class="bi bi-calendar-event me-2"></i>Date Found:</strong> 
                        <?= !empty($item['date_found']) ? formatDate($item['date_found']) : 'N/A'; ?>
                    </li>
                    <li class="list-group-item">
                        <strong><i class="bi bi-person-fill me-2"></i>Found By:</strong> 
                        <?= htmlspecialchars($item['finder_name']); ?>
                    </li>
                    <li class="list-group-item">
                        <strong><i class="bi bi-calendar-plus me-2"></i>Date Reported:</strong> 
                        <?= !empty($item['created_at']) ? formatDateTime($item['created_at']) : 'N/A'; ?>
                    </li>
                    <li class="list-group-item">
                        <strong><i class="bi bi-flag-fill me-2"></i>Status:</strong> 
                        <?= getStatusBadge($item['status']); ?>
                    </li>
                </ul>

                <?php if ($isFinder): ?>
                    <div class="alert alert-info">
                        <h5><i class="bi bi-info-circle-fill"></i>You Reported This Item</h5>
                        <p>This item is <strong><?= htmlspecialchars($item['status']); ?></strong>.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Claim Section -->
    <?php if ($canClaim): ?>
        <section class="claim-section">
            <h3><i class="bi bi-hand-thumbs-up-fill"></i>Is This Your Item?</h3>
            
            <div class="info-box">
                <p><i class="bi bi-exclamation-circle-fill"></i><strong>If this is your lost item, submit a claim request below!</strong></p>
            </div>

            <div class="card bg-light">
                <div class="card-body">
                    <form id="claimFoundItemForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="claim_photo" class="form-label">
                                <i class="bi bi-cloud-upload-fill"></i>Upload Photo (Optional)
                            </label>
                            <input type="file" name="claim_photo" id="claim_photo" class="form-control" accept="image/*">
                        </div>

                        <div class="mb-3">
                            <label for="claim_item_name" class="form-label">
                                <i class="bi bi-tag-fill"></i>Item Name (Auto-filled — editable)
                            </label>
                            <input type="text" id="claim_item_name" name="item_name" class="form-control" value="<?= htmlspecialchars($item['item_name']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="claim_description" class="form-label">
                                <i class="bi bi-file-text-fill"></i>Description / Notes
                            </label>
                            <textarea id="claim_description" name="description" class="form-control" rows="4"><?= htmlspecialchars($item['description']); ?></textarea>
                        </div>

                        <div id="claimAlertContainer"></div>

                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-send-fill"></i>Request Claim
                        </button>
                        <input type="hidden" name="lost_item_id" value="<?= $lostItemIdForClaim !== null ? $lostItemIdForClaim : '' ?>">
                        <input type="hidden" name="found_item_id" value="<?= $item['id'] ?>">
                    </form>
                </div>
            </div>
        </section>
    <?php elseif (!$isFinder && !$canClaim): ?>
        <div class="alert alert-warning mt-4">
            <h5><i class="bi bi-exclamation-triangle-fill"></i>Claim Already Submitted</h5>
            <p>You already submitted a claim request for this item. Your request is currently pending review.</p>
        </div>
    <?php endif; ?>

</main>

<!-- Footer -->
<footer>
    <div class="container">
        <p><i class="bi bi-shield-check"></i>&copy; 2024 Campus Lost & Found System. All rights reserved.</p>
    </div>
</footer>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('claimFoundItemForm');
        if (!form) return;

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Submitting...';

            const formData = new FormData();
            formData.append('found_item_id', <?= $itemId; ?>);
            formData.append('item_name', document.getElementById('claim_item_name').value);
            formData.append('description', document.getElementById('claim_description').value);
            formData.append('lost_item_id', <?= $lostItemIdForClaim ?? 'null'; ?>);


            const fileInput = document.getElementById('claim_photo');
            if (fileInput.files[0]) {
                formData.append('claim_photo', fileInput.files[0]);
            }

            try {
                const response = await fetch('../api/claims/create_claim.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                const alertContainer = document.getElementById('claimAlertContainer');

                if (data.success) {
                    alertContainer.innerHTML = `<div class="alert alert-success"><h5><i class="bi bi-check-circle-fill"></i>Success!</h5><p>${data.message}</p></div>`;
                    form.reset();
                } else {
                    alertContainer.innerHTML = `<div class="alert alert-danger"><h5><i class="bi bi-x-circle-fill"></i>Error</h5><p>${data.message}</p></div>`;
                }
            } catch (err) {
                const alertContainer = document.getElementById('claimAlertContainer');
                alertContainer.innerHTML = `<div class="alert alert-danger"><h5><i class="bi bi-x-circle-fill"></i>Error</h5><p>An error occurred: ${err.message}</p></div>`;
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    });
</script>

</body>
</html>