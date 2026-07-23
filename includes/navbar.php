<?php
$role = $_SESSION['role'] ?? '';
$user_name = htmlspecialchars($_SESSION['name'] ?? ($role === 'ADMIN' ? 'Admin User' : 'Faculty User'));
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 rounded-3 top-navbar">
    <div class="container-fluid">
        <button type="button" id="sidebarCollapse" class="btn btn-primary shadow-sm">
            <i class="fas fa-bars"></i>
        </button>

        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
            <li class="nav-item dropdown me-2">
                <a class="nav-link position-relative text-gray-500 dropdown-toggle text-decoration-none" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell fs-5"></i>
                    <span id="navNotificationBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" style="font-size: 0.65rem;">
                        0
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-notifications shadow border-0 animated--grow-in" aria-labelledby="notificationDropdown">
                    <div class="dropdown-header bg-primary text-white d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="fw-bold"><i class="fas fa-bell me-1"></i> Notifications</span>
                        <div>
                            <button class="btn btn-sm btn-link text-white p-0 text-decoration-none me-2" id="markAllReadDropdownBtn" style="font-size: 0.75rem;">Mark All Read</button>
                        </div>
                    </div>
                    <div id="notificationDropdownList" class="notification-list-container">
                        <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
                    </div>
                    <div class="dropdown-footer bg-light text-center py-2 border-top">
                        <button class="btn btn-sm text-primary fw-bold p-0 border-0 bg-transparent" data-bs-toggle="modal" data-bs-target="#notificationCenterModal">View All Notifications</button>
                    </div>
                </div>
            </li>
            <div class="topbar-divider d-none d-sm-block border-start mx-3" style="height: 2rem;"></div>
            <li class="nav-item">
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center text-gray-800" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="me-2 d-none d-lg-inline text-gray-600 small"><?php echo $user_name; ?></span>
                        <img src="<?php echo $role === 'ADMIN' ? 'HOD.png' : 'faculty.png'; ?>" alt="User Profile" class="rounded-circle shadow-sm" width="32" height="32" style="object-fit: cover;">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 animated--grow-in" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user-circle fa-sm fa-fw me-2 text-gray-400"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog fa-sm fa-fw me-2 text-gray-400"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt fa-sm fa-fw me-2 text-gray-400"></i> Logout</a></li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>
</nav>
