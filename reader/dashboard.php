<?php
require '../db.php';
require '../includes/functions.php';
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='reader') { header('Location: ../index.php'); exit; }

$uid = $_SESSION['user_id'];
$userPoints = $pdo->prepare("SELECT points FROM users WHERE user_id=?");
$userPoints->execute([$uid]);
$points = $userPoints->fetchColumn();

$myComments = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id=?");
$myComments->execute([$uid]);
$commentCount = $myComments->fetchColumn();

$myLikes = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE user_id=?");
$myLikes->execute([$uid]);
$likeCount = $myLikes->fetchColumn();

$search = trim($_GET['q'] ?? '');
if($search){
    $posts = $pdo->prepare("SELECT b.*, u.fullname as author_name, (SELECT COUNT(*) FROM comments WHERE blog_id=b.blog_id) as comment_count FROM blogs b LEFT JOIN users u ON b.author_id=u.user_id WHERE b.status='published' AND (b.title LIKE ? OR b.content LIKE ?) ORDER BY b.created_at DESC");
    $q = "%$search%"; 
    $posts->execute([$q,$q]);
} else {
    $posts = $pdo->prepare("SELECT b.*, u.fullname as author_name, (SELECT COUNT(*) FROM comments WHERE blog_id=b.blog_id) as comment_count FROM blogs b LEFT JOIN users u ON b.author_id=u.user_id WHERE b.status='published' ORDER BY b.created_at DESC");
    $posts->execute();
}
$posts = $posts->fetchAll();
$totalBlogs = count($posts);

$leaders = $pdo->query("SELECT fullname, points FROM users WHERE role='reader' ORDER BY points DESC LIMIT 5")->fetchAll();
$popular = $pdo->query("SELECT b.*, u.fullname as author_name FROM blogs b LEFT JOIN users u ON b.author_id=u.user_id WHERE b.status='published' ORDER BY b.likes DESC LIMIT 3")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Explore - BlogPublish</title>
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
    .rank-card { background: linear-gradient(135deg, #1a1a2e, #16213e); border-radius: 16px; padding: 20px; margin-top: 20px; color: white; text-align: center; }
    .rank-card h4 { font-size: 0.85rem; opacity: 0.7; margin-bottom: 8px; }
    .rank-card .badge-name { font-size: 1.5rem; font-weight: 700; margin-bottom: 4px; }
    .rank-card .points { font-size: 0.9rem; opacity: 0.8; }

    .main { flex: 1; margin-left: 260px; padding: 24px 32px; }
    
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
    .header h1 { font-size: 1.75rem; font-weight: 700; color: #1f2937; }
    .header p { color: #6b7280; margin-top: 4px; }
    .header-actions { display: flex; align-items: center; gap: 12px; }
    .search-form { display: flex; gap: 8px; }
    .search-box { display: flex; align-items: center; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 16px; gap: 10px; }
    .search-box input { border: none; outline: none; font-size: 0.9rem; width: 220px; }
    .btn { padding: 10px 20px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; border: none; }
    .btn-primary { background: #1a1a2e; color: white; }
    .btn-primary:hover { transform: translateY(-2px); }
    .user-avatar { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, #e94560, #ff6b6b); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; }
    .notif-btn { width: 42px; height: 42px; border-radius: 10px; background: #fff; border: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: center; cursor: pointer; }

    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px; }
    .stat-card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; transition: all 0.2s; }
    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); }
    .stat-card.highlight { background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; }
    .stat-card.highlight .stat-label { color: rgba(255,255,255,0.7); }
    .stat-label { font-size: 0.85rem; color: #6b7280; font-weight: 500; margin-bottom: 8px; }
    .stat-value { font-size: 2rem; font-weight: 700; margin-bottom: 8px; }
    .stat-change { font-size: 0.8rem; color: #10b981; display: flex; align-items: center; gap: 4px; }

    .content-wrapper { display: grid; grid-template-columns: 1fr 320px; gap: 24px; }
    
    .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .section-header h3 { font-size: 1.25rem; font-weight: 700; }
    .section-header span { color: #6b7280; font-weight: 400; font-size: 0.9rem; }
    .tabs { display: flex; gap: 8px; }
    .tab { padding: 8px 16px; border: 1px solid #e5e7eb; background: #fff; border-radius: 20px; font-size: 0.85rem; font-weight: 500; color: #6b7280; cursor: pointer; transition: all 0.2s; }
    .tab:hover, .tab.active { background: #1a1a2e; color: white; border-color: #1a1a2e; }

    .posts-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
    .post-card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; transition: all 0.3s; }
    .post-card:hover { transform: translateY(-6px); box-shadow: 0 16px 32px rgba(0,0,0,0.12); }
    .post-img { height: 160px; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; }
    .post-img i { font-size: 3rem; color: rgba(255,255,255,0.3); }
    .post-body { padding: 20px; }
    .post-meta { font-size: 0.85rem; color: #6b7280; margin-bottom: 8px; }
    .post-title { font-size: 1.1rem; font-weight: 700; color: #1f2937; margin-bottom: 8px; line-height: 1.4; }
    .post-excerpt { font-size: 0.9rem; color: #6b7280; line-height: 1.6; margin-bottom: 16px; }
    .post-footer { display: flex; justify-content: space-between; align-items: center; }
    .post-stats { display: flex; gap: 12px; font-size: 0.85rem; color: #6b7280; }
    .read-btn { padding: 8px 16px; background: #1a1a2e; color: white; border-radius: 8px; font-weight: 600; font-size: 0.85rem; text-decoration: none; transition: all 0.2s; }
    .read-btn:hover { background: #e94560; }

    .sidebar-card { background: #fff; border-radius: 16px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; margin-bottom: 20px; }
    .sidebar-card h3 { font-size: 1rem; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .sidebar-card h3 i { color: #f59e0b; }
    
    .leader-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; }
    .leader-rank { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; }
    .leader-rank.gold { background: #fef3c7; color: #d97706; }
    .leader-rank.silver { background: #f3f4f6; color: #6b7280; }
    .leader-name { flex: 1; font-weight: 600; }
    .leader-points { background: #1a1a2e; color: white; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }

    .popular-item { display: block; background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; padding: 16px; border-radius: 12px; margin-bottom: 12px; text-decoration: none; transition: all 0.2s; }
    .popular-item:hover { transform: translateX(4px); }
    .popular-item h4 { font-size: 0.95rem; margin-bottom: 4px; }
    .popular-item p { font-size: 0.8rem; opacity: 0.8; }

    .profile-card { text-align: center; padding: 24px; }
    .profile-avatar { width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(135deg, #e94560, #ff6b6b); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.5rem; margin: 0 auto 12px; }
    .profile-name { font-weight: 700; font-size: 1.1rem; margin-bottom: 4px; }
    .profile-badge { font-weight: 600; margin: 8px 0; }
    .profile-points { color: #6b7280; font-size: 0.9rem; }

    .empty-state { text-align: center; padding: 60px 20px; background: #fff; border-radius: 16px; border: 1px solid #e5e7eb; }
    .empty-state i { font-size: 3rem; color: #9ca3af; margin-bottom: 16px; }

    @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } .content-wrapper { grid-template-columns: 1fr; } }
    @media (max-width: 768px) { .sidebar { display: none; } .main { margin-left: 0; padding: 16px; } .stats-grid { grid-template-columns: 1fr; } .posts-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="dashboard">
    <aside class="sidebar">
      <div class="logo">Blog<span>Publish</span></div>
      <nav>
        <a href="dashboard.php" class="nav-item active"><i class="fas fa-compass"></i> Explore</a>
        <a href="#" class="nav-item"><i class="fas fa-fire"></i> Trending</a>
        <a href="#" class="nav-item"><i class="fas fa-bookmark"></i> Saved</a>
        <a href="#" class="nav-item"><i class="fas fa-heart"></i> Liked Posts</a>
        <div class="nav-section">Categories</div>
        <a href="#" class="nav-item"><i class="fas fa-laptop-code"></i> Technology</a>
        <a href="#" class="nav-item"><i class="fas fa-briefcase"></i> Business</a>
        <a href="#" class="nav-item"><i class="fas fa-palette"></i> Design</a>
        <div class="nav-section">Account</div>
        <a href="#" class="nav-item"><i class="fas fa-user"></i> Profile</a>
        <a href="../logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Log out</a>
      </nav>
      <div class="rank-card">
        <h4>Your Rank</h4>
        <div class="badge-name" style="color:<?=badge_color(badge_for_points($points))?>"><?=badge_for_points($points)?></div>
        <div class="points"><?=$points?> Points</div>
      </div>
    </aside>

    <main class="main">
      <div class="header">
        <div><h1>Explore</h1><p>Discover amazing stories</p></div>
        <div class="header-actions">
          <form method="GET" class="search-form">
            <div class="search-box"><i class="fas fa-search" style="color:#9ca3af;"></i><input type="text" name="q" placeholder="Search posts..." value="<?=htmlspecialchars($search)?>"></div>
            <button type="submit" class="btn btn-primary">Search</button>
          </form>
          <div class="notif-btn"><i class="fas fa-bell"></i></div>
          <div class="user-avatar"><?=strtoupper(substr($_SESSION['fullname'], 0, 1))?></div>
        </div>
      </div>

      <div class="stats-grid">
        <div class="stat-card highlight">
          <div class="stat-label">Your Points</div>
          <div class="stat-value"><?=number_format($points)?></div>
          <div class="stat-change"><i class="fas fa-star"></i> <?=badge_for_points($points)?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Posts Read</div>
          <div class="stat-value"><?=floor($points/15)?></div>
          <div class="stat-change"><i class="fas fa-book-open"></i> Keep reading!</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Comments</div>
          <div class="stat-value"><?=$commentCount?></div>
          <div class="stat-change"><i class="fas fa-comment"></i> Great!</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Likes Given</div>
          <div class="stat-value"><?=$likeCount?></div>
          <div class="stat-change"><i class="fas fa-heart"></i> Supporting</div>
        </div>
      </div>

      <div class="content-wrapper">
        <div>
          <div class="section-header">
            <h3><?=$search ? 'Search Results' : 'Latest Posts'?> <span>(<?=$totalBlogs?>)</span></h3>
            <div class="tabs">
              <button class="tab active">Latest</button>
              <button class="tab">Popular</button>
              <button class="tab">Following</button>
            </div>
          </div>
          
          <?php if(empty($posts)): ?>
          <div class="empty-state">
            <i class="fas fa-search"></i>
            <h4>No posts found</h4>
            <p>Try a different search term</p>
          </div>
          <?php else: ?>
          <div class="posts-grid">
            <?php foreach($posts as $p): ?>
            <div class="post-card">
              <div class="post-img"><i class="fas fa-newspaper"></i></div>
              <div class="post-body">
                <div class="post-meta"><i class="fas fa-user"></i> <?=htmlspecialchars($p['author_name'] ?? 'Anonymous')?> • <?=date('M d, Y', strtotime($p['created_at']))?></div>
                <h3 class="post-title"><?=htmlspecialchars($p['title'])?></h3>
                <p class="post-excerpt"><?=htmlspecialchars(substr($p['content'], 0, 100))?>...</p>
                <div class="post-footer">
                  <div class="post-stats">
                    <span><i class="fas fa-heart"></i> <?=intval($p['likes'])?></span>
                    <span><i class="fas fa-comment"></i> <?=intval($p['comment_count'])?></span>
                  </div>
                  <a href="view_post.php?blog_id=<?=$p['blog_id']?>" class="read-btn">Read <i class="fas fa-arrow-right"></i></a>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <div>
          <div class="sidebar-card">
            <h3><i class="fas fa-trophy"></i> Leaderboard</h3>
            <?php $rank = 1; foreach($leaders as $l): ?>
            <div class="leader-item">
              <span class="leader-rank <?=$rank<=3?'gold':'silver'?>"><?=$rank?></span>
              <span class="leader-name"><?=htmlspecialchars($l['fullname'])?></span>
              <span class="leader-points"><?=intval($l['points'])?></span>
            </div>
            <?php $rank++; endforeach; ?>
          </div>

          <div class="sidebar-card">
            <h3><i class="fas fa-fire" style="color:#e94560;"></i> Popular</h3>
            <?php foreach($popular as $pop): ?>
            <a href="view_post.php?blog_id=<?=$pop['blog_id']?>" class="popular-item">
              <h4><?=htmlspecialchars(substr($pop['title'], 0, 35))?>...</h4>
              <p><i class="fas fa-heart"></i> <?=intval($pop['likes'])?> likes</p>
            </a>
            <?php endforeach; ?>
          </div>

          <div class="sidebar-card profile-card">
            <div class="profile-avatar"><?=strtoupper(substr($_SESSION['fullname'], 0, 1))?></div>
            <div class="profile-name"><?=htmlspecialchars($_SESSION['fullname'])?></div>
            <div class="profile-badge" style="color:<?=badge_color(badge_for_points($points))?>"><?=badge_for_points($points)?> Reader</div>
            <div class="profile-points"><?=$points?> Points</div>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>
</html>