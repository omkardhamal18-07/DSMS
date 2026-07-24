<?php
$files = glob('*.php');

foreach ($files as $f) {
    if ($f === 'refactor_layouts.php' || strpos($f, 'fix_') !== false) continue;
    $c = file_get_contents($f);
    $orig = $c;
    
    // Remove the bootstrap JS script tag exactly
    $c = str_replace('<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>', '', $c);
    
    // Remove the sidebar toggle logic exactly
    $sidebarLogic = "document.getElementById('sidebarCollapse').addEventListener('click', function () {\n            document.getElementById('sidebar').classList.toggle('active');\n            document.getElementById('content').classList.toggle('active');\n        });";
    $c = str_replace($sidebarLogic, '', $c);
    
    // Clean up empty script tags if they exist
    $c = preg_replace('/<script>\s*<\/script>/is', '', $c);
    
    // Remove the comment
    $c = str_replace('<!-- Bootstrap JS Bundle with Popper -->', '', $c);
    $c = str_replace('<!-- Bootstrap Bundle with Popper -->', '', $c);
    
    if ($c !== $orig) {
        file_put_contents($f, $c);
        echo "Cleaned JS duplicates safely from $f\n";
    }
}
