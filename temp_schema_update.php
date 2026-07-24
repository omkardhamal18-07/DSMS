<?php
require 'database/db.php';

$queries = [
    "ALTER TABLE stationery_requests ADD COLUMN purpose TEXT AFTER status",
    "ALTER TABLE stationery_requests ADD COLUMN priority ENUM('LOW', 'MEDIUM', 'HIGH') DEFAULT 'MEDIUM' AFTER purpose",
    "ALTER TABLE stationery_requests ADD COLUMN required_date DATE AFTER priority"
];

foreach ($queries as $query) {
    if ($conn->query($query) === TRUE) {
        echo "Successfully executed: $query\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}
?>
