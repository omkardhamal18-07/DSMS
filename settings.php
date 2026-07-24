<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include("database/db.php");
$page_title = 'Settings - DSMS';
include 'includes/header.php';
?>

<div class="container-fluid px-0">
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-cog me-2"></i> Theme Settings</h6>
        </div>
        <div class="card-body">
            <div class="row text-center g-3">
                <div class="col-md-4">
                    <div class="theme-option p-3 border rounded-3 cursor-pointer" data-theme="light" id="theme-light">
                        <i class="fas fa-sun fa-2x mb-2 text-warning"></i>
                        <h6 class="mb-0">Light</h6>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="theme-option p-3 border rounded-3 cursor-pointer" data-theme="dark" id="theme-dark">
                        <i class="fas fa-moon fa-2x mb-2 text-secondary"></i>
                        <h6 class="mb-0">Dark</h6>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="theme-option p-3 border rounded-3 cursor-pointer" data-theme="blue" id="theme-blue">
                        <i class="fas fa-tint fa-2x mb-2 text-primary"></i>
                        <h6 class="mb-0">Blue</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const currentTheme = ThemeEngine.getCurrentTheme();
    const options = document.querySelectorAll('.theme-option');
    
    const activeOption = document.getElementById(`theme-${currentTheme}`);
    if (activeOption) activeOption.classList.add('active');

    options.forEach(option => {
        option.addEventListener('click', function() {
            const selectedTheme = this.getAttribute('data-theme');
            options.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
            ThemeEngine.setTheme(selectedTheme);
        });
    });
});
</script>
<style>
    .theme-option { cursor: pointer; transition: all 0.2s; }
    .theme-option:hover { border-color: var(--theme-primary) !important; background-color: rgba(0,0,0,0.02); }
    .theme-option.active { border-color: var(--theme-primary) !important; border-width: 2px !important; box-shadow: 0 0 0 0.2rem rgba(78,115,223,0.25); }
</style>
<?php
$extra_js = ob_get_clean();
include 'includes/footer.php';
?>
