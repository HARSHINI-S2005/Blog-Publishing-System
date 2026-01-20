<?php
require '../db.php';
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='editor') { header('Location: ../index.php'); exit; }

$view = $_GET['view'] ?? 'pending';
$editorId = $_SESSION['user_id'];

$pendingCount = $pdo->query("SELECT COUNT(*) FROM blogs WHERE status='pending'")->fetchColumn();
$approvedCount = $pdo->query("SELECT COUNT(*) FROM blogs WHERE status='approved'")->fetchColumn();
$rejectedCount = $pdo->query("SELECT COUNT(*) FROM blogs WHERE status='rejected'")->fetchColumn();
$publishedCount = $pdo->query("SELECT COUNT(*) FROM blogs WHERE status='published'")->fetchColumn();

// My reviewed blogs count
$myReviewedCount = $pdo->prepare("SELECT COUNT(*) FROM blogs WHERE reviewed_by=?");
$myReviewedCount->execute([$editorId]); $myReviewedCount = $myReviewedCount->fetchColumn();

// Fetch blogs based on view
if($view == 'approved') {
    $blogsStmt = $pdo->prepare("SELECT b.*, u.fullname as author_name, e.fullname as editor_name FROM blogs b LEFT JOIN users u ON b.author_id=u.user_id LEFT JOIN users e ON b.reviewed_by=e.user_id WHERE b.status='approved' ORDER BY b.updated_at DESC");
} elseif($view == 'rejected') {
    $blogsStmt = $pdo->prepare("SELECT b.*, u.fullname as author_name, e.fullname as editor_name FROM blogs b LEFT JOIN users u ON b.author_id=u.user_id LEFT JOIN users e ON b.reviewed_by=e.user_id WHERE b.status='rejected' ORDER BY b.updated_at DESC");
} elseif($view == 'myreviews') {
    $blogsStmt = $pdo->prepare("SELECT b.*, u.fullname as author_name, e.fullname as editor_name FROM blogs b LEFT JOIN users u ON b.author_id=u.user_id LEFT JOIN users e ON b.reviewed_by=e.user_id WHERE b.reviewed_by=? ORDER BY b.updated_at DESC");
    $blogsStmt->execute([$editorId]);
} else {
    $blogsStmt = $pdo->prepare("SELECT b.*, u.fullname as author_name FROM blogs b LEFT JOIN users u ON b.author_id=u.user_id WHERE b.status='pending' ORDER BY b.created_at DESC");
}
if($view != 'myreviews') $blogsStmt->execute();
$blogs = $blogsStmt->fetchAll();

$dailyData = $pdo->query("SELECT DAYNAME(updated_at) as day, COUNT(*) as count FROM blogs WHERE status IN ('approved','rejected') AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DAYNAME(updated_at)")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editor Dashboard - BlogPublish</title>
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
    .nav-item .badge { background: #e94560; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; margin-left: auto; }
    .nav-item .badge.green { background: #10b981; }
    .nav-item .badge.red { background: #ef4444; }
    .nav-item .badge.blue { background: #3b82f6; }
    .nav-section { font-size: 0.75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; padding: 16px 16px 8px; letter-spacing: 0.5px; }
    .tools-card { background: linear-gradient(135deg, #1a1a2e, #16213e); border-radius: 16px; padding: 20px; margin-top: 20px; color: white; }
    .tools-card h4 { margin-bottom: 8px; }
    .tools-card p { font-size: 0.85rem; opacity: 0.8; margin-bottom: 12px; }
    .tools-btn { background: #e94560; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; }

    .main { flex: 1; margin-left: 260px; padding: 24px 32px; }
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
    .header h1 { font-size: 1.75rem; font-weight: 700; color: #1f2937; }
    .header p { color: #6b7280; margin-top: 4px; }
    .header-actions { display: flex; align-items: center; gap: 16px; }
    .search-box { display: flex; align-items: center; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 16px; gap: 10px; }
    .search-box input { border: none; outline: none; font-size: 0.9rem; width: 200px; }
    .date-box { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 16px; font-size: 0.9rem; }
    .user-avatar { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, #e94560, #ff6b6b); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; }
    .notif-btn { width: 42px; height: 42px; border-radius: 10px; background: #fff; border: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; }
    .notif-badge { position: absolute; top: -4px; right: -4px; width: 18px; height: 18px; background: #e94560; color: white; font-size: 0.7rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; }

    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px; }
    .stat-card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; transition: all 0.2s; cursor: pointer; text-decoration: none; color: inherit; display: block; }
    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); }
    .stat-card.highlight { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white; }
    .stat-card.highlight .stat-label { color: rgba(255,255,255,0.8); }
    .stat-label { font-size: 0.85rem; color: #6b7280; font-weight: 500; margin-bottom: 8px; }
    .stat-value { font-size: 2rem; font-weight: 700; margin-bottom: 8px; }
    .stat-change { font-size: 0.8rem; display: flex; align-items: center; gap: 4px; }
    .stat-change.success { color: #10b981; }
    .stat-change.danger { color: #ef4444; }

    .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 28px; }
    .card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; }
    .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .card-header h3 { font-size: 1.1rem; font-weight: 600; }

    .bar-chart { display: flex; align-items: flex-end; justify-content: space-between; height: 180px; padding: 20px 0; gap: 12px; }
    .bar-item { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 8px; }
    .bar { width: 100%; max-width: 36px; background: #e5e7eb; border-radius: 6px 6px 0 0; transition: all 0.3s; cursor: pointer; min-height: 10px; }
    .bar:hover { background: #e94560; }
    .bar.active { background: #1a1a2e; }
    .bar-label { font-size: 0.8rem; color: #6b7280; }

    .activity-list { display: flex; flex-direction: column; gap: 12px; }
    .activity-item { display: flex; align-items: center; gap: 12px; padding: 12px; background: #f9fafb; border-radius: 10px; }
    .activity-icon { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .activity-icon.approved { background: #d1fae5; color: #059669; }
    .activity-icon.rejected { background: #fee2e2; color: #dc2626; }
    .activity-info h4 { font-size: 0.9rem; font-weight: 600; margin-bottom: 2px; }
    .activity-info p { font-size: 0.8rem; color: #6b7280; }

    .review-card { border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; margin-bottom: 20px; transition: all 0.2s; }
    .review-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
    .review-card.approved-card { border-left: 4px solid #10b981; }
    .review-card.rejected-card { border-left: 4px solid #ef4444; }
    .review-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
    .review-header h4 { font-size: 1.1rem; font-weight: 600; margin-bottom: 4px; }
    .review-meta { font-size: 0.85rem; color: #6b7280; }
    .review-meta i { margin-right: 4px; }
    .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; }
    .status-badge.pending { background: #fef3c7; color: #d97706; }
    .status-badge.approved { background: #d1fae5; color: #059669; }
    .status-badge.rejected { background: #fee2e2; color: #dc2626; }
    .review-content { background: #f9fafb; padding: 16px; border-radius: 10px; margin-bottom: 16px; line-height: 1.6; color: #374151; max-height: 150px; overflow: hidden; }
    .review-remark { background: #fef3c7; padding: 14px 16px; border-radius: 10px; margin-bottom: 16px; border-left: 4px solid #f59e0b; }
    .review-remark strong { color: #d97706; font-size: 0.85rem; }
    .review-remark p { color: #78350f; margin-top: 6px; line-height: 1.5; }
    .editor-info { background: #eef2ff; padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; border-left: 4px solid #6366f1; display: flex; align-items: center; gap: 12px; }
    .editor-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.85rem; }
    .editor-details { flex: 1; }
    .editor-details strong { color: #4338ca; font-size: 0.9rem; }
    .editor-details p { color: #6366f1; font-size: 0.8rem; margin-top: 2px; }
    .review-actions { display: flex; gap: 12px; align-items: center; }
    .review-actions input { flex: 1; padding: 12px 16px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 0.9rem; }
    .review-actions input:focus { outline: none; border-color: #1a1a2e; }
    .btn { padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s; border: none; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; text-decoration: none; }
    .btn-success { background: #10b981; color: white; }
    .btn-danger { background: #ef4444; color: white; }
    .btn-outline { background: transparent; border: 1px solid #e5e7eb; color: #374151; }

    .empty-state { text-align: center; padding: 60px 20px; }
    .empty-state i { font-size: 4rem; margin-bottom: 16px; opacity: 0.5; }
    .empty-state h4 { font-size: 1.25rem; margin-bottom: 8px; color: #1f2937; }
    .empty-state p { color: #6b7280; }

    .view-title { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
    .view-title h3 { font-size: 1.25rem; font-weight: 700; }
    .view-title .count { background: #1a1a2e; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; }

    @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } .content-grid { grid-template-columns: 1fr; } }
    @media (max-width: 768px) { .sidebar { display: none; } .main { margin-left: 0; padding: 16px; } .stats-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="dashboard">
    <aside class="sidebar">
      <div class="logo">Blog<span>Publish</span></div>
      <nav>
        <a href="dashboard.php" class="nav-item <?=$view=='pending'?'active':''?>"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="dashboard.php?view=pending" class="nav-item"><i class="fas fa-clock"></i> Pending <?php if($pendingCount): ?><span class="badge"><?=$pendingCount?></span><?php endif; ?></a>
        <a href="dashboard.php?view=approved" class="nav-item <?=$view=='approved'?'active':''?>"><i class="fas fa-check-circle"></i> Approved <?php if($approvedCount): ?><span class="badge green"><?=$approvedCount?></span><?php endif; ?></a>
        <a href="dashboard.php?view=rejected" class="nav-item <?=$view=='rejected'?'active':''?>"><i class="fas fa-times-circle"></i> Rejected <?php if($rejectedCount): ?><span class="badge red"><?=$rejectedCount?></span><?php endif; ?></a>
        <a href="dashboard.php?view=myreviews" class="nav-item <?=$view=='myreviews'?'active':''?>"><i class="fas fa-user-check"></i> My Reviews <?php if($myReviewedCount): ?><span class="badge blue"><?=$myReviewedCount?></span><?php endif; ?></a>
        <div class="nav-section">Analytics</div>
        <a href="#" class="nav-item"><i class="fas fa-chart-bar"></i> Reports</a>
        <a href="#" class="nav-item"><i class="fas fa-history"></i> Activity Log</a>
        <div class="nav-section">Account</div>
        <a href="#" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
        <a href="../logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Log out</a>
      </nav>
      <div class="tools-card">
        <h4>Editor Tools</h4>
        <p>Quick review shortcuts</p>
        <button class="tools-btn">View Guide</button>
      </div>
    </aside>

    <main class="main">
      <div class="header">
        <div><h1>Editor Dashboard</h1><p>Review and manage submissions</p></div>
        <div class="header-actions">
          <div class="search-box"><i class="fas fa-search" style="color:#9ca3af;"></i><input type="text" placeholder="Search..."></div>
          <div class="date-box"><i class="fas fa-calendar"></i><?=date('d M Y')?></div>
          <div class="notif-btn"><i class="fas fa-bell"></i><?php if($pendingCount): ?><span class="notif-badge"><?=$pendingCount?></span><?php endif; ?></div>
          <div class="user-avatar"><?=strtoupper(substr($_SESSION['fullname'], 0, 1))?></div>
        </div>
      </div>

      <div class="stats-grid">
        <a href="dashboard.php?view=pending" class="stat-card highlight">
          <div class="stat-label">Pending Review</div>
          <div class="stat-value"><?=$pendingCount?></div>
          <div class="stat-change"><i class="fas fa-clock"></i> Awaiting action</div>
        </a>
        <a href="dashboard.php?view=approved" class="stat-card">
          <div class="stat-label">Approved</div>
          <div class="stat-value"><?=$approvedCount?></div>
          <div class="stat-change success"><i class="fas fa-check"></i> Ready to publish</div>
        </a>
        <div class="stat-card">
          <div class="stat-label">Published</div>
          <div class="stat-value"><?=$publishedCount?></div>
          <div class="stat-change success"><i class="fas fa-globe"></i> Live</div>
        </div>
        <a href="dashboard.php?view=rejected" class="stat-card">
          <div class="stat-label">Rejected</div>
          <div class="stat-value"><?=$rejectedCount?></div>
          <div class="stat-change danger"><i class="fas fa-times"></i> Needs revision</div>
        </a>
      </div>

      <?php if($view == 'pending'): ?>
      <div class="content-grid">
        <div class="card">
          <div class="card-header"><h3>Daily Review Activity</h3></div>
          <div class="bar-chart">
            <?php 
            $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
            $daysFull = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
            foreach($days as $i => $d): 
              $count = $dailyData[$daysFull[$i]] ?? 0;
              $height = $count > 0 ? max(30, $count * 30) : 20;
            ?>
            <div class="bar-item"><div class="bar <?=date('D')==$d?'active':''?>" style="height:<?=$height?>px;"></div><span class="bar-label"><?=$d?></span></div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="card">
          <h3 style="margin-bottom:16px;">Recent Activity</h3>
          <div class="activity-list">
            <?php 
            $recent = $pdo->query("SELECT b.title, b.status, b.updated_at FROM blogs b WHERE b.status IN ('approved','rejected') ORDER BY b.updated_at DESC LIMIT 4")->fetchAll();
            if(empty($recent)): ?>
            <p style="color:#6b7280; text-align:center; padding:20px;">No recent activity</p>
            <?php else: foreach($recent as $act): ?>
            <div class="activity-item">
              <div class="activity-icon <?=$act['status']?>"><i class="fas fa-<?=$act['status']=='approved'?'check':'times'?>"></i></div>
              <div class="activity-info">
                <h4><?=htmlspecialchars(substr($act['title'], 0, 25))?>...</h4>
                <p><?=ucfirst($act['status'])?> • <?=date('M d', strtotime($act['updated_at']))?></p>
              </div>
            </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="card">
        <div class="view-title">
          <?php if($view == 'approved'): ?>
          <i class="fas fa-check-circle" style="color:#10b981; font-size:1.5rem;"></i>
          <h3>Approved Blogs</h3>
          <?php elseif($view == 'rejected'): ?>
          <i class="fas fa-times-circle" style="color:#ef4444; font-size:1.5rem;"></i>
          <h3>Rejected Blogs</h3>
          <?php elseif($view == 'myreviews'): ?>
          <i class="fas fa-user-check" style="color:#6366f1; font-size:1.5rem;"></i>
          <h3>My Reviews</h3>
          <?php else: ?>
          <i class="fas fa-clock" style="color:#f59e0b; font-size:1.5rem;"></i>
          <h3>Pending Reviews</h3>
          <?php endif; ?>
          <span class="count"><?=count($blogs)?></span>
        </div>
        
        <?php if(empty($blogs)): ?>
        <div class="empty-state">
          <i class="fas fa-inbox" style="color:<?=$view=='approved'?'#10b981':($view=='rejected'?'#ef4444':'#f59e0b')?>"></i>
          <h4>No blogs here</h4>
          <p><?=$view=='pending'?'All caught up!':'No blogs in this category'?></p>
        </div>
        <?php else: foreach($blogs as $b): ?>
        <div class="review-card <?=$b['status']=='approved'?'approved-card':($b['status']=='rejected'?'rejected-card':'')?>">
          <div class="review-header">
            <div>
              <h4><?=htmlspecialchars($b['title'])?></h4>
              <p class="review-meta"><i class="fas fa-user"></i> <?=htmlspecialchars($b['author_name'])?> • <i class="fas fa-calendar"></i> <?=date('M d, Y', strtotime($b['created_at']))?></p>
            </div>
            <span class="status-badge <?=$b['status']?>"><?=ucfirst($b['status'])?></span>
          </div>
          <div class="review-content"><?=nl2br(htmlspecialchars(substr($b['content'], 0, 300)))?>...</div>
          
          <?php if(isset($b['editor_name']) && $b['editor_name']): ?>
          <div class="editor-info">
            <div class="editor-avatar"><?=strtoupper(substr($b['editor_name'], 0, 1))?></div>
            <div class="editor-details">
              <strong>Reviewed by: <?=htmlspecialchars($b['editor_name'])?></strong>
              <p><?=ucfirst($b['status'])?> on <?=date('M d, Y \a\t h:i A', strtotime($b['updated_at']))?></p>
            </div>
          </div>
          <?php endif; ?>
          
          <?php if($b['editor_remark']): ?>
          <div class="review-remark">
            <strong><i class="fas fa-comment-dots"></i> Editor's Remark:</strong>
            <p><?=nl2br(htmlspecialchars($b['editor_remark']))?></p>
          </div>
          <?php endif; ?>
          
          <?php if($view == 'pending'): ?>
          <form method="POST" action="review_action.php" class="review-actions">
            <input type="hidden" name="blog_id" value="<?=$b['blog_id']?>">
            <input type="text" name="remark" placeholder="Add remark for author (optional)">
            <button type="submit" name="action" value="approve" class="btn btn-success"><i class="fas fa-check"></i> Approve</button>
            <button type="submit" name="action" value="reject" class="btn btn-danger"><i class="fas fa-times"></i> Reject</button>
          </form>
          <?php endif; ?>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </main>
  </div>
</body>
</html>