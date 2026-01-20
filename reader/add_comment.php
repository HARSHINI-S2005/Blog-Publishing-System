<?php
require '../db.php';
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='reader') { header('Location: ../index.php'); exit; }
$bid = (int)($_POST['blog_id'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
if($comment){
    $pdo->prepare("INSERT INTO comments (blog_id,user_id,comment_text,created_at) VALUES (?,?,?,NOW())")
        ->execute([$bid,$_SESSION['user_id'],$comment]);
    // award points for commenting
    $pdo->prepare("UPDATE users SET points = points + 10 WHERE user_id=?")->execute([$_SESSION['user_id']]);
}
header('Location: view_post.php?blog_id='.$bid); exit;
?>
