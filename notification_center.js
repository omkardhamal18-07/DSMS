/**
 * Notification Center JS - DSMS
 * Handles real-time auto-refreshing (30s), notification bell dropdowns, 
 * full notification center modal, search, filtering, pagination, and status actions.
 */

document.addEventListener("DOMContentLoaded", function () {
    let currentPage = 1;
    let currentFilter = 'ALL';
    let currentSearch = '';
    let autoRefreshTimer = null;

    // Elements
    const navBadge = document.getElementById('navNotificationBadge');
    const sidebarBadge = document.getElementById('sidebarNotificationBadge');
    const dropdownList = document.getElementById('notificationDropdownList');
    const modalList = document.getElementById('notificationModalList');
    const paginationContainer = document.getElementById('notificationPagination');
    const searchInput = document.getElementById('notificationSearchInput');
    const filterPills = document.querySelectorAll('.notification-filter-pill');
    const markAllReadBtn = document.getElementById('markAllReadBtn');
    const markAllReadDropdownBtn = document.getElementById('markAllReadDropdownBtn');

    // 1. Initial Load & Setup Auto-Refresh
    fetchUnreadCount();
    loadDropdownNotifications();

    autoRefreshTimer = setInterval(function () {
        fetchUnreadCount();
        if (dropdownList && dropdownList.closest('.dropdown-menu').classList.contains('show')) {
            loadDropdownNotifications();
        }
        if (modalList && document.getElementById('notificationCenterModal') && document.getElementById('notificationCenterModal').classList.contains('show')) {
            loadModalNotifications(currentPage);
        }
        // Refresh faculty recent notifications widget if present
        if (document.getElementById('facultyWidgetNotificationList')) {
            loadFacultyWidgetNotifications();
        }
    }, 30000); // 30 seconds

    // 2. Event Listeners for Filter Pills
    filterPills.forEach(pill => {
        pill.addEventListener('click', function (e) {
            e.preventDefault();
            filterPills.forEach(p => p.classList.remove('active', 'btn-primary'));
            filterPills.forEach(p => p.classList.add('btn-outline-primary'));

            this.classList.remove('btn-outline-primary');
            this.classList.add('active', 'btn-primary');

            currentFilter = this.getAttribute('data-filter') || 'ALL';
            currentPage = 1;
            loadModalNotifications(currentPage);
        });
    });

    // 3. Search Input Listener
    if (searchInput) {
        let searchTimeout = null;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function () {
                currentSearch = searchInput.value.trim();
                currentPage = 1;
                loadModalNotifications(currentPage);
            }, 300);
        });
    }

    // 4. Mark All Read Listeners
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function () {
            markAllAsRead();
        });
    }
    if (markAllReadDropdownBtn) {
        markAllReadDropdownBtn.addEventListener('click', function () {
            markAllAsRead();
        });
    }

    // 5. Open Notification Modal Listener
    const openModalTriggers = document.querySelectorAll('[data-bs-target="#notificationCenterModal"]');
    openModalTriggers.forEach(btn => {
        btn.addEventListener('click', function () {
            currentPage = 1;
            loadModalNotifications(currentPage);
        });
    });

    // Helper functions
    function fetchUnreadCount() {
        fetch('notifications_api.php?action=unread_count')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const count = data.unread_count;
                    updateBadgeUI(count);
                }
            })
            .catch(err => console.error('Error fetching unread count:', err));
    }

    function updateBadgeUI(count) {
        if (navBadge) {
            if (count > 0) {
                navBadge.textContent = count > 99 ? '99+' : count;
                navBadge.classList.remove('d-none');
                navBadge.classList.add('badge-pulse');
            } else {
                navBadge.textContent = '0';
                navBadge.classList.add('d-none');
                navBadge.classList.remove('badge-pulse');
            }
        }
        if (sidebarBadge) {
            if (count > 0) {
                sidebarBadge.textContent = count > 99 ? '99+' : count;
                sidebarBadge.classList.remove('d-none');
            } else {
                sidebarBadge.classList.add('d-none');
            }
        }
    }

    function getNotificationIcon(type) {
        switch (type) {
            case 'FACULTY_REQUEST':
                return { icon: 'fa-file-signature', bg: 'bg-primary text-white' };
            case 'LOW_STOCK':
                return { icon: 'fa-triangle-exclamation', bg: 'bg-danger text-white' };
            case 'STOCK_UPDATED':
                return { icon: 'fa-boxes-packing', bg: 'bg-success text-white' };
            case 'REQUEST_STATUS':
                return { icon: 'fa-clock-rotate-left', bg: 'bg-info text-white' };
            default:
                return { icon: 'fa-bell', bg: 'bg-secondary text-white' };
        }
    }

    function renderNotificationHTML(item, isCompact = false) {
        const iconInfo = getNotificationIcon(item.notification_type);
        const isLowStock = (item.notification_type === 'LOW_STOCK');
        const unreadClass = (item.is_read == 0) ? 'unread' : '';
        const lowStockClass = isLowStock ? 'low-stock' : '';

        // Truncate message for compact preview
        let displayMessage = item.message.replace(/\n/g, '<br>');
        if (isCompact && displayMessage.length > 120) {
            displayMessage = displayMessage.substring(0, 117) + '...';
        }

        return `
            <div class="notification-item ${unreadClass} ${lowStockClass} d-flex align-items-start position-relative" data-id="${item.notification_id}">
                <div class="notification-icon-box ${iconInfo.bg} me-3">
                    <i class="fas ${iconInfo.icon}"></i>
                </div>
                <div class="flex-grow-1 pe-3" onclick="handleNotificationClick(${item.notification_id}, '${item.notification_type}', ${item.reference_id})">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="mb-0 fs-6 fw-bold text-gray-800">${escapeHtml(item.title)}</h6>
                        ${isLowStock ? '<span class="badge bg-danger text-uppercase px-2 py-1 me-1" style="font-size:0.65rem;">LOW STOCK</span>' : ''}
                        ${item.is_read == 0 ? '<span class="badge bg-primary rounded-pill me-1" style="font-size:0.6rem;">NEW</span>' : ''}
                    </div>
                    <p class="mb-1 text-muted small text-break">${displayMessage}</p>
                    <small class="text-primary fw-bold" style="font-size: 0.75rem;">
                        <i class="far fa-clock me-1"></i>${item.relative_time}
                    </small>
                </div>
                <div class="notification-actions d-flex flex-column gap-1 ms-2 align-items-center">
                    ${item.is_read == 0 ? `
                    <button class="btn btn-sm text-secondary p-0 btn-toggle-read" title="Mark as Read" onclick="toggleReadStatus(${item.notification_id}, ${item.is_read}, event)">
                        <i class="fas fa-envelope fs-6"></i>
                    </button>
                    ` : `
                    <span class="text-muted"><i class="fas fa-envelope-open fs-6"></i></span>
                    `}
                    <button class="btn btn-sm text-danger p-0 btn-delete-notif" title="Delete" onclick="deleteNotification(${item.notification_id}, event)">
                        <i class="fas fa-trash-alt fs-6"></i>
                    </button>
                </div>
            </div>
        `;
    }

    function loadDropdownNotifications() {
        if (!dropdownList) return;
        dropdownList.style.transition = 'opacity 0.2s ease-in-out';
        dropdownList.style.opacity = '0.5';
        
        fetch('notifications_api.php?action=fetch&limit=5&page=1')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.notifications.length === 0) {
                        dropdownList.innerHTML = `
                            <div class="empty-notification-state p-3 text-center">
                                <i class="fas fa-bell-slash fa-2x text-gray-300 mb-2"></i>
                                <p class="mb-0 text-muted small">No notifications yet.</p>
                            </div>
                        `;
                    } else {
                        let html = '';
                        data.notifications.forEach(item => {
                            html += renderNotificationHTML(item, true);
                        });
                        dropdownList.innerHTML = html;
                    }
                }
                dropdownList.style.opacity = '1';
            })
            .catch(err => {
                console.error('Error loading dropdown notifications:', err);
                dropdownList.style.opacity = '1';
                dropdownList.style.pointerEvents = 'auto';
            });
    }

    function loadModalNotifications(page = 1) {
        if (!modalList) return;
        
        if (modalList.innerHTML.trim() !== '') {
            modalList.style.minHeight = modalList.offsetHeight + 'px';
        }
        
        modalList.style.transition = 'opacity 0.2s ease-in-out';
        modalList.style.opacity = '0.5';
        modalList.style.pointerEvents = 'none';

        const url = `notifications_api.php?action=fetch&page=${page}&limit=10&filter=${encodeURIComponent(currentFilter)}&search=${encodeURIComponent(currentSearch)}`;
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    updateBadgeUI(data.unread_count);
                    if (data.notifications.length === 0) {
                        modalList.innerHTML = `
                            <div class="empty-notification-state py-5 text-center">
                                <i class="fas fa-inbox fa-3x text-gray-300 mb-3"></i>
                                <h6 class="fw-bold text-gray-800">No Notifications Found</h6>
                                <p class="mb-0 text-muted small">There are no notifications matching your current filter criteria.</p>
                            </div>
                        `;
                        if (paginationContainer) paginationContainer.innerHTML = '';
                    } else {
                        let html = '';
                        data.notifications.forEach(item => {
                            html += renderNotificationHTML(item, false);
                        });
                        modalList.innerHTML = html;
                        renderPagination(data.current_page, data.total_pages);
                    }
                }
                modalList.style.opacity = '1';
                modalList.style.pointerEvents = 'auto';
                setTimeout(() => { modalList.style.minHeight = ''; }, 250);
            })
            .catch(err => {
                console.error('Error loading modal notifications:', err);
                modalList.innerHTML = '<div class="text-center py-5 text-danger">Error loading notifications.</div>';
                modalList.style.opacity = '1';
                modalList.style.pointerEvents = 'auto';
                setTimeout(() => { modalList.style.minHeight = ''; }, 250);
            });
    }

    function renderPagination(current, total) {
        if (!paginationContainer || total <= 1) {
            if (paginationContainer) paginationContainer.innerHTML = '';
            return;
        }

        let html = '<ul class="pagination pagination-sm mb-0 justify-content-center">';
        // Prev
        html += `<li class="page-item ${current === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changeNotifPage(${current - 1}); return false;">Previous</a>
        </li>`;

        for (let i = 1; i <= total; i++) {
            html += `<li class="page-item ${i === current ? 'active' : ''}">
                <a class="page-link" href="#" onclick="changeNotifPage(${i}); return false;">${i}</a>
            </li>`;
        }

        // Next
        html += `<li class="page-item ${current === total ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changeNotifPage(${current + 1}); return false;">Next</a>
        </li>`;
        html += '</ul>';

        paginationContainer.innerHTML = html;
    }

    window.changeNotifPage = function (page) {
        currentPage = page;
        loadModalNotifications(currentPage);
    };

    window.toggleReadStatus = function (id, currentStatus, event) {
        if (event) event.stopPropagation();
        if (currentStatus != 0) return; // Only allow Unread -> Read

        const action = 'mark_read';

        const formData = new FormData();
        formData.append('notification_id', id);

        fetch(`notifications_api.php?action=${action}`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                fetchUnreadCount();
                loadDropdownNotifications();
                if (modalList && document.getElementById('notificationCenterModal') && document.getElementById('notificationCenterModal').classList.contains('show')) {
                    loadModalNotifications(currentPage);
                }
            }
        });
    };

    window.deleteNotification = function (id, event) {
        if (event) event.stopPropagation();
        if (!confirm('Are you sure you want to delete this notification?')) return;

        const formData = new FormData();
        formData.append('notification_id', id);

        fetch('notifications_api.php?action=delete', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                fetchUnreadCount();
                loadDropdownNotifications();
                if (modalList) loadModalNotifications(currentPage);
            }
        });
    };

    function markAllAsRead() {
        const btn1 = document.getElementById('markAllReadBtn');
        const btn2 = document.getElementById('markAllReadDropdownBtn');
        if (btn1) btn1.disabled = true;
        if (btn2) btn2.disabled = true;

        fetch('notifications_api.php?action=mark_all_read', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                if (btn1) btn1.disabled = false;
                if (btn2) btn2.disabled = false;
                
                if (data.success) {
                    fetchUnreadCount();
                    loadDropdownNotifications();
                    if (document.getElementById('notificationModalList')) loadModalNotifications(1);
                    // Show small visual feedback
                    const toast = document.createElement('div');
                    toast.className = 'position-fixed bottom-0 end-0 p-3';
                    toast.style.zIndex = '9999';
                    toast.innerHTML = `<div class="toast show align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                                          <div class="d-flex">
                                            <div class="toast-body">All notifications marked as read.</div>
                                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                                          </div>
                                        </div>`;
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 3000);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                if (btn1) btn1.disabled = false;
                if (btn2) btn2.disabled = false;
                console.error('Error marking all as read:', err);
                alert('An error occurred while marking notifications as read.');
            });
    }

    window.handleNotificationClick = function (id, type, referenceId) {
        // First mark as read
        const formData = new FormData();
        formData.append('notification_id', id);
        fetch('notifications_api.php?action=mark_read', { method: 'POST', body: formData })
            .then(() => {
                fetchUnreadCount();
                loadDropdownNotifications();
            });

        if (type === 'FACULTY_REQUEST' && referenceId) {
            openFacultyRequestDetailModal(referenceId);
        } else if (type === 'LOW_STOCK') {
            window.location.href = 'inventory.php';
        }
    };

    function openFacultyRequestDetailModal(requestId) {
        const modalElem = document.getElementById('requestDetailModal');
        if (!modalElem) return;

        const modalObj = new bootstrap.Modal(modalElem);
        const bodyElem = document.getElementById('requestDetailModalBody');
        bodyElem.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
        modalObj.show();

        fetch(`notifications_api.php?action=get_request_details&request_id=${requestId}`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const req = res.data;
                    const statusBadge = req.status === 'APPROVED' ? '<span class="badge bg-success fs-6">APPROVED</span>' :
                                      (req.status === 'REJECTED' ? '<span class="badge bg-danger fs-6">REJECTED</span>' :
                                      '<span class="badge bg-warning text-dark fs-6">PENDING</span>');

                    let adminActionsHtml = '';
                    if (req.status === 'PENDING' && document.body.getAttribute('data-role') === 'ADMIN') {
                        adminActionsHtml = `
                            <hr>
                            <div class="d-flex justify-content-end gap-2 mt-3">
                                <button class="btn btn-success" onclick="processRequestFromModal(${req.request_id}, 'approve')"><i class="fas fa-check me-1"></i> Approve Request</button>
                                <button class="btn btn-danger" onclick="processRequestFromModal(${req.request_id}, 'reject')"><i class="fas fa-times me-1"></i> Reject Request</button>
                            </div>
                        `;
                    }

                    bodyElem.innerHTML = `
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase mb-0">Request ID</label>
                                <div class="fs-5 fw-bold text-primary">#REQ-${String(req.request_id).padStart(4, '0')}</div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <label class="text-muted small fw-bold text-uppercase mb-0">Status</label>
                                <div>${statusBadge}</div>
                            </div>
                            <div class="col-md-6 border-top pt-2">
                                <label class="text-muted small fw-bold text-uppercase mb-0">Faculty Name</label>
                                <div class="fw-bold text-gray-800">${escapeHtml(req.faculty_name)}</div>
                            </div>
                            <div class="col-md-6 border-top pt-2">
                                <label class="text-muted small fw-bold text-uppercase mb-0">Faculty ID & Dept</label>
                                <div class="text-gray-800">#FAC-${String(req.faculty_id).padStart(4, '0')} (${escapeHtml(req.department || 'General')})</div>
                            </div>
                            <div class="col-md-6 border-top pt-2">
                                <label class="text-muted small fw-bold text-uppercase mb-0">Requested Item</label>
                                <div class="fw-bold text-gray-800">${escapeHtml(req.item_name)} (${escapeHtml(req.category)})</div>
                            </div>
                            <div class="col-md-6 border-top pt-2">
                                <label class="text-muted small fw-bold text-uppercase mb-0">Requested Qty / Available</label>
                                <div class="fw-bold text-gray-800">${req.requested_quantity} ${escapeHtml(req.unit)} / <span class="${req.quantity_available < req.requested_quantity ? 'text-danger' : 'text-success'}">${req.quantity_available} available</span></div>
                            </div>
                            <div class="col-12 border-top pt-2">
                                <label class="text-muted small fw-bold text-uppercase mb-0">Submission Date & Time</label>
                                <div class="text-gray-800">${req.formatted_date}</div>
                            </div>
                            ${req.remarks ? `
                            <div class="col-12 border-top pt-2">
                                <label class="text-muted small fw-bold text-uppercase mb-0">Remarks / Description</label>
                                <div class="bg-light p-2 rounded text-gray-800">${escapeHtml(req.remarks)}</div>
                            </div>` : ''}
                        </div>
                        ${adminActionsHtml}
                    `;
                } else {
                    bodyElem.innerHTML = `<div class="text-danger py-3 text-center">${res.message}</div>`;
                }
            });
    }

    window.processRequestFromModal = function(requestId, actionType) {
        if (actionType === 'approve') {
            if (!confirm('Approve this stationery request?')) return;
            const fd = new FormData();
            fd.append('action', 'approve_request');
            fd.append('request_id', requestId);
            fetch('request_actions.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    alert(res.message);
                    if (res.success) {
                        const modalElem = document.getElementById('requestDetailModal');
                        const modalObj = bootstrap.Modal.getInstance(modalElem);
                        if (modalObj) modalObj.hide();
                        location.reload();
                    }
                });
        } else if (actionType === 'reject') {
            const reason = prompt('Enter rejection reason (Mandatory):');
            if (!reason || !reason.trim()) {
                alert('Rejection reason is required.');
                return;
            }
            const fd = new FormData();
            fd.append('action', 'reject_request');
            fd.append('request_id', requestId);
            fd.append('remarks', reason.trim());
            fetch('request_actions.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    alert(res.message);
                    if (res.success) {
                        const modalElem = document.getElementById('requestDetailModal');
                        const modalObj = bootstrap.Modal.getInstance(modalElem);
                        if (modalObj) modalObj.hide();
                        location.reload();
                    }
                });
        }
    };

    // Faculty New Request Form Submission
    const newRequestForm = document.getElementById('newRequestForm');
    if (newRequestForm) {
        newRequestForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const submitBtn = newRequestForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';

            const formData = new FormData(newRequestForm);
            formData.append('action', 'submit_faculty_request');

            fetch('notifications_api.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Submit Request';

                if (data.success) {
                    alert('Request submitted successfully!');
                    newRequestForm.reset();
                    const modalElem = document.getElementById('newRequestModal');
                    if (modalElem) {
                        const modalObj = bootstrap.Modal.getInstance(modalElem);
                        if (modalObj) modalObj.hide();
                    }
                    location.reload();
                } else {
                    alert(data.message || 'Error submitting request.');
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Submit Request';
                alert('Server error. Please try again.');
            });
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
