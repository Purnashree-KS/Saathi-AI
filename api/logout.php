<?php
include 'db_connect.php';

if (isset($_SESSION['user_email'])) {
    recordAuthEvent($conn, $_SESSION['user_email'], $_SESSION['user_name'], $_SESSION['role'], 'logout');
}

session_destroy();
echo json_encode(['success' => true]);
?>