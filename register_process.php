<?php
require 'db.php';
if($_SERVER['REQUEST_METHOD'] !== 'POST') exit('Invalid request');

$fullname = trim($_POST['fullname'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'reader';

if(!$fullname || !$email || !$password) {
    die('Please fill all required fields.');
}

// Check existing
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->execute([$email]);
if($stmt->fetch()){
    die('Email already registered. Go back and login.');
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$now = date('Y-m-d H:i:s');

$stmt = $pdo->prepare("INSERT INTO users (fullname,email,password,role,points,created_at) VALUES (?,?,?,?,0,?)");
$stmt->execute([$fullname,$email,$hash,$role,$now]);

header('Location: index.php');
exit;
?>
