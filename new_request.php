<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FACULTY') {
    header("Location: login.php");
    exit();
}

include("database/db.php");

// Fetch logged-in faculty details
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$faculty = $stmt->get_result()->fetch_assoc();

// Fetch unique categories and items from stationery table
$items_res = $conn->query("SELECT stationery_id, item_name, category, quantity_available, unit FROM stationery ORDER BY category, item_name");
$stationery_items = [];
$categories = [];
while ($row = $items_res->fetch_assoc()) {
    $stationery_items[] = $row;
    if (!in_array($row['category'], $categories)) {
        $categories[] = $row['category'];
    }
}
$items_json = json_encode($stationery_items);

// Calculate notification count (recent reviews in last 7 days)
$notif_count = 0;
$notif_query = "SELECT COUNT(*) as cnt FROM stationery_requests WHERE faculty_id = ? AND status IN ('APPROVED', 'REJECTED') AND review_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
if ($n_stmt = $conn->prepare($notif_query)) {
    $n_stmt->bind_param("i", $user_id);
    $n_stmt->execute();
    $notif_count = $n_stmt->get_result()->fetch_assoc()['cnt'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Stationery Request - DSMS</title>
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

            <!-- Main Content Area -->
            <div class="container-fluid px-0">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 fw-bold text-gray-800 mb-0">New Stationery Request</h1>
                    <a href="my_requests.php" class="btn btn-sm btn-outline-secondary shadow-sm"><i class="fas fa-arrow-left me-1"></i> Back to History</a>
                </div>

                <div id="alertPlaceholder"></div>

                <div class="row g-4">
                    <!-- Faculty Profile (Read-Only) -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm rounded-3 mb-4">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-user-tie me-2"></i> Faculty Details</h6>
                            </div>
                            <div class="card-body text-center py-4">
                                <img src="faculty.png" alt="Profile" class="rounded-circle mb-3 border p-1" style="width: 100px; height: 100px; object-fit: cover;">
                                <h5 class="fw-bold text-gray-800 mb-1"><?php echo htmlspecialchars($faculty['name']); ?></h5>
                                <p class="text-muted mb-3 small">Faculty Member</p>
                                <hr>
                                <div class="text-start px-2">
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Faculty ID:</span>
                                        <span class="fw-bold text-gray-800">#FAC-<?php echo sprintf("%03d", $faculty['user_id']); ?></span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Department:</span>
                                        <span class="fw-bold text-primary"><?php echo htmlspecialchars($faculty['department'] ?? 'Computer Science'); ?></span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Email Address:</span>
                                        <span class="fw-bold text-gray-800"><?php echo htmlspecialchars($faculty['email']); ?></span>
                                    </div>
                                    <div class="mb-0">
                                        <span class="text-muted small d-block">Contact Number:</span>
                                        <span class="fw-bold text-gray-800"><?php echo htmlspecialchars($faculty['contact_number'] ?? 'N/A'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stationery Request Form -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-file-invoice me-2"></i> Stationery Request Form</h6>
                            </div>
                            <div class="card-body p-4">
                                <form id="requestForm" onsubmit="submitRequest(event)">
                                    <div class="row">
                                        <!-- Category selection -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold text-gray-700">Category *</label>
                                            <select id="category" name="category" class="form-select" required onchange="filterItems()">
                                                <option value="">Select Category</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Item selection -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold text-gray-700">Stationery Item *</label>
                                            <select id="stationery_id" name="stationery_id" class="form-select" required disabled onchange="updateStockInfo()">
                                                <option value="">Select Item</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Stock Info (Contextual Info) -->
                                    <div id="stock_info_wrapper" class="alert alert-info py-2 px-3 mb-3 d-none align-items-center justify-content-between">
                                        <div>
                                            <i class="fas fa-info-circle me-1"></i> Available Stock: 
                                            <span id="stock_qty" class="fw-bold"></span> 
                                            <span id="stock_unit"></span>
                                        </div>
                                        <span id="stock_alert" class="badge rounded-pill bg-danger d-none">Low Stock</span>
                                    </div>

                                    <div class="row">
                                        <!-- Quantity -->
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold text-gray-700">Requested Quantity *</label>
                                            <input type="number" id="requested_quantity" name="requested_quantity" class="form-control" min="1" required placeholder="e.g. 5">
                                        </div>

                                        <!-- Priority -->
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold text-gray-700">Priority *</label>
                                            <select id="priority" name="priority" class="form-select" required>
                                                <option value="LOW">Low</option>
                                                <option value="MEDIUM" selected>Medium</option>
                                                <option value="HIGH">High</option>
                                            </select>
                                        </div>

                                        <!-- Required Date -->
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold text-gray-700">Required Date *</label>
                                            <input type="date" id="required_date" name="required_date" class="form-control" required>
                                        </div>
                                    </div>

                                    <!-- Purpose -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-gray-700">Purpose of Request *</label>
                                        <textarea id="purpose" name="purpose" class="form-control" rows="3" required placeholder="Describe the reason why this stationery is required (e.g., Programming lab practicals, exams, departmental records)..."></textarea>
                                    </div>

                                    <!-- Additional Remarks -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-gray-700">Additional Remarks (Optional)</label>
                                        <textarea id="remarks" name="remarks" class="form-control" rows="2" placeholder="Any additional notes or specifications..."></textarea>
                                    </div>

                                    <div class="text-end">
                                        <button type="reset" class="btn btn-light px-4 me-2" onclick="resetForm()">Reset Form</button>
                                        <button type="submit" id="submitBtn" class="btn btn-primary px-4"><i class="fas fa-paper-plane me-1"></i> Submit Request</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set up variables
        const stationeryItems = <?php echo $items_json; ?>;

        document.getElementById('sidebarCollapse').addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('active');
        });

        // Set min date of required date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('required_date').setAttribute('min', today);

        // Filter items based on selected category
        function filterItems() {
            const catSelect = document.getElementById('category');
            const itemSelect = document.getElementById('stationery_id');
            const selectedCat = catSelect.value;

            // Reset Item Selection
            itemSelect.innerHTML = '<option value="">Select Item</option>';
            document.getElementById('stock_info_wrapper').classList.add('d-none');

            if (!selectedCat) {
                itemSelect.disabled = true;
                return;
            }

            const filtered = stationeryItems.filter(i => i.category === selectedCat);
            filtered.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.stationery_id;
                opt.textContent = item.item_name;
                itemSelect.appendChild(opt);
            });

            itemSelect.disabled = false;
        }

        // Show stock information for selected item
        function updateStockInfo() {
            const itemSelect = document.getElementById('stationery_id');
            const selectedId = parseInt(itemSelect.value);
            const stockInfo = document.getElementById('stock_info_wrapper');
            const stockQty = document.getElementById('stock_qty');
            const stockUnit = document.getElementById('stock_unit');
            const stockAlert = document.getElementById('stock_alert');

            if (!selectedId) {
                stockInfo.classList.add('d-none');
                return;
            }

            const found = stationeryItems.find(i => i.stationery_id === selectedId);
            if (found) {
                stockQty.textContent = found.quantity_available;
                stockUnit.textContent = found.unit;
                stockInfo.classList.remove('d-none');
                stockInfo.classList.add('d-flex');

                if (found.quantity_available === 0) {
                    stockAlert.textContent = "Out of Stock";
                    stockAlert.className = "badge rounded-pill bg-danger";
                    stockAlert.classList.remove('d-none');
                } else if (found.quantity_available <= 5) {
                    stockAlert.textContent = "Low Stock";
                    stockAlert.className = "badge rounded-pill bg-warning text-dark";
                    stockAlert.classList.remove('d-none');
                } else {
                    stockAlert.classList.add('d-none');
                }
            } else {
                stockInfo.classList.add('d-none');
            }
        }

        function resetForm() {
            document.getElementById('stock_info_wrapper').classList.add('d-none');
            document.getElementById('stationery_id').disabled = true;
        }

        function showAlert(type, message) {
            const alertHtml = `<div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
            document.getElementById('alertPlaceholder').innerHTML = alertHtml;
            window.scrollTo(0,0);
        }

        async function submitRequest(e) {
            e.preventDefault();
            const form = document.getElementById('requestForm');
            const formData = new FormData(form);
            formData.append('action', 'submit_request');

            const submitBtn = document.getElementById('submitBtn');
            const originalHtml = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            submitBtn.disabled = true;

            try {
                const response = await fetch('faculty_request_actions.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    showAlert('success', '<i class="fas fa-check-circle me-1"></i> ' + result.message);
                    form.reset();
                    resetForm();
                    // Redirect to my requests after 1.5 seconds
                    setTimeout(() => {
                        window.location.href = 'my_requests.php';
                    }, 1500);
                } else {
                    showAlert('danger', '<i class="fas fa-exclamation-circle me-1"></i> ' + result.message);
                }
            } catch (error) {
                showAlert('danger', 'An error occurred while submitting your request. Please try again.');
            } finally {
                submitBtn.innerHTML = originalHtml;
                submitBtn.disabled = false;
            }
        }
    </script>
</body>
</html>
