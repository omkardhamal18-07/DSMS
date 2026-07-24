<?php
session_start();
$_SESSION['role'] = 'ADMIN';
$role = $_SESSION['role'];
$current_page = 'admin_dashboard.php';
include 'includes/sidebar.php';
