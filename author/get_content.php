<?php
require '../db.php';
session_start();

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) { 
    echo json_encode(['error' => 'Unauthorized']); 
    exit; 
}

$bid = (int)($_GET['blog_id'] ?? 0);

// Verify user has access to this blog
$stmt = $pdo->prepare("SELECT b.content, b.updated_at, u.fullname as last_editor FROM blogs b LEFT JOIN users u ON b.author_id = u.user_id WHERE b.blog_id=? AND (b.author_id=? OR b.co_author_id=?)");
$stmt->execute([$bid, $_SESSION['user_id'], $_SESSION['user_id']]);
$blog = $stmt->fetch();

if(!$blog) {
    echo json_encode(['error' => 'Not found']);
    exit;
}

echo json_encode([
    'content' => $blog['content'],
    'updated_at' => $blog['updated_at'],
    'last_editor' => $blog['last_editor']
]);
?>