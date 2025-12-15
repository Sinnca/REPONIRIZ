<?php global $pdo;
/**
 * Browse Lost Items
 * List of all verified lost items for students to browse
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireStudent();

$userId = getCurrentUserId();

// Get all listed lost items (not pending, not returned)
$stmt = $pdo->prepare("
    SELECT li.*, u.name as owner_name 
    FROM lost_items li
    JOIN users u ON li.user_id = u.id
    WHERE li.status IN ('listed', 'ready_for_claim')
    ORDER BY li.created_at DESC
");
$stmt->execute();
$lostItems = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Lost Items - Lost & Found</title>
    <!-- Bootstrap CSS -->
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
            border-top: 4px solid var(--primary-blue);
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

        .card-footer {
            background: #FAFAFA;
            border-top: 2px solid var(--border-color);
            padding: 1.2rem 2.5rem;
            font-size: 0.95rem;
            color: var(--text-dark);
            font-weight: 600;
        }

        .card-footer i {
            color: var(--primary-blue);
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

        .btn-info {
            background: #0EA5E9;
            color: var(--white);
            box-shadow: 0 2px 4px rgba(14, 165, 233, 0.3);
        }

        .btn-info:hover {
            background: #0284C7;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(14, 165, 233, 0.4);
            color: var(--white);
        }

        .btn-success {
            background: var(--success);
            color: var(--white);
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.4);
            color: var(--white);
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

        .bg-secondary {
            background: #6B7280 !important;
            color: var(--white) !important;
        }

        .bg-info {
            background: #0EA5E9 !important;
            color: var(--white) !important;
        }

        .bg-success {
            background: var(--success) !important;
            color: var(--white) !important;
        }

        .bg-danger {
            background: #DC2626 !important;
            color: var(--white) !important;
        }

        .bg-primary {
            background: var(--primary-blue) !important;
            color: var(--white) !important;
        }

        /* Card actions */
        .card-actions {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--border-color);
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

        .info-section h2 {
            color: var(--navy);
            font-weight: 800;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            letter-spacing: -0.02em;
            text-transform: uppercase;
            padding-bottom: 1.2rem;
            border-bottom: 2px solid var(--border-color);
        }

        .info-section h2 i {
            color: var(--gold);
        }

        .info-section p {
            color: var(--text-light);
            font-size: 0.95rem;
            line-height: 1.8;
            margin-bottom: 1.2rem;
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

        /* Empty State */
        .empty-state {
            background: var(--white);
            padding: 5rem 2rem;
            text-align: center;
            border-radius: 0;
            border: 2px dashed var(--border-color);
            border-top: 4px solid var(--primary-blue);
        }

        .empty-state i {
            font-size: 5rem;
            color: var(--primary-blue);
            margin-bottom: 1.5rem;
        }

        .empty-state p {
            color: var(--text-light);
            font-size: 1.2rem;
            margin: 0;
            font-weight: 500;
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
            
            .card-footer {
                padding: 1rem 1.8rem;
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
                <li class="nav-item"><a class="nav-link active" href="lost_items.php"><i class="bi bi-search me-1"></i>Browse Lost</a></li>
                <li class="nav-item"><a class="nav-link" href="found_items.php"><i class="bi bi-archive me-1"></i>Browse Found</a></li>
                <li class="nav-item"><a class="nav-link" href="my_items.php"><i class="bi bi-person-lines-fill me-1"></i>My Items</a></li>
                <li class="nav-item"><a class="nav-link" href="faq.php"><i class="bi bi-question-circle me-1"></i>FAQ</a></li>
            </ul>
            <span class="navbar-text me-3"><i class="bi bi-person-circle me-2"></i>Welcome, <strong><?= htmlspecialchars(getCurrentUserName()) ?></strong></span>
            <a class="btn btn-outline-primary btn-sm" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </div>
</nav>

<main class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="bi bi-search me-2"></i>Browse Lost Items</h1>
        <p>If you found any of these items, click "I Found This Item" to report it and help reunite it with its owner.</p>
    </div>

    <div class="content-wrapper">
        <!-- Left Column: Items Grid -->
        <div class="items-section">
            <?php if (count($lostItems) > 0): ?>
                <div class="items-grid">
                    <?php foreach ($lostItems as $item): ?>
                        <div class="card">
                            <?php if ($item['photo']): ?>
                                <img src="<?php echo getImageUrl($item['photo'], 'lost'); ?>"
                                     class="card-img-top" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                            <?php else: ?>
                                <div class="bg-secondary text-dark d-flex align-items-center justify-content-center">
                                    <div class="text-center">
                                        <i class="bi bi-image" style="font-size: 3.5rem; color: #9CA3AF;"></i>
                                        <p class="mb-0 mt-2" style="color: #6B7280; font-weight: 600; font-size: 0.95rem;">No Image Available</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars(substr($item['description'], 0, 120)) . '...'; ?></p>
                                <p class="mb-1"><strong>Date Lost:</strong> <?php echo formatDate($item['date_lost']); ?></p>
                                <p class="mb-2"><strong>Status:</strong> <?php echo getStatusBadge($item['status']); ?></p>
                                
                                <div class="card-actions">
                                    <a href="view_lost_item.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-info me-2 mb-2">
                                        <i class="bi bi-eye me-1"></i>View Details
                                    </a>

                                    <?php if ($item['status'] === 'listed'): ?>
                                        <?php if ($item['user_id'] != $userId): ?>
                                            <?php
                                            // Check if the current user already reported this lost item as found
                                            $stmtCheck = $pdo->prepare("SELECT id FROM found_items WHERE user_id = ? AND lost_item_id = ? LIMIT 1");
                                            $stmtCheck->execute([$userId, $item['id']]);
                                            $alreadyReported = $stmtCheck->fetch();
                                            ?>
                                            <?php if (!$alreadyReported): ?>
                                                <a href="report_found.php?lost_item_id=<?php echo $item['id']; ?>"
                                                   class="btn btn-sm btn-success mb-2">
                                                   <i class="bi bi-hand-thumbs-up me-1"></i>I Found This Item
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-warning mb-2">
                                                    <i class="bi bi-check-circle me-1"></i>Already Reported as Found
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-success mb-2" style="height: 2rem;">
                                                <i class="bi bi-person-check me-1"></i>You reported this item
                                            </span>
                                        <?php endif; ?>
                                    <?php elseif ($item['status'] === 'ready_for_claim'): ?>
                                        <span class="badge bg-warning mb-2">
                                            <i class="bi bi-exclamation-triangle me-1"></i>Match Found
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer">
                                <i class="bi bi-person-circle me-1"></i>Reported by: <?php echo htmlspecialchars($item['owner_name']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>No lost items currently listed.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Info Section -->
        <aside>
            <section class="info-section">
                <h2><i class="bi bi-question-circle me-2"></i>Found Something?</h2>
                <p>If you found an item that matches one of the listings above, click <strong>"I Found This Item"</strong> to help reunite it with its owner.</p>
                <p>Your report will be verified by the admin before connecting you with the item's owner.</p>
                <p style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid var(--border-color);">
                    <i class="bi bi-info-circle me-1" style="color: var(--primary-blue);"></i>
                    Don't see a matching item? You can still <a href="report_found.php">report your found item</a> directly, and we'll help match it with its rightful owner.
                </p>
            </section>
        </aside>
    </div>
</main>

<footer class="text-center">
    <div class="container">
        <p><i class="bi bi-shield-check"></i>&copy; 2025 Campus Lost & Found System. All rights reserved.</p>
    </div>
</footer>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>