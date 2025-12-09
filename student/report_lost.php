<?php
/**
 * Report Lost Item
 * Form for students to report lost items
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireStudent();

$userId = getCurrentUserId();
$userName = getCurrentUserName();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Lost Item - Lost & Found</title>
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F0F7FF 0%, #FFFFFF 50%, #E6F0FF 100%);
            color: var(--text-dark);
            min-height: 100vh;
            font-size: 15px;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
        }

        /* Navbar */
        .navbar {
            background: var(--white);
            box-shadow: 0 2px 20px rgba(0, 102, 255, 0.08);
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .navbar-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 800;
            font-size: 1.6rem;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }

        .navbar-nav .nav-link {
            color: var(--text-dark) !important;
            font-weight: 500;
            font-size: 0.9rem;
            margin: 0 0.3rem;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            background: var(--light-blue);
            color: var(--primary-blue) !important;
        }

        .navbar-nav .nav-link.active {
            background: var(--secondary-blue);
            color: var(--primary-blue) !important;
            font-weight: 600;
        }

        .navbar-text {
            color: var(--text-light) !important;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .btn-outline-light {
            border: 2px solid var(--primary-blue);
            color: var(--primary-blue);
            background: transparent;
            font-weight: 600;
        }

        .btn-outline-light:hover {
            background: var(--primary-blue);
            color: var(--white);
        }

        /* Main Content */
        main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
            animation: fadeInUp 0.6s ease-out;
        }

        .page-header {
            background: var(--white);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 4px 24px rgba(0, 102, 255, 0.08);
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
            letter-spacing: -1px;
            display: flex;
            align-items: center;
        }

        .page-header h1 i {
            margin-right: 1rem;
            font-size: 2.2rem;
        }

        .page-header p {
            color: var(--text-light);
            margin: 0.5rem 0 0 0;
            font-size: 1rem;
            font-weight: 500;
        }

        /* Form Card */
        .form-card {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 102, 255, 0.1);
            padding: 2.5rem;
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.6rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
        }

        .form-label i {
            margin-right: 0.5rem;
            color: var(--primary-blue);
            font-size: 1.1rem;
        }

        .required-indicator {
            color: #EF4444;
            margin-left: 0.25rem;
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 0.85rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(0, 102, 255, 0.1);
            outline: none;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 140px;
        }

        .form-text {
            color: var(--text-light);
            font-size: 0.85rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
        }

        .form-text i {
            margin-right: 0.5rem;
            font-size: 0.9rem;
        }

        /* Image Preview */
        #image-preview {
            max-width: 250px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 102, 255, 0.15);
            margin-top: 1rem;
            border: 2px solid var(--border-color);
        }

        /* Buttons */
        .btn {
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.75rem 1.8rem;
            transition: all 0.3s ease;
            border: none;
            letter-spacing: 0.3px;
        }

        .btn i {
            margin-right: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            box-shadow: 0 4px 12px rgba(0, 102, 255, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary-blue) 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 102, 255, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6B7280 0%, #4B5563 100%);
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #4B5563 0%, #374151 100%);
            transform: translateY(-2px);
        }

        /* Tips Section */
        .tips-section {
            background: var(--white);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 4px 24px rgba(0, 102, 255, 0.08);
            border: 1px solid var(--border-color);
        }

        .tips-section h2 {
            color: var(--primary-blue);
            font-weight: 800;
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
        }

        .tips-section h2 i {
            margin-right: 0.8rem;
            font-size: 2rem;
        }

        .list-group {
            border-radius: 12px;
            overflow: hidden;
        }

        .list-group-item {
            border: none;
            background: var(--light-blue);
            margin-bottom: 0.8rem;
            border-radius: 12px;
            padding: 1.2rem 1.5rem;
            color: var(--text-dark);
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: flex-start;
        }

        .list-group-item::before {
            content: '\f26a';
            font-family: 'bootstrap-icons';
            color: var(--primary-blue);
            margin-right: 1rem;
            font-size: 1.2rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .list-group-item:hover {
            background: var(--secondary-blue);
            transform: translateX(8px);
            box-shadow: 0 4px 12px rgba(0, 102, 255, 0.1);
        }

        /* Footer */
        footer {
            background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary-blue) 100%);
            color: var(--white);
            padding: 1.5rem 0;
            margin-top: 3rem;
            box-shadow: 0 -2px 20px rgba(0, 102, 255, 0.1);
        }

        footer i {
            margin-right: 0.5rem;
        }

        /* Alert Container */
        #alertContainer {
            margin-bottom: 1.5rem;
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            font-weight: 500;
            animation: slideIn 0.5s ease-out;
        }

        .alert-success {
            background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
            color: #065F46;
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
            color: #991B1B;
            border-left: 4px solid #EF4444;
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
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .navbar-brand {
                font-size: 1.3rem;
            }
        }

        /* File input styling */
        .form-control[type="file"] {
            padding: 0.75rem 1rem;
        }

        .form-control[type="file"]::file-selector-button {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            color: var(--white);
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            font-weight: 600;
            margin-right: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-control[type="file"]::file-selector-button:hover {
            background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary-blue) 100%);
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
                <li class="nav-item"><a class="nav-link active" href="index.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="report_lost.php"><i class="bi bi-exclamation-circle me-1"></i>Report Lost</a></li>
                <li class="nav-item"><a class="nav-link" href="report_found.php"><i class="bi bi-check-circle me-1"></i>Report Found</a></li>
                <li class="nav-item"><a class="nav-link" href="lost_items.php"><i class="bi bi-search me-1"></i>Browse Lost</a></li>
                <li class="nav-item"><a class="nav-link" href="found_items.php"><i class="bi bi-archive me-1"></i>Browse Found</a></li>
                <li class="nav-item"><a class="nav-link" href="my_items.php"><i class="bi bi-person-lines-fill me-1"></i>My Items</a></li>
            </ul>
            <span class="navbar-text me-3"><i class="bi bi-person-circle me-2"></i>Welcome, <strong><?= htmlspecialchars($userName) ?></strong></span>
            <a class="btn btn-outline-primary btn-sm" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </div>
</nav>

<main class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="bi bi-exclamation-triangle-fill"></i>Report Lost Item</h1>
        <p>Fill out the form below to report an item you've lost. Be as detailed as possible to help us locate your item.</p>
    </div>

    <div id="alertContainer"></div>

    <!-- Form Card -->
    <form method="POST" action="" enctype="multipart/form-data" id="lostItemForm" class="form-card">
        <div class="mb-3">
            <label for="item_name" class="form-label">
                <i class="bi bi-tag-fill"></i>Item Name<span class="required-indicator">*</span>
            </label>
            <input type="text" class="form-control" id="item_name" name="item_name"
                   placeholder="e.g., Black iPhone 13, Blue Backpack, Keys with Red Keychain" required>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">
                <i class="bi bi-file-text-fill"></i>Description<span class="required-indicator">*</span>
            </label>
            <textarea class="form-control" id="description" name="description" rows="5"
                      placeholder="Provide detailed description including distinctive features, brand, color, size, any unique markings, scratches, or stickers..." required></textarea>
            <small class="form-text">
                <i class="bi bi-info-circle-fill"></i>
                Be as specific as possible to help identify your item and distinguish it from similar items.
            </small>
        </div>

        <div class="mb-3">
            <label for="date_lost" class="form-label">
                <i class="bi bi-calendar-event-fill"></i>Date Lost<span class="required-indicator">*</span>
            </label>
            <input type="date" class="form-control" id="date_lost" name="date_lost" max="<?php echo date('Y-m-d'); ?>" required>
            <small class="form-text">
                <i class="bi bi-info-circle-fill"></i>
                Select the approximate date when you lost this item.
            </small>
        </div>

        <div class="mb-3">
            <label for="photo" class="form-label">
                <i class="bi bi-image-fill"></i>Photo <span style="color: var(--text-light); font-weight: 500;">(Optional)</span>
            </label>
            <input type="file" class="form-control" id="photo" name="photo" accept="image/jpeg,image/png,image/jpg,image/gif">
            <small class="form-text">
                <i class="bi bi-info-circle-fill"></i>
                Max file size: 5MB. Accepted formats: JPG, PNG, GIF. A photo helps identify your item faster.
            </small>
            <img id="image-preview" class="img-fluid" style="display:none;">
        </div>

        <div class="button-group">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-send-fill"></i>Submit Lost Item Report
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-x-circle-fill"></i>Cancel
            </a>
        </div>
    </form>

    <script>
        document.getElementById('lostItemForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            // Validate form
            if (!FormValidation.validateLostItem(this)) {
                return;
            }

            await LSF.FormHandlers.submitLostItem(this);
        });

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

    <!-- Tips Section -->
    <section class="tips-section">
        <h2><i class="bi bi-lightbulb-fill"></i>Tips for Reporting Lost Items</h2>
        <ul class="list-group list-group-flush">
            <li class="list-group-item">Provide as much detail as possible about your item including brand, model, color, and size</li>
            <li class="list-group-item">Include unique identifiers such as serial numbers, scratches, stickers, or custom modifications</li>
            <li class="list-group-item">Upload a clear photo if available - this significantly increases the chance of recovery</li>
            <li class="list-group-item">Specify the approximate date when you lost the item to help narrow down the search</li>
            <li class="list-group-item">Check back regularly for matching found items and respond promptly to any notifications</li>
        </ul>
    </section>
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