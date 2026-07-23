<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
?>
<nav id="sidebar" class="sidebar shadow">
    <div class="sidebar-header">
        <?php if ($role === 'ADMIN'): ?>
            <h3><i class="fas fa-boxes-stacked me-2"></i> DSMS ERP</h3>
        <?php else: ?>
            <h3><i class="fas fa-chalkboard-user me-2"></i> Faculty Portal</h3>
        <?php endif; ?>
    </div>
    
    <?php if ($role === 'FACULTY'): ?>
    <div class="text-center mt-4 mb-3 d-none d-md-block">
        <img src="faculty.png" alt="Faculty" class="rounded-circle bg-white p-1" style="width: 80px; height: 80px; object-fit: cover;">
    </div>
    <?php endif; ?>

    <ul class="list-unstyled components <?php echo ($role === 'FACULTY') ? 'mt-0' : ''; ?>">
        <?php if ($role === 'ADMIN'): ?>
            <li class="<?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>"><a href="admin_dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li class="<?php echo ($current_page == 'faculty_requests.php') ? 'active' : ''; ?>"><a href="faculty_requests.php"><i class="fas fa-code-pull-request"></i> Faculty Requests</a></li>
            <li class="<?php echo ($current_page == 'inventory.php') ? 'active' : ''; ?>"><a href="inventory.php"><i class="fas fa-warehouse"></i> Inventory</a></li>
            <li class="<?php echo ($current_page == 'issue_stationery.php') ? 'active' : ''; ?>"><a href="issue_stationery.php"><i class="fas fa-dolly"></i> Issue Stationery</a></li>
            <li><a href="#"><i class="fas fa-tags"></i> Categories</a></li>
            <li><a href="#"><i class="fas fa-chart-pie"></i> Reports</a></li>
            <li><a href="#" data-bs-toggle="modal" data-bs-target="#notificationCenterModal"><i class="fas fa-bell"></i> Notifications <span id="sidebarNotificationBadge" class="badge bg-danger rounded-pill float-end d-none">0</span></a></li>
            <li><a href="#"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
        <?php else: ?>
            <li class="<?php echo ($current_page == 'faculty_dashboard.php') ? 'active' : ''; ?>"><a href="faculty_dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li class="<?php echo ($current_page == 'new_request.php') ? 'active' : ''; ?>"><a href="new_request.php"><i class="fas fa-plus-circle"></i> New Request</a></li>
            <li class="<?php echo ($current_page == 'my_requests.php' || $current_page == 'request_history.php') ? 'active' : ''; ?>"><a href="my_requests.php"><i class="fas fa-clock-rotate-left"></i> Request History</a></li>
            <li><a href="#" data-bs-toggle="modal" data-bs-target="#notificationCenterModal"><i class="fas fa-bell"></i> Notifications <span id="sidebarNotificationBadge" class="badge bg-danger rounded-pill float-end d-none">0</span></a></li>
            <li><a href="#"><i class="fas fa-user"></i> Profile</a></li>
        <?php endif; ?>
        <li><a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</nav>
