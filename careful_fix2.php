<?php
$files = ['issue_stationery.php', 'my_requests.php', 'new_request.php', 'request_history.php', 'stock_history.php'];
foreach ($files as $f) {
    if (!file_exists($f)) continue;
    $c = file_get_contents($f);
    
    // 1. Fix parse error (remove duplicate PHP block)
    $pos = strpos($c, '<?php', 5);
    if ($pos !== false && $pos < 4000) {
        $c = substr($c, $pos);
    }
    
    // 2. Refactor layout (extract header and footer)
    preg_match('/<title>(.*?)<\/title>/', $c, $matches);
    $title = isset($matches[1]) ? $matches[1] : 'DSMS';
    
    $start_pos = strpos($c, '<!DOCTYPE html>');
    if ($start_pos !== false) {
        $navbar_pos = strpos($c, '<?php include \'includes/navbar.php\'; ?>');
        if ($navbar_pos !== false) {
            $end_pos = $navbar_pos + strlen('<?php include \'includes/navbar.php\'; ?>');
            
            $before_doctype = substr($c, $start_pos - 3, 3);
            if ($before_doctype === "?>\n" || $before_doctype === "?>\r\n") {
                 $start_pos -= strlen($before_doctype);
            } elseif (substr($c, $start_pos - 2, 2) === "?>") {
                 $start_pos -= 2;
            }
            
            $header_str = "<?php\n\$page_title = '$title';\nob_start();\n?>\n";
            if (preg_match('/<style>.*?<\/style>/is', substr($c, $start_pos, $end_pos - $start_pos), $style_match)) {
                $header_str .= $style_match[0] . "\n";
            }
            $header_str .= "<?php\n\$extra_css = ob_get_clean();\ninclude 'includes/header.php';\n?>\n";
            
            $c = substr_replace($c, $header_str, $start_pos, $end_pos - $start_pos);
        }
    }
    
    // Remove duplicate nested #content (if any)
    $c = preg_replace('/\s*<!-- Page Content -->\s*<div id="content">\s*<!-- Top Navbar -->\s*<\?php include \'includes\/navbar\.php\'; \?>\s*/is', "\n", $c);
    
    // Fix Footer part
    $footer_start = strpos($c, '<!-- Bootstrap JS Bundle with Popper -->');
    if ($footer_start !== false) {
        $footer_str = "<?php\n\$extra_js = ob_get_clean();\ninclude 'includes/footer.php';\n?>\n";
        $footer_chunk = substr($c, $footer_start);
        
        $footer_chunk = str_replace('<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>', '', $footer_chunk);
        $footer_chunk = str_replace('<!-- Bootstrap JS Bundle with Popper -->', '', $footer_chunk);
        
        $sidebarLogic = "document.getElementById('sidebarCollapse').addEventListener('click', function () {\n            document.getElementById('sidebar').classList.toggle('active');\n            document.getElementById('content').classList.toggle('active');\n        });";
        $footer_chunk = str_replace($sidebarLogic, '', $footer_chunk);
        
        $footer_chunk = str_replace('</body>', '', $footer_chunk);
        $footer_chunk = str_replace('</html>', '', $footer_chunk);
        $footer_chunk = preg_replace('/<script>\s*<\/script>/is', '', $footer_chunk);
        
        $c = substr_replace($c, "<?php ob_start(); ?>\n" . trim($footer_chunk) . "\n" . $footer_str, $footer_start, strlen($c) - $footer_start);
    }
    
    file_put_contents($f, $c);
    echo "Fixed $f\n";
}
