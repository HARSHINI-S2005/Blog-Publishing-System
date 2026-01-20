<?php
require '../db.php';
require '../includes/functions.php';
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='author') { header('Location: ../index.php'); exit; }

$uid = $_SESSION['user_id'];
$view = $_GET['view'] ?? 'dashboard';

$totalPosts = $pdo->prepare("SELECT COUNT(*) FROM blogs WHERE author_id=? OR co_author_id=?");
$totalPosts->execute([$uid, $uid]); $totalPosts = $totalPosts->fetchColumn();

$publishedPosts = $pdo->prepare("SELECT COUNT(*) FROM blogs WHERE (author_id=? OR co_author_id=?) AND status='published'");
$publishedPosts->execute([$uid, $uid]); $publishedPosts = $publishedPosts->fetchColumn();

$totalLikes = $pdo->prepare("SELECT SUM(likes) FROM blogs WHERE author_id=? OR co_author_id=?");
$totalLikes->execute([$uid, $uid]); $totalLikes = $totalLikes->fetchColumn() ?: 0;

$totalComments = $pdo->prepare("SELECT COUNT(*) FROM comments c JOIN blogs b ON c.blog_id=b.blog_id WHERE b.author_id=? OR b.co_author_id=?");
$totalComments->execute([$uid, $uid]); $totalComments = $totalComments->fetchColumn();

$userPoints = $pdo->prepare("SELECT points FROM users WHERE user_id=?");
$userPoints->execute([$uid]); $points = $userPoints->fetchColumn();

$postsStmt = $pdo->prepare("SELECT b.*, u.fullname as coauthor_name, (SELECT COUNT(*) FROM comments c WHERE c.blog_id=b.blog_id) as comment_count FROM blogs b LEFT JOIN users u ON b.co_author_id = u.user_id WHERE b.author_id=? OR b.co_author_id=? ORDER BY b.created_at DESC");
$postsStmt->execute([$uid,$uid]); $posts = $postsStmt->fetchAll();

// Get comments for author's blogs
$commentsStmt = $pdo->prepare("SELECT c.*, u.fullname, b.title as blog_title FROM comments c JOIN blogs b ON c.blog_id=b.blog_id LEFT JOIN users u ON c.user_id=u.user_id WHERE b.author_id=? OR b.co_author_id=? ORDER BY c.created_at DESC");
$commentsStmt->execute([$uid, $uid]); $allComments = $commentsStmt->fetchAll();

// Daily post data
$dailyData = $pdo->prepare("SELECT DAYNAME(created_at) as day, COUNT(*) as count FROM blogs WHERE (author_id=? OR co_author_id=?) AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DAYNAME(created_at), DAYOFWEEK(created_at) ORDER BY DAYOFWEEK(created_at)");
$dailyData->execute([$uid, $uid]); $dailyStats = $dailyData->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Author Dashboard - BlogPublish</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', sans-serif; background: #f5f7fa; min-height: 100vh; }
    .dashboard { display: flex; min-height: 100vh; }
    
    .sidebar { width: 260px; background: #fff; border-right: 1px solid #e5e7eb; padding: 24px 16px; position: fixed; height: 100vh; overflow-y: auto; }
    .logo { font-size: 1.5rem; font-weight: 800; color: #1a1a2e; padding: 0 12px 24px; border-bottom: 1px solid #e5e7eb; margin-bottom: 24px; }
    .logo span { color: #e94560; }
    .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #6b7280; text-decoration: none; border-radius: 10px; margin-bottom: 4px; transition: all 0.2s; font-weight: 500; }
    .nav-item:hover { background: #f5f7fa; color: #1f2937; }
    .nav-item.active { background: #1a1a2e; color: white; }
    .nav-section { font-size: 0.75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; padding: 16px 16px 8px; letter-spacing: 0.5px; }
    .badge-card { background: linear-gradient(135deg, #1a1a2e, #16213e); border-radius: 16px; padding: 20px; margin-top: auto; color: white; position: absolute; bottom: 24px; left: 16px; right: 16px; text-align: center; }
    .badge-card h4 { font-size: 0.85rem; opacity: 0.7; margin-bottom: 8px; }
    .badge-card .badge-name { font-size: 1.3rem; font-weight: 700; }
    .badge-card .points { font-size: 0.85rem; opacity: 0.7; margin-top: 4px; }

    .main { flex: 1; margin-left: 260px; padding: 24px 32px; }
    
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
    .header h1 { font-size: 1.75rem; font-weight: 700; color: #1f2937; }
    .header p { color: #6b7280; margin-top: 4px; }
    .header-actions { display: flex; align-items: center; gap: 16px; }
    .search-box { display: flex; align-items: center; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 16px; gap: 10px; }
    .search-box input { border: none; outline: none; font-size: 0.9rem; width: 200px; }
    .btn { padding: 10px 20px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; border: none; }
    .btn-primary { background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(26,26,46,0.3); }
    .btn-outline { background: transparent; border: 2px solid #e5e7eb; color: #1f2937; }
    .btn-outline:hover { border-color: #1a1a2e; }
    .user-avatar { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, #e94560, #ff6b6b); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; }
    .notif-btn { width: 42px; height: 42px; border-radius: 10px; background: #fff; border: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; }
    .notif-badge { position: absolute; top: -4px; right: -4px; width: 18px; height: 18px; background: #e94560; color: white; font-size: 0.7rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; }

    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px; }
    .stat-card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; transition: all 0.2s; }
    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); }
    .stat-card.highlight { background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; }
    .stat-card.highlight .stat-label { color: rgba(255,255,255,0.7); }
    .stat-label { font-size: 0.85rem; color: #6b7280; font-weight: 500; margin-bottom: 8px; }
    .stat-value { font-size: 2rem; font-weight: 700; margin-bottom: 8px; }
    .stat-change { font-size: 0.8rem; color: #10b981; display: flex; align-items: center; gap: 4px; }

    .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 28px; }
    .card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; }
    .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .card-header h3 { font-size: 1.1rem; font-weight: 600; }
    .chart-btns { display: flex; gap: 8px; }
    .chart-btn { padding: 6px 12px; border: 1px solid #e5e7eb; background: transparent; border-radius: 6px; font-size: 0.8rem; cursor: pointer; transition: all 0.2s; }
    .chart-btn.active, .chart-btn:hover { background: #1a1a2e; color: white; border-color: #1a1a2e; }

    .bar-chart { display: flex; align-items: flex-end; justify-content: space-between; height: 180px; padding: 20px 0; gap: 12px; }
    .bar-item { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 8px; }
    .bar { width: 100%; max-width: 36px; background: #e5e7eb; border-radius: 6px 6px 0 0; transition: all 0.3s; cursor: pointer; min-height: 10px; }
    .bar:hover { background: #e94560; }
    .bar.active { background: #1a1a2e; }
    .bar-label { font-size: 0.8rem; color: #6b7280; }

    .quick-actions { display: flex; flex-direction: column; gap: 10px; }
    .quick-actions .btn { justify-content: center; width: 100%; }

    .table-card { margin-top: 24px; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th { text-align: left; padding: 12px 16px; font-size: 0.8rem; font-weight: 600; color: #6b7280; text-transform: uppercase; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
    .data-table td { padding: 16px; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
    .data-table tr:hover { background: #f9fafb; }
    .table-item { display: flex; align-items: center; gap: 12px; }
    .table-icon { width: 40px; height: 40px; border-radius: 10px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #e94560; }
    .table-info h4 { font-size: 0.9rem; font-weight: 600; margin-bottom: 2px; }
    .table-info p { font-size: 0.8rem; color: #6b7280; }
    .status-badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; }
    .status-badge.published { background: #d1fae5; color: #059669; }
    .status-badge.pending { background: #fef3c7; color: #d97706; }
    .status-badge.draft { background: #dbeafe; color: #2563eb; }
    .status-badge.approved { background: #d1fae5; color: #059669; }
    .status-badge.rejected { background: #fee2e2; color: #dc2626; }
    .action-btns { display: flex; gap: 8px; }
    .action-btn { width: 36px; height: 36px; border-radius: 8px; border: 1px solid #e5e7eb; background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; color: #6b7280; text-decoration: none; }
    .action-btn:hover { border-color: #1a1a2e; color: #1a1a2e; }
    .action-btn.success { border-color: #10b981; color: #10b981; }
    .action-btn.success:hover { background: #10b981; color: white; }

    .editor-remark { background: #fff7ed; padding: 12px 16px; border-left: 4px solid #f59e0b; margin-top: 8px; border-radius: 0 8px 8px 0; }
    .editor-remark strong { color: #d97706; }

    .empty-state { text-align: center; padding: 60px 20px; color: #6b7280; }
    .empty-state i { font-size: 4rem; margin-bottom: 16px; opacity: 0.3; }

    /* Comments Section */
    .comment-item { display: flex; gap: 16px; padding: 20px; background: #f9fafb; border-radius: 12px; margin-bottom: 16px; }
    .comment-avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; flex-shrink: 0; }
    .comment-content { flex: 1; }
    .comment-header { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; flex-wrap: wrap; }
    .comment-author { font-weight: 600; color: #1f2937; }
    .comment-date { font-size: 0.8rem; color: #9ca3af; }
    .comment-blog { font-size: 0.8rem; color: #6366f1; background: #eef2ff; padding: 4px 10px; border-radius: 20px; }
    .comment-text { color: #4b5563; line-height: 1.6; }

    @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } .content-grid { grid-template-columns: 1fr; } }
    @media (max-width: 768px) { .sidebar { display: none; } .main { margin-left: 0; padding: 16px; } .stats-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="dashboard">
    <aside class="sidebar">
      <div class="logo">Blog<span>Publish</span></div>
      <nav>
        <a href="dashboard.php" class="nav-item <?=$view=='dashboard'?'active':''?>"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="create_post.php" class="nav-item"><i class="fas fa-plus-circle"></i> Create Post</a>
        <a href="#" class="nav-item"><i class="fas fa-file-alt"></i> My Posts</a>
        <a href="#" class="nav-item"><i class="fas fa-users"></i> Collaborations</a>
        <div class="nav-section">Analytics</div>
        <a href="#" class="nav-item"><i class="fas fa-chart-bar"></i> Statistics</a>
        <a href="dashboard.php?view=comments" class="nav-item <?=$view=='comments'?'active':''?>"><i class="fas fa-comments"></i> Comments</a>
        <div class="nav-section">Account</div>
        <a href="#" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
        <a href="../logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Log out</a>
      </nav>
      <div class="badge-card">
        <h4>Your Badge</h4>
        <div class="badge-name" style="color:<?=badge_color(badge_for_points($points))?>"><?=badge_for_points($points)?></div>
        <div class="points"><?=$points?> Points</div>
      </div>
    </aside>

    <main class="main">
      <div class="header">
        <div>
          <h1>Author Dashboard</h1>
          <p>Welcome back, <?=htmlspecialchars($_SESSION['fullname'])?></p>
        </div>
        <div class="header-actions">
          <div class="search-box"><i class="fas fa-search" style="color:#9ca3af;"></i><input type="text" placeholder="Search posts..."></div>
          <a href="create_post.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Post</a>
          <div class="notif-btn"><i class="fas fa-bell"></i><span class="notif-badge"><?=$totalComments?></span></div>
          <div class="user-avatar"><?=strtoupper(substr($_SESSION['fullname'], 0, 1))?></div>
        </div>
      </div>

      <div class="stats-grid">
        <div class="stat-card highlight">
          <div class="stat-label">Total Posts</div>
          <div class="stat-value"><?=$totalPosts?></div>
          <div class="stat-change"><i class="fas fa-file-alt"></i> All your posts</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Published</div>
          <div class="stat-value"><?=$publishedPosts?></div>
          <div class="stat-change"><i class="fas fa-check-circle"></i> Live posts</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Total Likes</div>
          <div class="stat-value"><?=number_format($totalLikes)?></div>
          <div class="stat-change"><i class="fas fa-heart"></i> On your posts</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Comments</div>
          <div class="stat-value"><?=number_format($totalComments)?></div>
          <div class="stat-change"><i class="fas fa-comment"></i> Engagement</div>
        </div>
      </div>

      <?php if($view == 'comments'): ?>
      <!-- Comments View -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-comments" style="color:#e94560;"></i> Comments on Your Blogs</h3>
          <span style="color:#6b7280;"><?=count($allComments)?> total comments</span>
        </div>
        <?php if(empty($allComments)): ?>
        <div class="empty-state">
          <i class="fas fa-comment-slash"></i>
          <h4>No comments yet</h4>
          <p>Your blogs haven't received any comments yet.</p>
        </div>
        <?php else: ?>
        <?php foreach($allComments as $c): ?>
        <div class="comment-item">
          <div class="comment-avatar"><?=strtoupper(substr($c['fullname'] ?? 'U', 0, 1))?></div>
          <div class="comment-content">
            <div class="comment-header">
              <span class="comment-author"><?=htmlspecialchars($c['fullname'] ?? 'User')?></span>
              <span class="comment-date"><?=date('M d, Y \a\t h:i A', strtotime($c['created_at']))?></span>
              <span class="comment-blog"><i class="fas fa-file-alt"></i> <?=htmlspecialchars(substr($c['blog_title'], 0, 30))?>...</span>
            </div>
            <p class="comment-text"><?=nl2br(htmlspecialchars($c['comment_text']))?></p>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <?php else: ?>
      <!-- Dashboard View -->
      <div class="content-grid">
        <div class="card">
          <div class="card-header">
            <h3>Daily Post Performance</h3>
            <div class="chart-btns">
              <button class="chart-btn active">Daily</button>
              <button class="chart-btn">Weekly</button>
            </div>
          </div>
          <div class="bar-chart">
            <?php 
            $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
            $daysFull = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
            foreach($days as $i => $day): 
              $count = $dailyStats[$daysFull[$i]] ?? 0;
              $height = $count > 0 ? max(30, $count * 40) : 20;
              $isToday = date('D') == $day;
            ?>
            <div class="bar-item">
              <div class="bar <?=$isToday?'active':''?>" style="height: <?=$height?>px;" title="<?=$count?> posts"></div>
              <span class="bar-label"><?=$day?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="card">
          <h3 style="margin-bottom: 16px;">Quick Actions</h3>
          <div class="quick-actions">
            <a href="create_post.php" class="btn btn-primary"><i class="fas fa-plus"></i> Create New Post</a>
            <a href="#" class="btn btn-outline"><i class="fas fa-edit"></i> Edit Drafts</a>
            <a href="#" class="btn btn-outline"><i class="fas fa-chart-line"></i> View Analytics</a>
          </div>
        </div>
      </div>

      <div class="card table-card">
        <div class="card-header">
          <h3>Your Posts</h3>
          <a href="create_post.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Post</a>
        </div>
        <?php if(empty($posts)): ?>
        <div class="empty-state">
          <i class="fas fa-file-alt"></i>
          <h4>No posts yet</h4>
          <p>Start creating amazing content!</p>
        </div>
        <?php else: ?>
        <table class="data-table">
          <thead><tr><th>Post Title</th><th>Status</th><th>Likes</th><th>Comments</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach($posts as $p): ?>
            <tr>
              <td>
                <div class="table-item">
                  <div class="table-icon"><i class="fas fa-file-alt"></i></div>
                  <div class="table-info">
                    <h4><?=htmlspecialchars(substr($p['title'], 0, 40))?><?=strlen($p['title'])>40?'...':''?></h4>
                    <p><?=date('M d, Y', strtotime($p['created_at']))?></p>
                  </div>
                </div>
              </td>
              <td><span class="status-badge <?=$p['status']?>"><?=ucfirst($p['status'])?></span></td>
              <td><strong><?=intval($p['likes'])?></strong></td>
              <td><strong><?=intval($p['comment_count'])?></strong></td>
              <td>
                <div class="action-btns">
                  <a href="edit_post.php?blog_id=<?=$p['blog_id']?>" class="action-btn" title="Edit"><i class="fas fa-edit"></i></a>
                  <a href="collaborate.php?blog_id=<?=$p['blog_id']?>" class="action-btn" title="Collaborate"><i class="fas fa-users"></i></a>
                  <?php if($p['status']==='approved'): ?>
                  <form method="POST" action="publish_post.php" style="display:inline;">
                    <input type="hidden" name="blog_id" value="<?=$p['blog_id']?>">
                    <button type="submit" class="action-btn success" title="Publish"><i class="fas fa-rocket"></i></button>
                  </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php if($p['editor_remark']): ?>
            <tr><td colspan="5"><div class="editor-remark"><strong><i class="fas fa-comment-dots"></i> Editor:</strong> <?=htmlspecialchars($p['editor_remark'])?></div></td></tr>
            <?php endif; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>