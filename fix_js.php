<?php
$files = glob('*.php');

$pattern_bootstrap = '/\s*<!--.*?Bootstrap.*?-->\s*<script src="https:\/\/cdn\.jsdelivr\.net\/npm\/bootstrap@5\.3\.2\/dist\/js\/bootstrap\.bundle\.min\.js"><\/script>\s*/is';
$pattern_bootstrap2 = '/\s*<script src="https:\/\/cdn\.jsdelivr\.net\/npm\/bootstrap@5\.3\.2\/dist\/js\/bootstrap\.bundle\.min\.js"><\/script>\s*/i';

$pattern_sidebar = '/\s*document\.getElementById\(\'sidebarCollapse\'\)\.addEventListener\(\'click\', function \(\) \{\s*document\.getElementById\(\'sidebar\'\)\.classList\.toggle\(\'active\'\);\s*document\.getElementById\(\'content\'\)\.classList\.toggle\(\'active\'\);\s*\}\);\s*/';

foreach ($files as $f) {
    if ($f === 'refactor_layouts.php' || strpos($f, 'fix_') !== false) continue;
    $c = file_get_contents($f);
    $orig = $c;
    
    $c = preg_replace($pattern_bootstrap, "\n", $c);
    $c = preg_replace($pattern_bootstrap2, "\n", $c);
    $c = preg_replace($pattern_sidebar, "\n", $c);
    
    if ($c !== $orig) {
        file_put_contents($f, $c);
        echo "Cleaned JS duplicates from $f\n";
    }
}
