<?php global $pdo;
/**
 * Student - Edit Lost Item
 * Edit lost item details (only if status is pending)
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireStudent();

$userId = getCurrentUserId();
$itemId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';

if (!$itemId) {
    header('Location: my_items.php');
    exit;
}

// Get lost item details
$stmt = $pdo->prepare("SELECT * FROM lost_items WHERE id = ? AND user_id = ?");
$stmt->execute([$itemId, $userId]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: my_items.php');
    exit;
}

// Only allow editing if status is pending
if ($item['status'] !== 'pending') {
    header('Location: view_lost_item.php?id=' . $itemId);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemName = sanitize($_POST['item_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $dateLost = sanitize($_POST['date_lost'] ?? '');
    $removePhoto = isset($_POST['remove_photo']);

    // Validation
    if (empty($itemName) || empty($description) || empty($dateLost)) {
        $error = 'All fields are required.';
    } elseif (!isValidDate($dateLost)) {
        $error = 'Invalid date format.';
    } else {
        $photoFilename = $item['photo'];

        // Handle photo removal
        if ($removePhoto && $photoFilename) {
            deleteImage($photoFilename, 'lost');
            $photoFilename = null;
        }

        // Handle new photo upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Delete old photo if exists
            if ($photoFilename) {
                deleteImage($photoFilename, 'lost');
            }

            $photoFilename = uploadImage($_FILES['photo'], 'lost');
            if ($photoFilename === false) {
                $error = 'Photo upload failed. Please check file size and format.';
            }
        }

        if (empty($error)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE lost_items 
                    SET item_name = ?, description = ?, photo = ?, date_lost = ? 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([
                    $itemName,
                    $description,
                    $photoFilename,
                    $dateLost,
                    $itemId,
                    $userId
                ]);

                $success = 'Lost item updated successfully!';
            } catch (PDOException $e) {
                $error = 'Failed to update lost item. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lost Item - Lost & Found</title>
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

        .btn-outline-primary {
            border: 2px solid var(--university-gold);
            color: var(--university-gold);
            background: transparent;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
        }

        .btn-outline-primary:hover {
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

        .page-header p {
            color: var(--text-light);
            margin: 0.8rem 0 0 0;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.6;
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

        /* Alert Messages */
        .alert {
            border-radius: 0;
            border: none;
            padding: 1.2rem 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
            animation: slideIn 0.5s ease-out;
            display: flex;
            align-items: center;
        }

        .alert i {
            font-size: 1.5rem;
            margin-right: 1rem;
        }

        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background: #FEE2E2;
            color: #991B1B;
            border-left: 4px solid var(--danger);
        }

        /* Form Card */
        .form-card {
            background: var(--white);
            border-radius: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            padding: 2.5rem;
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
            border-top: 4px solid var(--university-blue);
            position: relative;
        }

        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--university-blue) 0%, var(--university-gold) 100%);
        }

        /* Form Labels */
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

        .required-indicator {
            color: var(--danger);
            margin-left: 0.25rem;
            font-weight: 900;
        }

        /* Form Controls */
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
            min-height: 140px;
            font-family: 'Inter', sans-serif;
        }

        .form-text {
            color: var(--text-light);
            font-size: 0.85rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: flex-start;
            font-style: italic;
        }

        .form-text i {
            margin-right: 0.5rem;
            font-size: 0.9rem;
            margin-top: 0.1rem;
            flex-shrink: 0;
        }

        /* Current Photo Display */
        .current-photo-section {
            background: #FAFAFA;
            padding: 1.5rem;
            border-radius: 4px;
            border: 2px solid var(--border-color);
            margin-bottom: 1.5rem;
        }

        .current-photo-section img {
            max-width: 250px;
            height: auto;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 2px solid var(--border-color);
            margin-bottom: 1rem;
        }

        .form-check {
            margin-top: 1rem;
        }

        .form-check-input:checked {
            background-color: var(--university-blue);
            border-color: var(--university-blue);
        }

        .form-check-label {
            font-weight: 600;
            color: var(--text-dark);
            text-transform: none;
            letter-spacing: normal;
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

        .btn-primary {
            background: var(--university-blue);
            box-shadow: 0 2px 4px rgba(0, 51, 102, 0.3);
        }

        .btn-primary:hover {
            background: var(--navy);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 51, 102, 0.4);
        }

        .btn-secondary {
            background: #6B7280;
            box-shadow: 0 2px 4px rgba(107, 114, 128, 0.3);
        }

        .btn-secondary:hover {
            background: #4B5563;
            transform: translateY(-1px);
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--border-color);
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

            .form-card {
                padding: 1.5rem;
            }

            .button-group {
                flex-direction: column;
                gap: 0.8rem;
            }

            .btn {
                width: 100%;
            }
        }

        .mb-3 {
            margin-bottom: 1.8rem !important;
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
            <span class="navbar-text me-3"><i class="bi bi-person-circle me-2"></i>Welcome, <strong><?php echo htmlspecialchars(getCurrentUserName()); ?></strong></span>
            <a class="btn btn-outline-primary btn-sm" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </div>
</nav>
<!-- ========== END OF NAVBAR ========== -->

<main class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="bi bi-pencil-square"></i>Edit Lost Item</h1>
        <p>Update your lost item information. Make sure all details are accurate to help with recovery.</p>
    </div>

    <!-- Back Link -->
    <a href="my_items.php" class="back-link">
        <i class="bi bi-arrow-left-circle-fill"></i>Back to My Items
    </a>

    <!-- Error Message -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <!-- Success Message -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <div>
                <strong><?php echo htmlspecialchars($success); ?></strong>
                <div class="mt-2">
                    <a href="my_items.php" class="btn btn-sm btn-success">
                        <i class="bi bi-arrow-left-circle"></i>Back to My Items
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Edit Form -->
    <form method="POST" action="" enctype="multipart/form-data" class="form-card">
        
        <!-- Item Name -->
        <div class="mb-3">
            <label for="item_name" class="form-label">
                <i class="bi bi-tag-fill"></i>Item Name<span class="required-indicator">*</span>
            </label>
            <input
                type="text"
                class="form-control"
                id="item_name"
                name="item_name"
                value="<?php echo htmlspecialchars($item['item_name']); ?>"
                placeholder="e.g., Black iPhone 13, Blue Backpack"
                required
            >
        </div>

        <!-- Description -->
        <div class="mb-3">
            <label for="description" class="form-label">
                <i class="bi bi-file-text-fill"></i>Description<span class="required-indicator">*</span>
            </label>
            <textarea
                class="form-control"
                id="description"
                name="description"
                rows="5"
                placeholder="Provide detailed description including distinctive features, brand, color, size..."
                required
            ><?php echo htmlspecialchars($item['description']); ?></textarea>
            <small class="form-text">
                <i class="bi bi-info-circle-fill"></i>
                Be as specific as possible to help identify your item.
            </small>
        </div>

        <!-- Date Lost -->
        <div class="mb-3">
            <label for="date_lost" class="form-label">
                <i class="bi bi-calendar-event-fill"></i>Date Lost<span class="required-indicator">*</span>
            </label>
            <input
                type="date"
                class="form-control"
                id="date_lost"
                name="date_lost"
                value="<?php echo htmlspecialchars($item['date_lost']); ?>"
                max="<?php echo date('Y-m-d'); ?>"
                required
            >
            <small class="form-text">
                <i class="bi bi-info-circle-fill"></i>
                Select the approximate date when you lost this item.
            </small>
        </div>

        <!-- Current Photo Section -->
        <?php if ($item['photo']): ?>
            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-image-fill"></i>Current Photo
                </label>
                <div class="current-photo-section">
                    <img
                        src="<?php echo getImageUrl($item['photo'], 'lost'); ?>"
                        alt="Current photo"
                        class="img-fluid"
                    >
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remove_photo" value="1" id="removePhoto">
                        <label class="form-check-label" for="removePhoto">
                            <i class="bi bi-trash me-2"></i>Remove current photo
                        </label>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Upload New Photo -->
        <div class="mb-3">
            <label for="photo" class="form-label">
                <i class="bi bi-cloud-upload-fill"></i>Upload New Photo <span style="color: var(--text-light); font-weight: 500;">(Optional)</span>
            </label>
            <input
                type="file"
                class="form-control"
                id="photo"
                name="photo"
                accept="image/jpeg,image/png,image/jpg,image/gif"
            >
            <small class="form-text">
                <i class="bi bi-info-circle-fill"></i>
                Max file size: 5MB. Accepted formats: JPG, PNG, GIF
            </small>
        </div>

        <!-- Button Group -->
        <div class="button-group">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle-fill"></i>Update Lost Item
            </button>
            <a href="my_items.php" class="btn btn-secondary">
                <i class="bi bi-x-circle-fill"></i>Cancel
            </a>
        </div>

    </form>

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