<?php
$files = ['issue_stationery.php', 'my_requests.php', 'new_request.php', 'request_history.php', 'stock_history.php', 'settings.php'];

foreach ($files as $f) {
    if (!file_exists($f)) continue;
    $c = file_get_contents($f);
    
    // 1. Replace header
    $c = preg_replace('/<!DOCTYPE html>.*?<\?php include \'includes\/navbar\.php\'; \?>/is', "<?php \$page_title = 'DSMS'; include 'includes/header.php'; ?>", $c);
    
    // 2. Remove extra duplicate nested #content (fix_layout)
    $c = preg_replace('/\s*<!-- Page Content -->\s*<div id="content">\s*<!-- Top Navbar -->\s*<\?php include \'includes\/navbar\.php\'; \?>\s*/is', "\n", $c);
    
    // 3. Fix Footer
    $c = preg_replace('/<!-- Bootstrap JS Bundle with Popper -->.*?<\/html>/is', "<?php\n\$extra_js = ob_get_clean();\ninclude 'includes/footer.php';\n?>", $c);
    
    // 4. Remove sidebar JS
    $sidebarLogic = "document.getElementById('sidebarCollapse').addEventListener('click', function () {\n            document.getElementById('sidebar').classList.toggle('active');\n            document.getElementById('content').classList.toggle('active');\n        });";
    $c = str_replace($sidebarLogic, '', $c);
    
    // Remove Bootstrap JS duplicate
    $c = str_replace('<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>', '', $c);
    
    file_put_contents($f, $c);
    echo "Restored and fixed $f\n";
}
