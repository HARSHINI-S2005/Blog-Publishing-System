<?php
require '../db.php';
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='editor') { header('Location: ../index.php'); exit; }

if($_SERVER['REQUEST_METHOD']==='POST'){
    $id = (int)($_POST['blog_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $remark = trim($_POST['remark'] ?? '');
    $editorId = $_SESSION['user_id'];
    
    if($action==='approve'){
        $pdo->prepare("UPDATE blogs SET status='approved', editor_remark=?, reviewed_by=?, updated_at=NOW() WHERE blog_id=?")->execute([$remark, $editorId, $id]);
    } else {
        $pdo->prepare("UPDATE blogs SET status='rejected', editor_remark=?, reviewed_by=?, updated_at=NOW() WHERE blog_id=?")->execute([$remark, $editorId, $id]);
    }
}
header('Location: dashboard.php'); exit;
?>