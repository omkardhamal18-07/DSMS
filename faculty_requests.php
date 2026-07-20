<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: login.php");
    exit();
}
include("database/db.php");

$status_filter = isset($_GET['status']) ? strtoupper($_GET['status']) : 'ALL';
$valid_statuses = ['ALL', 'PENDING', 'APPROVED', 'REJECTED'];
if (!in_array($status_filter, $valid_statuses)) $status_filter = 'ALL';

// Counts for cards
$total_requests = $conn->query("SELECT COUNT(*) as c FROM stationery_requests")->fetch_assoc()['c'];
$pending_requests = $conn->query("SELECT COUNT(*) as c FROM stationery_requests WHERE status = 'PENDING'")->fetch_assoc()['c'];
$approved_requests = $conn->query("SELECT COUNT(*) as c FROM stationery_requests WHERE status = 'APPROVED'")->fetch_assoc()['c'];
$rejected_requests = $conn->query("SELECT COUNT(*) as c FROM stationery_requests WHERE status = 'REJECTED'")->fetch_assoc()['c'];
$today_requests = $conn->query("SELECT COUNT(*) as c FROM stationery_requests WHERE DATE(request_date) = CURDATE()")->fetch_assoc()['c'];

// Fetch requests for table
$query = "SELECT r.*, u.name as faculty_name, u.department, s.item_name, s.category, s.quantity_available,
          rev.name as reviewer_name 
          FROM stationery_requests r 
          JOIN users u ON r.faculty_id = u.user_id 
          JOIN stationery s ON r.stationery_id = s.stationery_id 
          LEFT JOIN users rev ON r.reviewed_by = rev.user_id ";

if ($status_filter !== 'ALL') {
    $query .= " WHERE r.status = '" . $conn->real_escape_string($status_filter) . "'";
}
$query .= " ORDER BY r.request_date DESC";

$result = $conn->query($query);
$requests = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Requests - DSMS</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="admin_style.css">
    <style>
        .hover-lift:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="sidebar shadow">
            <div class="sidebar-header">
                <h3><i class="fas fa-boxes-stacked me-2"></i> DSMS ERP</h3>
            </div>
            <ul class="list-unstyled components">
                <li><a href="admin_dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li class="active">
                    <a href="#facultyRequestsSubmenu" data-bs-toggle="collapse" aria-expanded="true" class="dropdown-toggle">
                        <i class="fas fa-code-pull-request"></i> Faculty Requests
                    </a>
                    <ul class="collapse list-unstyled show" id="facultyRequestsSubmenu">
                        <li class="<?php echo $status_filter === 'ALL' ? 'active' : ''; ?>"><a href="faculty_requests.php?status=ALL"><i class="fas fa-list ms-3 me-2"></i> All Requests</a></li>
                        <li class="<?php echo $status_filter === 'PENDING' ? 'active' : ''; ?>"><a href="faculty_requests.php?status=PENDING"><i class="fas fa-clock ms-3 me-2"></i> Pending Requests</a></li>
                        <li class="<?php echo $status_filter === 'APPROVED' ? 'active' : ''; ?>"><a href="faculty_requests.php?status=APPROVED"><i class="fas fa-check-circle ms-3 me-2"></i> Approved Requests</a></li>
                        <li class="<?php echo $status_filter === 'REJECTED' ? 'active' : ''; ?>"><a href="faculty_requests.php?status=REJECTED"><i class="fas fa-times-circle ms-3 me-2"></i> Rejected Requests</a></li>
                        <li><a href="request_history.php"><i class="fas fa-history ms-3 me-2"></i> Request History</a></li>
                    </ul>
                </li>
                <li><a href="inventory.php"><i class="fas fa-warehouse"></i> Inventory</a></li>
                <li><a href="#"><i class="fas fa-dolly"></i> Issue Stationery</a></li>
                <li><a href="#"><i class="fas fa-tags"></i> Categories</a></li>
                <li><a href="#"><i class="fas fa-chart-pie"></i> Reports</a></li>
                <li><a href="faculty_requests.php?status=PENDING"><i class="fas fa-bell"></i> Notifications <?php if($pending_requests > 0): ?><span class="badge bg-danger rounded-pill float-end"><?php echo $pending_requests; ?></span><?php endif; ?></a></li>
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
                    <div class="d-none d-sm-block ms-3">
                        <h5 class="mb-0 text-gray-800">Faculty Requests Management</h5>
                    </div>
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                        <li class="nav-item">
                            <a class="nav-link position-relative text-gray-500" href="faculty_requests.php?status=PENDING"><i class="fas fa-bell fs-5"></i>
                                <?php if($pending_requests > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                                    <?php echo $pending_requests; ?>
                                </span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <div class="topbar-divider d-none d-sm-block border-start mx-3" style="height: 2rem;"></div>
                        <li class="nav-item">
                            <div class="dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center text-gray-800" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="me-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin User'); ?></span>
                                    <img src="HOD.png" alt="User Profile" class="rounded-circle shadow-sm" width="32" height="32" style="object-fit: cover;">
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 animated--grow-in" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt fa-sm fa-fw me-2 text-gray-400"></i> Logout</a></li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="container-fluid px-0">
                <div id="alertPlaceholder"></div>
                
                <!-- Summary Cards Row -->
                <div class="row g-4 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <a href="faculty_requests.php?status=ALL" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm border-start border-primary border-4 rounded-3 hover-lift <?php echo $status_filter === 'ALL' ? 'bg-light' : ''; ?>">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col me-2">
                                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Requests</div>
                                            <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($total_requests); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-list fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <a href="faculty_requests.php?status=PENDING" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm border-start border-warning border-4 rounded-3 hover-lift <?php echo $status_filter === 'PENDING' ? 'bg-light' : ''; ?>">
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
                    <div class="col-xl-3 col-md-6">
                        <a href="faculty_requests.php?status=APPROVED" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm border-start border-success border-4 rounded-3 hover-lift <?php echo $status_filter === 'APPROVED' ? 'bg-light' : ''; ?>">
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
                    <div class="col-xl-3 col-md-6">
                        <a href="faculty_requests.php?status=REJECTED" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm border-start border-danger border-4 rounded-3 hover-lift <?php echo $status_filter === 'REJECTED' ? 'bg-light' : ''; ?>">
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
                </div>

                <!-- Table Card -->
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-primary"><?php echo ucfirst(strtolower($status_filter)); ?> Requests</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-muted">
                                    <tr>
                                        <th class="ps-3">Req ID</th>
                                        <th>Faculty Name</th>
                                        <th>Department</th>
                                        <th>Item</th>
                                        <th>Qty</th>
                                        <th>Date</th>
                                        <?php if ($status_filter === 'APPROVED'): ?>
                                        <th>Approval Date</th>
                                        <th>Approved By</th>
                                        <?php elseif ($status_filter === 'REJECTED'): ?>
                                        <th>Rejection Reason</th>
                                        <?php else: ?>
                                        <th>Status</th>
                                        <?php endif; ?>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($requests)): ?>
                                        <tr><td colspan="9" class="text-center py-4 text-muted">No requests found.</td></tr>
                                    <?php else: 
                                        foreach ($requests as $req):
                                    ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-gray-800">#REQ-<?php echo $req['request_id']; ?></td>
                                        <td><?php echo htmlspecialchars($req['faculty_name']); ?></td>
                                        <td><?php echo htmlspecialchars($req['department'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($req['item_name']); ?>
                                            <div class="small text-muted"><?php echo htmlspecialchars($req['category']); ?></div>
                                        </td>
                                        <td class="fw-bold text-primary"><?php echo $req['requested_quantity']; ?></td>
                                        <td class="text-muted small"><?php echo date('M d, Y', strtotime($req['request_date'])); ?></td>
                                        
                                        <?php if ($status_filter === 'APPROVED'): ?>
                                        <td class="text-muted small"><?php echo date('M d, Y', strtotime($req['review_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($req['reviewer_name']); ?></td>
                                        <?php elseif ($status_filter === 'REJECTED'): ?>
                                        <td><span class="text-danger small"><?php echo htmlspecialchars($req['remarks'] ?? 'No reason provided'); ?></span></td>
                                        <?php else: ?>
                                        <td>
                                            <?php 
                                            if ($req['status'] === 'PENDING') echo '<span class="badge bg-warning text-dark px-2 py-1">Pending</span>';
                                            elseif ($req['status'] === 'APPROVED') echo '<span class="badge bg-success px-2 py-1">Approved</span>';
                                            elseif ($req['status'] === 'REJECTED') echo '<span class="badge bg-danger px-2 py-1">Rejected</span>';
                                            ?>
                                        </td>
                                        <?php endif; ?>
                                        
                                        <td>
                                            <button class="btn btn-sm btn-outline-info view-btn" data-req='<?php echo json_encode($req); ?>'><i class="fas fa-eye"></i></button>
                                            <?php if ($req['status'] === 'PENDING'): ?>
                                            <button class="btn btn-sm btn-success approve-btn" data-id="<?php echo $req['request_id']; ?>"><i class="fas fa-check"></i></button>
                                            <button class="btn btn-sm btn-danger reject-btn" data-id="<?php echo $req['request_id']; ?>"><i class="fas fa-times"></i></button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- View Details Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Request Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-2">
                        <div class="col-sm-5 fw-bold text-gray-700">Request ID:</div>
                        <div class="col-sm-7" id="v_request_id"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-5 fw-bold text-gray-700">Faculty Name:</div>
                        <div class="col-sm-7" id="v_faculty_name"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-5 fw-bold text-gray-700">Department:</div>
                        <div class="col-sm-7" id="v_department"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-5 fw-bold text-gray-700">Requested Item:</div>
                        <div class="col-sm-7" id="v_item_name"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-5 fw-bold text-gray-700">Category:</div>
                        <div class="col-sm-7" id="v_category"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-5 fw-bold text-gray-700">Available Stock:</div>
                        <div class="col-sm-7" id="v_available_stock"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-5 fw-bold text-gray-700">Requested Quantity:</div>
                        <div class="col-sm-7 fw-bold text-primary" id="v_requested_qty"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-5 fw-bold text-gray-700">Purpose/Remarks:</div>
                        <div class="col-sm-7" id="v_remarks"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-5 fw-bold text-gray-700">Request Date:</div>
                        <div class="col-sm-7" id="v_request_date"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-5 fw-bold text-gray-700">Current Status:</div>
                        <div class="col-sm-7" id="v_status"></div>
                    </div>
                    <div id="v_review_section" class="d-none">
                        <div class="row mb-2">
                            <div class="col-sm-5 fw-bold text-gray-700">Reviewed By:</div>
                            <div class="col-sm-7" id="v_reviewer"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-5 fw-bold text-gray-700">Review Date:</div>
                            <div class="col-sm-7" id="v_review_date"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Reject Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="rejectForm" onsubmit="submitForm(event, 'rejectForm', 'request_actions.php')">
                        <input type="hidden" name="action" value="reject_request">
                        <input type="hidden" name="request_id" id="r_request_id">
                        <div class="mb-3">
                            <label class="form-label text-gray-700 fw-bold">Rejection Reason (Mandatory)</label>
                            <textarea name="remarks" class="form-control" rows="3" required placeholder="Please provide a reason for rejecting this request..."></textarea>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Reject Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarCollapse').addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('content').classList.toggle('active');
        });

        function showAlert(type, message) {
            const alertHtml = `<div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
            document.getElementById('alertPlaceholder').innerHTML = alertHtml;
            window.scrollTo(0,0);
        }

        async function submitForm(e, formId, url) {
            e.preventDefault();
            const form = document.getElementById(formId);
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                const modalEl = form.closest('.modal');
                if(modalEl) bootstrap.Modal.getInstance(modalEl).hide();
                
                if (result.success) {
                    showAlert('success', '<i class="fas fa-check-circle me-1"></i> ' + result.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('danger', '<i class="fas fa-exclamation-circle me-1"></i> ' + result.message);
                }
            } catch (error) {
                showAlert('danger', 'An error occurred while processing your request.');
                const modalEl = form.closest('.modal');
                if(modalEl) bootstrap.Modal.getInstance(modalEl).hide();
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }

        // View Details Logic
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const req = JSON.parse(this.dataset.req);
                document.getElementById('v_request_id').textContent = '#REQ-' + req.request_id;
                document.getElementById('v_faculty_name').textContent = req.faculty_name;
                document.getElementById('v_department').textContent = req.department || 'N/A';
                document.getElementById('v_item_name').textContent = req.item_name;
                document.getElementById('v_category').textContent = req.category;
                
                const stockEl = document.getElementById('v_available_stock');
                stockEl.textContent = req.quantity_available;
                if(parseInt(req.quantity_available) < parseInt(req.requested_quantity)) {
                    stockEl.className = "col-sm-7 fw-bold text-danger";
                } else {
                    stockEl.className = "col-sm-7 text-success";
                }

                document.getElementById('v_requested_qty').textContent = req.requested_quantity;
                document.getElementById('v_remarks').textContent = req.remarks || 'None';
                document.getElementById('v_request_date').textContent = req.request_date;
                
                const statusEl = document.getElementById('v_status');
                if (req.status === 'PENDING') statusEl.innerHTML = '<span class="badge bg-warning text-dark px-2 py-1">Pending</span>';
                else if (req.status === 'APPROVED') statusEl.innerHTML = '<span class="badge bg-success px-2 py-1">Approved</span>';
                else if (req.status === 'REJECTED') statusEl.innerHTML = '<span class="badge bg-danger px-2 py-1">Rejected</span>';

                if (req.status !== 'PENDING') {
                    document.getElementById('v_review_section').classList.remove('d-none');
                    document.getElementById('v_reviewer').textContent = req.reviewer_name || 'System';
                    document.getElementById('v_review_date').textContent = req.review_date;
                } else {
                    document.getElementById('v_review_section').classList.add('d-none');
                }

                new bootstrap.Modal(document.getElementById('viewModal')).show();
            });
        });

        // Approve Logic
        document.querySelectorAll('.approve-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                if(!confirm('Are you sure you want to approve this request? Stock will be deducted automatically.')) return;
                
                const btnIcon = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                this.disabled = true;

                const formData = new FormData();
                formData.append('action', 'approve_request');
                formData.append('request_id', this.dataset.id);

                try {
                    const response = await fetch('request_actions.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    if (result.success) {
                        showAlert('success', '<i class="fas fa-check-circle me-1"></i> ' + result.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert('danger', '<i class="fas fa-exclamation-circle me-1"></i> ' + result.message);
                        this.innerHTML = btnIcon;
                        this.disabled = false;
                    }
                } catch (error) {
                    showAlert('danger', 'An error occurred while processing your request.');
                    this.innerHTML = btnIcon;
                    this.disabled = false;
                }
            });
        });

        // Reject Logic
        document.querySelectorAll('.reject-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('r_request_id').value = this.dataset.id;
                document.getElementById('rejectForm').reset();
                new bootstrap.Modal(document.getElementById('rejectModal')).show();
            });
        });
    </script>
</body>
</html>
