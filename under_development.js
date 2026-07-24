document.addEventListener("DOMContentLoaded", function () {
    const modalHtml = `
    <div class="modal fade" id="underDevModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-3">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title fw-bold">Under Development</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <p class="text-gray-800 fs-5 mb-2">This feature is currently under development and is planned for a future sprint.</p>
                    <p class="text-muted mb-0">Thank you for your patience.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-primary px-4 rounded-pill" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const underDevModal = new bootstrap.Modal(document.getElementById('underDevModal'));

    document.body.addEventListener('click', function(e) {
        const target = e.target.closest('a, button');
        if (!target) return;

        // Ignore if it's already functional based on classes/attributes
        if (target.hasAttribute('data-bs-toggle') || target.hasAttribute('data-bs-dismiss') || target.id === 'sidebarCollapse' || target.hasAttribute('onclick')) return;
        if (target.classList.contains('view-btn') || target.classList.contains('approve-btn') || target.classList.contains('reject-btn') || target.classList.contains('btn-close') || target.classList.contains('dropdown-item') || target.classList.contains('notification-filter-pill') || target.id === 'markAllReadBtn' || target.id === 'markAllReadDropdownBtn') {
            return;
        }

        let shouldShowPopup = false;

        if (target.tagName === 'A') {
            const href = target.getAttribute('href');
            // If the link explicitly goes to # and is not meant to toggle a bootstrap component
            if (href === '#' || href === '' || href === null) {
                shouldShowPopup = true;
            }
        } else if (target.tagName === 'BUTTON') {
            // If button is inside a form, it submits the form.
            if (target.closest('form')) return;
            // Otherwise it's an action button. If it's not a functional button:
            // Assuming all other standalone buttons are unimplemented mockups.
            shouldShowPopup = true;
        }

        if (shouldShowPopup) {
            e.preventDefault();
            underDevModal.show();
        }
    });
});
