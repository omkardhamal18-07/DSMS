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
$total_requests = $conn->query("SELECT COUNT(*) as c FROM stationery_requests")->fetch_assoc()['c'];
$pending_requests = $conn->query("SELECT COUNT(*) as c FROM stationery_requests WHERE status = 'PENDING'")->fetch_assoc()['c'];
$approved_requests = $conn->query("SELECT COUNT(*) as c FROM stationery_requests WHERE status = 'APPROVED'")->fetch_assoc()['c'];
$completed_requests = $conn->query("SELECT COUNT(*) as c FROM stationery_requests WHERE status = 'COMPLETED'")->fetch_assoc()['c'];
$today_issues = $conn->query("SELECT SUM(issued_quantity) as c FROM issue_items ii JOIN issue_records ir ON ii.issue_id = ir.issue_id WHERE DATE(ir.issue_date) = CURDATE()")->fetch_assoc()['c'] ?? 0;
$monthly_issues = $conn->query("SELECT SUM(issued_quantity) as c FROM issue_items ii JOIN issue_records ir ON ii.issue_id = ir.issue_id WHERE MONTH(ir.issue_date) = MONTH(CURDATE()) AND YEAR(ir.issue_date) = YEAR(CURDATE())")->fetch_assoc()['c'] ?? 0;
$rejected_requests = $conn->query("SELECT COUNT(*) as c FROM stationery_requests WHERE status = 'REJECTED'")->fetch_assoc()['c'];
$today_requests = $conn->query("SELECT COUNT(*) as c FROM stationery_requests WHERE DATE(request_date) = CURDATE()")->fetch_assoc()['c'];
$monthly_requests = $conn->query("SELECT COUNT(*) as c FROM stationery_requests WHERE MONTH(request_date) = MONTH(CURDATE()) AND YEAR(request_date) = YEAR(CURDATE())")->fetch_assoc()['c'];

// Fetch Users Stats
$total_faculty = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'FACULTY'")->fetch_assoc()['c'];

$page_title = 'ERP Admin Dashboard - DSMS';
include 'includes/header.php';
?>
<!-- Dashboard Content -->
            <div class="container-fluid px-0">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 fw-bold text-gray-800 mb-0">Dashboard</h1>
                    <button class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-download fa-sm text-white-50 me-1"></i> Generate Report</button>
                </div>

                <!-- Metrics Cards -->
                <div class="row g-4 mb-4">
                    <!-- Card 1: Total Faculty -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card h-100 border-0 shadow-sm border-start border-primary border-4 rounded-3 hover-lift">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col me-2">
                                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Faculty</div>
                                        <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($total_faculty); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Card 2: Total Requests -->
                    <div class="col-xl-3 col-md-6">
                        <a href="faculty_requests.php?status=ALL" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm border-start border-secondary border-4 rounded-3 hover-lift">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col me-2">
                                            <div class="text-xs fw-bold text-secondary text-uppercase mb-1">Total Requests</div>
                                            <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($total_requests); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-file-invoice fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <!-- Card 3: Pending Requests -->
                    <div class="col-xl-3 col-md-6">
                        <a href="faculty_requests.php?status=PENDING" class="text-decoration-none">
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
                        </a>
                    </div>
                    <!-- Card 4: Approved Requests -->
                    <div class="col-xl-3 col-md-6">
                        <a href="faculty_requests.php?status=APPROVED" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm border-start border-success border-4 rounded-3 hover-lift">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col me-2">
                                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Approved Requests</div>
                                            <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($approved_requests); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-thumbs-up fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <!-- Card 5: Rejected Requests -->
                    <div class="col-xl-3 col-md-6">
                        <a href="faculty_requests.php?status=REJECTED" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm border-start border-danger border-4 rounded-3 hover-lift">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col me-2">
                                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">Rejected Requests</div>
                                            <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($rejected_requests); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-thumbs-down fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <!-- Card 6: Completed Requests -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card h-100 border-0 shadow-sm border-start border-dark border-4 rounded-3 hover-lift">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col me-2">
                                        <div class="text-xs fw-bold text-dark text-uppercase mb-1">Completed Requests</div>
                                        <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($completed_requests); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-double fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Card 7: Total Inventory -->
                    <div class="col-xl-3 col-md-6">
                        <a href="inventory.php" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm border-start border-info border-4 rounded-3 hover-lift">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col me-2">
                                            <div class="text-xs fw-bold text-info text-uppercase mb-1">Total Inventory</div>
                                            <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($total_items); ?> Items</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-boxes-stacked fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <!-- Card 8: Available Stock -->
                    <div class="col-xl-3 col-md-6">
                        <a href="inventory.php?filter=available" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm border-start border-success border-4 rounded-3 hover-lift">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col me-2">
                                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Available Stock</div>
                                            <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($available_stock); ?> Units</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
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
                                                    elseif ($req['status'] === 'COMPLETED') $status_badge = '<span class="badge bg-primary px-2 py-1">Completed</span>';
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


<?php include 'includes/footer.php'; ?>
