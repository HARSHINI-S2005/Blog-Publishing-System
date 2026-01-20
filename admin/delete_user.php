<?php
require '../db.php';
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='admin') { header('Location: ../index.php'); exit; }
if($_SERVER['REQUEST_METHOD']==='POST'){
    $id = (int)($_POST['user_id'] ?? 0);
    $pdo->prepare("DELETE FROM users WHERE user_id=?")->execute([$id]);
}
header('Location: dashboard.php'); exit;
