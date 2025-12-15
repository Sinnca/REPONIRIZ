<?php global $pdo;
/**
 * Browse Found Items
 * List of all verified/listed found items for students to browse
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireStudent();

$userId = getCurrentUserId();

// 1. Get all verified/listed found items
$stmt = $pdo->prepare("
    SELECT fi.*, u.name as finder_name
    FROM found_items fi
    JOIN users u ON fi.user_id = u.id
    WHERE fi.status IN ('verified','listed')
    ORDER BY fi.created_at DESC
");
$stmt->execute();
$foundItems = $stmt->fetchAll();

// 2. Get all active claim requests made by this user (pending or scheduled)
$claimStmt = $pdo->prepare("
    SELECT found_item_id
    FROM claim_requests
    WHERE requester_id = :userId
      AND status IN ('pending', 'scheduled')
");
$claimStmt->execute(['userId' => $userId]);
$claimsByUser = $claimStmt->fetchAll(PDO::FETCH_COLUMN);

// 3. Get recent rejected claims (last 7 days) to show feedback
$rejectedClaimStmt = $pdo->prepare("
    SELECT found_item_id, notes
    FROM claim_requests
    WHERE requester_id = :userId
      AND status = 'rejected'
      AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$rejectedClaimStmt->execute(['userId' => $userId]);
$rejectedClaims = $rejectedClaimStmt->fetchAll(PDO::FETCH_KEY_PAIR); // key: found_item_id, value: notes
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Found Items - Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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

        /* Main Content */
        main {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem 1.5rem 4rem;
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

        /* Content Layout */
        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 450px;
            gap: 3rem;
            align-items: start;
        }
        
        .items-section {
            min-width: 0;
        }

        /* Item Cards */
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 2.5rem;
        }

        .card {
            border: 1px solid var(--border-color);
            border-radius: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            background: var(--white);
            overflow: hidden;
            transition: all 0.3s ease;
            border-top: 4px solid var(--success);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            transform: translateY(-6px);
            border-top-color: var(--gold);
        }

        .card-img-top {
            height: 300px;
            object-fit: cover;
            border-bottom: 2px solid var(--border-color);
        }

        .card .bg-secondary {
            height: 300px;
            background: #E5E7EB !important;
            border-bottom: 2px solid var(--border-color);
        }

        .card-body {
            padding: 2.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--navy);
            margin-bottom: 1.5rem;
            letter-spacing: -0.02em;
            text-transform: uppercase;
            line-height: 1.3;
        }

        .card-text {
            color: var(--text-light);
            font-size: 1.05rem;
            margin-bottom: 1.8rem;
            line-height: 1.8;
            flex: 1;
        }

        .card-body p {
            font-size: 1rem;
            margin-bottom: 1rem;
            color: var(--text-dark);
            line-height: 1.6;
        }

        .card-body strong {
            color: var(--navy);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
        }

        /* Buttons */
        .btn {
            border-radius: 4px;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 0.65rem 1.5rem;
            transition: all 0.2s ease;
            border: none;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .btn-sm {
            padding: 0.5rem 1.2rem;
            font-size: 0.8rem;
        }

        .btn-primary {
            background: var(--primary-blue);
            color: var(--white);
            box-shadow: 0 2px 4px rgba(0, 102, 255, 0.3);
        }

        .btn-primary:hover {
            background: var(--dark-blue);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 102, 255, 0.4);
            color: var(--white);
        }

        .btn-outline-success {
            border: 2px solid var(--success);
            color: var(--success);
            background: transparent;
            font-weight: 700;
        }

        .btn-outline-success:hover {
            background: var(--success);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
        }

        /* Badge Styles */
        .badge {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-radius: 0;
        }

        .bg-warning {
            background: var(--warning) !important;
            color: #78350F !important;
        }

        .bg-danger {
            background: var(--danger) !important;
            color: var(--white) !important;
        }

        .bg-success {
            background: var(--success) !important;
            color: var(--white) !important;
        }

        .bg-info {
            background: #0EA5E9 !important;
            color: var(--white) !important;
        }

        /* Alert */
        .alert {
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 0;
            padding: 1.5rem 2rem;
            font-weight: 500;
            border-left: 4px solid var(--primary-blue);
            font-size: 1rem;
        }

        .alert-info {
            background: #DBEAFE;
            color: #1E40AF;
            border-left-color: var(--primary-blue);
        }

        /* Card actions */
        .card-actions {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--border-color);
        }

        /* Rejection note */
        .rejection-note {
            background: #FEE2E2;
            border-left: 3px solid var(--danger);
            padding: 1rem 1.2rem;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #991B1B;
            font-style: italic;
            line-height: 1.6;
        }

        /* Info Section */
        .info-section {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--gold);
            position: sticky;
            top: 2rem;
        }

        .info-section h3 {
            color: var(--navy);
            font-weight: 800;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            letter-spacing: -0.02em;
            text-transform: uppercase;
        }

        .info-section p {
            color: var(--text-light);
            font-size: 0.95rem;
            line-height: 1.8;
            margin-bottom: 1.2rem;
        }

        .info-section ol {
            color: var(--text-dark);
            font-weight: 500;
            padding-left: 1.5rem;
            font-size: 0.95rem;
        }

        .info-section ol li {
            margin-bottom: 1rem;
            padding-left: 0.5rem;
            line-height: 1.7;
        }

        .info-section a {
            color: var(--primary-blue);
            font-weight: 700;
            text-decoration: none;
            border-bottom: 2px solid var(--primary-blue);
        }

        .info-section a:hover {
            color: var(--navy);
            border-bottom-color: var(--navy);
        }

        /* Footer */
        footer {
            background: var(--navy);
            color: var(--white);
            padding: 2rem 0;
            margin-top: 4rem;
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

        /* Status badges */
        .badge.bg-primary {
            background: var(--primary-blue) !important;
            color: var(--white) !important;
        }

        /* Responsive */
        @media (max-width: 1400px) {
            .content-wrapper {
                grid-template-columns: 1fr 400px;
                gap: 2.5rem;
            }
            
            .items-grid {
                grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            }
        }

        @media (max-width: 1200px) {
            .content-wrapper {
                grid-template-columns: 1fr;
            }
            
            .info-section {
                position: static;
                margin-top: 3rem;
            }
            
            .items-grid {
                grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 1.5rem;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .navbar-brand {
                font-size: 1.3rem;
            }

            .items-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .card-body {
                padding: 1.8rem;
            }
            
            .info-section {
                padding: 1.8rem;
            }
            
            main {
                padding: 1.5rem 1rem 3rem;
            }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php"><i class="bi bi-box-seam me-2"></i>Lost & Found</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="report_lost.php"><i class="bi bi-exclamation-circle me-1"></i>Report Lost</a></li>
                <li class="nav-item"><a class="nav-link" href="report_found.php"><i class="bi bi-check-circle me-1"></i>Report Found</a></li>
                <li class="nav-item"><a class="nav-link" href="lost_items.php"><i class="bi bi-search me-1"></i>Browse Lost</a></li>
                <li class="nav-item"><a class="nav-link active" href="found_items.php"><i class="bi bi-archive me-1"></i>Browse Found</a></li>
                <li class="nav-item"><a class="nav-link" href="my_items.php"><i class="bi bi-person-lines-fill me-1"></i>My Items</a></li>
            </ul>
            <span class="navbar-text me-3"><i class="bi bi-person-circle me-2"></i>Welcome, <strong><?= htmlspecialchars(getCurrentUserName()) ?></strong></span>
            <a class="btn btn-outline-primary btn-sm" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </div>
</nav>

<main class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="bi bi-archive me-2"></i>Browse Found Items</h1>
        <p>Check if your lost item has been found by someone. View details and claim your items here.</p>
    </div>

    <?php if (count($foundItems) > 0): ?>
        <div class="content-wrapper">
            <div class="items-section">
                <div class="items-grid">
                    <?php foreach ($foundItems as $item): ?>
                        <div class="card">
                            <?php if ($item['photo']): ?>
                                <img src="<?= getImageUrl($item['photo'], 'found'); ?>"
                                     class="card-img-top" alt="<?= htmlspecialchars($item['item_name']); ?>">
                            <?php else: ?>
                                <div class="bg-secondary text-dark d-flex align-items-center justify-content-center">
                                    <div class="text-center">
                                        <i class="bi bi-image" style="font-size: 3.5rem; color: #9CA3AF;"></i>
                                        <p class="mb-0 mt-2" style="color: #6B7280; font-weight: 600; font-size: 0.95rem;">No Image Available</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($item['item_name']); ?></h5>
                                <p class="card-text"><?= htmlspecialchars(substr($item['description'], 0, 120)) . '...'; ?></p>
                                <p class="mb-1"><strong>Date Found:</strong> <?= formatDate($item['date_found']); ?></p>
                                <p class="mb-2"><strong>Status:</strong> <?= getStatusBadge($item['status']); ?></p>
                                
                                <div class="card-actions">
                                    <a href="view_found_item.php?id=<?= $item['id']; ?>" class="btn btn-primary btn-sm me-2 mb-2">
                                        <i class="bi bi-eye me-1"></i>View Details
                                    </a>

                                    <?php
                                    // Claim status display logic
                                    if (in_array($item['id'], $claimsByUser)): ?>
                                        <span class="badge bg-warning mb-2">
                                            <i class="bi bi-clock-history me-1"></i>Pending Claim
                                        </span>
                                    <?php elseif (isset($rejectedClaims[$item['id']])): ?>
                                        <span class="badge bg-danger mb-2">
                                            <i class="bi bi-x-circle me-1"></i>Claim Rejected
                                        </span>
                                        <?php if (!empty($rejectedClaims[$item['id']])): ?>
                                            <div class="rejection-note">
                                                <i class="bi bi-info-circle me-1"></i>
                                                <strong>Admin Note:</strong> <?= htmlspecialchars($rejectedClaims[$item['id']]); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php elseif ($item['user_id'] != $userId): ?>
                                        <a href="view_found_item.php?id=<?= $item['id']; ?>" class="btn btn-outline-success btn-sm mb-2">
                                            <i class="bi bi-hand-thumbs-up me-1"></i>Claim This Item
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Info Section -->
            <aside class="info-section">
                <h3><i class="bi bi-question-circle me-2"></i>Is This Your Item?</h3>
                <p>If you see an item that belongs to you, follow these steps to claim it:</p>
                <ol>
                    <li><strong>Report your lost item first</strong> - Make sure you have submitted a lost item report in the system</li>
                    <li><strong>View the item details</strong> - Click "View Details" to verify it matches your lost item description</li>
                    <li><strong>Admin verification</strong> - The admin will link the found item to your lost item report</li>
                    <li><strong>Request a claim</strong> - Once linked, you can request a claim from your dashboard</li>
                </ol>
                <p style="margin-top: 1.5rem;">
                    <i class="bi bi-exclamation-triangle me-2" style="color: var(--warning);"></i>
                    Haven't reported your lost item yet? <a href="report_lost.php">Report it now</a> to start the claiming process.
                </p>
            </aside>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <strong>No found items currently available.</strong> Check back later or report a lost item.
        </div>
    <?php endif; ?>
</main>

<footer class="text-center">
    <div class="container">
        <p><i class="bi bi-shield-check"></i>&copy; 2025 Campus Lost & Found System. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>