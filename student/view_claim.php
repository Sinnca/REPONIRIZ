<?php
global $pdo;
/**
 * Student - View Claim Details
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireStudent();

$userId = getCurrentUserId();
$claimId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$claimId) header('Location: index.php');

// Fetch claim details using COALESCE to handle missing claim_requests info
$stmt = $pdo->prepare("
    SELECT cr.*, 
           COALESCE(cr.item_name, li.item_name) AS display_item_name,
           COALESCE(cr.item_description, li.description) AS display_item_description,
           COALESCE(cr.photo, li.photo) AS display_photo,
           li.date_lost AS display_date_lost,
           fi.item_name AS found_item_name,
           fi.description AS found_description,
           fi.photo AS found_photo,
           fi.date_found,
           admin.name AS admin_name
    FROM claim_requests cr
    LEFT JOIN lost_items li ON cr.lost_item_id = li.id
    LEFT JOIN found_items fi ON cr.found_item_id = fi.id
    LEFT JOIN users admin ON cr.admin_id = admin.id
    WHERE cr.id = ? AND cr.requester_id = ?
");
$stmt->execute([$claimId, $userId]);
$claim = $stmt->fetch();

if (!$claim) header('Location: index.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Details - Lost & Found</title>
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
            max-width: 1200px;
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
            border-left: 6px solid var(--university-blue);
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
            color: var(--university-gold);
        }

        .page-header .claim-id {
            display: inline-block;
            background: var(--university-gold);
            color: var(--university-blue);
            padding: 0.5rem 1.2rem;
            border-radius: 4px;
            font-weight: 800;
            font-size: 1.1rem;
            margin-top: 1rem;
            letter-spacing: 0.05em;
        }

        /* Back Link */
        .back-link {
            display: inline-flex;
            align-items: center;
            color: var(--university-blue);
            font-weight: 600;
            text-decoration: none;
            margin-bottom: 1.5rem;
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

        /* Status Alerts */
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

        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border-left-color: var(--success);
        }

        .alert-danger {
            background: #FEE2E2;
            color: #991B1B;
            border-left-color: var(--danger);
        }

        .alert ul {
            margin: 1rem 0;
            padding-left: 1.5rem;
        }

        .alert ul li {
            margin: 0.5rem 0;
            font-weight: 500;
        }

        .alert p {
            margin-bottom: 1rem;
        }

        /* Cards */
        .card {
            border: 1px solid var(--border-color);
            border-radius: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            background: var(--white);
            margin-bottom: 2rem;
            border-top: 4px solid var(--university-blue);
            position: relative;
            overflow: hidden;
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

        .card-body {
            padding: 2rem;
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--navy);
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .card-title i {
            margin-right: 0.8rem;
            color: var(--university-gold);
            font-size: 1.6rem;
        }

        .card p {
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .card p strong {
            color: var(--navy);
            font-weight: 700;
            display: inline-block;
            min-width: 150px;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
        }

        /* Image Display */
        .img-fluid {
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 2px solid var(--border-color);
            max-width: 100%;
            height: auto;
        }

        .form-label {
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 0.8rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
        }

        .form-label i {
            margin-right: 0.5rem;
            color: var(--university-gold);
        }

        /* Next Steps Card */
        .next-steps-card {
            background: linear-gradient(135deg, #F0F9FF 0%, #E0F2FE 100%);
            border-left-color: var(--info);
        }

        .next-steps-card ol {
            margin: 1.5rem 0;
            padding-left: 1.8rem;
        }

        .next-steps-card ol li {
            margin: 1rem 0;
            padding-left: 0.5rem;
            font-weight: 500;
            line-height: 1.6;
        }

        .next-steps-card ol li strong {
            color: var(--university-blue);
            font-weight: 800;
        }

        .text-warning {
            color: var(--warning) !important;
            font-weight: 700;
            padding: 1rem;
            background: #FEF3C7;
            border-radius: 4px;
            border-left: 4px solid var(--warning);
            margin-top: 1rem;
        }

        /* Section with Images */
        .info-section {
            padding: 2rem;
        }

        .info-section h5 {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--navy);
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .info-section h5 i {
            margin-right: 0.8rem;
            color: var(--university-gold);
            font-size: 1.6rem;
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

            .card-body {
                padding: 1.5rem;
            }

            .info-section {
                padding: 1.5rem;
            }

            .card p strong {
                display: block;
                margin-bottom: 0.3rem;
            }
        }

        /* Badge for Claim Reference */
        .badge-custom {
            background: var(--university-blue);
            color: var(--university-gold);
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: 800;
            font-size: 1.1rem;
            letter-spacing: 0.05em;
            display: inline-block;
        }

        /* Highlight Important Info */
        .highlight-box {
            background: #FEF3C7;
            border-left: 4px solid var(--university-gold);
            padding: 1rem 1.5rem;
            border-radius: 4px;
            margin: 1rem 0;
        }

        .highlight-box p {
            margin-bottom: 0;
        }

        .highlight-box strong {
            color: var(--university-blue);
            font-weight: 800;
        }

        /* Layout wrapper for side-by-side sections */
        .content-wrapper {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
        }

        .main-content {
            flex: 1;
        }

        .sidebar-content {
            width: 350px;
            flex-shrink: 0;
        }

        @media (max-width: 992px) {
            .content-wrapper {
                flex-direction: column;
            }

            .sidebar-content {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<!-- ========== NAVBAR - MATCHING STUDENT DASHBOARD ========== -->
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php"><i class="bi bi-box-seam"></i>Lost & Found</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="report_lost.php"><i class="bi bi-exclamation-circle me-1"></i>Report Lost</a></li>
                <li class="nav-item"><a class="nav-link" href="report_found.php"><i class="bi bi-check-circle me-1"></i>Report Found</a></li>
                <li class="nav-item"><a class="nav-link" href="lost_items.php"><i class="bi bi-search me-1"></i>Browse Lost</a></li>
                <li class="nav-item"><a class="nav-link" href="found_items.php"><i class="bi bi-archive me-1"></i>Browse Found</a></li>
                <li class="nav-item"><a class="nav-link active" href="my_items.php"><i class="bi bi-person-lines-fill me-1"></i>My Items</a></li>
                <li class="nav-item"><a class="nav-link" href="faq.php"><i class="bi bi-question-circle me-1"></i>FAQ</a></li>
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
        <h1><i class="bi bi-clipboard-check-fill"></i>Claim Request Details</h1>
        <div class="claim-id">CLAIM #<?= $claim['id']; ?></div>
    </div>

    <!-- Back Link -->
    <a href="my_items.php" class="back-link">
        <i class="bi bi-arrow-left-circle-fill"></i>Back to My Items
    </a>

    <div class="content-wrapper">
        <div class="main-content">
            <!-- Status Alert -->
            <div class="mb-4">
                <?php if ($claim['status'] === 'pending'): ?>
                    <div class="alert alert-info">
                        <h5><i class="bi bi-hourglass-split"></i>Pending Review</h5>
                        <p>Your claim request is being reviewed by the admin. You will be notified once a decision is made.</p>
                    </div>
                <?php elseif ($claim['status'] === 'scheduled'): ?>
                    <div class="alert alert-success">
                        <h5><i class="bi bi-check-circle-fill"></i>Claim Approved!</h5>
                        <div class="highlight-box">
                            <p><strong><i class="bi bi-calendar-event me-2"></i>Scheduled Date:</strong> <?= formatDateTime($claim['schedule_date']); ?></p>
                        </div>
                        <p><strong>Please bring the following when claiming your item:</strong></p>
                        <ul>
                            <li><i class="bi bi-card-checklist me-2"></i>Valid Student ID</li>
                            <li><i class="bi bi-file-earmark-text me-2"></i>Proof of ownership (photo, receipt, or unique features)</li>
                            <li><i class="bi bi-hash me-2"></i>Claim reference number: <span class="badge-custom">#<?= $claim['id']; ?></span></li>
                        </ul>
                    </div>
                <?php elseif ($claim['status'] === 'completed'): ?>
                    <div class="alert alert-success">
                        <h5><i class="bi bi-check-circle-fill"></i>Claim Completed!</h5>
                        <p>Your item has been returned successfully. Thank you for using the Lost & Found system.</p>
                    </div>
                <?php elseif ($claim['status'] === 'rejected'): ?>
                    <div class="alert alert-danger">
                        <h5><i class="bi bi-x-circle-fill"></i>Claim Rejected</h5>
                        <p>Your claim request was rejected. Please contact the admin office for more information.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Claim Information -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-info-circle-fill"></i>Claim Information</h5>
                    <p><strong><i class="bi bi-hash me-2"></i>Claim ID:</strong> #<?= $claim['id']; ?></p>
                    <p><strong><i class="bi bi-calendar-plus me-2"></i>Request Date:</strong> <?= formatDateTime($claim['created_at']); ?></p>
                    <?php if (!empty($claim['schedule_date'])): ?>
                        <p><strong><i class="bi bi-calendar-event me-2"></i>Scheduled Date:</strong> <?= formatDateTime($claim['schedule_date']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($claim['admin_name'])): ?>
                        <p><strong><i class="bi bi-person-badge me-2"></i>Processed By:</strong> <?= htmlspecialchars($claim['admin_name']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($claim['notes'])): ?>
                        <p><strong><i class="bi bi-chat-left-text me-2"></i>Admin Notes:</strong> <?= nl2br(htmlspecialchars($claim['notes'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Claimer Provided Information -->
            <div class="card">
                <div class="info-section">
                    <h5><i class="bi bi-person-fill"></i>Your Provided Information</h5>
                    <div class="row g-4">
                        <?php if (!empty($claim['display_photo'])): ?>
                            <div class="col-md-4">
                                <label class="form-label"><i class="bi bi-image-fill"></i>Claim Photo:</label>
                                <img src="<?= getImageUrl($claim['display_photo'], 'claim'); ?>" class="img-fluid" alt="Claim Photo">
                            </div>
                        <?php endif; ?>

                        <div class="<?= !empty($claim['display_photo']) ? 'col-md-8' : 'col-12'; ?>">
                            <p><strong><i class="bi bi-tag-fill me-2"></i>Item Name:</strong> <?= htmlspecialchars($claim['display_item_name'] ?? 'N/A'); ?></p>
                            <p><strong><i class="bi bi-file-text-fill me-2"></i>Description:</strong> <?= nl2br(htmlspecialchars($claim['display_item_description'] ?? 'N/A')); ?></p>
                            <p><strong><i class="bi bi-calendar-x me-2"></i>Date Lost:</strong> <?= !empty($claim['display_date_lost']) ? formatDate($claim['display_date_lost']) : 'N/A'; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Found Item Information -->
            <?php if (!empty($claim['found_item_id'])): ?>
                <div class="card">
                    <div class="info-section">
                        <h5><i class="bi bi-search"></i>Found Item Information</h5>
                        <div class="row g-4">
                            <?php if (!empty($claim['found_photo'])): ?>
                                <div class="col-md-4">
                                    <label class="form-label"><i class="bi bi-image-fill"></i>Finder Provided Photo:</label>
                                    <img src="<?= getImageUrl($claim['found_photo'], 'found'); ?>" class="img-fluid" alt="Found Item Photo">
                                </div>
                            <?php endif; ?>
                            <div class="<?= !empty($claim['found_photo']) ? 'col-md-8' : 'col-12'; ?>">
                                <p><strong><i class="bi bi-tag-fill me-2"></i>Item Name:</strong> <?= htmlspecialchars($claim['found_item_name'] ?? 'N/A'); ?></p>
                                <p><strong><i class="bi bi-file-text-fill me-2"></i>Description:</strong> <?= nl2br(htmlspecialchars($claim['found_description'] ?? 'N/A')); ?></p>
                                <p><strong><i class="bi bi-calendar-check me-2"></i>Date Found:</strong> <?= !empty($claim['date_found']) ? formatDate($claim['date_found']) : 'N/A'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar: Next Steps -->
        <?php if ($claim['status'] === 'scheduled'): ?>
            <div class="sidebar-content">
                <div class="card next-steps-card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-list-check"></i>Next Steps</h5>
                        <ol>
                            <li>Save your claim reference number: <span class="badge-custom">#<?= $claim['id']; ?></span></li>
                            <li>Arrive at the Lost & Found office on <strong><?= formatDateTime($claim['schedule_date']); ?></strong></li>
                            <li>Present student ID and proof of ownership</li>
                            <li>Verify item matches your lost item</li>
                            <li>Sign acknowledgment form</li>
                            <li>Receive your item</li>
                        </ol>
                        <p class="text-warning"><strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Important:</strong> Contact admin to reschedule if you cannot attend.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
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