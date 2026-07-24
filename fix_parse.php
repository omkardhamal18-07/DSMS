<?php
$files = ['faculty_dashboard.php', 'faculty_requests.php', 'my_requests.php'];
foreach ($files as $f) {
    if (file_exists($f)) {
        $c = file_get_contents($f);
        $pos = strpos($c, '<?php', 5);
        if ($pos !== false && $pos < 4000) { // Safety check to ensure it's near the top
            file_put_contents($f, substr($c, $pos));
            echo "Fixed $f\n";
        }
    }
}
