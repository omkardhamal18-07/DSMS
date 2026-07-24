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

$page_title = 'Faculty Dashboard - DSMS';
include 'includes/header.php';
?>

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
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newRequestModal"><i class="fas fa-plus me-1"></i> New Request</button>
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
                                    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#newRequestModal"><i class="fas fa-plus-circle me-2"></i> New Stationery Request</button>
                                    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#notificationCenterModal"><i class="fas fa-bell me-2"></i> View Notification Center</button>
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

<?php include 'includes/footer.php'; ?>
