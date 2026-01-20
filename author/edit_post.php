<?php
require '../db.php';
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='author') { header('Location: ../index.php'); exit; }
$bid = (int)($_GET['blog_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM blogs WHERE blog_id=? AND (author_id=? OR co_author_id=?)");
$stmt->execute([$bid,$_SESSION['user_id'],$_SESSION['user_id']]);
$post = $stmt->fetch();
if(!$post) { header('Location: dashboard.php'); exit; }

if($_SERVER['REQUEST_METHOD']==='POST'){
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $co = (int)($_POST['co_author'] ?? 0) ?: null;
    $action = $_POST['action'] ?? 'draft'; // draft, pending, publish
    if($action==='publish'){
        // only allow publish if editor approved
        $check = $pdo->prepare("SELECT status FROM blogs WHERE blog_id=?");
        $check->execute([$bid]); $row = $check->fetch();
        if($row && $row['status']==='approved'){
            $pdo->prepare("UPDATE blogs SET status='published', title=?,content=?,co_author_id=?,updated_at=? WHERE blog_id=?")
                ->execute([$title,$content,$co,date('Y-m-d H:i:s'),$bid]);
        }
    } else {
        // save draft or submit pending
        $status = $action==='pending' ? 'pending' : 'draft';
        $pdo->prepare("UPDATE blogs SET title=?,content=?,co_author_id=?,status=?,updated_at=? WHERE blog_id=?")
            ->execute([$title,$content,$co,$status,date('Y-m-d H:i:s'),$bid]);
    }
    header('Location: dashboard.php'); exit;
}

$authors = $pdo->prepare("SELECT user_id,fullname FROM users WHERE role='author' AND user_id!=?");
$authors->execute([$_SESSION['user_id']]);
$authors = $authors->fetchAll();
?>
<!doctype html><html><head>
<meta charset="utf-8"><title>Edit Post</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head><body>
<div class="container">
  <div class="navbar">
    <div><strong>Edit Post</strong></div>
    <div><a class="btn-outline" href="dashboard.php">Back</a></div>
  </div>

  <div class="card">
    <form method="POST">
      <div><label>Title</label><input name="title" value="<?=htmlspecialchars($post['title'])?>" required class="form-control"></div>

      <div style="margin-top:10px;">
        <label>Co-author (optional)</label>
        <select name="co_author" class="form-control">
          <option value="">-- None --</option>
          <?php foreach($authors as $a): ?>
            <option value="<?=$a['user_id']?>" <?= $post['co_author_id']==$a['user_id'] ? 'selected':''?>><?=htmlspecialchars($a['fullname'])?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="margin-top:10px;">
        <label>Content</label>
        <textarea name="content" rows="12" class="form-control"><?=htmlspecialchars($post['content'])?></textarea>
      </div>

      <div style="margin-top:12px; display:flex; gap:8px;">
        <button name="action" value="draft" class="btn btn-outline">Save Draft</button>
        <button name="action" value="pending" class="btn btn-primary">Submit for Review</button>
        <?php if($post['status']==='approved'): ?>
          <button name="action" value="publish" class="btn btn-primary" style="background:var(--accent); border-color:var(--accent);">Publish</button>
        <?php endif; ?>
      </div>

      <?php if($post['editor_remark']): ?>
        <div style="margin-top:12px;" class="card">
          <strong>Editor Remark:</strong>
          <p class="small"><?=nl2br(htmlspecialchars($post['editor_remark']))?></p>
        </div>
      <?php endif; ?>

    </form>
  </div>
</div>
</body></html>
