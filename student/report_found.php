<?php global $pdo;
/**
 * Report Found Item
 * Form for students to report found items
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireStudent();

$userId = getCurrentUserId();

// Check if linking to a lost item (via "I Found This" button)
$lostItemId = isset($_GET['lost_item_id']) ? intval($_GET['lost_item_id']) : null;
$lostItemData = null;

if ($lostItemId) {
    $stmt = $pdo->prepare("SELECT * FROM lost_items WHERE id = ?");
    $stmt->execute([$lostItemId]);
    $lostItemData = $stmt->fetch();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $item_name = sanitize($_POST['item_name']);
    $description = sanitize($_POST['description']);
    $date_found = sanitize($_POST['date_found']);
    $lost_item_id = isset($_POST['lost_item_id']) ? intval($_POST['lost_item_id']) : null;

    // Handle file upload
    $photo = uploadImage($_FILES['photo'] ?? null, 'found');
    if ($photo === false) {
        $_SESSION['flash_message'] = 'Photo upload failed or invalid file.';
        $_SESSION['flash_type'] = 'danger';
        header("Location: report_found.php");
        exit;
    }

    // Insert found item
    $stmt = $pdo->prepare("
        INSERT INTO found_items (user_id, lost_item_id, item_name, description, date_found, photo, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$userId, $lost_item_id, $item_name, $description, $date_found, $photo]);

    // Create notification for the student
    $message = "Your found item '{$item_name}' has been submitted. Please bring it to the office for verification.";
    createNotification($pdo, $userId, $message);

    // Redirect to dashboard with success message
    $_SESSION['flash_message'] = 'Found item submitted successfully!';
    $_SESSION['flash_type'] = 'success';
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Found Item - Lost & Found</title>
    <script src="../assets/js/main.js" defer></script>
    <script src="../assets/js/validation.js" defer></script>
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
            --gold: #FDB813;
            --navy: #002D72;
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
        .navbar {
            background: var(--white);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 0.8rem 0;
            border-bottom: 3px solid var(--primary-blue);
        }

        .navbar-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--navy) !important;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            text-transform: uppercase;
        }

        .navbar-brand i {
            color: var(--primary-blue);
            margin-right: 0.5rem;
            font-size: 1.8rem;
        }

        .navbar-nav .nav-link {
            color: var(--text-dark) !important;
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
            background: var(--light-blue);
            color: var(--primary-blue) !important;
        }

        .navbar-nav .nav-link.active {
            background: var(--primary-blue);
            color: var(--white) !important;
            font-weight: 700;
        }

        .navbar-text {
            color: var(--text-dark) !important;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .navbar-text strong {
            color: var(--primary-blue);
            font-weight: 700;
        }

        .btn-outline-light {
            border: 2px solid var(--primary-blue);
            color: var(--primary-blue);
            background: transparent;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
        }

        .btn-outline-light:hover {
            background: var(--primary-blue);
            color: var(--white);
            border-color: var(--primary-blue);
        }

        /* Main Content */
        main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
            animation: fadeInUp 0.6s ease-out;
        }

        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            align-items: start;
        }

        .page-header {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            border-left: 6px solid var(--primary-blue);
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
            color: var(--primary-blue);
        }

        .page-header p {
            color: var(--text-light);
            margin: 0.8rem 0 0 0;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.6;
        }

        /* Alert */
        .alert {
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 0;
            padding: 1rem 1.5rem;
            font-weight: 500;
            animation: slideIn 0.5s ease-out;
            margin-bottom: 1.5rem;
        }

        .alert-info {
            background: #DBEAFE;
            color: #1E40AF;
            border-left: 4px solid var(--primary-blue);
        }

        .alert-info h5 {
            color: var(--navy);
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            font-weight: 800;
        }

        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background: #FEE2E2;
            color: #991B1B;
            border-left: 4px solid #DC2626;
        }

        /* Form Card */
        .form-card {
            background: var(--white);
            border-radius: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            padding: 2.5rem;
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
            border-top: 4px solid var(--primary-blue);
            position: relative;
        }

        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--primary-blue) 0%, var(--gold) 100%);
        }

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
            color: var(--primary-blue);
            font-size: 1rem;
        }

        .required-indicator {
            color: #DC2626;
            margin-left: 0.25rem;
            font-weight: 900;
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
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.1);
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

        /* Image Preview */
        #image-preview {
            max-width: 250px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-top: 1rem;
            border: 2px solid var(--border-color);
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
            background: var(--primary-blue);
            box-shadow: 0 2px 4px rgba(0, 102, 255, 0.3);
        }

        .btn-primary:hover {
            background: var(--dark-blue);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 102, 255, 0.4);
        }

        .btn-secondary {
            background: #6B7280;
            box-shadow: 0 2px 4px rgba(107, 114, 128, 0.3);
        }

        .btn-secondary:hover {
            background: #4B5563;
            transform: translateY(-1px);
        }

        /* Tips Section */
        .tips-section {
            background: var(--white);
            border-radius: 0;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
            position: sticky;
            top: 2rem;
            border-left: 4px solid var(--gold);
        }

        .tips-section h2 {
            color: var(--navy);
            font-weight: 800;
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            text-transform: uppercase;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .tips-section h2 i {
            margin-right: 0.8rem;
            font-size: 1.6rem;
            color: var(--gold);
        }

        .list-group {
            border-radius: 0;
            overflow: hidden;
        }

        .list-group-item {
            border: none;
            background: #FAFAFA;
            margin-bottom: 0.6rem;
            border-radius: 0;
            border-left: 3px solid var(--primary-blue);
            padding: 1rem 1.2rem;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: flex-start;
        }

        .list-group-item::before {
            content: '→';
            color: var(--primary-blue);
            margin-right: 0.8rem;
            font-size: 1.2rem;
            font-weight: 900;
            flex-shrink: 0;
        }

        .list-group-item:hover {
            background: var(--light-blue);
            transform: translateX(4px);
            border-left-color: var(--navy);
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

        footer p {
            margin: 0;
            font-weight: 500;
            font-size: 0.9rem;
            letter-spacing: 0.02em;
        }

        footer i {
            margin-right: 0.5rem;
            color: var(--gold);
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
            .page-header h1 {
                font-size: 2rem;
            }

            .form-card {
                padding: 1.5rem;
            }

            .tips-section {
                padding: 1.5rem;
                position: static;
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .navbar-brand {
                font-size: 1.3rem;
            }

            .content-wrapper {
                grid-template-columns: 1fr;
            }
        }

        /* File input styling */
        .form-control[type="file"] {
            padding: 0.75rem 1rem;
        }

        .form-control[type="file"]::file-selector-button {
            background: var(--primary-blue);
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
            background: var(--dark-blue);
            transform: translateY(-1px);
        }

        /* Form group spacing */
        .mb-3 {
            margin-bottom: 1.8rem !important;
        }

        /* Button group */
        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--border-color);
        }

        @media (max-width: 576px) {
            .button-group {
                flex-direction: column;
                gap: 0.8rem;
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
                <li class="nav-item"><a class="nav-link active" href="report_found.php"><i class="bi bi-check-circle me-1"></i>Report Found</a></li>
                <li class="nav-item"><a class="nav-link" href="lost_items.php"><i class="bi bi-search me-1"></i>Browse Lost</a></li>
                <li class="nav-item"><a class="nav-link" href="found_items.php"><i class="bi bi-archive me-1"></i>Browse Found</a></li>
                <li class="nav-item"><a class="nav-link" href="my_items.php"><i class="bi bi-person-lines-fill me-1"></i>My Items</a></li>
            </ul>
            <span class="navbar-text me-3"><i class="bi bi-person-circle me-2"></i>Welcome, <strong><?= htmlspecialchars(getCurrentUserName()) ?></strong></span>
            <a class="btn btn-outline-primary btn-sm" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </div>
</nav>

<main class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="bi bi-check-circle-fill"></i>Report Found Item</h1>
        <p>Fill out the form below to report an item you've found. Help reunite items with their owners!</p>
    </div>

    <?php if ($lostItemData): ?>
        <div class="alert alert-info">
            <h5><i class="bi bi-link-45deg me-2"></i>Linking to Lost Item: <?php echo htmlspecialchars($lostItemData['item_name']); ?></h5>
            <p class="mb-0"><?php echo htmlspecialchars($lostItemData['description']); ?></p>
        </div>
    <?php endif; ?>

    <div id="alertContainer">
        <?php displayFlashMessage(); ?>
    </div>

    <div class="content-wrapper">
        <!-- Left Column: Form -->
        <div>
            <!-- Form Card -->
            <form method="POST" action="" enctype="multipart/form-data" id="foundItemForm" class="form-card">
                <?php if ($lostItemId): ?>
                    <input type="hidden" name="lost_item_id" value="<?php echo $lostItemId; ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="item_name" class="form-label">
                        <i class="bi bi-tag-fill"></i>Item Name<span class="required-indicator">*</span>
                    </label>
                    <input type="text" class="form-control" id="item_name" name="item_name"
                           value="<?php echo $lostItemData ? htmlspecialchars($lostItemData['item_name']) : ''; ?>"
                           placeholder="e.g., Black iPhone 13, Blue Backpack, Keys with Red Keychain" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">
                        <i class="bi bi-file-text-fill"></i>Description<span class="required-indicator">*</span>
                    </label>
                    <textarea class="form-control" id="description" name="description" rows="5"
                              placeholder="Describe the item you found, including condition, location found, distinctive features, etc." required></textarea>
                    <small class="form-text">
                        <i class="bi bi-info-circle-fill"></i>
                        Provide details about where and when you found it to help verify ownership.
                    </small>
                </div>

                <div class="mb-3">
                    <label for="date_found" class="form-label">
                        <i class="bi bi-calendar-event-fill"></i>Date Found<span class="required-indicator">*</span>
                    </label>
                    <input type="date" class="form-control" id="date_found" name="date_found" max="<?php echo date('Y-m-d'); ?>" required>
                    <small class="form-text">
                        <i class="bi bi-info-circle-fill"></i>
                        Select the date when you found this item.
                    </small>
                </div>

                <div class="mb-3">
                    <label for="photo" class="form-label">
                        <i class="bi bi-image-fill"></i>Photo <span style="color: var(--text-light); font-weight: 500;">(Optional)</span>
                    </label>
                    <input type="file" class="form-control" id="photo" name="photo" accept="image/jpeg,image/png,image/jpg,image/gif">
                    <small class="form-text">
                        <i class="bi bi-info-circle-fill"></i>
                        Max file size: 5MB. Accepted formats: JPG, PNG, GIF. A photo helps verify the item.
                    </small>
                    <img id="image-preview" class="img-fluid" style="display:none;">
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send-fill"></i>Submit Found Item Report
                    </button>
                    <a href="<?php echo $lostItemId ? 'lost_items.php' : 'index.php'; ?>" class="btn btn-secondary">
                        <i class="bi bi-x-circle-fill"></i>Cancel
                    </a>
                </div>
            </form>

            <script>
                // Image preview
                document.getElementById('photo').addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    const preview = document.getElementById('image-preview');
                    
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                        }
                        reader.readAsDataURL(file);
                    } else {
                        preview.style.display = 'none';
                    }
                });
            </script>
        </div>

        <!-- Right Column: Tips Section -->
        <aside>
            <section class="tips-section">
                <h2><i class="bi bi-lightbulb-fill"></i>Tips</h2>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Describe the item accurately to help the owner identify it and verify ownership</li>
                    <li class="list-group-item">Include the specific location where you found the item and the approximate time</li>
                    <li class="list-group-item">Upload a clear photo if possible to help with identification and verification</li>
                    <li class="list-group-item">Admin will verify your submission before it becomes visible to other users</li>
                    <li class="list-group-item">You will be notified to bring the item to the office for official verification</li>
                </ul>
            </section>
        </aside>
    </div>
</main>

<footer class="text-center">
    <div class="container">
        <p><i class="bi bi-shield-check"></i>&copy; 2024 Campus Lost & Found System. All rights reserved.</p>
    </div>
</footer>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>