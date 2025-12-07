<?php global $pdo;
/**
 * API - Get Notifications
 * Fetch user notifications for real-time updates
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

// Check authentication
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

$userId = getCurrentUserId();
$unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

try {
    $query = "SELECT * FROM notifications WHERE user_id = ?";

    if ($unreadOnly) {
        $query .= " AND is_read = 0";
    }

    $query .= " ORDER BY created_at DESC LIMIT ?";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId, $limit]);
    $notifications = $stmt->fetchAll();

    // Get unread count
    $stmtCount = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmtCount->execute([$userId]);
    $unreadCount = $stmtCount->fetch()['count'];

    // Format dates
    foreach ($notifications as &$notification) {
        $notification['formatted_date'] = formatDateTime($notification['created_at']);
    }

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unreadCount
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch notifications'
    ]);
}
?>