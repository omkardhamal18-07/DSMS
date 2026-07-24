<?php
$files = glob("*.php");
foreach ($files as $file) {
    if ($file === "login.php" || $file === "settings.php" || $file === "update_headers.php") {
        continue;
    }
    
    $content = file_get_contents($file);
    if (strpos($content, "</head>") !== false && strpos($content, "theme.css") === false) {
        $replacement = "    <!-- Theme System -->\n    <link rel=\"stylesheet\" href=\"theme.css\">\n    <script src=\"theme.js\"></script>\n</head>";
        $content = str_replace("</head>", $replacement, $content);
        file_put_contents($file, $content);
        echo "Updated $file\n";
    }
}
?>
