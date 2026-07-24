<?php
$files = ['issue_stationery.php', 'my_requests.php', 'new_request.php', 'request_history.php', 'stock_history.php'];
foreach ($files as $f) {
    if (!file_exists($f)) continue;
    $c = file_get_contents($f);
    
    $start_pos = strpos($c, '<!DOCTYPE html>');
    if ($start_pos === false) continue;
    
    $php_logic = substr($c, 0, $start_pos);
    
    preg_match('/<title>(.*?)<\/title>/', $c, $title_matches);
    $title = isset($title_matches[1]) ? $title_matches[1] : 'DSMS';
    
    $style_block = '';
    if (preg_match('/<style>.*?<\/style>/is', $c, $style_matches)) {
        $style_block = $style_matches[0] . "\n";
    }
    
    $content_start = strpos($c, '<?php include \'includes/navbar.php\'; ?>');
    if ($content_start !== false) {
        $content_start += strlen('<?php include \'includes/navbar.php\'; ?>');
    } else {
        continue;
    }
    
    $footer_start = strpos($c, '<!-- Bootstrap JS Bundle with Popper -->');
    if ($footer_start === false) $footer_start = strpos($c, '<!-- Bootstrap Bundle with Popper -->');
    if ($footer_start === false) $footer_start = strpos($c, '</body>');
    
    $main_content = substr($c, $content_start, $footer_start - $content_start);
    
    // Remove the two closing divs before the footer
    $main_content = preg_replace('/<\/div>\s*<\/div>\s*$/', '', $main_content);
    
    $script_block = '';
    $footer_chunk = substr($c, $footer_start);
    $footer_chunk = preg_replace('/<script src="https:\/\/cdn\.jsdelivr\.net\/npm\/bootstrap@5\.3\.2\/dist\/js\/bootstrap\.bundle\.min\.js"><\/script>/is', '', $footer_chunk);
    $sidebarLogic = "document.getElementById('sidebarCollapse').addEventListener('click', function () {\n            document.getElementById('sidebar').classList.toggle('active');\n            document.getElementById('content').classList.toggle('active');\n        });";
    $footer_chunk = str_replace($sidebarLogic, '', $footer_chunk);
    
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
    echo "Perfectly refactored $f\n";
}
