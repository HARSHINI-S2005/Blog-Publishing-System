<?php
require 'db.php';
session_start();
if($_SERVER['REQUEST_METHOD'] !== 'POST') exit('Invalid request');

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if(!$email || !$password) die('Missing credentials');

$stmt = $pdo->prepare("SELECT user_id,fullname,email,password,role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();
if(!$user || !password_verify($password, $user['password'])) {
    die('Invalid email or password');
}

$_SESSION['user_id'] = $user['user_id'];
$_SESSION['fullname'] = $user['fullname'];
$_SESSION['role'] = $user['role'];

// Redirect to dashboard.php which further routes to role dashboards
header('Location: dashboard.php');
exit;
?>
