<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FACULTY') {
    header("Location: login.php");
    exit();
}

include("database/db.php");

$user_id = $_SESSION['user_id'];

// Fetch logged-in faculty details
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$faculty = $stmt->get_result()->fetch_assoc();

// Dynamic KPI Counts
$total_requests = 0;
$pending_requests = 0;
$approved_requests = 0;
$rejected_requests = 0;

$count_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'APPROVED' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'REJECTED' THEN 1 ELSE 0 END) as rejected
FROM stationery_requests WHERE faculty_id = ?";
if ($c_stmt = $conn->prepare($count_query)) {
    $c_stmt->bind_param("i", $user_id);
    $c_stmt->execute();
    $counts = $c_stmt->get_result()->fetch_assoc();
    $total_requests = $counts['total'] ?? 0;
    $pending_requests = $counts['pending'] ?? 0;
    $approved_requests = $counts['approved'] ?? 0;
    $rejected_requests = $counts['rejected'] ?? 0;
}

// Fetch 5 most recent requests
$recent_requests = [];
$recent_query = "SELECT r.*, s.item_name, s.unit FROM stationery_requests r JOIN stationery s ON r.stationery_id = s.stationery_id WHERE r.faculty_id = ? ORDER BY r.request_date DESC LIMIT 5";
if ($r_stmt = $conn->prepare($recent_query)) {
    $r_stmt->bind_param("i", $user_id);
    $r_stmt->execute();
    $result = $r_stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_requests[] = $row;
    }
}

// Fetch notifications (recent reviews in last 7 days)
$notifications = [];
$notif_query = "SELECT r.*, s.item_name, rev.name as reviewer_name 
               FROM stationery_requests r 
               JOIN stationery s ON r.stationery_id = s.stationery_id 
               LEFT JOIN users rev ON r.reviewed_by = rev.user_id 
               WHERE r.faculty_id = ? AND r.status IN ('APPROVED', 'REJECTED') AND r.review_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
               ORDER BY r.review_date DESC LIMIT 5";
if ($n_stmt = $conn->prepare($notif_query)) {
    $n_stmt->bind_param("i", $user_id);
    $n_stmt->execute();
    $result = $n_stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}
$notif_count = count($notifications);
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
<body>
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
                <li><a href="new_request.php"><i class="fas fa-plus-circle"></i> New Request</a></li>
                <li><a href="my_requests.php"><i class="fas fa-clock-rotate-left"></i> Request History</a></li>
                <li><a href="my_requests.php"><i class="fas fa-bell"></i> Notifications <?php if($notif_count > 0): ?><span class="badge bg-danger rounded-pill float-end"><?php echo $notif_count; ?></span><?php endif; ?></a></li>
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
                        <li class="nav-item me-3">
                            <a class="nav-link position-relative text-gray-500" href="my_requests.php">
                                <i class="fas fa-bell fs-5"></i>
                                <?php if($notif_count > 0): ?>
                                <span class="position-absolute top-25 start-75 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <div class="topbar-divider d-none d-sm-block border-start mx-3" style="height: 2rem;"></div>
                        <li class="nav-item">
                            <div class="dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center text-gray-800" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="me-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($faculty['name']); ?></span>
                                    <img src="faculty.png" alt="User Profile" class="rounded-circle shadow-sm bg-light border p-1" width="32" height="32" style="object-fit: cover;">
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userDropdown">
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
                </div>

                <!-- Metrics Cards -->
                <div class="row g-4 mb-4">
                    <!-- Total Requests -->
                    <div class="col-xl-3 col-md-6">
                        <a href="my_requests.php?status=ALL" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm border-start border-primary border-4 rounded-3 hover-lift">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col me-2">
                                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Requests</div>
                                            <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $total_requests; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <!-- Pending -->
                    <div class="col-xl-3 col-md-6">
                        <a href="my_requests.php?status=PENDING" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm border-start border-warning border-4 rounded-3 hover-lift">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col me-2">
                                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">Pending</div>
                                            <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $pending_requests; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <!-- Approved -->
                    <div class="col-xl-3 col-md-6">
                        <a href="my_requests.php?status=APPROVED" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm border-start border-success border-4 rounded-3 hover-lift">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col me-2">
                                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Approved</div>
                                            <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $approved_requests; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <!-- Rejected -->
                    <div class="col-xl-3 col-md-6">
                        <a href="my_requests.php?status=REJECTED" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm border-start border-danger border-4 rounded-3 hover-lift">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col me-2">
                                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">Rejected</div>
                                            <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $rejected_requests; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Recent Requests Table -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm rounded-3 h-100">
                            <div class="card-header bg-white border-bottom py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 fw-bold text-primary">Recent Requests</h6>
                                <a href="my_requests.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0 table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Req ID</th>
                                                <th>Item</th>
                                                <th>Quantity</th>
                                                <th>Date Submitted</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recent_requests)): ?>
                                                <tr><td colspan="5" class="text-center py-4 text-muted">No recent requests found.</td></tr>
                                            <?php else: 
                                                foreach ($recent_requests as $req):
                                            ?>
                                            <tr>
                                                <td class="fw-bold">#REQ-<?php echo $req['request_id']; ?></td>
                                                <td><?php echo htmlspecialchars($req['item_name']); ?></td>
                                                <td><?php echo $req['requested_quantity']; ?> <?php echo htmlspecialchars($req['unit']); ?></td>
                                                <td class="small text-muted"><?php echo date('Y-m-d', strtotime($req['request_date'])); ?></td>
                                                <td>
                                                    <?php 
                                                    if ($req['status'] === 'PENDING') echo '<span class="badge bg-warning text-dark px-2 py-1 rounded-pill">Pending</span>';
                                                    elseif ($req['status'] === 'APPROVED') echo '<span class="badge bg-success px-2 py-1 rounded-pill">Approved</span>';
                                                    elseif ($req['status'] === 'REJECTED') echo '<span class="badge bg-danger px-2 py-1 rounded-pill">Rejected</span>';
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; endif; ?>
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
                                    <a href="new_request.php" class="btn btn-primary text-start"><i class="fas fa-plus-circle me-2"></i> New Stationery Request</a>
                                    <a href="my_requests.php" class="btn btn-outline-secondary text-start"><i class="fas fa-search me-2"></i> Track Request Status</a>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Summary -->
                        <div class="card border-0 shadow-sm rounded-3 mb-4">
                            <div class="card-body text-center pt-4 pb-4">
                                <img src="faculty.png" alt="Profile" class="rounded-circle mb-3 border p-1" style="width: 80px; height: 80px; object-fit: cover;">
                                <h5 class="fw-bold text-gray-800 mb-1"><?php echo htmlspecialchars($faculty['name']); ?></h5>
                                <p class="text-muted mb-1 small">Faculty Member</p>
                                <p class="text-muted mb-3 small fw-bold text-primary"><?php echo htmlspecialchars($faculty['department'] ?? 'Computer Science'); ?> Dept.</p>
                                <a href="new_request.php" class="btn btn-sm btn-outline-primary px-4 rounded-pill">New Request</a>
                            </div>
                        </div>

                        <!-- Notifications -->
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white border-bottom py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 fw-bold text-primary">Recent Notifications</h6>
                                <?php if($notif_count > 0): ?>
                                <span class="badge bg-danger rounded-pill"><?php echo $notif_count; ?> New</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush rounded-bottom">
                                    <?php if(empty($notifications)): ?>
                                        <div class="p-3 text-center text-muted small">No new updates in the last 7 days.</div>
                                    <?php else: 
                                        foreach($notifications as $notif):
                                            $bg_class = ($notif['status'] === 'APPROVED') ? 'bg-success' : 'bg-danger';
                                            $icon = ($notif['status'] === 'APPROVED') ? 'fa-check' : 'fa-times';
                                            $status_text = ($notif['status'] === 'APPROVED') ? 'Approved' : 'Rejected';
                                            $time_ago = date('M d, Y', strtotime($notif['review_date']));
                                    ?>
                                    <div class="list-group-item d-flex align-items-start py-3 px-4 border-bottom-0">
                                        <div class="activity-icon <?php echo $bg_class; ?> text-white rounded-circle me-3 flex-shrink-0">
                                            <i class="fas <?php echo $icon; ?>"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 text-gray-800 fw-bold fs-6">Request #REQ-<?php echo $notif['request_id']; ?> <?php echo $status_text; ?></h6>
                                            <p class="mb-1 text-muted small">Your request for <?php echo htmlspecialchars($notif['item_name']); ?> has been <?php echo strtolower($status_text); ?> by HOD.</p>
                                            <small class="text-primary fw-bold" style="font-size: 0.75rem;"><?php echo $time_ago; ?></small>
                                        </div>
                                    </div>
                                    <?php endforeach; endif; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top text-center py-2">
                                <a href="my_requests.php" class="text-decoration-none small fw-bold text-primary">View All History</a>
                            </div>
                        </div>
                        
                    </div>
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
</body>
</html>
