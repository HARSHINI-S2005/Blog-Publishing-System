<?php
require '../db.php';
session_start();

// Ensure only logged-in authors can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'author') {
    header('Location: ../index.php');
    exit;
}

// Get blog ID (from POST or GET)
$blog_id = isset($_POST['blog_id']) ? (int)$_POST['blog_id'] : (int)($_GET['blog_id'] ?? 0);
if(!$blog_id){
    header('Location: dashboard.php');
    exit;
}

// Fetch current post and verify owner (author or co-author allowed to act)
$stmt = $pdo->prepare("SELECT * FROM blogs WHERE blog_id = ?");
$stmt->execute([$blog_id]);
$blog = $stmt->fetch();

if (!$blog) {
    echo "<div style='color:red; text-align:center; margin-top:50px;'>Blog not found.</div>";
    exit;
}

// Check that the current user is the author or co-author
$userId = $_SESSION['user_id'];
if ($blog['author_id'] != $userId && ($blog['co_author_id'] === null || $blog['co_author_id'] != $userId)) {
    echo "<div style='color:red; text-align:center; margin-top:50px;'>You are not authorized to change this post.</div>";
    exit;
}

// Handle the workflow:
// - If post is 'approved' -> publish it (author publishes after editor approved).
// - If post is 'draft' or 'rejected' -> submit as 'pending' for editor review.
// - Otherwise (pending/published) show message or redirect.
$status = $blog['status'];

if ($status === 'approved') {
    // Publish the post
    $pdo->prepare("UPDATE blogs SET status = 'published', updated_at = NOW() WHERE blog_id = ?")
        ->execute([$blog_id]);

    // Optionally: award author points for publishing (uncomment if desired)
    // $pdo->prepare("UPDATE users SET points = points + 50 WHERE user_id = ?")->execute([$blog['author_id']]);

    echo "<script>
        alert('Post published successfully.');
        window.location.href = 'dashboard.php';
    </script>";
    exit;
}

if (in_array($status, ['draft','rejected'])) {
    // Submit to editor (pending)
    $pdo->prepare("UPDATE blogs SET status = 'pending', submitted_at = NOW(), updated_at = NOW() WHERE blog_id = ?")
        ->execute([$blog_id]);

    echo "<script>
        alert('Blog submitted for editor approval.');
        window.location.href = 'dashboard.php';
    </script>";
    exit;
}

// If status is 'pending' or 'published' or anything else:
if ($status === 'pending') {
    echo "<div style='color:orange; text-align:center; margin-top:50px;'>This post is already submitted and waiting for editor review.</div>";
    exit;
}
if ($status === 'published') {
    echo "<div style='color:green; text-align:center; margin-top:50px;'>This post is already published.</div>";
    exit;
}

// fallback
header('Location: dashboard.php');
exit;
?>
