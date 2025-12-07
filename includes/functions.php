<?php
/**
 * Helper Functions
 * Sanitization, validation, file uploads, and utilities
 */

/**
 * Count how many items a user has submitted today
 * @param PDO $pdo
 * @param int $userId
 * @param string $type 'lost' or 'found'
 * @return int
 */
function getUserDailySubmissionCount(PDO $pdo, int $userId, $type = 'lost') {
    $table = ($type === 'lost') ? 'lost_items' : 'found_items';
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM $table 
        WHERE user_id = ? 
          AND DATE(created_at) = CURDATE()
    ");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Check if user can submit more items today
 * Returns true if allowed, false if limit reached
 * @param PDO $pdo
 * @param int $userId
 * @param string $type 'lost' or 'found'
 * @param int $limit Maximum items per day (default 2)
 * @return bool
 */
function canUserSubmitItem($pdo, $userId, $type = 'lost', $limit = 2) {
    return getUserDailySubmissionCount($pdo, $userId, $type) < $limit;
}

/**
 * Sanitize input data
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate date format (YYYY-MM-DD)
 */
function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Handle file upload
 * @param array $file - $_FILES['field_name']
 * @param string $type - 'lost' or 'found'
 * @return string|false - filename on success, false on failure
 */
function uploadImage($file, $type = 'lost') {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // No file uploaded
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return false;
    }

    $filename = uniqid() . '_' . time() . '.' . $ext;
    $uploadPath = ($type === 'lost') ? LOST_ITEMS_PATH : FOUND_ITEMS_PATH;

    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
        return $filename;
    }

    return false;
}

/**
 * Delete uploaded file
 */
function deleteImage($filename, $type = 'lost') {
    if (empty($filename)) {
        return true;
    }

    $path = ($type === 'lost') ? LOST_ITEMS_PATH : FOUND_ITEMS_PATH;
    $filepath = $path . $filename;

    if (file_exists($filepath)) {
        return unlink($filepath);
    }

    return true;
}

/**
 * Get image URL
 */
if (!function_exists('getImageUrl')) {
    function getImageUrl($filename, $type = 'lost') {
        if (empty($filename)) {
            return BASE_URL . 'assets/img/no-image.png';
        }
        switch ($type) {
            case 'found':
                $url = BASE_URL . 'assets/uploads/found_items/';
                break;
            case 'claim':
                $url = BASE_URL . 'assets/uploads/claim_requests/';
                break;
            default:
                $url = BASE_URL . 'assets/uploads/lost_items/';
        }
        return $url . $filename;
    }
}

/**
 * Format date for display
 */
function formatDate($date) {
    if (empty($date)) return 'N/A';
    return date('F j, Y', strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime) {
    if (empty($datetime)) return 'N/A';
    return date('F j, Y g:i A', strtotime($datetime));
}

/**
 * Get status badge HTML
 */
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge badge-warning">Pending</span>',
        'listed' => '<span class="badge badge-info">Listed</span>',
        'ready_for_claim' => '<span class="badge badge-success">Ready for Claim</span>',
        'returned' => '<span class="badge badge-secondary">Returned</span>',
        'rejected' => '<span class="badge badge-danger">Rejected</span>',
        'verified' => '<span class="badge badge-success">Verified</span>',
        'claimed' => '<span class="badge badge-secondary">Claimed</span>',
        'approved' => '<span class="badge badge-success">Approved</span>',
        'scheduled' => '<span class="badge badge-primary">Scheduled</span>',
        'completed' => '<span class="badge badge-secondary">Completed</span>',
    ];
    return $badges[$status] ?? '<span class="badge badge-light">' . ucfirst($status) . '</span>';
}

/**
 * Create notification
 */
function createNotification($pdo, $userId, $message) {
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, message) 
        VALUES (?, ?)
    ");
    return $stmt->execute([$userId, $message]);
}

/**
 * Get unread notification count
 */
function getUnreadNotificationCount($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}

/**
 * Redirect with message
 */
function redirect($url, $message = '', $type = 'success') {
    if (!empty($message)) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Display flash message
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';

        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($message);
        echo '<button type="button" class="close" data-dismiss="alert">&times;</button>';
        echo '</div>';

        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
