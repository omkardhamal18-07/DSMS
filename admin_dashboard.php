<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: login.php");
    exit();
}
include("database/db.php");

// Fetch Inventory Stats
$total_items = $conn->query("SELECT COUNT(*) as c FROM stationery")->fetch_assoc()['c'];
$available_stock = $conn->query("SELECT SUM(quantity_available) as s FROM stationery")->fetch_assoc()['s'];
if (empty($available_stock)) $available_stock = 0;
$low_stock_alerts = $conn->query("SELECT COUNT(*) as c FROM stationery WHERE quantity_available <= minimum_stock AND quantity_available > 0")->fetch_assoc()['c'];
$out_of_stock_alerts = $conn->query("SELECT COUNT(*) as c FROM stationery WHERE quantity_available = 0")->fetch_assoc()['c'];
$total_alerts = $low_stock_alerts + $out_of_stock_alerts;

// Fetch Requests Stats
$pending_requests = $conn->query("SELECT COUNT(*) as c FROM stationery_requests WHERE status = 'PENDING'")->fetch_assoc()['c'];
$approved_requests = $conn->query("SELECT COUNT(*) as c FROM stationery_requests WHERE status = 'APPROVED'")->fetch_assoc()['c'];
$rejected_requests = $conn->query("SELECT COUNT(*) as c FROM stationery_requests WHERE status = 'REJECTED'")->fetch_assoc()['c'];
$today_requests = $conn->query("SELECT COUNT(*) as c FROM stationery_requests WHERE DATE(request_date) = CURDATE()")->fetch_assoc()['c'];
$monthly_requests = $conn->query("SELECT COUNT(*) as c FROM stationery_requests WHERE MONTH(request_date) = MONTH(CURDATE()) AND YEAR(request_date) = YEAR(CURDATE())")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERP Admin Dashboard - DSMS</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="admin_style.css">
</head>
<body data-role="ADMIN">
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="sidebar shadow">
            <div class="sidebar-header">
                <h3><i class="fas fa-boxes-stacked me-2"></i> DSMS ERP</h3>
            </div>
            <ul class="list-unstyled components">
                <li class="active"><a href="admin_dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="faculty_requests.php"><i class="fas fa-code-pull-request"></i> Faculty Requests</a></li>
                <li><a href="inventory.php"><i class="fas fa-warehouse"></i> Inventory</a></li>
                <li><a href="#"><i class="fas fa-dolly"></i> Issue Stationery</a></li>
                <li><a href="#"><i class="fas fa-tags"></i> Categories</a></li>
                <li><a href="#"><i class="fas fa-chart-pie"></i> Reports</a></li>
                <li><a href="#" data-bs-toggle="modal" data-bs-target="#notificationCenterModal"><i class="fas fa-bell"></i> Notifications <span id="sidebarNotificationBadge" class="badge bg-danger rounded-pill float-end d-none">0</span></a></li>
                <li><a href="#"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <!-- Top Navbar -->
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
                                    <button class="btn btn-sm text-primary fw-bold p-0 border-0 bg-transparent" data-bs-toggle="modal" data-bs-target="#notificationCenterModal">View All Notifications Center</button>
                                </div>
                            </div>
                        </li>
                        <div class="topbar-divider d-none d-sm-block border-start mx-3" style="height: 2rem;"></div>
                        <li class="nav-item">
                            <div class="dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center text-gray-800" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="me-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin User'); ?></span>
                                    <img src="HOD.png" alt="User Profile" class="rounded-circle shadow-sm" width="32" height="32" style="object-fit: cover;">
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

            <!-- Dashboard Content -->
            <div class="container-fluid px-0">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 fw-bold text-gray-800 mb-0">Dashboard</h1>
                    <button class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-download fa-sm text-white-50 me-1"></i> Generate Report</button>
                </div>

                <!-- Metrics Cards -->
                <div class="row g-4 mb-4">
                    <!-- Card 1 -->
                    <div class="col-xl-3 col-md-6">
                        <a href="inventory.php" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm border-start border-primary border-4 rounded-3 hover-lift">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col me-2">
                                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Items</div>
                                            <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($total_items); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-boxes-stacked fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <!-- Card 2 -->
                    <div class="col-xl-3 col-md-6">
                        <a href="inventory.php?filter=available" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm border-start border-success border-4 rounded-3 hover-lift">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col me-2">
                                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Available Stock</div>
                                            <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($available_stock); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <!-- Card 3 -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card h-100 border-0 shadow-sm border-start border-warning border-4 rounded-3 hover-lift">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col me-2">
                                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">Pending Requests</div>
                                        <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($pending_requests); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Card 4 -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card h-100 border-0 shadow-sm border-start border-info border-4 rounded-3 hover-lift">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col me-2">
                                        <div class="text-xs fw-bold text-info text-uppercase mb-1">Today's Requests</div>
                                        <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($today_requests); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Card 5 -->
                    <div class="col-xl-3 col-md-6">
                        <a href="inventory.php?filter=low_stock" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm border-start border-danger border-4 rounded-3 hover-lift">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col me-2">
                                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">Low Stock Alerts</div>
                                            <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($total_alerts); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <!-- Card 6 -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card h-100 border-0 shadow-sm border-start border-secondary border-4 rounded-3 hover-lift">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col me-2">
                                        <div class="text-xs fw-bold text-secondary text-uppercase mb-1">Monthly Requests</div>
                                        <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($monthly_requests); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chart-area fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                     <!-- Card 7 -->
                     <div class="col-xl-3 col-md-6">
                        <div class="card h-100 border-0 shadow-sm border-start border-primary border-4 rounded-3 hover-lift">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col me-2">
                                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Approved Requests</div>
                                        <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($approved_requests); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-thumbs-up fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                     <!-- Card 8 -->
                     <div class="col-xl-3 col-md-6">
                        <div class="card h-100 border-0 shadow-sm border-start border-dark border-4 rounded-3 hover-lift">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col me-2">
                                        <div class="text-xs fw-bold text-dark text-uppercase mb-1">Rejected Requests</div>
                                        <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($rejected_requests); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-thumbs-down fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-8">
                         <div class="card shadow-sm border-0 rounded-3 h-100">
                            <div class="card-header bg-white border-0 py-3">
                                <h6 class="m-0 fw-bold text-primary">Weekly Requests Trend</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="weeklyRequestsChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card shadow-sm border-0 rounded-3 h-100">
                            <div class="card-header bg-white border-0 py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 fw-bold text-primary">Inventory Composition</h6>
                            </div>
                            <div class="card-body pb-2">
                                <canvas id="inventoryPieChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tables Row 1 -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-6">
                        <div class="card shadow-sm border-0 rounded-3 h-100">
                            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">Low Stock Items</h6>
                                <a href="inventory.php?filter=low_stock" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light text-muted">
                                            <tr>
                                                <th>Item Name</th>
                                                <th>Category</th>
                                                <th>Available Stock</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $low_stock_query = "SELECT item_name, category, quantity_available, minimum_stock FROM stationery WHERE quantity_available <= minimum_stock ORDER BY quantity_available ASC LIMIT 5";
                                            $low_stock_res = $conn->query($low_stock_query);
                                            if($low_stock_res->num_rows > 0):
                                                while($ls_item = $low_stock_res->fetch_assoc()):
                                                    $status_badge = ($ls_item['quantity_available'] == 0) ? '<span class="badge bg-danger rounded-pill px-3">Out of Stock</span>' : '<span class="badge bg-warning text-dark rounded-pill px-3">Low Stock</span>';
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($ls_item['item_name']); ?></td>
                                                <td><?php echo htmlspecialchars($ls_item['category']); ?></td>
                                                <td><?php echo $ls_item['quantity_available']; ?></td>
                                                <td><?php echo $status_badge; ?></td>
                                            </tr>
                                            <?php 
                                                endwhile;
                                            else:
                                            ?>
                                            <tr><td colspan="4" class="text-center text-muted">No low stock items.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card shadow-sm border-0 rounded-3 h-100">
                            <div class="card-header bg-white border-0 py-3">
                                <h6 class="m-0 fw-bold text-primary">Recent Activities</h6>
                            </div>
                            <div class="card-body">
                                <div class="activity-timeline">
                                    <div class="activity-item d-flex mb-4">
                                        <div class="activity-icon bg-primary text-white rounded-circle p-2 me-3">
                                            <i class="fas fa-dolly-box"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 fw-bold text-gray-800">Stock Replenished</p>
                                            <small class="text-muted">A4 Paper (+50) - 2 hours ago</small>
                                        </div>
                                    </div>
                                    <div class="activity-item d-flex mb-4">
                                        <div class="activity-icon bg-success text-white rounded-circle p-2 me-3">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 fw-bold text-gray-800">Request Approved</p>
                                            <small class="text-muted">Req #1041 by Admin - 5 hours ago</small>
                                        </div>
                                    </div>
                                    <div class="activity-item d-flex">
                                        <div class="activity-icon bg-warning text-dark rounded-circle p-2 me-3">
                                            <i class="fas fa-user-plus"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 fw-bold text-gray-800">New User Added</p>
                                            <small class="text-muted">Faculty Jane Doe added - 1 day ago</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tables Row 2 -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-12">
                         <div class="card shadow-sm border-0 rounded-3 h-100">
                            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">Recent Faculty Requests</h6>
                                <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light text-muted">
                                            <tr>
                                                <th>Req ID</th>
                                                <th>Faculty Name</th>
                                                <th>Item</th>
                                                <th>Qty</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $recent_req_query = "SELECT r.request_id, r.requested_quantity, r.request_date, r.status, u.name as faculty_name, s.item_name 
                                                                 FROM stationery_requests r 
                                                                 JOIN users u ON r.faculty_id = u.user_id 
                                                                 JOIN stationery s ON r.stationery_id = s.stationery_id 
                                                                 ORDER BY r.request_date DESC LIMIT 5";
                                            $recent_req_res = $conn->query($recent_req_query);
                                            if($recent_req_res->num_rows > 0):
                                                while($req = $recent_req_res->fetch_assoc()):
                                                    $status_badge = '';
                                                    if ($req['status'] === 'PENDING') $status_badge = '<span class="badge bg-warning text-dark px-2 py-1">Pending</span>';
                                                    elseif ($req['status'] === 'APPROVED') $status_badge = '<span class="badge bg-success px-2 py-1">Approved</span>';
                                                    elseif ($req['status'] === 'REJECTED') $status_badge = '<span class="badge bg-danger px-2 py-1">Rejected</span>';
                                            ?>
                                            <tr>
                                                <td class="fw-bold">#REQ-<?php echo $req['request_id']; ?></td>
                                                <td><?php echo htmlspecialchars($req['faculty_name']); ?></td>
                                                <td><?php echo htmlspecialchars($req['item_name']); ?></td>
                                                <td><?php echo $req['requested_quantity']; ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($req['request_date'])); ?></td>
                                                <td><?php echo $status_badge; ?></td>
                                                <td>
                                                    <a href="faculty_requests.php?status=ALL" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></a>
                                                </td>
                                            </tr>
                                            <?php 
                                                endwhile;
                                            else:
                                            ?>
                                            <tr><td colspan="7" class="text-center text-muted">No recent requests found.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Notification Center Modal -->
    <div class="modal fade" id="notificationCenterModal" tabindex="-1" aria-labelledby="notificationCenterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow rounded-3">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold" id="notificationCenterModalLabel"><i class="fas fa-bell me-2"></i> Notification Center</h5>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-light rounded-pill px-3" id="markAllReadBtn"><i class="fas fa-check-double me-1"></i> Mark All as Read</button>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-0">
                    <!-- Filters & Search Bar -->
                    <div class="p-3 bg-light border-bottom">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                    <input type="text" id="notificationSearchInput" class="form-control border-start-0 ps-0" placeholder="Search notifications by keyword...">
                                </div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="btn-group flex-wrap" role="group" aria-label="Notification Filters">
                                    <button type="button" class="btn btn-sm btn-primary active notification-filter-pill" data-filter="ALL">All</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary notification-filter-pill" data-filter="FACULTY_REQUEST">Requests</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary notification-filter-pill" data-filter="LOW_STOCK">Low Stock</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary notification-filter-pill" data-filter="STOCK_UPDATED">Stock Updated</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Notification Feed List -->
                    <div id="notificationModalList" class="p-2" style="max-height: 480px; overflow-y: auto;">
                        <div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>
                    </div>
                </div>
                <div class="modal-footer bg-light justify-content-between py-2">
                    <small class="text-muted"><i class="fas fa-sync-alt me-1"></i> Auto-refreshes every 30 seconds</small>
                    <div id="notificationPagination"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Detail Modal (Triggered when clicking a Faculty Request notification) -->
    <div class="modal fade" id="requestDetailModal" tabindex="-1" aria-labelledby="requestDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow rounded-3">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="requestDetailModalLabel"><i class="fas fa-file-alt me-2"></i> Faculty Request Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="requestDetailModalBody">
                    <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom JS -->
    <script src="admin_script.js"></script>
    <script src="notification_center.js?v=1"></script>
    <script src="under_development.js?v=2"></script>
</body>
</html>
