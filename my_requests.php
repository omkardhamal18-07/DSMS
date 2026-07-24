<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FACULTY') {
    header("Location: login.php");
    exit();
}

include("database/db.php");

$user_id = $_SESSION['user_id'];
$status_filter = isset($_GET['status']) ? strtoupper($_GET['status']) : 'ALL';
$valid_statuses = ['ALL', 'PENDING', 'APPROVED', 'REJECTED'];
if (!in_array($status_filter, $valid_statuses)) $status_filter = 'ALL';

// Fetch logged-in faculty details
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$faculty = $stmt->get_result()->fetch_assoc();

// Counts for cards
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

// Fetch requests for table
$query = "SELECT r.*, s.item_name, s.category, s.quantity_available, s.unit, rev.name as reviewer_name 
          FROM stationery_requests r 
          JOIN stationery s ON r.stationery_id = s.stationery_id 
          LEFT JOIN users rev ON r.reviewed_by = rev.user_id 
          WHERE r.faculty_id = ?";

if ($status_filter !== 'ALL') {
    $query .= " AND r.status = '" . $conn->real_escape_string($status_filter) . "'";
}
$query .= " ORDER BY r.request_date DESC";

$requests = [];
if ($r_stmt = $conn->prepare($query)) {
    $r_stmt->bind_param("i", $user_id);
    $r_stmt->execute();
    $result = $r_stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}
?>
<?php
$page_title = 'Request History - DSMS';
ob_start();
?>
<style>
        .hover-lift:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
        }
    </style>
<?php
$extra_css = ob_get_clean();
include 'includes/header.php';
?>
<!-- Main Content -->
            <div class="container-fluid px-0">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 fw-bold text-gray-800 mb-0">My Stationery Requests</h1>
                    <a href="new_request.php" class="btn btn-primary shadow-sm"><i class="fas fa-plus me-1"></i> New Request</a>
                </div>

                <div id="alertPlaceholder"></div>

                <!-- Summary Cards Row -->
                <div class="row g-4 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <a href="my_requests.php?status=ALL" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm border-start border-primary border-4 rounded-3 hover-lift <?php echo $status_filter === 'ALL' ? 'bg-light' : ''; ?>">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col me-2">
                                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Requests</div>
                                            <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $total_requests; ?></div>
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
                        <a href="my_requests.php?status=PENDING" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm border-start border-warning border-4 rounded-3 hover-lift <?php echo $status_filter === 'PENDING' ? 'bg-light' : ''; ?>">
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
                    <div class="col-xl-3 col-md-6">
                        <a href="my_requests.php?status=APPROVED" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm border-start border-success border-4 rounded-3 hover-lift <?php echo $status_filter === 'APPROVED' ? 'bg-light' : ''; ?>">
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
                    <div class="col-xl-3 col-md-6">
                        <a href="my_requests.php?status=REJECTED" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm border-start border-danger border-4 rounded-3 hover-lift <?php echo $status_filter === 'REJECTED' ? 'bg-light' : ''; ?>">
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
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th>Qty</th>
                                        <th>Required Date</th>
                                        <th>Priority</th>
                                        <th>Date Submitted</th>
                                        <th>Status</th>
                                        <th class="pe-3 text-end">Action</th>
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
                                        <td class="fw-bold"><?php echo htmlspecialchars($req['item_name']); ?></td>
                                        <td><span class="badge bg-light text-secondary"><?php echo htmlspecialchars($req['category']); ?></span></td>
                                        <td class="fw-bold text-primary"><?php echo $req['requested_quantity']; ?> <?php echo htmlspecialchars($req['unit']); ?></td>
                                        <td class="small text-muted"><?php echo date('M d, Y', strtotime($req['required_date'])); ?></td>
                                        <td>
                                            <?php 
                                            if ($req['priority'] === 'HIGH') echo '<span class="badge bg-danger rounded-pill">High</span>';
                                            elseif ($req['priority'] === 'MEDIUM') echo '<span class="badge bg-warning text-dark rounded-pill">Medium</span>';
                                            else echo '<span class="badge bg-info rounded-pill">Low</span>';
                                            ?>
                                        </td>
                                        <td class="text-muted small"><?php echo date('M d, Y h:i A', strtotime($req['request_date'])); ?></td>
                                        <td>
                                            <?php 
                                            if ($req['status'] === 'PENDING') echo '<span class="badge bg-warning text-dark px-2 py-1">Pending</span>';
                                            elseif ($req['status'] === 'APPROVED') echo '<span class="badge bg-success px-2 py-1">Approved</span>';
                                            elseif ($req['status'] === 'REJECTED') echo '<span class="badge bg-danger px-2 py-1">Rejected</span>';
                                            ?>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <button class="btn btn-sm btn-outline-info view-btn" data-req='<?php echo json_encode($req); ?>' title="View Details"><i class="fas fa-eye"></i></button>
                                            <?php if ($req['status'] === 'PENDING'): ?>
                                            <button class="btn btn-sm btn-outline-primary edit-btn" data-req='<?php echo json_encode($req); ?>' title="Edit Request"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-sm btn-outline-danger cancel-btn" data-id="<?php echo $req['request_id']; ?>" title="Cancel Request"><i class="fas fa-trash"></i></button>
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
                    <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i> Request Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-2">
                        <div class="col-sm-5 fw-bold text-gray-700">Request ID:</div>
                        <div class="col-sm-7" id="v_request_id"></div>
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
                        <div class="col-sm-5 fw-bold text-gray-700">Requested Quantity:</div>
                        <div class="col-sm-7 fw-bold text-primary" id="v_requested_qty"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-5 fw-bold text-gray-700">Required Date:</div>
                        <div class="col-sm-7" id="v_required_date"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-5 fw-bold text-gray-700">Priority:</div>
                        <div class="col-sm-7" id="v_priority"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-5 fw-bold text-gray-700">Purpose:</div>
                        <div class="col-sm-7" id="v_purpose"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-5 fw-bold text-gray-700">Additional Remarks:</div>
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
                    
                    <!-- Admin Review Section -->
                    <div id="v_review_section" class="d-none">
                        <hr>
                        <div class="row mb-2">
                            <div class="col-sm-5 fw-bold text-gray-700">Reviewed By:</div>
                            <div class="col-sm-7" id="v_reviewer"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-5 fw-bold text-gray-700">Review Date:</div>
                            <div class="col-sm-7" id="v_review_date"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-5 fw-bold text-danger">HOD Remarks:</div>
                            <div class="col-sm-7 fw-bold" id="v_hod_remarks"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Request Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editForm" onsubmit="submitEdit(event)">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Edit Request</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_request">
                        <input type="hidden" name="request_id" id="edit_request_id">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Item Name</label>
                            <input type="text" id="edit_item_name" class="form-control-plaintext fw-bold text-primary" readonly>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Requested Quantity *</label>
                                <input type="number" name="requested_quantity" id="edit_requested_qty" class="form-control" min="1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Priority *</label>
                                <select name="priority" id="edit_priority" class="form-select" required>
                                    <option value="LOW">Low</option>
                                    <option value="MEDIUM">Medium</option>
                                    <option value="HIGH">High</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Required Date *</label>
                            <input type="date" name="required_date" id="edit_required_date" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Purpose *</label>
                            <textarea name="purpose" id="edit_purpose" class="form-control" rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Additional Remarks (Optional)</label>
                            <textarea name="remarks" id="edit_remarks" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="editSubmitBtn" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>

<?php ob_start(); ?>
<script>
document.getElementById('sidebarCollapse').addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('active');
        });

        // Set min date for edit required date
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('edit_required_date').setAttribute('min', today);

        function showAlert(type, message) {
            const alertHtml = `<div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
            document.getElementById('alertPlaceholder').innerHTML = alertHtml;
            window.scrollTo(0,0);
        }

        // View Details
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const req = JSON.parse(this.dataset.req);
                document.getElementById('v_request_id').textContent = '#REQ-' + req.request_id;
                document.getElementById('v_item_name').textContent = req.item_name;
                document.getElementById('v_category').textContent = req.category;
                document.getElementById('v_requested_qty').textContent = req.requested_quantity + ' ' + req.unit;
                document.getElementById('v_required_date').textContent = req.required_date;
                document.getElementById('v_priority').textContent = req.priority;
                document.getElementById('v_purpose').textContent = req.purpose;
                document.getElementById('v_remarks').textContent = req.remarks || 'None';
                document.getElementById('v_request_date').textContent = req.request_date;
                
                const statusEl = document.getElementById('v_status');
                if (req.status === 'PENDING') statusEl.innerHTML = '<span class="badge bg-warning text-dark px-2 py-1">Pending</span>';
                else if (req.status === 'APPROVED') statusEl.innerHTML = '<span class="badge bg-success px-2 py-1">Approved</span>';
                else if (req.status === 'REJECTED') statusEl.innerHTML = '<span class="badge bg-danger px-2 py-1">Rejected</span>';

                const reviewSection = document.getElementById('v_review_section');
                if (req.status !== 'PENDING') {
                    reviewSection.classList.remove('d-none');
                    document.getElementById('v_reviewer').textContent = req.reviewer_name || 'System';
                    document.getElementById('v_review_date').textContent = req.review_date;
                    document.getElementById('v_hod_remarks').textContent = req.remarks || 'None'; // Remarks are populated on rejection
                    
                    if (req.status === 'REJECTED') {
                        document.getElementById('v_hod_remarks').parentElement.classList.remove('d-none');
                    } else {
                        document.getElementById('v_hod_remarks').parentElement.classList.add('d-none');
                    }
                } else {
                    reviewSection.classList.add('d-none');
                }

                new bootstrap.Modal(document.getElementById('viewModal')).show();
            });
        });

        // Edit Modal Open
        const editModal = new bootstrap.Modal(document.getElementById('editModal'));
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const req = JSON.parse(this.dataset.req);
                document.getElementById('edit_request_id').value = req.request_id;
                document.getElementById('edit_item_name').value = req.item_name;
                document.getElementById('edit_requested_qty').value = req.requested_quantity;
                document.getElementById('edit_priority').value = req.priority;
                document.getElementById('edit_required_date').value = req.required_date;
                document.getElementById('edit_purpose').value = req.purpose;
                document.getElementById('edit_remarks').value = req.remarks || '';
                
                editModal.show();
            });
        });

        // Submit Edit
        async function submitEdit(e) {
            e.preventDefault();
            const form = document.getElementById('editForm');
            const formData = new FormData(form);
            
            const submitBtn = document.getElementById('editSubmitBtn');
            const originalHtml = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;

            try {
                const response = await fetch('faculty_request_actions.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                editModal.hide();
                if (result.success) {
                    showAlert('success', '<i class="fas fa-check-circle me-1"></i> ' + result.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('danger', '<i class="fas fa-exclamation-circle me-1"></i> ' + result.message);
                }
            } catch (error) {
                editModal.hide();
                showAlert('danger', 'An error occurred while saving changes.');
            } finally {
                submitBtn.innerHTML = originalHtml;
                submitBtn.disabled = false;
            }
        }

        // Cancel Request
        document.querySelectorAll('.cancel-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                if(!confirm('Are you sure you want to cancel this pending request? This action cannot be undone.')) return;
                
                const originalHtml = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                this.disabled = true;

                const formData = new FormData();
                formData.append('action', 'cancel_request');
                formData.append('request_id', this.dataset.id);

                try {
                    const response = await fetch('faculty_request_actions.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.success) {
                        showAlert('success', '<i class="fas fa-check-circle me-1"></i> ' + result.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert('danger', '<i class="fas fa-exclamation-circle me-1"></i> ' + result.message);
                        this.innerHTML = originalHtml;
                        this.disabled = false;
                    }
                } catch (error) {
                    showAlert('danger', 'An error occurred while cancelling your request.');
                    this.innerHTML = originalHtml;
                    this.disabled = false;
                }
            });
        });
</script>
<?php
$extra_js = ob_get_clean();
include 'includes/footer.php';
?>
