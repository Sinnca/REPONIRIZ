<?php
global $pdo;
/**
 * API - Create Claim Request
 * Students can submit a claim even without previous lost post
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

if (!isLoggedIn() || !isStudent()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// --- STEP 0: Collect & sanitize inputs ---
$userId = getCurrentUserId();

// Ensure IDs are positive integers or NULL to prevent FK errors
$lostItemId = isset($_POST['lost_item_id']) && is_numeric($_POST['lost_item_id']) && $_POST['lost_item_id'] > 0
    ? intval($_POST['lost_item_id'])
    : null;

$foundItemId = isset($_POST['found_item_id']) && is_numeric($_POST['found_item_id']) && $_POST['found_item_id'] > 0
    ? intval($_POST['found_item_id'])
    : null;

$notes = sanitize($_POST['notes'] ?? '');
$itemName = sanitize($_POST['item_name'] ?? '');
$itemDescription = sanitize($_POST['description'] ?? '');

// Validate required fields
if (!$itemName || !$itemDescription) {
    echo json_encode(['success' => false, 'message' => 'Item name and description are required.']);
    exit;
}

// Upload directories
$claimUploadDir   = realpath(__DIR__ . '/../../assets/uploads/claim_requests') ?: __DIR__ . '/../../assets/uploads/claim_requests';
$foundUploadDir   = realpath(__DIR__ . '/../../assets/uploads/found_items') ?: __DIR__ . '/../../assets/uploads/found_items';
$lostUploadDir    = realpath(__DIR__ . '/../../assets/uploads/lost_items') ?: __DIR__ . '/../../assets/uploads/lost_items';

// Ensure claim upload dir exists
if (!is_dir($claimUploadDir)) {
    if (!mkdir($claimUploadDir, 0755, true) && !is_dir($claimUploadDir)) {
        echo json_encode(['success' => false, 'message' => 'Unable to create upload directory.']);
        exit;
    }
}

// --- STEP 1: Handle uploaded photo (if any) ---
$claimPhoto = null;
if (isset($_FILES['claim_photo']) && is_array($_FILES['claim_photo']) && $_FILES['claim_photo']['error'] === UPLOAD_ERR_OK) {
    $origName = basename($_FILES['claim_photo']['name']);
    $ext = pathinfo($origName, PATHINFO_EXTENSION);
    $fileName = uniqid('claim_', true) . ($ext ? '.' . $ext : '');
    $target = rtrim($claimUploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($_FILES['claim_photo']['tmp_name'], $target)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload the photo.']);
        exit;
    }

    $claimPhoto = $fileName;
}

// Helper to copy a photo from another uploads folder into claim_requests folder and return new filename (or null)
function copyImageToClaimFolder($sourceDir, $sourceFile, $claimUploadDir) {
    if (empty($sourceFile)) return null;
    $sourcePath = rtrim($sourceDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $sourceFile;
    if (!is_file($sourcePath) || !is_readable($sourcePath)) return null;

    $ext = pathinfo($sourceFile, PATHINFO_EXTENSION);
    $newName = uniqid('claim_', true) . ($ext ? '.' . $ext : '');
    $targetPath = rtrim($claimUploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newName;

    if (@copy($sourcePath, $targetPath)) {
        return $newName;
    }
    return null;
}

try {
    // --- STEP 2: Validate lost item if provided ---
    if ($lostItemId !== null) {
        $stmtLostCheck = $pdo->prepare("SELECT id, photo FROM lost_items WHERE id = ?");
        $stmtLostCheck->execute([$lostItemId]);
        $lostRow = $stmtLostCheck->fetch();
        if (!$lostRow) {
            $lostItemId = null; // fallback to null to prevent FK error
            $lostPhotoFile = null;
        } else {
            $lostPhotoFile = $lostRow['photo'] ?? null;
        }
    } else {
        $lostPhotoFile = null;
    }

    // --- STEP 3: Validate found item if provided ---
    if ($foundItemId !== null) {
        $stmtFoundCheck = $pdo->prepare("SELECT id, photo FROM found_items WHERE id = ?");
        $stmtFoundCheck->execute([$foundItemId]);
        $foundRow = $stmtFoundCheck->fetch();
        if (!$foundRow) {
            $foundItemId = null; // fallback to null to prevent FK error
            $foundPhotoFile = null;
        } else {
            $foundPhotoFile = $foundRow['photo'] ?? null;
        }
    } else {
        $foundPhotoFile = null;
    }

    // --- STEP 4: Prevent duplicate claims per scenario ---
    $isLostItemOwner = false;
    if ($lostItemId) {
        $stmtLostOwner = $pdo->prepare("SELECT user_id FROM lost_items WHERE id = ?");
        $stmtLostOwner->execute([$lostItemId]);
        $lostOwner = $stmtLostOwner->fetchColumn();
        $isLostItemOwner = ($lostOwner == $userId);
    }

    if ($isLostItemOwner) {
        $stmtCheckDuplicate = $pdo->prepare("
            SELECT id 
            FROM claim_requests 
            WHERE requester_id = ? 
              AND lost_item_id = ? 
              AND found_item_id = ?
              AND status = 'pending'
        ");
        $stmtCheckDuplicate->execute([$userId, $lostItemId, $foundItemId]);
    } else {
        $stmtCheckDuplicate = $pdo->prepare("
            SELECT id 
            FROM claim_requests 
            WHERE requester_id = ? 
              AND found_item_id = ?
              AND status = 'pending'
        ");
        $stmtCheckDuplicate->execute([$userId, $foundItemId]);
    }

    if ($stmtCheckDuplicate->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You already submitted a claim request for this item.']);
        exit;
    }

    // --- STEP 4.5: Handle fallback photo ---
    if (empty($claimPhoto)) {
        if (!empty($foundPhotoFile)) {
            $copied = copyImageToClaimFolder($foundUploadDir, $foundPhotoFile, $claimUploadDir);
            if ($copied) $claimPhoto = $copied;
        }
        if (empty($claimPhoto) && !empty($lostPhotoFile)) {
            $copied = copyImageToClaimFolder($lostUploadDir, $lostPhotoFile, $claimUploadDir);
            if ($copied) $claimPhoto = $copied;
        }
    }

    // --- STEP 5: Insert claim request ---
    $stmtInsert = $pdo->prepare("
        INSERT INTO claim_requests
        (lost_item_id, found_item_id, requester_id, status, notes, photo, item_name, item_description)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtInsert->execute([
        $lostItemId,
        $foundItemId,
        $userId,
        CLAIM_STATUS_PENDING,
        $notes ?: null,
        $claimPhoto,
        $itemName,
        $itemDescription
    ]);

    $claimId = $pdo->lastInsertId();

    // --- STEP 7: Notify admin ---
    $adminStmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $admin = $adminStmt->fetch();
    if ($admin) {
        createNotification($pdo, $admin['id'], "New claim request submitted by a student.");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Claim request submitted successfully!',
        'claim_id' => $claimId
    ]);
    exit;

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
