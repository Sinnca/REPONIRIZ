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
                <li class="nav-item"><a class="nav-link" href="report_lost.php">Report Lost Item</a></li>
                <li class="nav-item"><a class="nav-link active" href="report_found.php">Report Found Item</a></li>
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
    <h1 class="mb-4">Report Found Item</h1>

    <?php if ($lostItemData): ?>
        <div class="alert alert-info mb-4">
            <h5>Linking to Lost Item: <?php echo htmlspecialchars($lostItemData['item_name']); ?></h5>
            <p><?php echo htmlspecialchars($lostItemData['description']); ?></p>
        </div>
    <?php endif; ?>

    <div id="alertContainer">
        <?php displayFlashMessage(); ?>
    </div>

    <form method="POST" action="" enctype="multipart/form-data" id="foundItemForm" class="card p-4 shadow-sm bg-white">
        <?php if ($lostItemId): ?>
            <input type="hidden" name="lost_item_id" value="<?php echo $lostItemId; ?>">
        <?php endif; ?>

        <div class="mb-3">
            <label for="item_name" class="form-label">Item Name *</label>
            <input type="text" class="form-control" id="item_name" name="item_name"
                   value="<?php echo $lostItemData ? htmlspecialchars($lostItemData['item_name']) : ''; ?>"
                   placeholder="e.g., Black iPhone 13" required>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description *</label>
            <textarea class="form-control" id="description" name="description" rows="5"
                      placeholder="Describe the item you found, including condition, location found, etc." required></textarea>
            <small class="form-text text-muted">Provide details about where and when you found it.</small>
        </div>

        <div class="mb-3">
            <label for="date_found" class="form-label">Date Found *</label>
            <input type="date" class="form-control" id="date_found" name="date_found" max="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div class="mb-3">
            <label for="photo" class="form-label">Photo (Optional)</label>
            <input type="file" class="form-control" id="photo" name="photo" accept="image/jpeg,image/png,image/jpg,image/gif">
            <small class="form-text text-muted">Max file size: 5MB. Accepted formats: JPG, PNG, GIF</small>
            <img id="image-preview" class="img-fluid mt-2" style="display:none; max-width: 200px;">
        </div>

        <div class="mb-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Submit Found Item Report</button>
            <a href="<?php echo $lostItemId ? 'lost_items.php' : 'index.php'; ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>

    <section class="mt-5">
        <h2>Tips for Reporting Found Items</h2>
        <ul class="list-group list-group-flush">
            <li class="list-group-item">Describe the item accurately to help the owner identify it</li>
            <li class="list-group-item">Include the location where you found it</li>
            <li class="list-group-item">Upload a photo if possible</li>
            <li class="list-group-item">Admin will verify your submission before it's visible to others</li>
            <li class="list-group-item">You will be notified to bring the item to the office for verification</li>
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
