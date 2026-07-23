<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: login.php");
    exit();
}
include("database/db.php");

// Filter and Search inputs
$search_faculty = isset($_GET['search_faculty']) ? $conn->real_escape_string($_GET['search_faculty']) : '';
$search_item = isset($_GET['search_item']) ? $conn->real_escape_string($_GET['search_item']) : '';
$search_id = isset($_GET['search_id']) ? intval(str_replace('#REQ-', '', $_GET['search_id'])) : 0;
$filter_status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$filter_date = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : '';
$filter_dept = isset($_GET['department']) ? $conn->real_escape_string($_GET['department']) : '';

// Pagination
$limit = 15;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Build query
$where_clauses = [];
if (!empty($search_faculty)) $where_clauses[] = "u.name LIKE '%$search_faculty%'";
if (!empty($search_item)) $where_clauses[] = "s.item_name LIKE '%$search_item%'";
if ($search_id > 0) $where_clauses[] = "r.request_id = $search_id";
if (!empty($filter_status)) $where_clauses[] = "r.status = '$filter_status'";
if (!empty($filter_date)) $where_clauses[] = "DATE(r.request_date) = '$filter_date'";
if (!empty($filter_dept)) $where_clauses[] = "u.department = '$filter_dept'";

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Count query
$count_query = "SELECT COUNT(*) as total FROM stationery_requests r JOIN users u ON r.faculty_id = u.user_id JOIN stationery s ON r.stationery_id = s.stationery_id $where_sql";
$total_result = $conn->query($count_query)->fetch_assoc();
$total_items = $total_result['total'];
$total_pages = ceil($total_items / $limit);

// Fetch data
$query = "SELECT r.*, u.name as faculty_name, u.department, s.item_name, s.category,
          rev.name as reviewer_name 
          FROM stationery_requests r 
          JOIN users u ON r.faculty_id = u.user_id 
          JOIN stationery s ON r.stationery_id = s.stationery_id 
          LEFT JOIN users rev ON r.reviewed_by = rev.user_id 
          $where_sql 
          ORDER BY r.request_date DESC LIMIT $limit OFFSET $offset";

$result = $conn->query($query);
$history = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
}

// Fetch departments for filter
$dept_query = "SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != ''";
$departments = $conn->query($dept_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request History - DSMS</title>
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
        <?php include 'includes/sidebar.php'; ?>

        <!-- Page Content -->
        <div id="content">
            <!-- Top Navbar -->
            <?php include 'includes/navbar.php'; ?>

            <!-- Main Content -->
            <div class="container-fluid px-0">
                
                <!-- Filters -->
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-body">
                        <form method="GET" action="request_history.php" class="row g-3 align-items-end">
                            <div class="col-md-1">
                                <label class="form-label small text-muted">Request ID</label>
                                <input type="text" name="search_id" class="form-control form-control-sm" placeholder="#REQ-100" value="<?php echo htmlspecialchars($_GET['search_id'] ?? ''); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">Faculty Name</label>
                                <input type="text" name="search_faculty" class="form-control form-control-sm" placeholder="Name" value="<?php echo htmlspecialchars($_GET['search_faculty'] ?? ''); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">Item Name</label>
                                <input type="text" name="search_item" class="form-control form-control-sm" placeholder="Item" value="<?php echo htmlspecialchars($_GET['search_item'] ?? ''); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">All Statuses</option>
                                    <option value="PENDING" <?php echo $filter_status === 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="APPROVED" <?php echo $filter_status === 'APPROVED' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="REJECTED" <?php echo $filter_status === 'REJECTED' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">Department</label>
                                <select name="department" class="form-select form-select-sm">
                                    <option value="">All Departments</option>
                                    <?php if($departments->num_rows > 0): while($d = $departments->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($d['department']); ?>" <?php echo $filter_dept === $d['department'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['department']); ?></option>
                                    <?php endwhile; endif; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">Date</label>
                                <input type="date" name="date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($_GET['date'] ?? ''); ?>">
                            </div>
                            <div class="col-md-1 text-end">
                                <button type="submit" class="btn btn-sm btn-primary w-100 mb-1">Filter</button>
                                <a href="request_history.php" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="card shadow-sm border-0 rounded-3 mb-4">
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
                                        <th>Status</th>
                                        <th>Reviewed By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($history)): ?>
                                        <tr><td colspan="8" class="text-center py-4 text-muted">No request history found matching your criteria.</td></tr>
                                    <?php else: 
                                        foreach ($history as $req):
                                    ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-gray-800">#REQ-<?php echo $req['request_id']; ?></td>
                                        <td><?php echo htmlspecialchars($req['faculty_name']); ?></td>
                                        <td><?php echo htmlspecialchars($req['department'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($req['item_name']); ?></td>
                                        <td class="fw-bold text-primary"><?php echo $req['requested_quantity']; ?></td>
                                        <td class="text-muted small"><?php echo date('M d, Y', strtotime($req['request_date'])); ?></td>
                                        <td>
                                            <?php 
                                            if ($req['status'] === 'PENDING') echo '<span class="badge bg-warning text-dark px-2 py-1">Pending</span>';
                                            elseif ($req['status'] === 'APPROVED') echo '<span class="badge bg-success px-2 py-1">Approved</span>';
                                            elseif ($req['status'] === 'REJECTED') echo '<span class="badge bg-danger px-2 py-1">Rejected</span>';
                                            ?>
                                        </td>
                                        <td class="text-muted small">
                                            <?php echo htmlspecialchars($req['reviewer_name'] ?? '-'); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php
                        $qs = $_GET;
                        unset($qs['page']);
                        $query_string = http_build_query($qs);
                        $query_string = $query_string ? '&' . $query_string : '';
                        ?>
                        <li class="page-item <?php if($page <= 1) echo 'disabled'; ?>">
                            <a class="page-link" href="?page=<?php echo $page-1 . $query_string; ?>">Previous</a>
                        </li>
                        <?php for($i=1; $i<=$total_pages; $i++): ?>
                            <li class="page-item <?php if($i == $page) echo 'active'; ?>">
                                <a class="page-link" href="?page=<?php echo $i . $query_string; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php if($page >= $total_pages) echo 'disabled'; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1 . $query_string; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>

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
    </script>
    <script src="under_development.js"></script>
</body>
</html>
