<?php
// includes/footer.php
?>
        </div> <!-- End of Page Content -->
    </div> <!-- End of Wrapper -->

    <!-- Notification Center Modal -->
    <div class="modal fade" id="notificationCenterModal" tabindex="-1" aria-labelledby="notificationCenterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow rounded-3">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold" id="notificationCenterModalLabel"><i class="fas fa-bell me-2"></i> Notification Center</h5>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-light rounded-pill px-3" id="markAllReadBtn" onclick="void(0)"><i class="fas fa-check-double me-1"></i> Mark All as Read</button>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-0">
                    <!-- Filters & Search Bar -->
                    <div class="p-3 bg-light border-bottom">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                    <input type="text" id="notificationSearchInput" class="form-control border-start-0 ps-0" placeholder="Search notifications by keyword...">
                                </div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="btn-group flex-wrap" role="group" aria-label="Notification Filters">
                                    <button type="button" class="btn btn-sm btn-primary active notification-filter-pill" data-filter="ALL" onclick="void(0)">All</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary notification-filter-pill" data-filter="FACULTY_REQUEST" onclick="void(0)">Requests</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary notification-filter-pill" data-filter="LOW_STOCK" onclick="void(0)">Low Stock</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary notification-filter-pill" data-filter="STOCK_UPDATED" onclick="void(0)">Stock Updated</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Notification Feed List -->
                    <div id="notificationModalList" class="p-2" style="max-height: 480px; overflow-y: auto;">
                        <div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>
                    </div>
                </div>
                <div class="modal-footer bg-light justify-content-between py-2">
                    <small class="text-muted"><i class="fas fa-sync-alt me-1"></i> Auto-refreshes every 30 seconds</small>
                    <div id="notificationPagination"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Detail Modal (Triggered when clicking a Faculty Request notification) -->
    <div class="modal fade" id="requestDetailModal" tabindex="-1" aria-labelledby="requestDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow rounded-3">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="requestDetailModalLabel"><i class="fas fa-file-alt me-2"></i> Faculty Request Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="requestDetailModalBody">
                    <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js (Optional but good to have globally if used frequently, or included specifically on pages) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Admin Script for Charts and global logic -->
    <script src="admin_script.js"></script>
    
    <!-- Custom JS -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const sidebarCollapse = document.getElementById('sidebarCollapse');
            const sidebar = document.getElementById('sidebar');

            if (sidebarCollapse && sidebar) {
                // Remove inline listener if added elsewhere, and use this central one
                sidebarCollapse.addEventListener('click', function (e) {
                    sidebar.classList.toggle('active');
                });
            }
        });
    </script>
    <script src="notification_center.js?v=3"></script>
    <script src="under_development.js?v=4"></script>
    <?php if(isset($extra_js)) echo $extra_js; ?>
</body>
</html>
