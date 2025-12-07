<?php global $pdo;
/**
 * API - Get Items
 * Fetch lost or found items (with filtering)
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

// Get parameters
$type = isset($_GET['type']) ? sanitize($_GET['type']) : 'lost'; // lost or found
$status = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

try {
    if ($type === 'lost') {
        $query = "
            SELECT li.*, u.name as owner_name, u.email as owner_email
            FROM lost_items li
            JOIN users u ON li.user_id = u.id
            WHERE 1=1
        ";

        if ($status !== 'all') {
            $query .= " AND li.status = :status";
        }

        if (!empty($search)) {
            $query .= " AND (li.item_name LIKE :search OR li.description LIKE :search)";
        }

        if ($userId) {
            $query .= " AND li.user_id = :user_id";
        }

        // For students, only show listed or ready_for_claim items (not pending)
        if (isStudent() && !$userId) {
            $query .= " AND li.status IN ('listed', 'ready_for_claim')";
        }

        $query .= " ORDER BY li.created_at DESC";

        $stmt = $pdo->prepare($query);

        if ($status !== 'all') {
            $stmt->bindValue(':status', $status);
        }
        if (!empty($search)) {
            $stmt->bindValue(':search', "%$search%");
        }
        if ($userId) {
            $stmt->bindValue(':user_id', $userId);
        }

        $stmt->execute();
        $items = $stmt->fetchAll();

        // Format items with image URLs
        foreach ($items as &$item) {
            $item['photo_url'] = $item['photo'] ? getImageUrl($item['photo'], 'lost') : null;
            $item['formatted_date'] = formatDate($item['date_lost']);
            $item['formatted_created'] = formatDateTime($item['created_at']);
        }

    } else {
        $query = "
            SELECT fi.*, u.name as finder_name, u.email as finder_email
            FROM found_items fi
            JOIN users u ON fi.user_id = u.id
            WHERE 1=1
        ";

        if ($status !== 'all') {
            $query .= " AND fi.status = :status";
        }

        if (!empty($search)) {
            $query .= " AND (fi.item_name LIKE :search OR fi.description LIKE :search)";
        }

        if ($userId) {
            $query .= " AND fi.user_id = :user_id";
        }

        // For students, only show verified/listed items
        if (isStudent() && !$userId) {
            $query .= " AND fi.status IN ('verified', 'listed')";
        }

        $query .= " ORDER BY fi.created_at DESC";

        $stmt = $pdo->prepare($query);

        if ($status !== 'all') {
            $stmt->bindValue(':status', $status);
        }
        if (!empty($search)) {
            $stmt->bindValue(':search', "%$search%");
        }
        if ($userId) {
            $stmt->bindValue(':user_id', $userId);
        }

        $stmt->execute();
        $items = $stmt->fetchAll();

        // Format items with image URLs
        foreach ($items as &$item) {
            $item['photo_url'] = $item['photo'] ? getImageUrl($item['photo'], 'found') : null;
            $item['formatted_date'] = formatDate($item['date_found']);
            $item['formatted_created'] = formatDateTime($item['created_at']);
        }
    }

    echo json_encode([
        'success' => true,
        'items' => $items,
        'count' => count($items)
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch items'
    ]);
}
?>