<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: login.php");
    exit();
}
include("database/db.php");

// Pagination and Filtering logic
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? trim($_GET['filter']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// Build Query
$where_clauses = ["1=1"];
$params = [];
$types = "";

if ($search !== '') {
    // Search by Item Name, Category, or ID
    $where_clauses[] = "(item_name LIKE ? OR category LIKE ? OR stationery_id = ?)";
    $search_like = "%$search%";
    $search_id = intval($search);
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_id;
    $types .= "ssi";
}

if ($category !== '') {
    $where_clauses[] = "category = ?";
    $params[] = $category;
    $types .= "s";
}

if ($filter === 'available') {
    $where_clauses[] = "quantity_available > minimum_stock";
} elseif ($filter === 'low_stock') {
    $where_clauses[] = "quantity_available <= minimum_stock AND quantity_available > 0";
} elseif ($filter === 'out_of_stock') {
    $where_clauses[] = "quantity_available = 0";
}

$where_sql = implode(" AND ", $where_clauses);

// Get Total Count
$count_query = "SELECT COUNT(*) as total FROM stationery WHERE $where_sql";
if ($stmt = $conn->prepare($count_query)) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $total_result = $stmt->get_result()->fetch_assoc();
    $total_items = $total_result['total'];
    $total_pages = ceil($total_items / $limit);
} else {
    $total_items = 0;
    $total_pages = 1;
}

// Get Items
$items_query = "SELECT * FROM stationery WHERE $where_sql ORDER BY stationery_id DESC LIMIT ? OFFSET ?";
$items = [];
if ($stmt = $conn->prepare($items_query)) {
    $current_types = $types . "ii";
    $current_params = $params;
    $current_params[] = $limit;
    $current_params[] = $offset;
    $stmt->bind_param($current_types, ...$current_params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

// Get Categories for filter
$cat_query = "SELECT DISTINCT category FROM stationery WHERE category IS NOT NULL AND category != ''";
$categories = $conn->query($cat_query);

// Get Stationery Master List
$master_query = "SELECT item_name, category FROM stationery_master ORDER BY category, item_name";
$master_items_result = $conn->query($master_query);
$master_list = [];
while($row = $master_items_result->fetch_assoc()) {
    $master_list[] = $row;
}
$master_json = json_encode($master_list);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - DSMS</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="admin_style.css">
    <style>
        .status-badge { width: 100px; text-align: center; }
        .action-btn { margin-right: 5px; }
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
                    <!-- Header specific search, we use page search instead -->
                    <div class="d-none d-sm-block ms-3">
                        <h5 class="mb-0 text-gray-800">Inventory Management</h5>
                    </div>
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                        <li class="nav-item">
                            <a class="nav-link position-relative text-gray-500" href="#"><i class="fas fa-bell fs-5"></i>
                                <span class="position-absolute top-25 start-75 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
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

            <!-- Main Inventory Content -->
            <div class="container-fluid px-0">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 fw-bold text-gray-800 mb-0">Stationery Stock</h1>
                    <div>
                        <button class="btn btn-danger shadow-sm me-2" data-bs-toggle="modal" data-bs-target="#resetModal"><i class="fas fa-trash-alt me-1"></i> Reset Inventory</button>
                        <a href="stock_history.php" class="btn btn-outline-secondary shadow-sm me-2"><i class="fas fa-history me-1"></i> Stock History</a>
                        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addItemModal"><i class="fas fa-plus me-1"></i> Add Item</button>
                    </div>
                </div>

                <!-- Filters & Search -->
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-body">
                        <form method="GET" action="inventory.php" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold">Search</label>
                                <input type="text" name="search" class="form-control" placeholder="ID, Name, or Category" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted small fw-bold">Filter Status</label>
                                <select name="filter" class="form-select">
                                    <option value="" <?php if($filter=='') echo 'selected'; ?>>All Items</option>
                                    <option value="available" <?php if($filter=='available') echo 'selected'; ?>>Available</option>
                                    <option value="low_stock" <?php if($filter=='low_stock') echo 'selected'; ?>>Low Stock</option>
                                    <option value="out_of_stock" <?php if($filter=='out_of_stock') echo 'selected'; ?>>Out of Stock</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted small fw-bold">Category</label>
                                <select name="category" class="form-select">
                                    <option value="">All Categories</option>
                                    <?php while($c = $categories->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($c['category']); ?>" <?php if($category==$c['category']) echo 'selected'; ?>><?php echo htmlspecialchars($c['category']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-grid">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i> Apply</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Alert Placeholder -->
                <div id="alertPlaceholder"></div>

                <!-- Inventory Table -->
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-muted">
                                    <tr>
                                        <th class="ps-3">ID</th>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th>Qty</th>
                                        <th>Min Stock</th>
                                        <th>Unit</th>
                                        <th>Status</th>
                                        <th>Last Updated</th>
                                        <th class="pe-3 text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($items)): ?>
                                        <tr><td colspan="9" class="text-center py-4 text-muted">No items found matching your criteria.</td></tr>
                                    <?php endif; ?>
                                    
                                    <?php foreach ($items as $item): 
                                        $qty = $item['quantity_available'];
                                        $min = $item['minimum_stock'];
                                        
                                        if ($qty == 0) {
                                            $status = '<span class="badge bg-danger rounded-pill px-3 py-2 status-badge">Out of Stock</span>';
                                        } elseif ($qty <= $min) {
                                            $status = '<span class="badge bg-warning text-dark rounded-pill px-3 py-2 status-badge">Low Stock</span>';
                                        } else {
                                            $status = '<span class="badge bg-success rounded-pill px-3 py-2 status-badge">Available</span>';
                                        }
                                    ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-secondary"><?php echo sprintf("ST%03d", $item['stationery_id']); ?></td>
                                        <td class="fw-bold text-gray-800"><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['category']); ?></td>
                                        <td class="fw-bold fs-6"><?php echo $qty; ?></td>
                                        <td class="text-muted"><?php echo $min; ?></td>
                                        <td class="text-muted"><?php echo htmlspecialchars($item['unit']); ?></td>
                                        <td><?php echo $status; ?></td>
                                        <td class="text-muted small"><?php echo date('M d, Y', strtotime($item['last_updated'])); ?></td>
                                        <td class="pe-3 text-end">
                                            <button class="btn btn-sm btn-outline-success action-btn" title="Update Stock" onclick='openStockModal(<?php echo json_encode($item); ?>)'><i class="fas fa-cubes"></i></button>
                                            <button class="btn btn-sm btn-outline-primary action-btn" title="Edit Item" onclick='openEditModal(<?php echo json_encode($item); ?>)'><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-sm btn-outline-danger" title="Delete Item" onclick="openDeleteModal(<?php echo $item['stationery_id']; ?>)"><i class="fas fa-trash"></i></button>
                                        </td>
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
                            <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>&category=<?php echo urlencode($category); ?>">Previous</a>
                        </li>
                        <?php for($i=1; $i<=$total_pages; $i++): ?>
                            <li class="page-item <?php if($i == $page) echo 'active'; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>&category=<?php echo urlencode($category); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php if($page >= $total_pages) echo 'disabled'; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>&category=<?php echo urlencode($category); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Modals -->

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addForm" onsubmit="submitForm(event, 'addForm', 'inventory_actions.php')">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addItemModalLabel">Add New Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_item">
                        <div class="mb-3">
                            <label class="form-label">Item Name *</label>
                            <select name="item_name" id="add_item_name" class="form-select" required onchange="updateAddCategory()">
                                <option value="">Select Item</option>
                                <?php foreach($master_list as $mi): ?>
                                    <option value="<?php echo htmlspecialchars($mi['item_name']); ?>"><?php echo htmlspecialchars($mi['item_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category *</label>
                            <input type="text" name="category" id="add_category" class="form-control" readonly required>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Quantity *</label>
                                <input type="number" name="quantity" class="form-control" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Min Stock *</label>
                                <input type="number" name="minimum_stock" class="form-control" min="0" value="10" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Unit *</label>
                                <input type="text" name="unit" class="form-control" placeholder="e.g. Pieces, Reams" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editForm" onsubmit="submitForm(event, 'editForm', 'inventory_actions.php')">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editItemModalLabel">Edit Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_item">
                        <input type="hidden" name="stationery_id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Item Name *</label>
                            <input type="text" name="item_name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category *</label>
                            <input type="text" name="category" id="edit_category" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Min Stock *</label>
                                <input type="number" name="minimum_stock" id="edit_min" class="form-control" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Unit *</label>
                                <input type="text" name="unit" id="edit_unit" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_desc" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="alert alert-info py-2 small">Note: To update quantity, use the "Update Stock" button.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Stock Modal -->
    <div class="modal fade" id="stockModal" tabindex="-1" aria-labelledby="stockModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form id="stockForm" onsubmit="submitForm(event, 'stockForm', 'inventory_actions.php')">
                    <div class="modal-header">
                        <h5 class="modal-title" id="stockModalLabel">Update Stock</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_stock">
                        <input type="hidden" name="stationery_id" id="stock_id">
                        <div class="mb-3 text-center">
                            <h6 id="stock_item_name" class="fw-bold text-primary"></h6>
                            <p class="mb-0">Current Stock: <span id="stock_current" class="fw-bold fs-5"></span></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Operation</label>
                            <select name="operation" class="form-select" required>
                                <option value="increase">Increase Stock</option>
                                <option value="reduce">Reduce Stock</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" name="amount" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this item? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <form id="deleteForm" onsubmit="submitForm(event, 'deleteForm', 'inventory_actions.php')">
                        <input type="hidden" name="action" value="delete_item">
                        <input type="hidden" name="stationery_id" id="delete_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset Inventory Modal -->
    <div class="modal fade" id="resetModal" tabindex="-1" aria-labelledby="resetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="resetModalLabel">Reset Inventory</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to reset the inventory? This will delete all current stock.
                </div>
                <div class="modal-footer">
                    <form id="resetForm" onsubmit="submitForm(event, 'resetForm', 'inventory_actions.php')">
                        <input type="hidden" name="action" value="reset_inventory">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Yes, Reset</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const masterList = <?php echo $master_json; ?>;

        function updateAddCategory() {
            const select = document.getElementById('add_item_name');
            const catInput = document.getElementById('add_category');
            const selectedItem = select.value;
            const found = masterList.find(i => i.item_name === selectedItem);
            if (found) {
                catInput.value = found.category;
            } else {
                catInput.value = '';
            }
        }

        // UI Interaction Functions
        document.getElementById('sidebarCollapse').addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('content').classList.toggle('active');
        });

        const editModal = new bootstrap.Modal(document.getElementById('editItemModal'));
        function openEditModal(item) {
            document.getElementById('edit_id').value = item.stationery_id;
            document.getElementById('edit_name').value = item.item_name;
            document.getElementById('edit_category').value = item.category;
            document.getElementById('edit_min').value = item.minimum_stock;
            document.getElementById('edit_unit').value = item.unit;
            document.getElementById('edit_desc').value = item.description;
            editModal.show();
        }

        const stockModal = new bootstrap.Modal(document.getElementById('stockModal'));
        function openStockModal(item) {
            document.getElementById('stock_id').value = item.stationery_id;
            document.getElementById('stock_item_name').innerText = item.item_name;
            document.getElementById('stock_current').innerText = item.quantity_available;
            stockModal.show();
        }

        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        function openDeleteModal(id) {
            document.getElementById('delete_id').value = id;
            deleteModal.show();
        }

        function showAlert(type, message) {
            const alertHtml = `<div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
            document.getElementById('alertPlaceholder').innerHTML = alertHtml;
            window.scrollTo(0,0);
        }

        // Form Submit via Fetch API
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
                    // Reload page after 1.5 seconds to show updated data
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
    <script src="under_development.js?v=2"></script>
</body>
</html>
