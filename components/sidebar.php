<?php
// Get current page filename for active state
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<!-- Sidebar Styles -->
<style>
    body {
        background: #f5f7fa;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        margin-left: 280px !important;
    }

    /* Sidebar container */
    #sidebar {
        height: 100vh;
        width: 280px;
        background: linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
        overflow: hidden;
        position: fixed;
        z-index: 1000;
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.08);
        top: 0;
        left: 0;
        border-right: 1px solid #e5e7eb;
    }

    /* Sidebar header */
    #sidebar .brand-header {
        padding: 28px 24px;
        border-bottom: 1px solid #e5e7eb;
        background: white;
        position: relative;
    }

    #sidebar .brand-text {
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: -0.5px;
        color: #003DA5;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 2px;
    }

    #sidebar .brand-text i {
        font-size: 2rem;
        background: linear-gradient(135deg, #003DA5, #0052d4);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    #sidebar .brand-subtitle {
        font-size: 0.75rem;
        color: #6b7280;
        margin-top: 2px;
        font-weight: 500;
        letter-spacing: 0.3px;
    }

    /* Navigation Container */
    .nav-container {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 104px);
        padding: 8px 0 0 0;
    }

    .nav-main {
        flex: 1;
        overflow: hidden;
        padding-bottom: 10px;
    }

    /* Section Headers */
    .nav-section-header {
        padding: 12px 24px 6px 24px;
        font-size: 0.625rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        color: #9ca3af;
        margin-top: 4px;
    }

    /* Nav Links */
    .sidebar-nav-link {
        color: #4b5563;
        font-size: 0.875rem;
        padding: 10px 24px;
        border-radius: 10px;
        margin: 2px 16px;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        display: flex;
        align-items: center;
        font-weight: 500;
        position: relative;
    }

    .sidebar-nav-link:hover {
        background: linear-gradient(90deg, rgba(0, 61, 165, 0.08), transparent);
        color: #003DA5;
        transform: translateX(4px);
    }

    .sidebar-nav-link.active {
        background: linear-gradient(90deg, #003DA5, #0052d4);
        color: white;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(0, 61, 165, 0.25);
    }

    .sidebar-nav-link.active::after {
        content: '';
        position: absolute;
        right: 12px;
        width: 6px;
        height: 6px;
        background: white;
        border-radius: 50%;
    }

    /* Icons */
    .sidebar-nav-link i {
        font-size: 1.125rem;
        width: 28px;
        min-width: 28px;
        color: inherit;
    }

    /* Dropdown/Submenu links */
    .nav-submenu {
        display: block !important;
        height: auto !important;
        background: transparent;
        padding: 2px 0;
        margin: 0;
    }

    .nav-submenu .sidebar-nav-link {
        padding: 8px 24px 8px 60px;
        font-size: 0.8125rem;
        font-weight: 500;
        margin: 2px 16px;
    }

    .nav-submenu .sidebar-nav-link i {
        font-size: 1rem;
        opacity: 0.8;
    }

    .nav-submenu .sidebar-nav-link:hover {
       background: linear-gradient(90deg, rgba(0, 61, 165, 0.08), transparent);
        color: #003DA5;
        transform: translateX(4px);
    }

    .nav-submenu .sidebar-nav-link.active {
         background: linear-gradient(90deg, #003DA5, #0052d4);
        color: white;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(0, 61, 165, 0.25);
    }
    .nav-submenu .sidebar-nav-link.active::after {
        background: white;
    }

    /* Parent menu item (non-clickable header) */
    .nav-parent {
        color: #1f2937;
        font-size: 0.8125rem;
        padding: 8px 24px;
        font-weight: 600;
        display: flex;
        align-items: center;
        pointer-events: none;
        opacity: 0.7;
        margin: 2px 16px 0 16px;
    }

    .nav-parent i {
        font-size: 1rem;
        width: 28px;
        min-width: 28px;
    }

    /* User section */
    .user-section {
        position: sticky;
        bottom: 0;
        width: 100%;
        background: white;
        border-top: 1px solid #e5e7eb;
        padding: 16px;
        margin-top: auto;
    }

    .logout-btn {
        color: #dc2626;
        font-size: 0.875rem;
        padding: 10px 20px;
        border-radius: 10px;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        font-weight: 600;
        border: 2px solid #fee2e2;
        background: linear-gradient(135deg, #fef2f2, #ffffff);
        gap: 8px;
    }

    .logout-btn:hover {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        color: white;
        border-color: #dc2626;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25);
    }

    .logout-btn i {
        font-size: 1.125rem;
        margin-right: 0;
    }

    /* Hide Scrollbar - Not needed since overflow is hidden */

    /* Responsive */
    @media (max-width: 768px) {
        body {
            margin-left: 0 !important;
        }

        #sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        #sidebar.show {
            transform: translateX(0);
        }
    }
</style>

<!-- Sidebar -->
<div id="sidebar">
    <!-- Brand Header -->
    <div class="brand-header">
        <div class="brand-text">
            <i class="bi bi-shield-check"></i>
            Admin Panel
        </div>
        <div class="brand-subtitle">Lost & Found System</div>
    </div>

    <!-- Navigation Container -->
    <div class="nav-container">
        <div class="nav-main">
            
            <!-- Main Section -->
            <div class="nav-section-header">Main</div>
            
            <!-- Dashboard -->
            <a class="sidebar-nav-link <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>" href="index.php">
                <i class="bi bi-speedometer2"></i>
                <span class="ms-2">Dashboard</span>
            </a>

            <!-- Items Section -->
            <div class="nav-section-header">Items Management</div>
            
            <div class="nav-parent">
                <i class="bi bi-box-seam"></i>
                <span class="ms-2">Items</span>
            </div>

            <div class="nav-submenu">
                <a class="sidebar-nav-link <?php echo $currentPage == 'pending_lost.php' ? 'active' : ''; ?>" href="pending_lost.php">
                    <i class="bi bi-search"></i>
                    <span class="ms-2">Pending Lost</span>
                </a>

                <a class="sidebar-nav-link <?php echo $currentPage == 'pending_found.php' ? 'active' : ''; ?>" href="pending_found.php">
                    <i class="bi bi-bag-check"></i>
                    <span class="ms-2">Pending Found</span>
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

            <!-- Reports Section -->
            <div class="nav-section-header">Reports</div>
            
            <div class="nav-parent">
                <i class="bi bi-graph-up"></i>
                <span class="ms-2">Analytics</span>
            </div>

            <div class="nav-submenu">
                <a class="sidebar-nav-link <?php echo $currentPage == 'statistics.php' ? 'active' : ''; ?>" href="statistics.php">
                    <i class="bi bi-bar-chart-line"></i>
                    <span class="ms-2">Statistics</span>
                </a>
            </div>

        </div>

        <!-- User Section -->
        <div class="user-section">
            <a class="logout-btn" href="../logout.php">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</div>