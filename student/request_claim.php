<?php global $pdo;
/**
 * Student - Request Claim
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireStudent();

$userId = getCurrentUserId();
$lostItemId  = isset($_GET['lost_item_id']) ? intval($_GET['lost_item_id']) : 0;
$foundItemId = isset($_GET['found_item_id']) ? intval($_GET['found_item_id']) : 0;

// Redirect if missing IDs
if (!$lostItemId || !$foundItemId) {
    header('Location: index.php');
    exit;
}

// Fetch lost item
$stmt = $pdo->prepare("
    SELECT li.*, u.name as owner_name 
    FROM lost_items li
    JOIN users u ON li.user_id = u.id
    WHERE li.id = ?
");
$stmt->execute([$lostItemId]);
$lostItem = $stmt->fetch();
if (!$lostItem) {
    header('Location: index.php');
    exit;
}

// Fetch found item
$stmtFound = $pdo->prepare("
    SELECT fi.*, u.name as finder_name
    FROM found_items fi
    JOIN users u ON fi.user_id = u.id
    WHERE fi.id = ? AND fi.lost_item_id = ? AND fi.status IN ('verified','listed')
");
$stmtFound->execute([$foundItemId, $lostItemId]);
$foundItem = $stmtFound->fetch();
if (!$foundItem) {
    header('Location: view_lost_item.php?id=' . $lostItemId);
    exit;
}

// Check claim eligibility
$canClaim = true;
$errorMessage = '';

// Lost item must be ready for claim
if ($lostItem['status'] !== 'ready_for_claim') {
    $canClaim = false;
    $errorMessage = 'This item is not ready for claim yet.';
}

// Check if user already submitted a claim for this found item
$stmtExisting = $pdo->prepare("
    SELECT * 
    FROM claim_requests 
    WHERE lost_item_id = ? AND found_item_id = ? AND requester_id = ?
");
$stmtExisting->execute([$lostItemId, $foundItemId, $userId]);
$existingClaim = $stmtExisting->fetch();
if ($existingClaim) {
    $canClaim = false;
    $errorMessage = 'You have already submitted a claim request for this found item.';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Claim - Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="../assets/js/main.js" defer></script>
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">Lost & Found</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="report_lost.php">Report Lost</a></li>
                <li class="nav-item"><a class="nav-link" href="report_found.php">Report Found</a></li>
                <li class="nav-item"><a class="nav-link" href="lost_items.php">Browse Lost</a></li>
                <li class="nav-item"><a class="nav-link" href="found_items.php">Browse Found</a></li>
                <li class="nav-item"><a class="nav-link" href="my_items.php">My Items</a></li>
            </ul>
            <span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars(getCurrentUserName()); ?></span>
            <a class="btn btn-outline-light" href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<main class="container mb-5">
    <h1 class="mb-3">Request Claim</h1>
    <a href="view_lost_item.php?id=<?php echo $lostItemId; ?>" class="btn btn-secondary mb-3">&larr; Back to Item Details</a>

    <?php if (!$canClaim): ?>
        <div class="alert alert-warning">
            <?php echo htmlspecialchars($errorMessage); ?>
            <hr>
            <a href="index.php" class="btn btn-sm btn-primary mt-2">Back to Dashboard</a>
        </div>
    <?php else: ?>

        <!-- Lost Item Card -->
        <div class="card mb-4">
            <div class="row g-3 p-3">
                <div class="col-md-4">
                    <?php if ($lostItem['photo']): ?>
                        <img src="<?php echo getImageUrl($lostItem['photo'], 'lost'); ?>" class="img-fluid rounded">
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <h5><?php echo htmlspecialchars($lostItem['item_name']); ?></h5>
                    <p><?php echo nl2br(htmlspecialchars($lostItem['description'])); ?></p>
                    <p><strong>Date Lost:</strong> <?php echo formatDate($lostItem['date_lost']); ?></p>
                    <p><strong>Reported By:</strong> <?php echo htmlspecialchars($lostItem['owner_name']); ?></p>
                </div>
            </div>
        </div>

        <!-- Found Item Card -->
    <?php if ($foundItem): ?>
        <div class="card mb-4">
            <div class="row g-3 p-3">
                <div class="col-md-4">
                    <?php if ($foundItem['photo']): ?>
                        <img src="<?php echo getImageUrl($foundItem['photo'], 'found'); ?>" class="img-fluid rounded">
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <h5><?php echo htmlspecialchars($foundItem['item_name']); ?></h5>
                    <p><?php echo nl2br(htmlspecialchars($foundItem['description'])); ?></p>
                    <p><strong>Date Found:</strong> <?php echo formatDate($foundItem['date_found']); ?></p>
                    <p><strong>Found By:</strong> <?php echo htmlspecialchars($foundItem['finder_name']); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

        <!-- Claim Form -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Submit Claim Request</h5>
                <form id="claimRequestForm">
                    <!-- Hidden inputs for required fields -->
                    <input type="hidden" name="lost_item_id" value="<?php echo $lostItemId; ?>">
                    <input type="hidden" name="item_name" value="<?php echo htmlspecialchars($lostItem['item_name']); ?>">
                    <input type="hidden" name="description" value="<?php echo htmlspecialchars($lostItem['description']); ?>">

                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes (Optional)</label>
                        <textarea id="notes" name="notes" class="form-control" rows="4"
                                  placeholder="Add details like serial numbers, unique features, or proof of purchase..."></textarea>
                    </div>

                    <div class="alert alert-info">
                        <h6>What Happens Next?</h6>
                        <ol class="mb-0">
                            <li>Admin reviews your claim request</li>
                            <li>Admin verifies ownership details</li>
                            <li>If approved, admin schedules a claim date/time</li>
                            <li>You’ll receive a notification with schedule details</li>
                            <li>Bring valid ID and proof of ownership to claim your item</li>
                        </ol>
                    </div>

                    <button type="submit" class="btn btn-success">Submit Claim Request</button>
                    <a href="view_lost_item.php?id=<?php echo $lostItemId; ?>" class="btn btn-secondary ms-2">Cancel</a>
                </form>
            </div>
        </div>

        <script>
            document.getElementById('claimRequestForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                const form = document.getElementById('claimRequestForm');
                const formData = new FormData(form);

                try {
                    const response = await fetch('../api/claims/create_claim.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        alert(data.message);
                        window.location.href = 'my_items.php';
                    } else {
                        alert(data.message);
                    }
                } catch (err) {
                    alert('An error occurred: ' + err.message);
                }
            });
        </script>

    <?php endif; ?>
</main>

<footer class="bg-dark text-white text-center py-3">
    &copy; 2024 Campus Lost & Found System
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
