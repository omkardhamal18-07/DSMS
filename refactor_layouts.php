<?php
$files = [
    'admin_dashboard.php',
    'faculty_dashboard.php',
    'faculty_requests.php',
    'inventory.php',
    'issue_stationery.php',
    'my_requests.php',
    'new_request.php',
    'request_history.php',
    'settings.php',
    'stock_history.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);

    // Extract title
    preg_match('/<title>(.*?)<\/title>/', $content, $matches);
    $title = isset($matches[1]) ? $matches[1] : 'DSMS';

    // Replace header
    // Find the start of <!DOCTYPE html>
    $start_pos = strpos($content, '<!DOCTYPE html>');
    if ($start_pos !== false) {
        // Find the end of navbar include
        $navbar_pos = strpos($content, '<?php include \'includes/navbar.php\'; ?>');
        if ($navbar_pos !== false) {
            $end_pos = $navbar_pos + strlen('<?php include \'includes/navbar.php\'; ?>');
            
            $header_replacement = "?>\n<?php \$page_title = '$title'; include 'includes/header.php'; ?>\n";
            // Check if there is a trailing ?> before <!DOCTYPE html>
            $before_doctype = substr($content, $start_pos - 3, 3);
            if ($before_doctype === "?>\n" || $before_doctype === "?>\r\n") {
                 $header_replacement = "<?php \$page_title = '$title'; include 'includes/header.php'; ?>\n";
                 $start_pos -= strlen($before_doctype);
            } elseif (substr($content, $start_pos - 2, 2) === "?>") {
                 $header_replacement = "<?php \$page_title = '$title'; include 'includes/header.php'; ?>\n";
                 $start_pos -= 2;
            }
            
            $content = substr_replace($content, $header_replacement, $start_pos, $end_pos - $start_pos);
        }
    }

    // Replace footer
    // Some files might have different footers. Usually they end with </div>...</body></html>
    // Let's replace from the first <script src="...bootstrap.bundle.min.js"></script> to the end
    $footer_start = strpos($content, '<!-- Bootstrap JS Bundle with Popper -->');
    if ($footer_start !== false) {
        // But wait, the wrapper div closing tags are before this.
        // We need to replace from the closing tags of the content div.
        // It's safer to just look for the first </div> that closes the content, which is hard.
        // Actually, footer.php contains:
        //        </div> <!-- End of Page Content -->
        //    </div> <!-- End of Wrapper -->
        //    <!-- Bootstrap JS Bundle...
        // So we can search for `    </div>\n    </div>\n\n    <!-- Bootstrap` or similar.
    }
}
?>
