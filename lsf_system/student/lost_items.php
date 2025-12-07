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
</head>
<body class="bg-light">

<!-- Header & Navbar -->
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
                <li class="nav-item"><a class="nav-link" href="report_found.php">Report Found Item</a></li>
                <li class="nav-item"><a class="nav-link active" href="lost_items.php">Browse Lost Items</a></li>
                <li class="nav-item"><a class="nav-link" href="found_items.php">Browse Found Items</a></li>
                <li class="nav-item"><a class="nav-link" href="my_items.php">My Items</a></li>
            </ul>
            <span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars(getCurrentUserName()); ?></span>
            <a class="btn btn-outline-light" href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<main class="container">

    <div class="mb-4">
        <h1 class="mb-2">Browse Lost Items</h1>
        <p>If you found any of these items, click "I Found This Item" to report it.</p>
    </div>

    <?php if (count($lostItems) > 0): ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($lostItems as $item): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <?php if ($item['photo']): ?>
                            <img src="<?php echo getImageUrl($item['photo'], 'lost'); ?>"
                                 class="card-img-top" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                        <?php else: ?>
                            <div class="bg-secondary text-white d-flex align-items-center justify-content-center"
                                 style="height:200px;">
                                <p class="mb-0">No Image Available</p>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars(substr($item['description'], 0, 100)) . '...'; ?></p>
                            <p class="mb-1"><strong>Date Lost:</strong> <?php echo formatDate($item['date_lost']); ?></p>
                            <p class="mb-2"><strong>Status:</strong> <?php echo getStatusBadge($item['status']); ?></p>
                            <a href="view_lost_item.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-info me-1 mb-1">View Details</a>

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
                                           class="btn btn-sm btn-success mb-1">I Found This Item</a>
                                    <?php else: ?>
                                        <span class="badge bg-warning mb-1">Already Reported as Found</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary mb-1">You reported this item</span>
                                <?php endif; ?>
                            <?php elseif ($item['status'] === 'ready_for_claim'): ?>
                                <span class="badge bg-warning mb-1">Match Found</span>
                            <?php endif; ?>


                        </div>
                        <div class="card-footer text-muted">
                            Reported by: <?php echo htmlspecialchars($item['owner_name']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No lost items currently listed.</p>
    <?php endif; ?>

    <section class="mt-5">
        <h2>Found Something?</h2>
        <p>If you found an item that matches one of the listings above, click "I Found This Item" to help reunite it with its owner.</p>
        <p>Don't see a matching item? You can still <a href="report_found.php">report your found item</a> directly.</p>
    </section>

</main>

<footer class="bg-dark text-white text-center py-3 mt-5">
    &copy; 2024 Campus Lost & Found System
</footer>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
