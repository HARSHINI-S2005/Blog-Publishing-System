<?php
require '../db.php';
session_start();
if(!isset($_SESSION['user_id'])) { http_response_code(403); echo 'Forbidden'; exit; }
$bid = (int)($_POST['blog_id'] ?? 0);
$content = $_POST['content'] ?? '';
// Basic permission: only allow if author or co-author
$stmt = $pdo->prepare("SELECT * FROM blogs WHERE blog_id=? AND (author_id=? OR co_author_id=?)");
$stmt->execute([$bid,$_SESSION['user_id'],$_SESSION['user_id']]);
if(!$stmt->fetch()) { http_response_code(403); echo 'No permission'; exit; }
$pdo->prepare("UPDATE blogs SET content=?, updated_at=? WHERE blog_id=?")->execute([$content,date('Y-m-d H:i:s'),$bid]);
echo 'Saved';
