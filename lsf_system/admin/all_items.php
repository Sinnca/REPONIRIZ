<?php
/**
 * Admin - All Items Management
 * View all lost and found items in the system
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

// Get filter parameters
$type = isset($_GET['type']) ? sanitize($_GET['type']) : 'lost';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query for lost items
if ($type === 'lost') {
    $query = "
        SELECT li.*, u.name as submitter_name, u.email as submitter_email
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

    $query .= " ORDER BY li.created_at DESC";

    $stmt = $pdo->prepare($query);

    if ($status !== 'all') {
        $stmt->bindValue(':status', $status);
    }
    if (!empty($search)) {
        $stmt->bindValue(':search', "%$search%");
    }

    $stmt->execute();
    $items = $stmt->fetchAll();
}
// Build query for found items
else {
    $query = "
        SELECT fi.*, u.name as submitter_name, u.email as submitter_email
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

    $query .= " ORDER BY fi.created_at DESC";

    $stmt = $pdo->prepare($query);

    if ($status !== 'all') {
        $stmt->bindValue(':status', $status);
    }
    if (!empty($search)) {
        $stmt->bindValue(':search', "%$search%");
    }

    $stmt->execute();
    $items = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Items - Admin</title>
</head>
<body>

<header>
    <nav>
        <h1>Lost & Found System - Admin</h1>
        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="pending_lost.php">Pending Lost Items</a></li>
            <li><a href="pending_found.php">Pending Found Items</a></li>
            <li><a href="claim_requests.php">Claim Requests</a></li>
            <li><a href="all_items.php">All Items</a></li>
            <li><a href="statistics.php">Statistics</a></li>
        </ul>
        <div>
            <span>Admin: <?php echo htmlspecialchars(getCurrentUserName()); ?></span>
            <a href="../logout.php">Logout</a>
        </div>
    </nav>
</header>

<main>

    <h1>All Items Management</h1>

    <a href="index.php">&larr; Back to Dashboard</a>

    <!-- Filters -->
    <section>
        <h2>Filter Items</h2>

        <form method="GET" action="">

            <div>
                <label for="type">Item Type</label>
                <select id="type" name="type">
                    <option value="lost" <?php echo $type === 'lost' ? 'selected' : ''; ?>>Lost Items</option>
                    <option value="found" <?php echo $type === 'found' ? 'selected' : ''; ?>>Found Items</option>
                </select>
            </div>

            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Statuses</option>

                    <?php if ($type === 'lost'): ?>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="listed" <?php echo $status === 'listed' ? 'selected' : ''; ?>>Listed</option>
                        <option value="ready_for_claim" <?php echo $status === 'ready_for_claim' ? 'selected' : ''; ?>>Ready for Claim</option>
                        <option value="returned" <?php echo $status === 'returned' ? 'selected' : ''; ?>>Returned</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <?php else: ?>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="verified" <?php echo $status === 'verified' ? 'selected' : ''; ?>>Verified</option>
                        <option value="listed" <?php echo $status === 'listed' ? 'selected' : ''; ?>>Listed</option>
                        <option value="claimed" <?php echo $status === 'claimed' ? 'selected' : ''; ?>>Claimed</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label for="search">Search</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    placeholder="Search item name or description..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >
            </div>

            <div>
                <button type="submit">Apply Filters</button>
                <a href="all_items.php">Clear Filters</a>
            </div>

        </form>
    </section>

    <!-- Items List -->
    <section>
        <h2><?php echo ucfirst($type); ?> Items (<?php echo count($items); ?>)</h2>

        <?php if (count($items) > 0): ?>

            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Photo</th>
                    <th>Item Name</th>
                    <th>Description</th>
                    <th>Date <?php echo $type === 'lost' ? 'Lost' : 'Found'; ?></th>
                    <th>Submitted By</th>
                    <th>Status</th>
                    <th>Date Submitted</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo $item['id']; ?></td>
                        <td>
                            <?php if ($item['photo']): ?>
                                <img
                                    src="<?php echo getImageUrl($item['photo'], $type); ?>"
                                    alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                                    style="width: 50px; height: 50px; object-fit: cover;"
                                >
                            <?php else: ?>
                                <span>No Image</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                        <td><?php echo htmlspecialchars(substr($item['description'], 0, 60)) . '...'; ?></td>
                        <td>
                            <?php
                            $dateField = $type === 'lost' ? 'date_lost' : 'date_found';
                            echo formatDate($item[$dateField]);
                            ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($item['submitter_name']); ?><br>
                            <small><?php echo htmlspecialchars($item['submitter_email']); ?></small>
                        </td>
                        <td><?php echo getStatusBadge($item['status']); ?></td>
                        <td><?php echo formatDate($item['created_at']); ?></td>
                        <td>
                            <?php if ($type === 'lost'): ?>
                                <a href="verify_lost.php?id=<?php echo $item['id']; ?>">View</a>
                            <?php else: ?>
                                <a href="verify_found.php?id=<?php echo $item['id']; ?>">View</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        <?php else: ?>
            <div class="info-message">
                <p>No items found matching your filters.</p>
            </div>
        <?php endif; ?>
    </section>

</main>

<footer>
    <p>&copy; 2024 Campus Lost & Found System - Admin Panel</p>
</footer>

</body>
</html>