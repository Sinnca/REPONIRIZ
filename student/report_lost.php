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
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">Lost & Found System</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="report_lost.php">Report Lost Item</a></li>
                <li class="nav-item"><a class="nav-link" href="report_found.php">Report Found Item</a></li>
                <li class="nav-item"><a class="nav-link" href="lost_items.php">Browse Lost Items</a></li>
                <li class="nav-item"><a class="nav-link" href="found_items.php">Browse Found Items</a></li>
                <li class="nav-item"><a class="nav-link" href="my_items.php">My Items</a></li>
            </ul>
            <span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars(getCurrentUserName()); ?></span>
            <a class="btn btn-outline-light" href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<main class="container mb-5">
    <h1 class="mb-4">Report Lost Item</h1>

    <div id="alertContainer"></div>

    <form method="POST" action="" enctype="multipart/form-data" id="lostItemForm" class="card p-4 shadow-sm bg-white">
        <div class="mb-3">
            <label for="item_name" class="form-label">Item Name *</label>
            <input type="text" class="form-control" id="item_name" name="item_name"
                   placeholder="e.g., Black iPhone 13" required>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description *</label>
            <textarea class="form-control" id="description" name="description" rows="5"
                      placeholder="Provide detailed description including distinctive features, brand, color, etc." required></textarea>
            <small class="form-text text-muted">Be as specific as possible to help identify your item.</small>
        </div>

        <div class="mb-3">
            <label for="date_lost" class="form-label">Date Lost *</label>
            <input type="date" class="form-control" id="date_lost" name="date_lost" max="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div class="mb-3">
            <label for="photo" class="form-label">Photo (Optional)</label>
            <input type="file" class="form-control" id="photo" name="photo" accept="image/jpeg,image/png,image/jpg,image/gif">
            <small class="form-text text-muted">Max file size: 5MB. Accepted formats: JPG, PNG, GIF</small>
            <img id="image-preview" class="img-fluid mt-2" style="display:none; max-width: 200px;">
        </div>

        <div class="mb-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Submit Lost Item Report</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
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
    </script>

    <section class="mt-5">
        <h2>Tips for Reporting Lost Items</h2>
        <ul class="list-group list-group-flush">
            <li class="list-group-item">Provide as much detail as possible about your item</li>
            <li class="list-group-item">Include unique identifiers (serial numbers, scratches, stickers)</li>
            <li class="list-group-item">Upload a clear photo if available</li>
            <li class="list-group-item">Specify the approximate date when you lost the item</li>
            <li class="list-group-item">Check back regularly for matching found items</li>
        </ul>
    </section>
</main>

<footer class="bg-dark text-white text-center py-3 mt-5">
    &copy; 2024 Campus Lost & Found System
</footer>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
