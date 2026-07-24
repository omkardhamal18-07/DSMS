<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: login.php");
    exit();
}
include("database/db.php");

$active_tab = $_GET['tab'] ?? 'pending';

// Fetch Pending Requests
$pending_query = "SELECT r.request_id, r.request_date, r.review_date, u.name as faculty_name, u.department, u.email, s.item_name, s.quantity_available, r.requested_quantity, r.status, s.stationery_id, r.faculty_id
FROM stationery_requests r
JOIN users u ON r.faculty_id = u.user_id
JOIN stationery s ON r.stationery_id = s.stationery_id
WHERE r.status = 'APPROVED'
ORDER BY r.review_date DESC";
$pending_res = $conn->query($pending_query);
$pending_requests = [];
while($row = $pending_res->fetch_assoc()){
    $pending_requests[] = $row;
}

// Fetch Issue History
$history_query = "SELECT ir.issue_id, ir.issue_source, ir.request_id, u.name as faculty_name, u.department, ir.issue_date, ir.status, ir.remarks, 
(SELECT SUM(issued_quantity) FROM issue_items WHERE issue_id = ir.issue_id) as total_qty, 
(SELECT COUNT(*) FROM issue_items WHERE issue_id = ir.issue_id) as num_items, 
u2.name as issued_by_name
FROM issue_records ir
JOIN users u ON ir.faculty_id = u.user_id
JOIN users u2 ON ir.issued_by = u2.user_id
ORDER BY ir.issue_date DESC";
$history_res = $conn->query($history_query);
$issue_history = [];
while($row = $history_res->fetch_assoc()){
    $issue_history[] = $row;
}

// Fetch Faculties for Direct Issue
$faculty_res = $conn->query("SELECT user_id, name, department FROM users WHERE role = 'FACULTY' ORDER BY name ASC");
$faculties = [];
while($row = $faculty_res->fetch_assoc()){
    $faculties[] = $row;
}

// Fetch Items for Direct Issue
$items_res = $conn->query("SELECT stationery_id, item_name, category, quantity_available FROM stationery WHERE quantity_available > 0 ORDER BY item_name ASC");
$stationery_items = [];
while($row = $items_res->fetch_assoc()){
    $stationery_items[] = $row;
}
$items_json = json_encode($stationery_items);

// Unread notifications count
$pending_count = $conn->query("SELECT COUNT(*) as c FROM stationery_requests WHERE status = 'PENDING'")->fetch_assoc()['c'];
?>

<?php
$page_title = 'Issue Stationery - DSMS';
ob_start();
?>
<style>
        .nav-tabs .nav-link {
            color: #4e73df;
            font-weight: bold;
        }
        .nav-tabs .nav-link.active {
            color: #fff;
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .table-responsive { overflow-x: auto; }
        .item-row { transition: all 0.2s; }
        .item-row:hover { background-color: #f8f9fc; }
    </style>
<?php
$extra_css = ob_get_clean();
include 'includes/header.php';
?>
<div class="container-fluid px-0">
                <div id="alertPlaceholder"></div>

                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4" id="issueTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $active_tab=='pending'?'active':''; ?>" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="true"><i class="fas fa-clipboard-list me-1"></i> Pending Issues</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $active_tab=='direct'?'active':''; ?>" id="direct-tab" data-bs-toggle="tab" data-bs-target="#direct" type="button" role="tab" aria-controls="direct" aria-selected="false"><i class="fas fa-bolt me-1"></i> New Direct Issue</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $active_tab=='history'?'active':''; ?>" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab" aria-controls="history" aria-selected="false"><i class="fas fa-history me-1"></i> Issue History</button>
                    </li>
                </ul>

                <div class="tab-content" id="issueTabsContent">
                    
                    <!-- PENDING ISSUES TAB -->
                    <div class="tab-pane fade <?php echo $active_tab=='pending'?'show active':''; ?>" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                        <div class="card shadow-sm border-0 rounded-3 mb-4">
                            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">Approved Requests Awaiting Issue</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light text-muted">
                                            <tr>
                                                <th class="ps-3">Req ID</th>
                                                <th>Faculty Name</th>
                                                <th>Department</th>
                                                <th>Request Date</th>
                                                <th>Approved Date</th>
                                                <th>Total Items</th>
                                                <th>Total Qty</th>
                                                <th>Status</th>
                                                <th class="pe-3 text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(empty($pending_requests)): ?>
                                                <tr><td colspan="9" class="text-center py-4 text-muted">No pending issues to process.</td></tr>
                                            <?php else: ?>
                                                <?php foreach($pending_requests as $req): ?>
                                                <tr>
                                                    <td class="ps-3 fw-bold text-secondary">#REQ-<?php echo $req['request_id']; ?></td>
                                                    <td class="fw-bold text-gray-800"><?php echo htmlspecialchars($req['faculty_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($req['department'] ?? 'N/A'); ?></td>
                                                    <td class="text-muted small"><?php echo date('M d, Y', strtotime($req['request_date'])); ?></td>
                                                    <td class="text-muted small"><?php echo $req['review_date'] ? date('M d, Y', strtotime($req['review_date'])) : '-'; ?></td>
                                                    <td>1</td> <!-- As per schema, 1 item per request -->
                                                    <td><?php echo $req['requested_quantity']; ?></td>
                                                    <td><span class="badge bg-warning text-dark px-2 py-1">Ready to Issue</span></td>
                                                    <td class="pe-3 text-end">
                                                        <button class="btn btn-sm btn-primary" onclick='openIssueModal(<?php echo json_encode($req); ?>)'><i class="fas fa-box-open me-1"></i> Issue</button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- NEW DIRECT ISSUE TAB -->
                    <div class="tab-pane fade <?php echo $active_tab=='direct'?'show active':''; ?>" id="direct" role="tabpanel" aria-labelledby="direct-tab">
                        <div class="card shadow-sm border-0 rounded-3 mb-4">
                            <div class="card-header bg-white border-0 py-3">
                                <h6 class="m-0 fw-bold text-primary">Direct Issue Form</h6>
                            </div>
                            <div class="card-body">
                                <form id="directIssueForm" onsubmit="submitDirectIssue(event)">
                                    <input type="hidden" name="action" value="issue_direct">
                                    <div class="row mb-4">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold">Select Faculty *</label>
                                            <select name="faculty_id" id="direct_faculty" class="form-select" required onchange="autoFillDepartment()">
                                                <option value="">-- Select Faculty --</option>
                                                <?php foreach($faculties as $fac): ?>
                                                    <option value="<?php echo $fac['user_id']; ?>" data-dept="<?php echo htmlspecialchars($fac['department']); ?>"><?php echo htmlspecialchars($fac['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold">Department</label>
                                            <input type="text" id="direct_department" class="form-control bg-light" readonly>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold">Issue Date *</label>
                                            <input type="date" name="issue_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>

                                    <h6 class="fw-bold text-gray-800 mb-3 border-bottom pb-2">Item Selection</h6>
                                    <div class="table-responsive mb-3">
                                        <table class="table table-bordered align-middle" id="itemsTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th width="50%">Item Name</th>
                                                    <th width="25%">Available Stock</th>
                                                    <th width="25%">Quantity to Issue</th>
                                                </tr>
                                            </thead>
                                            <tbody id="itemsContainer">
                                                <tr class="item-row">
                                                    <td>
                                                        <select class="form-select item-select" name="items[0][id]" required onchange="updateRowStock(this)">
                                                            <option value="">-- Select Item --</option>
                                                            <?php foreach($stationery_items as $item): ?>
                                                                <option value="<?php echo $item['stationery_id']; ?>" data-stock="<?php echo $item['quantity_available']; ?>"><?php echo htmlspecialchars($item['item_name']) . " (" . htmlspecialchars($item['category']) . ")"; ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </td>
                                                    <td><input type="text" class="form-control bg-light text-center stock-display" readonly value="-"></td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <input type="number" class="form-control text-center qty-input" name="items[0][qty]" min="1" required oninput="validateRowQty(this)">
                                                            <button type="button" class="btn btn-sm text-danger remove-btn p-0" onclick="removeRow(this)" style="display: none;" title="Remove Item"><i class="fas fa-times fs-5"></i></button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="mb-4">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()"><i class="fas fa-plus me-1"></i> Add Another Item</button>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Remarks</label>
                                        <textarea name="remarks" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('directIssueForm').reset(); resetDirectForm();">Clear</button>
                                        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-paper-plane me-1"></i> Submit Direct Issue</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- ISSUE HISTORY TAB -->
                    <div class="tab-pane fade <?php echo $active_tab=='history'?'show active':''; ?>" id="history" role="tabpanel" aria-labelledby="history-tab">
                        <div class="card shadow-sm border-0 rounded-3 mb-4">
                            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">Issue History</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light text-muted">
                                            <tr>
                                                <th class="ps-3">Issue ID</th>
                                                <th>Source</th>
                                                <th>Req ID</th>
                                                <th>Faculty Name</th>
                                                <th>Num Items</th>
                                                <th>Total Qty</th>
                                                <th>Issued By</th>
                                                <th>Issue Date</th>
                                                <th class="pe-3 text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(empty($issue_history)): ?>
                                                <tr><td colspan="9" class="text-center py-4 text-muted">No issue history found.</td></tr>
                                            <?php else: ?>
                                                <?php foreach($issue_history as $hist): ?>
                                                <tr>
                                                    <td class="ps-3 fw-bold text-secondary">#ISS-<?php echo $hist['issue_id']; ?></td>
                                                    <td>
                                                        <?php if($hist['issue_source'] == 'REQUEST'): ?>
                                                            <span class="badge bg-info text-dark">Request</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Direct</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-muted"><?php echo $hist['request_id'] ? '#REQ-'.$hist['request_id'] : '-'; ?></td>
                                                    <td class="fw-bold text-gray-800"><?php echo htmlspecialchars($hist['faculty_name']); ?></td>
                                                    <td><?php echo $hist['num_items']; ?></td>
                                                    <td><?php echo $hist['total_qty']; ?></td>
                                                    <td><?php echo htmlspecialchars($hist['issued_by_name']); ?></td>
                                                    <td class="text-muted small"><?php echo date('M d, Y h:i A', strtotime($hist['issue_date'])); ?></td>
                                                    <td class="pe-3 text-end">
                                                        <button class="btn btn-sm btn-outline-primary" onclick="viewIssueDetails(<?php echo $hist['issue_id']; ?>)"><i class="fas fa-eye me-1"></i> View</button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- End Tab Content -->
            </div>
        </div>
    </div>

    <!-- Modals -->

    <!-- Request-Based Issue Modal -->
    <div class="modal fade" id="requestIssueModal" tabindex="-1" aria-labelledby="requestIssueModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <form id="requestIssueForm" onsubmit="submitRequestIssue(event)">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="requestIssueModalLabel"><i class="fas fa-dolly me-2"></i> Issue Stationery</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" name="action" value="issue_request">
                        <input type="hidden" name="request_id" id="modal_req_id">
                        
                        <div class="row mb-4">
                            <div class="col-md-6 border-end">
                                <h6 class="text-muted text-uppercase small fw-bold mb-3">Faculty Details</h6>
                                <p class="mb-1 fw-bold text-gray-800" id="modal_fac_name"></p>
                                <p class="mb-1 text-muted small"><i class="fas fa-building me-1"></i> <span id="modal_fac_dept"></span></p>
                                <p class="mb-0 text-muted small"><i class="fas fa-envelope me-1"></i> <span id="modal_fac_email"></span></p>
                            </div>
                            <div class="col-md-6 ps-4">
                                <h6 class="text-muted text-uppercase small fw-bold mb-3">Request Details</h6>
                                <p class="mb-1"><span class="text-muted">Request ID:</span> <span class="fw-bold" id="modal_req_id_display"></span></p>
                                <p class="mb-1"><span class="text-muted">Requested On:</span> <span id="modal_req_date"></span></p>
                                <p class="mb-0"><span class="text-muted">Approved On:</span> <span id="modal_app_date"></span></p>
                            </div>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item Name</th>
                                        <th class="text-center">Requested Qty</th>
                                        <th class="text-center">Available Stock</th>
                                        <th class="text-center" width="20%">Issue Quantity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-bold" id="modal_item_name"></td>
                                        <td class="text-center fw-bold text-primary fs-5" id="modal_req_qty"></td>
                                        <td class="text-center fw-bold fs-5" id="modal_stock_qty"></td>
                                        <td>
                                            <input type="number" name="issue_quantity" id="modal_issue_qty" class="form-control text-center fw-bold" min="1" required oninput="validateModalQty()">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div id="modal_stock_warning" class="text-danger small fw-bold mt-1" style="display: none;"><i class="fas fa-exclamation-triangle"></i> Insufficient stock! Quantity adjusted to maximum available.</div>
                            <div id="modal_stock_remaining" class="text-muted small mt-1 text-end">Remaining Stock after issue: <span id="modal_remaining_display" class="fw-bold">0</span></div>
                        </div>

                        <div>
                            <label class="form-label fw-bold text-muted small">Remarks (Optional)</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Any comments..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success px-4" id="btn_issue_submit"><i class="fas fa-check-circle me-1"></i> Issue Stationery</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Issue Details Modal -->
    <div class="modal fade" id="viewIssueModal" tabindex="-1" aria-labelledby="viewIssueModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="viewIssueModalLabel"><i class="fas fa-file-invoice me-2"></i> Issue Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="viewIssueBody">
                    <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="fas fa-print me-1"></i> Print</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>

<?php ob_start(); ?>
<script>
const allItems = <?php echo $items_json; ?>;
        
        // --- Sidebar Toggle ---
        document.getElementById('sidebarCollapse').addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('content').classList.toggle('active');
        });

        // --- Alert System ---
        function showAlert(type, message) {
            const alertHtml = `<div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
            document.getElementById('alertPlaceholder').innerHTML = alertHtml;
            window.scrollTo(0,0);
        }

        // --- Request-Based Issue Logic ---
        const requestModal = new bootstrap.Modal(document.getElementById('requestIssueModal'));
        let currentModalStock = 0;

        function openIssueModal(req) {
            document.getElementById('modal_req_id').value = req.request_id;
            document.getElementById('modal_fac_name').innerText = req.faculty_name;
            document.getElementById('modal_fac_dept').innerText = req.department || 'N/A';
            document.getElementById('modal_fac_email').innerText = req.email;
            document.getElementById('modal_req_id_display').innerText = '#REQ-' + req.request_id;
            document.getElementById('modal_req_date').innerText = req.request_date.substring(0,10);
            document.getElementById('modal_app_date').innerText = req.review_date ? req.review_date.substring(0,10) : '-';
            
            document.getElementById('modal_item_name').innerText = req.item_name;
            document.getElementById('modal_req_qty').innerText = req.requested_quantity;
            
            currentModalStock = parseInt(req.quantity_available);
            
            const stockTd = document.getElementById('modal_stock_qty');
            stockTd.innerText = currentModalStock;
            if(currentModalStock == 0) stockTd.className = "text-center fw-bold fs-5 text-danger";
            else if(currentModalStock < parseInt(req.requested_quantity)) stockTd.className = "text-center fw-bold fs-5 text-warning";
            else stockTd.className = "text-center fw-bold fs-5 text-success";
            
            let initialQty = Math.min(parseInt(req.requested_quantity), currentModalStock);
            document.getElementById('modal_issue_qty').value = initialQty;
            document.getElementById('modal_issue_qty').max = currentModalStock;
            
            if(currentModalStock == 0) {
                document.getElementById('modal_issue_qty').disabled = true;
                document.getElementById('btn_issue_submit').disabled = true;
            } else {
                document.getElementById('modal_issue_qty').disabled = false;
                document.getElementById('btn_issue_submit').disabled = false;
            }

            validateModalQty();
            requestModal.show();
        }

        function validateModalQty() {
            const input = document.getElementById('modal_issue_qty');
            const warning = document.getElementById('modal_stock_warning');
            const remaining = document.getElementById('modal_remaining_display');
            let val = parseInt(input.value) || 0;
            
            if (val > currentModalStock) {
                warning.style.display = 'block';
                input.value = currentModalStock;
                val = currentModalStock;
            } else {
                warning.style.display = 'none';
            }
            remaining.innerText = currentModalStock - val;
        }

        async function submitRequestIssue(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const btn = document.getElementById('btn_issue_submit');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            btn.disabled = true;

            try {
                const res = await fetch('issue_actions.php', { method: 'POST', body: formData });
                const data = await res.json();
                requestModal.hide();
                if(data.success) {
                    showAlert('success', data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('danger', data.message);
                }
            } catch (err) {
                requestModal.hide();
                showAlert('danger', 'Error processing request.');
            }
        }

        // --- Direct Issue Logic ---
        function autoFillDepartment() {
            const select = document.getElementById('direct_faculty');
            const deptInput = document.getElementById('direct_department');
            if(select.selectedIndex > 0) {
                deptInput.value = select.options[select.selectedIndex].getAttribute('data-dept');
            } else {
                deptInput.value = '';
            }
        }

        let rowCount = 1;
        
        function updateSelectOptions() {
            const selects = document.querySelectorAll('.item-select');
            const selectedValues = Array.from(selects).map(s => s.value).filter(v => v !== "");
            
            selects.forEach(select => {
                const currentValue = select.value;
                Array.from(select.options).forEach(option => {
                    if (option.value === "") return;
                    if (selectedValues.includes(option.value) && option.value !== currentValue) {
                        option.disabled = true;
                    } else {
                        option.disabled = false;
                    }
                });
            });
        }

        function addRow() {
            const container = document.getElementById('itemsContainer');
            const tr = document.createElement('tr');
            tr.className = 'item-row';
            
            let options = '<option value="">-- Select Item --</option>';
            allItems.forEach(item => {
                options += `<option value="${item.stationery_id}" data-stock="${item.quantity_available}">${item.item_name} (${item.category})</option>`;
            });

            tr.innerHTML = `
                <td>
                    <select class="form-select item-select" name="items[${rowCount}][id]" required onchange="updateRowStock(this)">
                        ${options}
                    </select>
                </td>
                <td><input type="text" class="form-control bg-light text-center stock-display" readonly value="-"></td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <input type="number" class="form-control text-center qty-input" name="items[${rowCount}][qty]" min="1" required oninput="validateRowQty(this)">
                        <button type="button" class="btn btn-sm text-danger remove-btn p-0" onclick="removeRow(this)" title="Remove Item"><i class="fas fa-times fs-5"></i></button>
                    </div>
                </td>
            `;
            container.appendChild(tr);
            updateSelectOptions();
            updateRemoveButtons();
            rowCount++;
        }

        function removeRow(btn) {
            btn.closest('tr').remove();
            updateSelectOptions();
            updateRemoveButtons();
        }

        function updateRemoveButtons() {
            const btns = document.querySelectorAll('.remove-btn');
            if(btns.length === 1) {
                btns[0].style.display = 'none';
            } else {
                btns.forEach(b => b.style.display = 'inline-block');
            }
        }

        function updateRowStock(select) {
            const tr = select.closest('tr');
            const stockDisplay = tr.querySelector('.stock-display');
            const qtyInput = tr.querySelector('.qty-input');
            
            if(select.selectedIndex > 0) {
                const stock = select.options[select.selectedIndex].getAttribute('data-stock');
                stockDisplay.value = stock;
                qtyInput.max = stock;
                validateRowQty(qtyInput);
            } else {
                stockDisplay.value = '-';
                qtyInput.removeAttribute('max');
            }
            updateSelectOptions();
        }

        function validateRowQty(input) {
            const max = parseInt(input.max);
            let val = parseInt(input.value);
            if(max !== undefined && !isNaN(max) && val > max) {
                input.value = max;
                input.classList.add('is-invalid');
                setTimeout(() => input.classList.remove('is-invalid'), 1000);
            }
        }

        function resetDirectForm() {
            document.getElementById('direct_department').value = '';
            document.getElementById('itemsContainer').innerHTML = `
                <tr class="item-row">
                    <td>
                        <select class="form-select item-select" name="items[0][id]" required onchange="updateRowStock(this)">
                            <option value="">-- Select Item --</option>
                            ${allItems.map(i => `<option value="${i.stationery_id}" data-stock="${i.quantity_available}">${i.item_name} (${i.category})</option>`).join('')}
                        </select>
                    </td>
                    <td><input type="text" class="form-control bg-light text-center stock-display" readonly value="-"></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" class="form-control text-center qty-input" name="items[0][qty]" min="1" required oninput="validateRowQty(this)">
                            <button type="button" class="btn btn-sm text-danger remove-btn p-0" onclick="removeRow(this)" style="display: none;" title="Remove Item"><i class="fas fa-times fs-5"></i></button>
                        </div>
                    </td>
                </tr>
            `;
            rowCount = 1;
            updateSelectOptions();
            updateRemoveButtons();
        }

        async function submitDirectIssue(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const btn = form.querySelector('button[type="submit"]');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            btn.disabled = true;

            try {
                const res = await fetch('issue_actions.php', { method: 'POST', body: formData });
                const data = await res.json();
                if(data.success) {
                    showAlert('success', data.message);
                    resetDirectForm();
                    // Optional: switch back to history tab
                    setTimeout(() => { window.location.href = 'issue_stationery.php?tab=history'; }, 1500);
                } else {
                    showAlert('danger', data.message);
                    btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Submit Direct Issue';
                    btn.disabled = false;
                }
            } catch(err) {
                showAlert('danger', 'Submission failed.');
                btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Submit Direct Issue';
                btn.disabled = false;
            }
        }

        // --- View History Details Logic ---
        const viewModal = new bootstrap.Modal(document.getElementById('viewIssueModal'));
        
        async function viewIssueDetails(issue_id) {
            const body = document.getElementById('viewIssueBody');
            body.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>';
            viewModal.show();

            const fd = new FormData();
            fd.append('action', 'get_issue_details');
            fd.append('issue_id', issue_id);

            try {
                const res = await fetch('issue_actions.php', { method: 'POST', body: fd });
                const data = await res.json();
                if(data.success) {
                    const r = data.record;
                    const items = data.items;
                    
                    let html = `
                        <div class="row mb-4">
                            <div class="col-sm-6">
                                <h6 class="text-muted text-uppercase small fw-bold">Issue Information</h6>
                                <p class="mb-1 fw-bold fs-5">#ISS-${r.issue_id}</p>
                                <p class="mb-1"><span class="text-muted">Source:</span> ${r.issue_source === 'REQUEST' ? '<span class="badge bg-info text-dark">Request ('+ (r.request_id?'#REQ-'+r.request_id:'-') +')</span>' : '<span class="badge bg-secondary">Direct</span>'}</p>
                                <p class="mb-1"><span class="text-muted">Date:</span> ${r.issue_date}</p>
                                <p class="mb-0"><span class="text-muted">Issued By:</span> ${r.issued_by_name}</p>
                            </div>
                            <div class="col-sm-6">
                                <h6 class="text-muted text-uppercase small fw-bold">Recipient</h6>
                                <p class="mb-1 fw-bold text-gray-800">${r.faculty_name}</p>
                                <p class="mb-1 text-muted small"><i class="fas fa-building me-1"></i> ${r.department}</p>
                            </div>
                        </div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-3">Issued Items</h6>
                        <table class="table table-bordered align-middle">
                            <thead class="table-light"><tr><th>Item Name</th><th class="text-center">Quantity</th></tr></thead>
                            <tbody>
                    `;
                    let total = 0;
                    items.forEach(i => {
                        html += `<tr><td>${i.item_name}</td><td class="text-center fw-bold">${i.issued_quantity}</td></tr>`;
                        total += parseInt(i.issued_quantity);
                    });
                    html += `
                            </tbody>
                            <tfoot class="table-light"><tr><th class="text-end">Total</th><th class="text-center fs-5">${total}</th></tr></tfoot>
                        </table>
                        <div class="mt-4 p-3 bg-light rounded">
                            <h6 class="text-muted text-uppercase small fw-bold mb-1">Remarks</h6>
                            <p class="mb-0">${r.remarks || '<em class="text-muted">No remarks</em>'}</p>
                        </div>
                    `;
                    body.innerHTML = html;
                } else {
                    body.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            } catch(err) {
                body.innerHTML = `<div class="alert alert-danger">Error fetching details.</div>`;
            }
        }
</script>
<?php
$extra_js = ob_get_clean();
include 'includes/footer.php';
?>
