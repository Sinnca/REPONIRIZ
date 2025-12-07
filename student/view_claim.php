<?php
global $pdo;
/**
 * Student - View Claim Details
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireStudent();

$userId = getCurrentUserId();
$claimId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$claimId) header('Location: index.php');

// Fetch claim details using COALESCE to handle missing claim_requests info
$stmt = $pdo->prepare("
    SELECT cr.*, 
           COALESCE(cr.item_name, li.item_name) AS display_item_name,
           COALESCE(cr.item_description, li.description) AS display_item_description,
           COALESCE(cr.photo, li.photo) AS display_photo,
           li.date_lost AS display_date_lost,
           fi.item_name AS found_item_name,
           fi.description AS found_description,
           fi.photo AS found_photo,
           fi.date_found,
           admin.name AS admin_name
    FROM claim_requests cr
    LEFT JOIN lost_items li ON cr.lost_item_id = li.id
    LEFT JOIN found_items fi ON cr.found_item_id = fi.id
    LEFT JOIN users admin ON cr.admin_id = admin.id
    WHERE cr.id = ? AND cr.requester_id = ?
");
$stmt->execute([$claimId, $userId]);
$claim = $stmt->fetch();

if (!$claim) header('Location: index.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Details - Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <span class="navbar-text me-3">Welcome, <?= htmlspecialchars(getCurrentUserName()); ?></span>
            <a class="btn btn-outline-light" href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<main class="container mb-5">
    <h1 class="mb-3">Claim Request Details</h1>
    <a href="my_items.php" class="btn btn-secondary mb-3">&larr; Back to My Items</a>

    <!-- Status -->
    <div class="mb-4">
        <?php if ($claim['status'] === 'pending'): ?>
            <div class="alert alert-info">Your claim request is being reviewed by the admin.</div>
        <?php elseif ($claim['status'] === 'scheduled'): ?>
            <div class="alert alert-success">
                <h5>✓ Claim Approved!</h5>
                <p><strong>Scheduled Date:</strong> <?= formatDateTime($claim['schedule_date']); ?></p>
                <p>Please bring the following when claiming your item:</p>
                <ul>
                    <li>Valid Student ID</li>
                    <li>Proof of ownership (photo, receipt, or unique features)</li>
                    <li>Claim reference number: <strong>#<?= $claim['id']; ?></strong></li>
                </ul>
            </div>
        <?php elseif ($claim['status'] === 'completed'): ?>
            <div class="alert alert-success">
                <h5>✓ Claim Completed!</h5>
                <p>Your item has been returned successfully.</p>
            </div>
        <?php elseif ($claim['status'] === 'rejected'): ?>
            <div class="alert alert-danger">
                <h5>Claim Rejected</h5>
                <p>Your claim request was rejected.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Claim Information -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Claim Information</h5>
            <p><strong>Claim ID:</strong> <?= $claim['id']; ?></p>
            <p><strong>Request Date:</strong> <?= formatDateTime($claim['created_at']); ?></p>
            <?php if (!empty($claim['schedule_date'])): ?>
                <p><strong>Scheduled Date:</strong> <?= formatDateTime($claim['schedule_date']); ?></p>
            <?php endif; ?>
            <?php if (!empty($claim['admin_name'])): ?>
                <p><strong>Processed By:</strong> <?= htmlspecialchars($claim['admin_name']); ?></p>
            <?php endif; ?>
            <?php if (!empty($claim['notes'])): ?>
                <p><strong>Admin Notes:</strong> <?= nl2br(htmlspecialchars($claim['notes'])); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Claimer Provided Information -->
    <div class="card mb-4">
        <div class="row g-3 p-3">
            <h5 class="mb-3">Your Provided Information</h5>

            <?php if (!empty($claim['display_photo'])): ?>
                <div class="col-md-4">
                    <label class="form-label">Claim Photo:</label>
                    <img src="<?= getImageUrl($claim['display_photo'], 'claim'); ?>" class="img-fluid rounded" alt="Claim Photo">
                </div>
            <?php endif; ?>

            <div class="<?= !empty($claim['display_photo']) ? 'col-md-8' : 'col-12'; ?>">
                <p><strong>Item Name:</strong> <?= htmlspecialchars($claim['display_item_name'] ?? 'N/A'); ?></p>
                <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($claim['display_item_description'] ?? 'N/A')); ?></p>
                <p><strong>Date Lost:</strong> <?= !empty($claim['display_date_lost']) ? formatDate($claim['display_date_lost']) : 'N/A'; ?></p>
            </div>
        </div>
    </div>

    <!-- Found Item Information -->
    <?php if (!empty($claim['found_item_id'])): ?>
        <div class="card mb-4">
            <div class="row g-3 p-3">
                <h5 class="mb-3">Found Item Information</h5>
                <div class="col-md-4">
                    <?php if (!empty($claim['found_photo'])): ?>
                        <label class="form-label">Finder Provided Photo:</label>
                        <img src="<?= getImageUrl($claim['found_photo'], 'found'); ?>" class="img-fluid rounded" alt="Found Item Photo">
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <p><strong>Item Name:</strong> <?= htmlspecialchars($claim['found_item_name'] ?? 'N/A'); ?></p>
                    <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($claim['found_description'] ?? 'N/A')); ?></p>
                    <p><strong>Date Found:</strong> <?= !empty($claim['date_found']) ? formatDate($claim['date_found']) : 'N/A'; ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Next Steps -->
    <?php if ($claim['status'] === 'scheduled'): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h5>Next Steps</h5>
                <ol>
                    <li>Save your claim reference number: <strong>#<?= $claim['id']; ?></strong></li>
                    <li>Arrive at the Lost & Found office on <strong><?= formatDateTime($claim['schedule_date']); ?></strong></li>
                    <li>Present student ID and proof of ownership</li>
                    <li>Verify item matches your lost item</li>
                    <li>Sign acknowledgment form</li>
                    <li>Receive your item</li>
                </ol>
                <p class="text-warning"><strong>Important:</strong> Contact admin to reschedule if you cannot attend.</p>
            </div>
        </div>
    <?php endif; ?>
</main>

<footer class="bg-dark text-white text-center py-3">
    &copy; 2024 Campus Lost & Found System
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
