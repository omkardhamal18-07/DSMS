<?php
$files = glob("*.php");
$exclude = ['login.php', 'admin_dashboard.php', 'fix_js.php', 'fix_parse.php', 'careful_fix.php', 'careful_fix2.php', 'final_fix.php', 'perfect_final_fix.php', 'update_headers.php', 'test_sidebar.php', 'restore_one.php'];

foreach ($files as $f) {
    if (in_array($f, $exclude)) continue;
    if (!file_exists($f)) continue;
    $c = file_get_contents($f);
    
    // First, fix any duplicate PHP block
    $php_end_pos = strpos($c, '?>');
    if ($php_end_pos !== false) {
        $php_end_pos += 2;
        $second_php = strpos($c, '<?php', $php_end_pos);
        if ($second_php !== false) {
            $between = trim(substr($c, $php_end_pos, $second_php - $php_end_pos));
            if ($between === '' && substr($c, $second_php, 12) === '<?php'.PHP_EOL.'session') {
                // duplicate block found
                $c = substr($c, $second_php);
            }
        }
    }
    
    $start_pos = strpos($c, '<!DOCTYPE html>');
    if ($start_pos === false) continue; // Already refactored
    
    $php_logic = substr($c, 0, $start_pos);
    
    preg_match('/<title>(.*?)<\/title>/', $c, $title_matches);
    $title = isset($title_matches[1]) ? $title_matches[1] : 'DSMS';
    
    $style_block = '';
    if (preg_match('/<style>.*?<\/style>/is', $c, $style_matches)) {
        $style_block = $style_matches[0] . "\n";
    }
    
    if (preg_match('/<nav class="navbar.*?<\/nav>/is', $c, $nav_matches, PREG_OFFSET_CAPTURE)) {
        $content_start = $nav_matches[0][1] + strlen($nav_matches[0][0]);
    } else {
        // Maybe it's already using include 'includes/navbar.php';
        $nav_inc = '<?php include \'includes/navbar.php\'; ?>';
        $nav_pos = strpos($c, $nav_inc);
        if ($nav_pos !== false) {
            $content_start = $nav_pos + strlen($nav_inc);
        } else {
            echo "Skipped $f (no navbar found)\n";
            continue;
        }
    }
    
    $footer_start = strpos($c, '<!-- Bootstrap JS Bundle with Popper -->');
    if ($footer_start === false) $footer_start = strpos($c, '<!-- Bootstrap Bundle with Popper -->');
    if ($footer_start === false) $footer_start = strpos($c, '</body>');
    
    $main_content = substr($c, $content_start, $footer_start - $content_start);
    $main_content = preg_replace('/<\/div>\s*<\/div>\s*$/', '', $main_content);
    
    $script_block = '';
    $footer_chunk = substr($c, $footer_start);
    $footer_chunk = preg_replace('/<script src="https:\/\/cdn\.jsdelivr\.net\/npm\/bootstrap@5\.3\.2\/dist\/js\/bootstrap\.bundle\.min\.js"><\/script>/is', '', $footer_chunk);
    $sidebarLogic = "document.getElementById('sidebarCollapse').addEventListener('click', function () {\n            document.getElementById('sidebar').classList.toggle('active');\n            document.getElementById('content').classList.toggle('active');\n        });";
    $footer_chunk = str_replace($sidebarLogic, '', $footer_chunk);
    $footer_chunk = str_replace('</body>', '', $footer_chunk);
    $footer_chunk = str_replace('</html>', '', $footer_chunk);
    
    if (preg_match_all('/<script>(.*?)<\/script>/is', $footer_chunk, $script_matches)) {
        foreach ($script_matches[1] as $inner_script) {
            if (trim($inner_script) !== '') {
                $script_block .= "<script>\n" . trim($inner_script) . "\n</script>\n";
            }
        }
    }
    
    $new_content = trim($php_logic) . "\n\n";
    $new_content .= "<?php\n\$page_title = '$title';\n";
    if ($style_block) {
        $new_content .= "ob_start();\n?>\n$style_block<?php\n\$extra_css = ob_get_clean();\n";
    }
    $new_content .= "include 'includes/header.php';\n?>\n";
    $new_content .= trim($main_content) . "\n\n";
    if ($script_block) {
        $new_content .= "<?php ob_start(); ?>\n$script_block<?php\n\$extra_js = ob_get_clean();\ninclude 'includes/footer.php';\n?>\n";
    } else {
        $new_content .= "<?php include 'includes/footer.php'; ?>\n";
    }
    
    file_put_contents($f, $new_content);
    echo "Refactored $f\n";
}
?>
