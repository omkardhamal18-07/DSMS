<?php
$file = 'my_requests.php';
$content = file_get_contents($file);

$logic = "<?php
session_start();
if (!isset(\$_SESSION['user_id']) || \$_SESSION['role'] !== 'FACULTY') {
    header(\"Location: login.php\");
    exit();
}

include(\"database/db.php\");

\$user_id = \$_SESSION['user_id'];
\$status_filter = isset(\$_GET['status']) ? strtoupper(\$_GET['status']) : 'ALL';
\$valid_statuses = ['ALL', 'PENDING', 'APPROVED', 'REJECTED'];
if (!in_array(\$status_filter, \$valid_statuses)) \$status_filter = 'ALL';

// Fetch logged-in faculty details
\$stmt = \$conn->prepare(\"SELECT * FROM users WHERE user_id = ?\");
\$stmt->bind_param(\"i\", \$user_id);
\$stmt->execute();
\$faculty = \$stmt->get_result()->fetch_assoc();

// Counts for cards
\$total_requests = 0;
\$pending_requests = 0;
\$approved_requests = 0;
\$rejected_requests = 0;

\$count_query = \"SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'APPROVED' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'REJECTED' THEN 1 ELSE 0 END) as rejected
FROM stationery_requests WHERE faculty_id = ?\";
if (\$c_stmt = \$conn->prepare(\$count_query)) {
    \$c_stmt->bind_param(\"i\", \$user_id);
    \$c_stmt->execute();
    \$counts = \$c_stmt->get_result()->fetch_assoc();
    \$total_requests = \$counts['total'] ?? 0;
    \$pending_requests = \$counts['pending'] ?? 0;
    \$approved_requests = \$counts['approved'] ?? 0;
    \$rejected_requests = \$counts['rejected'] ?? 0;
}

// Fetch requests for table
\$query = \"SELECT r.*, s.item_name, s.category, s.quantity_available, s.unit, rev.name as reviewer_name 
          FROM stationery_requests r 
          JOIN stationery s ON r.stationery_id = s.stationery_id 
          LEFT JOIN users rev ON r.reviewed_by = rev.user_id 
          WHERE r.faculty_id = ?\";

if (\$status_filter !== 'ALL') {
    \$query .= \" AND r.status = '\" . \$conn->real_escape_string(\$status_filter) . \"'\";
}
\$query .= \" ORDER BY r.request_date DESC\";

\$requests = [];
if (\$r_stmt = \$conn->prepare(\$query)) {
    \$r_stmt->bind_param(\"i\", \$user_id);
    \$r_stmt->execute();
    \$result = \$r_stmt->get_result();
    while(\$row = \$result->fetch_assoc()) {
        \$requests[] = \$row;
    }
}
?>
";

// We need to replace the first line `<?php` of the current file with the full logic and `<?php`
$content = preg_replace('/^<\?php/', $logic . "<?php", $content, 1);
file_put_contents($file, $content);
echo "Injected missing backend logic successfully!";
?>
