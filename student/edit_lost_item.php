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
</head>
<body>

<header>
    <nav>
        <h1>Lost & Found System</h1>
        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="report_lost.php">Report Lost Item</a></li>
            <li><a href="report_found.php">Report Found Item</a></li>
            <li><a href="lost_items.php">Browse Lost Items</a></li>
            <li><a href="found_items.php">Browse Found Items</a></li>
            <li><a href="my_items.php">My Items</a></li>
        </ul>
        <div>
            <span>Welcome, <?php echo htmlspecialchars(getCurrentUserName()); ?></span>
            <a href="../logout.php">Logout</a>
        </div>
    </nav>
</header>

<main>

    <h1>Edit Lost Item</h1>

    <a href="my_items.php">&larr; Back to My Items</a>

    <?php if (!empty($error)): ?>
        <div class="error-message">
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="success-message">
            <p><?php echo htmlspecialchars($success); ?></p>
            <a href="my_items.php">Back to My Items</a>
        </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">

        <div>
            <label for="item_name">Item Name *</label>
            <input
                type="text"
                id="item_name"
                name="item_name"
                value="<?php echo htmlspecialchars($item['item_name']); ?>"
                required
            >
        </div>

        <div>
            <label for="description">Description *</label>
            <textarea
                id="description"
                name="description"
                rows="5"
                required
            ><?php echo htmlspecialchars($item['description']); ?></textarea>
        </div>

        <div>
            <label for="date_lost">Date Lost *</label>
            <input
                type="date"
                id="date_lost"
                name="date_lost"
                value="<?php echo htmlspecialchars($item['date_lost']); ?>"
                max="<?php echo date('Y-m-d'); ?>"
                required
            >
        </div>

        <!-- Current Photo -->
        <?php if ($item['photo']): ?>
            <div>
                <label>Current Photo</label>
                <div>
                    <img
                        src="<?php echo getImageUrl($item['photo'], 'lost'); ?>"
                        alt="Current photo"
                        style="max-width: 200px; height: auto;"
                    >
                </div>
                <label>
                    <input type="checkbox" name="remove_photo" value="1">
                    Remove current photo
                </label>
            </div>
        <?php endif; ?>

        <div>
            <label for="photo">Upload New Photo (Optional)</label>
            <input
                type="file"
                id="photo"
                name="photo"
                accept="image/jpeg,image/png,image/jpg,image/gif"
            >
            <small>Max file size: 5MB. Accepted formats: JPG, PNG, GIF</small>
        </div>

        <div>
            <button type="submit">Update Lost Item</button>
            <a href="my_items.php">Cancel</a>
        </div>

    </form>

</main>

<footer>
    <p>&copy; 2024 Campus Lost & Found System</p>
</footer>

</body>
</html>