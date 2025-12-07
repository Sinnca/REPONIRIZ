<?php
// Get current page filename for active state
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<!-- Sidebar Styles -->
<style>
    body {
        background: linear-gradient(145deg, #e5e8ec, #fafbfc);
        font-family: "Inter", sans-serif;
        margin-left: 250px !important;
    }

    /* Sidebar container - Fixed expanded */
    #sidebar {
        height: 100vh;
        width: 250px;
        background: rgba(31, 41, 55, 0.85);
        backdrop-filter: blur(12px);
        overflow-x: hidden;
        overflow-y: auto;
        position: fixed;
        z-index: 1000;
        box-shadow: 4px 0 25px rgba(0,0,0,0.15);
        top: 0;
        left: 0;
    }

    /* Sidebar header */
    #sidebar .brand-text {
        font-size: 1.25rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    /* Nav Links */
    .sidebar-nav-link {
        color: #dbe3ec !important;
        font-size: 15px;
        padding: 10px 15px;
        border-radius: 8px;
        margin: 4px 8px;
        transition: all .25s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        cursor: pointer;
    }

    .sidebar-nav-link:hover {
        background: rgba(255, 255, 255, 0.12);
        color: #ffffff !important;
        box-shadow: inset 0 0 8px rgba(255, 255, 255, 0.15);
    }

    .sidebar-nav-link.active {
        background: rgba(59, 130, 246, 0.3);
        color: #ffffff !important;
    }

    /* Icons */
    .sidebar-nav-link i {
        font-size: 20px;
        width: 30px;
        min-width: 30px;
    }

    /* Dropdown links */
    .collapse .sidebar-nav-link {
        padding-left: 45px;
        font-size: 14px;
        opacity: 0.85;
    }

    .collapse .sidebar-nav-link:hover {
        opacity: 1;
    }

    /* User section */
    .user-section {
        position: sticky;
        bottom: 0;
        width: 100%;
        background: rgba(31, 41, 55, 0.95);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding: 15px;
        margin-top: auto;
    }

    .user-info {
        color: #dbe3ec;
        font-size: 14px;
        white-space: nowrap;
        overflow: hidden;
    }

    .logout-btn {
        color: #dbe3ec !important;
        font-size: 14px;
        padding: 8px 12px;
        border-radius: 6px;
        transition: all .25s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        width: 100%;
    }

    .logout-btn:hover {
        background: rgba(239, 68, 68, 0.2);
        color: #ffffff !important;
    }

    /* Scrollbar styling */
    #sidebar::-webkit-scrollbar {
        width: 6px;
    }

    #sidebar::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.1);
    }

    #sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
    }

    #sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    /* Keep dropdowns always visible */
    .collapse {
        display: block !important;
        height: auto !important;
    }
</style>

<!-- Sidebar -->
<div id="sidebar">
    <div class="d-flex align-items-center p-3 border-bottom border-secondary" style="position: sticky; top: 0; background: rgba(31, 41, 55, 0.95); z-index: 10;">
        <span class="brand-text text-white">Admin Panel</span>
    </div>

    <div style="display: flex; flex-direction: column; min-height: calc(100vh - 60px);">
        <ul class="nav flex-column mt-3" style="flex: 1; padding-bottom: 20px;">
            
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="sidebar-nav-link <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="bi bi-speedometer2"></i>
                    <span class="ms-2">Dashboard</span>
                </a>
            </li>

            <!-- ITEMS -->
            <li class="nav-item mt-2">
                <a class="sidebar-nav-link" href="#" style="pointer-events: none; opacity: 0.9;">
                    <i class="bi bi-box-seam"></i>
                    <span class="ms-2">Items</span>
                </a>

                <div class="collapse show" id="itemsMenu">
                    <a class="sidebar-nav-link <?php echo $currentPage == 'pending_lost.php' ? 'active' : ''; ?>" href="pending_lost.php">
                        <i class="bi bi-search"></i>
                        <span class="ms-2">Pending Lost Items</span>
                    </a>

                    <a class="sidebar-nav-link <?php echo $currentPage == 'pending_found.php' ? 'active' : ''; ?>" href="pending_found.php">
                        <i class="bi bi-bag-check"></i>
                        <span class="ms-2">Pending Found Items</span>
                    </a>

                    <a class="sidebar-nav-link <?php echo $currentPage == 'claim_requests.php' ? 'active' : ''; ?>" href="claim_requests.php">
                        <i class="bi bi-journal-text"></i>
                        <span class="ms-2">Claim Requests</span>
                    </a>

                    <a class="sidebar-nav-link <?php echo $currentPage == 'all_items.php' ? 'active' : ''; ?>" href="all_items.php">
                        <i class="bi bi-collection"></i>
                        <span class="ms-2">All Items</span>
                    </a>
                </div>
            </li>

            <!-- REPORTS -->
            <li class="nav-item mt-2">
                <a class="sidebar-nav-link" href="#" style="pointer-events: none; opacity: 0.9;">
                    <i class="bi bi-graph-up"></i>
                    <span class="ms-2">Reports</span>
                </a>

                <div class="collapse show" id="reportsMenu">
                    <a class="sidebar-nav-link <?php echo $currentPage == 'statistics.php' ? 'active' : ''; ?>" href="statistics.php">
                        <i class="bi bi-bar-chart-line"></i>
                        <span class="ms-2">Statistics</span>
                    </a>
                </div>
            </li>

        </ul>

        <!-- User Section -->
        <div class="user-section">
            <a class="logout-btn" href="../logout.php">
                <i class="bi bi-box-arrow-right"></i>
                <span class="ms-2">Logout</span>
            </a>
        </div>
    </div>
</div>