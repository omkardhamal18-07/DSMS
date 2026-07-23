<?php
include("database/db.php");

// 1. Create stationery_master table
$sql_create_master = "
CREATE TABLE IF NOT EXISTS stationery_master (
    master_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(50) NOT NULL
);
";
$conn->query($sql_create_master);

// 2. Insert predefined items
$master_items = [
    // Paper
    ['A4 Sheets', 'Paper'],
    ['A3 Sheets', 'Paper'],
    ['Legal Paper', 'Paper'],
    ['Chart Paper', 'Paper'],
    ['Graph Paper', 'Paper'],
    ['Sticky Notes', 'Paper'],
    ['Notebook', 'Paper'],
    ['Register', 'Paper'],
    // Writing
    ['Blue Pen', 'Writing'],
    ['Black Pen', 'Writing'],
    ['Red Pen', 'Writing'],
    ['Green Pen', 'Writing'],
    ['Pencil', 'Writing'],
    ['Mechanical Pencil', 'Writing'],
    ['Permanent Marker', 'Writing'],
    ['Whiteboard Marker', 'Writing'],
    ['Highlighter', 'Writing'],
    ['Chalk', 'Writing'],
    // Correction
    ['Eraser', 'Correction'],
    ['Correction Pen', 'Correction'],
    ['Correction Tape', 'Correction'],
    // Filing & Office
    ['Plastic File', 'Filing & Office'],
    ['Box File', 'Filing & Office'],
    ['Folder', 'Filing & Office'],
    ['Clipboard', 'Filing & Office'],
    ['Ring Binder', 'Filing & Office'],
    ['Document Folder', 'Filing & Office'],
    // Fasteners
    ['Stapler', 'Fasteners'],
    ['Stapler Pins', 'Fasteners'],
    ['Staple Remover', 'Fasteners'],
    ['Paper Clips', 'Fasteners'],
    ['Binder Clips', 'Fasteners'],
    ['Rubber Bands', 'Fasteners'],
    // Adhesives & Cutting
    ['Glue Stick', 'Adhesives & Cutting'],
    ['Liquid Glue', 'Adhesives & Cutting'],
    ['Transparent Tape', 'Adhesives & Cutting'],
    ['Brown Tape', 'Adhesives & Cutting'],
    ['Double-Sided Tape', 'Adhesives & Cutting'],
    ['Scissors', 'Adhesives & Cutting'],
    ['Cutter', 'Adhesives & Cutting'],
    // Measuring & Drawing
    ['Whiteboard Duster', 'Measuring & Drawing'],
    ['Whiteboard Cleaner', 'Measuring & Drawing'],
    // Miscellaneous
    ['Envelopes', 'Miscellaneous'],
    ['ID Card Holder', 'Miscellaneous'],
    ['Lanyard', 'Miscellaneous'],
    ['Attendance Register', 'Miscellaneous']
];

foreach ($master_items as $item) {
    $name = $conn->real_escape_string($item[0]);
    $cat = $conn->real_escape_string($item[1]);
    $conn->query("INSERT IGNORE INTO stationery_master (item_name, category) VALUES ('$name', '$cat')");
}

// 3. Ensure minimum_stock exists in stationery (Add if missing)
$check_col = $conn->query("SHOW COLUMNS FROM stationery LIKE 'minimum_stock'");
if ($check_col->num_rows == 0) {
    $conn->query("ALTER TABLE stationery ADD COLUMN minimum_stock INT NOT NULL DEFAULT 10 AFTER quantity_available");
}

// 4. Delete existing inventory to remove demo data
// Disable foreign key checks to allow deleting items that might have requests
$conn->query("SET FOREIGN_KEY_CHECKS=0");
$conn->query("TRUNCATE TABLE stationery");
$conn->query("SET FOREIGN_KEY_CHECKS=1");

// 5. Add department to users
$check_user_col = $conn->query("SHOW COLUMNS FROM users LIKE 'department'");
if ($check_user_col->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN department VARCHAR(100) AFTER role");
    $conn->query("UPDATE users SET department = 'Computer Science' WHERE role = 'FACULTY'");
}

// 6. Create notifications table
include_once("notification_helper.php");
ensure_notifications_table($conn);

echo "Database updated successfully.\n";
?>
