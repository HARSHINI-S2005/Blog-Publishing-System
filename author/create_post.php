<?php
require '../db.php';
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='author') { header('Location: ../index.php'); exit; }

if($_SERVER['REQUEST_METHOD']==='POST'){
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $co = (int)($_POST['co_author'] ?? 0) ?: null;
    // status: either draft (save) or pending (submit for review)
    $status = $_POST['action'] ?? 'draft'; // action='draft' or 'pending'
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO blogs (title,content,author_id,co_author_id,status,created_at,likes) VALUES (?,?,?,?,?,? ,0)");
    $stmt->execute([$title,$content,$_SESSION['user_id'],$co,$status,$now]);
    header('Location: dashboard.php'); exit;
}

$authors = $pdo->prepare("SELECT user_id,fullname FROM users WHERE role='author' AND user_id!=?");
$authors->execute([$_SESSION['user_id']]);
$authors = $authors->fetchAll();
?>
<!doctype html><html><head>
<meta charset="utf-8"><title>Create Post</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head><body>
<div class="container">
  <div class="navbar">
    <div><strong>Author Panel</strong></div>
    <div><a class="btn-outline" href="dashboard.php">Back</a></div>
  </div>

  <div class="card">
    <h3>Create Post</h3>
    <form method="POST">
      <div><label>Title</label><input name="title" required class="form-control"></div>
      <div style="margin-top:10px;">
        <label>Co-author (optional)</label>
        <select name="co_author" class="form-control">
          <option value="">-- None --</option>
          <?php foreach($authors as $a): ?>
            <option value="<?=$a['user_id']?>"><?=htmlspecialchars($a['fullname'])?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="margin-top:10px;">
        <label>Content</label>
        <textarea name="content" rows="12" class="form-control" required></textarea>
      </div>

      <div style="margin-top:12px; display:flex; gap:8px;">
        <button name="action" value="draft" class="btn btn-outline">Save Draft</button>
        <button name="action" value="pending" class="btn btn-primary">Submit for Review</button>
      </div>
    </form>
  </div>
</div>
</body></html>

