<?php
require '../db.php';
session_start();

// Allow only logged-in readers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'reader') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$bid = (int)($_POST['blog_id'] ?? 0);

if ($bid) {
    try {
        // Check if user already liked the post
        $check = $pdo->prepare("SELECT like_id FROM likes WHERE user_id = ? AND blog_id = ?");
        $check->execute([$_SESSION['user_id'], $bid]);

        if ($check->rowCount() === 0) {
            // Insert into likes table
            $pdo->prepare("INSERT INTO likes (user_id, blog_id) VALUES (?, ?)")->execute([$_SESSION['user_id'], $bid]);

            // Increment blog likes count
            $pdo->prepare("UPDATE blogs SET likes = likes + 1 WHERE blog_id = ?")->execute([$bid]);

            // Award points to the user
            $pdo->prepare("UPDATE users SET points = points + 5 WHERE user_id = ?")->execute([$_SESSION['user_id']]);

            echo 'OK';
        } else {
            echo 'ALREADY_LIKED';
        }
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }
} else {
    echo 'No ID';
}
?>
