<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php'); exit;
}
$role = $_SESSION['role'];

switch($role) {
    case 'admin': header('Location: admin/dashboard.php'); break;
    case 'editor': header('Location: editor/dashboard.php'); break;
    case 'author': header('Location: author/dashboard.php'); break;
    case 'reader': header('Location: reader/dashboard.php'); break;
    default: echo "Unknown role"; break;
}
exit;
?>
