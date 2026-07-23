<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: login.php");
    exit();
}
include("database/db.php");

// Pagination logic
$limit = 15;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Get Total Count
$count_query = "SELECT COUNT(*) as total FROM stock_history";
$total_result = $conn->query($count_query)->fetch_assoc();
$total_items = $total_result['total'];
$total_pages = ceil($total_items / $limit);

// Get History
$history_query = "SELECT h.*, s.item_name, u.name as admin_name 
                  FROM stock_history h 
                  LEFT JOIN stationery s ON h.stationery_id = s.stationery_id 
                  LEFT JOIN users u ON h.admin_id = u.user_id 
                  ORDER BY h.created_at DESC 
                  LIMIT ? OFFSET ?";
$history = [];
if ($stmt = $conn->prepare($history_query)) {
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock History - DSMS</title>
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
                <h3><i class="fas fa-boxes-stacked me-2"></i> DSMS ERP</h3>
            </div>
            <ul class="list-unstyled components">
                <li><a href="admin_dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="faculty_requests.php"><i class="fas fa-code-pull-request"></i> Faculty Requests</a></li>
                <li class="active"><a href="inventory.php"><i class="fas fa-warehouse"></i> Inventory</a></li>
                <li><a href="issue_stationery.php"><i class="fas fa-dolly"></i> Issue Stationery</a></li>
                <li><a href="#"><i class="fas fa-tags"></i> Categories</a></li>
                <li><a href="#"><i class="fas fa-chart-pie"></i> Reports</a></li>
                <li><a href="#"><i class="fas fa-bell"></i> Notifications <span class="badge bg-danger rounded-pill float-end">3</span></a></li>
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
                        <h5 class="mb-0 text-gray-800">Stock History</h5>
                    </div>
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 fw-bold text-gray-800 mb-0">Stock Update Log</h1>
                    <div>
                        <button class="btn btn-danger shadow-sm me-2" data-bs-toggle="modal" data-bs-target="#clearHistoryModal"><i class="fas fa-trash-alt me-1"></i> Clear History</button>
                        <a href="inventory.php" class="btn btn-outline-secondary shadow-sm"><i class="fas fa-arrow-left me-1"></i> Back to Inventory</a>
                    </div>
                </div>

                <!-- Alert Placeholder -->
                <div id="alertPlaceholder"></div>

                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-muted">
                                    <tr>
                                        <th class="ps-3">Date & Time</th>
                                        <th>Item Name</th>
                                        <th>Previous Qty</th>
                                        <th>New Qty</th>
                                        <th>Action</th>
                                        <th>Admin Name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($history)): ?>
                                        <tr><td colspan="6" class="text-center py-4 text-muted">No stock history found.</td></tr>
                                    <?php endif; ?>
                                    
                                    <?php foreach ($history as $h): ?>
                                    <tr>
                                        <td class="ps-3 text-muted small"><?php echo date('M d, Y h:i A', strtotime($h['created_at'])); ?></td>
                                        <td class="fw-bold text-gray-800"><?php echo htmlspecialchars($h['item_name'] ?? 'Deleted Item'); ?></td>
                                        <td class="fw-bold text-secondary"><?php echo $h['previous_quantity']; ?></td>
                                        <td class="fw-bold text-primary"><?php echo $h['new_quantity']; ?></td>
                                        <td>
                                            <?php 
                                            $action_class = "text-muted";
                                            if (strpos($h['action'], 'Increased') !== false) $action_class = "text-success fw-bold";
                                            if (strpos($h['action'], 'Reduced') !== false) $action_class = "text-danger fw-bold";
                                            ?>
                                            <span class="<?php echo $action_class; ?>"><?php echo htmlspecialchars($h['action']); ?></span>
                                        </td>
                                        <td class="text-muted"><i class="fas fa-user-shield me-1"></i> <?php echo htmlspecialchars($h['admin_name']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php if($page <= 1) echo 'disabled'; ?>">
                            <a class="page-link" href="?page=<?php echo $page-1; ?>">Previous</a>
                        </li>
                        <?php for($i=1; $i<=$total_pages; $i++): ?>
                            <li class="page-item <?php if($i == $page) echo 'active'; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php if($page >= $total_pages) echo 'disabled'; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Clear History Modal -->
    <div class="modal fade" id="clearHistoryModal" tabindex="-1" aria-labelledby="clearHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="clearHistoryModalLabel">Clear History</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete all stock history? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <form id="clearHistoryForm" onsubmit="submitForm(event, 'clearHistoryForm', 'inventory_actions.php')">
                        <input type="hidden" name="action" value="clear_history">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Yes, Delete All</button>
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
                
                // Hide Modals
                bootstrap.Modal.getInstance(form.closest('.modal')).hide();
                
                if (result.success) {
                    showAlert('success', '<i class="fas fa-check-circle me-1"></i> ' + result.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('danger', '<i class="fas fa-exclamation-circle me-1"></i> ' + result.message);
                }
            } catch (error) {
                showAlert('danger', 'An error occurred while processing your request.');
                bootstrap.Modal.getInstance(form.closest('.modal')).hide();
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }
    </script>
    <script src="under_development.js"></script>
</body>
</html>
