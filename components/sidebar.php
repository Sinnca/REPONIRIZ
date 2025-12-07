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
    }

    /* Sidebar container */
    #sidebar {
        height: 100vh;
        width: 75px;
        background: rgba(31, 41, 55, 0.85);
        backdrop-filter: blur(12px);
        transition: all 0.35s ease;
        overflow-x: hidden;
        overflow-y: auto;
        position: fixed;
        z-index: 1000;
        box-shadow: 4px 0 25px rgba(0,0,0,0.15);
        top: 0;
        left: 0;
    }

    #sidebar.expanded {
        width: 250px;
    }

    /* Sidebar header */
    #sidebar .brand-text {
        font-size: 1.25rem;
        font-weight: 600;
        opacity: 0;
        transition: opacity .3s ease;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    #sidebar.expanded .brand-text {
        opacity: 1;
    }

    #toggleBtn {
        border: none;
        background: rgba(255, 255, 255, 0.15);
        color: white;
        padding: 6px 10px;
        border-radius: 8px;
        transition: background .2s ease;
        cursor: pointer;
    }

    #toggleBtn:hover {
        background: rgba(255, 255, 255, 0.3);
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

    /* Text hidden when collapsed */
    #sidebar:not(.expanded) .menu-text {
        display: none;
    }

    #sidebar:not(.expanded) .dropdown-toggle::after {
        display: none;
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

    /* Content area adjustment */
    body {
        margin-left: 75px;
        transition: margin-left 0.35s ease;
    }

    body.sidebar-expanded {
        margin-left: 250px;
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

    #sidebar:not(.expanded) .user-info {
        display: none;
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

    /* Smooth collapse animations */
    .collapse {
        transition: height 0.25s ease;
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
</style>

<!-- Sidebar -->
<div id="sidebar">
    <div class="d-flex align-items-center p-3 border-bottom border-secondary" style="position: sticky; top: 0; background: rgba(31, 41, 55, 0.95); z-index: 10;">
        <button id="toggleBtn">
            <i class="bi bi-list fs-5"></i>
        </button>
        <span class="brand-text ms-3 text-white">Admin Panel</span>
    </div>

    <div style="display: flex; flex-direction: column; min-height: calc(100vh - 60px);">
        <ul class="nav flex-column mt-3" style="flex: 1; padding-bottom: 20px;">
            
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="sidebar-nav-link <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="bi bi-speedometer2"></i>
                    <span class="menu-text ms-2">Dashboard</span>
                </a>
            </li>

            <!-- ITEMS -->
            <li class="nav-item mt-2">
                <a class="sidebar-nav-link dropdown-toggle" data-bs-toggle="collapse" href="#itemsMenu" role="button">
                    <i class="bi bi-box-seam"></i>
                    <span class="menu-text ms-2">Items</span>
                </a>

                <div class="collapse" id="itemsMenu">
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
                <a class="sidebar-nav-link dropdown-toggle" data-bs-toggle="collapse" href="#reportsMenu" role="button">
                    <i class="bi bi-graph-up"></i>
                    <span class="menu-text ms-2">Reports</span>
                </a>

                <div class="collapse" id="reportsMenu">
                    <a class="sidebar-nav-link <?php echo $currentPage == 'statistics.php' ? 'active' : ''; ?>" href="statistics.php">
                        <i class="bi bi-bar-chart-line"></i>
                        <span class="ms-2">Statistics</span>
                    </a>
                </div>
            </li>

        </ul>

        <!-- User Section -->
        <div class="user-section">
            <div class="user-info mb-2">
                <i class="bi bi-person-circle"></i>
                <span class="ms-2"><?php echo htmlspecialchars($userName); ?></span>
            </div>
            <a class="logout-btn" href="../logout.php">
                <i class="bi bi-box-arrow-right"></i>
                <span class="menu-text ms-2">Logout</span>
            </a>
        </div>
    </div>
</div>

<!-- Sidebar Toggle Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById("sidebar");
        const toggleBtn = document.getElementById("toggleBtn");

        // Load saved state
        const savedState = localStorage.getItem('sidebarExpanded');
        if (savedState === 'true') {
            sidebar.classList.add('expanded');
            document.body.classList.add('sidebar-expanded');
        }

        toggleBtn.addEventListener("click", () => {
            sidebar.classList.toggle("expanded");
            document.body.classList.toggle("sidebar-expanded");
            
            // Save state
            localStorage.setItem('sidebarExpanded', sidebar.classList.contains('expanded'));

            // Close dropdowns when collapsed
            if (!sidebar.classList.contains("expanded")) {
                document.querySelectorAll('.collapse.show').forEach(c => {
                    const bsCollapse = bootstrap.Collapse.getInstance(c);
                    if (bsCollapse) {
                        bsCollapse.hide();
                    }
                });
            }
        });
    });
</script>