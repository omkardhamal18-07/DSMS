<?php
$files = [
    'admin_dashboard.php', 'inventory.php', 'faculty_requests.php', 
    'issue_stationery.php', 'request_history.php', 'reports.php', 
    'notifications.php', 'settings.php', 'faculty_dashboard.php', 
    'my_requests.php', 'new_request.php', 'stock_history.php'
];

foreach ($files as $f) {
    if (file_exists($f)) {
        $c = file_get_contents($f);
        
        $pattern = '/\s*<!-- Page Content -->\s*<div id="content">\s*<!-- Top Navbar -->\s*<\?php include \'includes\/navbar\.php\'; \?>\s*/is';
        
        $new_c = preg_replace($pattern, "\n", $c, 1);
        if ($new_c !== null && $new_c !== $c) {
            file_put_contents($f, $new_c);
            echo "Fixed layout in $f\n";
        } else {
            echo "Pattern not found in $f\n";
        }
    }
}
