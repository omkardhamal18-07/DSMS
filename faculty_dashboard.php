<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FACULTY') {
    header("Location: login.php");
    exit();
}
include("database/db.php");

$fac_id = intval($_SESSION['user_id']);

// Fetch available stationery items for request dropdown
$stationery_items = $conn->query("SELECT stationery_id, item_name, category, unit, quantity_available FROM stationery ORDER BY item_name ASC");

// Fetch Faculty Stats
$total_req_fac = $conn->query("SELECT COUNT(*) as c FROM stationery_requests WHERE faculty_id = $fac_id")->fetch_assoc()['c'];
$pending_req_fac = $conn->query("SELECT COUNT(*) as c FROM stationery_requests WHERE faculty_id = $fac_id AND status = 'PENDING'")->fetch_assoc()['c'];
$approved_req_fac = $conn->query("SELECT COUNT(*) as c FROM stationery_requests WHERE faculty_id = $fac_id AND status = 'APPROVED'")->fetch_assoc()['c'];
$rejected_req_fac = $conn->query("SELECT COUNT(*) as c FROM stationery_requests WHERE faculty_id = $fac_id AND status = 'REJECTED'")->fetch_assoc()['c'];

// Recent requests for table
$recent_requests_fac = $conn->query("SELECT r.*, s.item_name, s.unit FROM stationery_requests r JOIN stationery s ON r.stationery_id = s.stationery_id WHERE r.faculty_id = $fac_id ORDER BY r.request_date DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - DSMS</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="admin_style.css">
</head>
<body data-role="FACULTY">
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="sidebar shadow">
            <div class="sidebar-header">
                <h3><i class="fas fa-chalkboard-user me-2"></i> Faculty Portal</h3>
            </div>
            <div class="text-center mt-4 mb-3 d-none d-md-block">
                <img src="faculty.png" alt="Faculty" class="rounded-circle bg-white p-1" style="width: 80px; height: 80px; object-fit: cover;">
            </div>
            <ul class="list-unstyled components mt-0">
                <li class="active"><a href="faculty_dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="#" data-bs-toggle="modal" data-bs-target="#newRequestModal"><i class="fas fa-plus-circle"></i> New Request</a></li>
                <li><a href="#" data-bs-toggle="modal" data-bs-target="#notificationCenterModal"><i class="fas fa-bell"></i> Notifications <span id="sidebarNotificationBadge" class="badge bg-danger rounded-pill float-end d-none">0</span></a></li>
                <li><a href="#"><i class="fas fa-user"></i> Profile</a></li>
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
                        <li class="nav-item dropdown me-3">
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
                                        <button class="btn btn-sm btn-link text-white p-0 text-decoration-none" id="markAllReadDropdownBtn" style="font-size: 0.75rem;">Mark All Read</button>
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
                                    <span class="me-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Faculty Member'); ?></span>
                                    <img src="faculty.png" alt="User Profile" class="rounded-circle shadow-sm bg-light border p-1" width="32" height="32" style="object-fit: cover;">
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 animated--grow-in" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-user fa-sm fa-fw me-2 text-gray-400"></i> Profile</a></li>
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
                    <h1 class="h3 fw-bold text-gray-800 mb-0">Faculty Dashboard</h1>
                    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#newRequestModal"><i class="fas fa-plus-circle me-1"></i> New Request</button>
                </div>

                <!-- Metrics Cards -->
                <div class="row g-4 mb-4">
                    <!-- Total Requests -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card h-100 border-0 shadow-sm border-start border-primary border-4 rounded-3 hover-lift">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col me-2">
                                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Requests</div>
                                        <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $total_req_fac; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Pending -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card h-100 border-0 shadow-sm border-start border-warning border-4 rounded-3 hover-lift">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col me-2">
                                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">Pending</div>
                                        <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $pending_req_fac; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Approved -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card h-100 border-0 shadow-sm border-start border-success border-4 rounded-3 hover-lift">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col me-2">
                                        <div class="text-xs fw-bold text-success text-uppercase mb-1">Approved</div>
                                        <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $approved_req_fac; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Rejected -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card h-100 border-0 shadow-sm border-start border-danger border-4 rounded-3 hover-lift">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col me-2">
                                        <div class="text-xs fw-bold text-danger text-uppercase mb-1">Rejected</div>
                                        <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $rejected_req_fac; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Recent Requests Table -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm rounded-3 h-100">
                            <div class="card-header bg-white border-bottom py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 fw-bold text-primary">Recent Requests</h6>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newRequestModal"><i class="fas fa-plus me-1"></i> New Request</button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0 table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Req ID</th>
                                                <th>Item</th>
                                                <th>Quantity</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            if ($recent_requests_fac && $recent_requests_fac->num_rows > 0):
                                                while ($req = $recent_requests_fac->fetch_assoc()):
                                                    $s_badge = ($req['status'] === 'APPROVED') ? '<span class="badge bg-success px-2 py-1 rounded-pill">Approved</span>' :
                                                              (($req['status'] === 'REJECTED') ? '<span class="badge bg-danger px-2 py-1 rounded-pill">Rejected</span>' :
                                                              '<span class="badge bg-warning text-dark px-2 py-1 rounded-pill">Pending</span>');
                                            ?>
                                            <tr>
                                                <td class="fw-bold">#REQ-<?php echo str_pad($req['request_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                                <td><?php echo htmlspecialchars($req['item_name']); ?></td>
                                                <td><?php echo $req['requested_quantity'] . ' ' . htmlspecialchars($req['unit']); ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($req['request_date'])); ?></td>
                                                <td><?php echo $s_badge; ?></td>
                                            </tr>
                                            <?php 
                                                endwhile;
                                            else:
                                            ?>
                                            <tr><td colspan="5" class="text-center text-muted py-4">No recent requests found. Submit a request above!</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Side Panel: Quick Actions, Profile, Notifications -->
                    <div class="col-lg-4">
                        
                        <!-- Quick Actions -->
                        <div class="card border-0 shadow-sm rounded-3 mb-4">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="m-0 fw-bold text-primary">Quick Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#newRequestModal"><i class="fas fa-plus-circle me-2"></i> New Stationery Request</button>
                                    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#notificationCenterModal"><i class="fas fa-bell me-2"></i> View Notification Center</button>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Summary -->
                        <div class="card border-0 shadow-sm rounded-3 mb-4">
                            <div class="card-body text-center pt-4 pb-4">
                                <img src="faculty.png" alt="Profile" class="rounded-circle mb-3 border p-1" style="width: 80px; height: 80px; object-fit: cover;">
                                <h5 class="fw-bold text-gray-800 mb-1"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Faculty Member'); ?></h5>
                                <p class="text-muted mb-1 small">Faculty Member</p>
                                <p class="text-muted mb-3 small fw-bold text-primary"><?php echo htmlspecialchars($_SESSION['department'] ?? 'Computer Science Dept.'); ?></p>
                            </div>
                        </div>

                        <!-- Notifications Card -->
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white border-bottom py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-bell me-2"></i> Notification Center</h6>
                            </div>
                            <div class="card-body p-3 text-center">
                                <p class="text-muted small mb-3">View all request status updates, stock availability notifications, and admin remarks.</p>
                                <button class="btn btn-outline-primary btn-sm rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#notificationCenterModal">
                                    Open Notification Center
                                </button>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Request Modal -->
    <div class="modal fade" id="newRequestModal" tabindex="-1" aria-labelledby="newRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-3">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="newRequestModalLabel"><i class="fas fa-plus-circle me-2"></i> New Stationery Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="newRequestForm">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="stationery_id" class="form-label fw-bold text-gray-800">Select Stationery Item <span class="text-danger">*</span></label>
                            <select class="form-select" id="stationery_id" name="stationery_id" required>
                                <option value="">-- Select Item --</option>
                                <?php 
                                if ($stationery_items && $stationery_items->num_rows > 0):
                                    while ($st = $stationery_items->fetch_assoc()):
                                ?>
                                <option value="<?php echo $st['stationery_id']; ?>">
                                    <?php echo htmlspecialchars($st['item_name']) . " (" . htmlspecialchars($st['category']) . ") - Stock: " . $st['quantity_available'] . " " . htmlspecialchars($st['unit']); ?>
                                </option>
                                <?php 
                                    endwhile;
                                endif; 
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="requested_quantity" class="form-label fw-bold text-gray-800">Quantity Required <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="requested_quantity" name="requested_quantity" min="1" required placeholder="Enter quantity">
                        </div>
                        <div class="mb-3">
                            <label for="remarks" class="form-label fw-bold text-gray-800">Purpose / Short Description</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Specify course, lab, or purpose (optional)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Notification Center Modal -->
    <div class="modal fade" id="notificationCenterModal" tabindex="-1" aria-labelledby="notificationCenterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow rounded-3">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold" id="notificationCenterModalLabel"><i class="fas fa-bell me-2"></i> Faculty Notification Center</h5>
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
                                    <input type="text" id="notificationSearchInput" class="form-control border-start-0 ps-0" placeholder="Search notifications...">
                                </div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="btn-group flex-wrap" role="group" aria-label="Notification Filters">
                                    <button type="button" class="btn btn-sm btn-primary active notification-filter-pill" data-filter="ALL">All</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary notification-filter-pill" data-filter="REQUEST_STATUS">Request Status</button>
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

    <!-- Request Detail Modal -->
    <div class="modal fade" id="requestDetailModal" tabindex="-1" aria-labelledby="requestDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow rounded-3">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="requestDetailModalLabel"><i class="fas fa-file-alt me-2"></i> Request Details</h5>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Sidebar Toggle Script -->
    <script>
        document.getElementById('sidebarCollapse').addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('active');
        });
    </script>
    <script src="notification_center.js?v=1"></script>
    <script src="under_development.js?v=2"></script>
</body>
</html>
