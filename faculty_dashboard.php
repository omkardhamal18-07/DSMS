<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FACULTY') {
    header("Location: login.php");
    exit();
}
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
    <!-- Custom CSS (using the same as Admin) -->
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
                <li class="active"><a href="#"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="#"><i class="fas fa-plus-circle"></i> New Request</a></li>
                <li><a href="#"><i class="fas fa-clock-rotate-left"></i> Request History</a></li>
                <li><a href="#"><i class="fas fa-bell"></i> Notifications <span class="badge bg-danger rounded-pill float-end">2</span></a></li>
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
                        <li class="nav-item me-3">
                            <a class="nav-link position-relative text-gray-500" href="#"><i class="fas fa-bell fs-5"></i>
                                <span class="position-absolute top-25 start-75 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                            </a>
                        </li>
                        <div class="topbar-divider d-none d-sm-block border-start mx-3" style="height: 2rem;"></div>
                        <li class="nav-item">
                            <div class="dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center text-gray-800" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="me-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Faculty Name'); ?></span>
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
                    <h1 class="h3 fw-bold text-gray-800 mb-0">Dashboard</h1>
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
                                        <div class="h5 mb-0 fw-bold text-gray-800">24</div>
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
                                        <div class="h5 mb-0 fw-bold text-gray-800">5</div>
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
                                        <div class="h5 mb-0 fw-bold text-gray-800">17</div>
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
                                        <div class="h5 mb-0 fw-bold text-gray-800">2</div>
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
                                <button class="btn btn-sm btn-primary">View All</button>
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
                                            <tr>
                                                <td class="fw-bold">#REQ-101</td>
                                                <td>Whiteboard Markers</td>
                                                <td>2 Boxes</td>
                                                <td>2023-10-15</td>
                                                <td><span class="badge bg-warning text-dark px-2 py-1 rounded-pill">Pending</span></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">#REQ-100</td>
                                                <td>A4 Paper Rims</td>
                                                <td>5 Rims</td>
                                                <td>2023-10-10</td>
                                                <td><span class="badge bg-success px-2 py-1 rounded-pill">Approved</span></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">#REQ-099</td>
                                                <td>Stapler Pins</td>
                                                <td>10 Packets</td>
                                                <td>2023-10-05</td>
                                                <td><span class="badge bg-success px-2 py-1 rounded-pill">Approved</span></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">#REQ-098</td>
                                                <td>Scientific Calculators</td>
                                                <td>2 Units</td>
                                                <td>2023-09-28</td>
                                                <td><span class="badge bg-danger px-2 py-1 rounded-pill">Rejected</span></td>
                                            </tr>
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
                                    <button class="btn btn-primary" type="button"><i class="fas fa-plus-circle me-2"></i> New Stationery Request</button>
                                    <button class="btn btn-outline-secondary" type="button"><i class="fas fa-search me-2"></i> Track Request</button>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Summary -->
                        <div class="card border-0 shadow-sm rounded-3 mb-4">
                            <div class="card-body text-center pt-4 pb-4">
                                <img src="faculty.png" alt="Profile" class="rounded-circle mb-3 border p-1" style="width: 80px; height: 80px; object-fit: cover;">
                                <h5 class="fw-bold text-gray-800 mb-1"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Faculty Name'); ?></h5>
                                <p class="text-muted mb-1 small">Faculty Member</p>
                                <p class="text-muted mb-3 small fw-bold text-primary">Computer Science Dept.</p>
                                <button class="btn btn-sm btn-outline-primary px-4 rounded-pill">Edit Profile</button>
                            </div>
                        </div>

                        <!-- Notifications -->
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white border-bottom py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 fw-bold text-primary">Notifications</h6>
                                <span class="badge bg-danger rounded-pill">2 New</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush rounded-bottom">
                                    <a href="#" class="list-group-item list-group-item-action d-flex align-items-start py-3 px-4 border-bottom-0">
                                        <div class="activity-icon bg-success text-white rounded-circle me-3 flex-shrink-0">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 text-gray-800 fw-bold fs-6">Request #REQ-100 Approved</h6>
                                            <p class="mb-1 text-muted small">Your request for A4 Paper Rims has been approved by HOD.</p>
                                            <small class="text-primary fw-bold" style="font-size: 0.75rem;">2 hours ago</small>
                                        </div>
                                    </a>
                                    <a href="#" class="list-group-item list-group-item-action d-flex align-items-start py-3 px-4">
                                        <div class="activity-icon bg-danger text-white rounded-circle me-3 flex-shrink-0">
                                            <i class="fas fa-times"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 text-gray-800 fw-bold fs-6">Request #REQ-098 Rejected</h6>
                                            <p class="mb-1 text-muted small">Your request for Scientific Calculators was rejected.</p>
                                            <small class="text-primary fw-bold" style="font-size: 0.75rem;">1 day ago</small>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top text-center py-2">
                                <a href="#" class="text-decoration-none small fw-bold text-primary">View All Notifications</a>
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
    <script src="under_development.js"></script>
</body>
</html>
