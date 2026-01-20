<?php
require '../db.php';
require '../includes/functions.php';
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='reader') { header('Location: ../index.php'); exit; }

$bid = (int)($_GET['blog_id'] ?? 0);
$stmt = $pdo->prepare("SELECT b.*, u.fullname as author_name FROM blogs b LEFT JOIN users u ON b.author_id=u.user_id WHERE b.blog_id=? AND b.status='published'");
$stmt->execute([$bid]);
$post = $stmt->fetch();
if(!$post){ echo "Post not found."; exit; }

// Award read points
$pdo->prepare("UPDATE users SET points = points + 15 WHERE user_id=?")->execute([$_SESSION['user_id']]);

$comments = $pdo->prepare("SELECT c.*, u.fullname FROM comments c LEFT JOIN users u ON c.user_id=u.user_id WHERE c.blog_id=? ORDER BY c.created_at DESC");
$comments->execute([$bid]);
$comments = $comments->fetchAll();

$liked = $pdo->prepare("SELECT * FROM likes WHERE user_id=? AND blog_id=?");
$liked->execute([$_SESSION['user_id'],$bid]);
$hasLiked = $liked->rowCount()>0;

$userPoints = $pdo->prepare("SELECT points FROM users WHERE user_id=?");
$userPoints->execute([$_SESSION['user_id']]);
$points = $userPoints->fetchColumn();

$leaders = $pdo->query("SELECT fullname, points FROM users WHERE role='reader' ORDER BY points DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?=htmlspecialchars($post['title'])?> - BlogPublish</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', sans-serif; background: #f5f7fa; min-height: 100vh; }
    .layout { display: flex; min-height: 100vh; }
    
    /* Sidebar */
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

    /* Main Content */
    .main { flex: 1; margin-left: 260px; padding: 24px 32px; }
    
    /* Header */
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .back-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; color: #1f2937; text-decoration: none; font-weight: 500; transition: all 0.2s; }
    .back-btn:hover { border-color: #1a1a2e; background: #f9fafb; }
    .header-actions { display: flex; align-items: center; gap: 12px; }
    .user-avatar { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, #e94560, #ff6b6b); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; }

    /* Content Grid */
    .content-grid { display: grid; grid-template-columns: 1fr 320px; gap: 24px; }
    
    /* Article Card */
    .article-card { background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; }
    .article-hero { height: 280px; background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%); display: flex; align-items: center; justify-content: center; position: relative; }
    .article-hero i { font-size: 5rem; color: rgba(255,255,255,0.3); }
    .article-hero::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 100px; background: linear-gradient(transparent, rgba(0,0,0,0.3)); }
    
    .article-body { padding: 32px; }
    .article-meta { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; flex-wrap: wrap; }
    .author-info { display: flex; align-items: center; gap: 12px; }
    .author-avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #e94560, #ff6b6b); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.1rem; }
    .author-details h4 { font-size: 1rem; font-weight: 600; color: #1f2937; }
    .author-details p { font-size: 0.85rem; color: #6b7280; }
    .article-date { font-size: 0.85rem; color: #6b7280; display: flex; align-items: center; gap: 6px; margin-left: auto; }
    
    .article-title { font-size: 2rem; font-weight: 800; color: #1f2937; line-height: 1.3; margin-bottom: 24px; }
    .article-content { font-size: 1.05rem; line-height: 1.9; color: #374151; }
    .article-content p { margin-bottom: 20px; }
    
    /* Action Bar */
    .action-bar { display: flex; align-items: center; gap: 16px; padding: 20px 0; margin-top: 24px; border-top: 1px solid #e5e7eb; }
    .like-btn { display: flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 30px; font-weight: 600; cursor: pointer; transition: all 0.3s; border: none; font-size: 0.95rem; }
    .like-btn.liked { background: #fee2e2; color: #e94560; }
    .like-btn.not-liked { background: #1a1a2e; color: white; }
    .like-btn.not-liked:hover { background: #e94560; transform: scale(1.05); }
    .like-btn i { font-size: 1.1rem; }
    .share-btn { padding: 12px 24px; background: #f3f4f6; border-radius: 30px; font-weight: 600; cursor: pointer; border: none; color: #374151; display: flex; align-items: center; gap: 8px; transition: all 0.2s; }
    .share-btn:hover { background: #e5e7eb; }
    .bookmark-btn { width: 48px; height: 48px; border-radius: 50%; background: #f3f4f6; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; margin-left: auto; }
    .bookmark-btn:hover { background: #fef3c7; color: #f59e0b; }

    /* Comments Section */
    .comments-section { background: #fff; border-radius: 20px; padding: 32px; margin-top: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; }
    .section-title { font-size: 1.25rem; font-weight: 700; color: #1f2937; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; }
    .section-title i { color: #e94560; }
    .section-title span { background: #1a1a2e; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; margin-left: 8px; }
    
    /* Comment Form */
    .comment-form { margin-bottom: 32px; }
    .comment-form textarea { width: 100%; padding: 16px 20px; border: 2px solid #e5e7eb; border-radius: 16px; font-size: 0.95rem; resize: none; transition: all 0.2s; font-family: inherit; min-height: 120px; }
    .comment-form textarea:focus { outline: none; border-color: #1a1a2e; box-shadow: 0 0 0 4px rgba(26,26,46,0.1); }
    .comment-form textarea::placeholder { color: #9ca3af; }
    .comment-form-actions { display: flex; justify-content: flex-end; margin-top: 12px; }
    .submit-btn { padding: 12px 28px; background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; border: none; border-radius: 30px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px; }
    .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(26,26,46,0.3); }
    
    /* Comments List */
    .comment-item { display: flex; gap: 16px; padding: 20px 0; border-bottom: 1px solid #f3f4f6; }
    .comment-item:last-child { border-bottom: none; }
    .comment-avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; flex-shrink: 0; }
    .comment-content { flex: 1; }
    .comment-header { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
    .comment-author { font-weight: 600; color: #1f2937; }
    .comment-date { font-size: 0.8rem; color: #9ca3af; }
    .comment-text { color: #4b5563; line-height: 1.7; }
    .comment-actions { display: flex; gap: 16px; margin-top: 12px; }
    .comment-action { font-size: 0.85rem; color: #6b7280; cursor: pointer; display: flex; align-items: center; gap: 4px; transition: color 0.2s; }
    .comment-action:hover { color: #e94560; }

    /* Sidebar Cards */
    .sidebar-card { background: #fff; border-radius: 16px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; margin-bottom: 20px; }
    .sidebar-card h3 { font-size: 1rem; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .sidebar-card h3 i { color: #f59e0b; }
    .leader-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; }
    .leader-rank { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; background: #fef3c7; color: #d97706; }
    .leader-name { flex: 1; font-weight: 600; font-size: 0.9rem; }
    .leader-points { background: #1a1a2e; color: white; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
    
    .related-post { display: block; padding: 12px; background: #f9fafb; border-radius: 10px; margin-bottom: 10px; text-decoration: none; transition: all 0.2s; }
    .related-post:hover { background: #f3f4f6; transform: translateX(4px); }
    .related-post h4 { font-size: 0.9rem; font-weight: 600; color: #1f2937; margin-bottom: 4px; }
    .related-post p { font-size: 0.8rem; color: #6b7280; }

    .empty-comments { text-align: center; padding: 40px 20px; color: #6b7280; }
    .empty-comments i { font-size: 3rem; margin-bottom: 12px; opacity: 0.3; }

    @media (max-width: 1200px) { .content-grid { grid-template-columns: 1fr; } }
    @media (max-width: 768px) { 
      .sidebar { display: none; } 
      .main { margin-left: 0; padding: 16px; } 
      .article-title { font-size: 1.5rem; }
      .article-body { padding: 20px; }
      .action-bar { flex-wrap: wrap; }
    }
  </style>
</head>
<body>
  <div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="logo">Blog<span>Publish</span></div>
      <nav>
        <a href="dashboard.php" class="nav-item"><i class="fas fa-compass"></i> Explore</a>
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

    <!-- Main Content -->
    <main class="main">
      <!-- Header -->
      <div class="header">
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Explore</a>
        <div class="header-actions">
          <div class="user-avatar"><?=strtoupper(substr($_SESSION['fullname'], 0, 1))?></div>
        </div>
      </div>

      <!-- Content Grid -->
      <div class="content-grid">
        <div>
          <!-- Article -->
          <article class="article-card">
            <div class="article-hero">
              <i class="fas fa-newspaper"></i>
            </div>
            <div class="article-body">
              <div class="article-meta">
                <div class="author-info">
                  <div class="author-avatar"><?=strtoupper(substr($post['author_name'] ?? 'A', 0, 1))?></div>
                  <div class="author-details">
                    <h4><?=htmlspecialchars($post['author_name'] ?? 'Anonymous')?></h4>
                    <p>Author</p>
                  </div>
                </div>
                <div class="article-date">
                  <i class="fas fa-calendar"></i>
                  <?=date('F d, Y', strtotime($post['created_at']))?>
                </div>
              </div>
              
              <h1 class="article-title"><?=htmlspecialchars($post['title'])?></h1>
              
              <div class="article-content">
                <?=nl2br(htmlspecialchars($post['content']))?>
              </div>

              <!-- Action Bar -->
              <div class="action-bar">
                <button id="likeBtn" class="like-btn <?=$hasLiked ? 'liked' : 'not-liked'?>">
                  <i class="fas fa-heart"></i>
                  <span><?=$hasLiked ? 'Liked' : 'Like'?></span>
                  <span>(<?=intval($post['likes'])?>)</span>
                </button>
                <button class="share-btn">
                  <i class="fas fa-share"></i> Share
                </button>
                <button class="bookmark-btn">
                  <i class="fas fa-bookmark"></i>
                </button>
              </div>
            </div>
          </article>

          <!-- Comments Section -->
          <section class="comments-section" id="comments">
            <h2 class="section-title">
              <i class="fas fa-comments"></i> Comments
              <span><?=count($comments)?></span>
            </h2>

            <!-- Comment Form -->
            <form class="comment-form" method="POST" action="add_comment.php">
              <input type="hidden" name="blog_id" value="<?=$bid?>">
              <textarea name="comment" placeholder="Share your thoughts on this article..." required></textarea>
              <div class="comment-form-actions">
                <button type="submit" class="submit-btn">
                  <i class="fas fa-paper-plane"></i> Post Comment
                </button>
              </div>
            </form>

            <!-- Comments List -->
            <?php if(empty($comments)): ?>
            <div class="empty-comments">
              <i class="fas fa-comment-slash"></i>
              <p>No comments yet. Be the first to share your thoughts!</p>
            </div>
            <?php else: ?>
            <?php foreach($comments as $c): ?>
            <div class="comment-item">
              <div class="comment-avatar"><?=strtoupper(substr($c['fullname'] ?? 'U', 0, 1))?></div>
              <div class="comment-content">
                <div class="comment-header">
                  <span class="comment-author"><?=htmlspecialchars($c['fullname'] ?? 'User')?></span>
                  <span class="comment-date"><?=date('M d, Y \a\t h:i A', strtotime($c['created_at']))?></span>
                </div>
                <p class="comment-text"><?=nl2br(htmlspecialchars($c['comment_text']))?></p>
                <div class="comment-actions">
                  <span class="comment-action"><i class="fas fa-thumbs-up"></i> Like</span>
                  <span class="comment-action"><i class="fas fa-reply"></i> Reply</span>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </section>
        </div>

        <!-- Sidebar -->
        <aside>
          <!-- Leaderboard -->
          <div class="sidebar-card">
            <h3><i class="fas fa-trophy"></i> Leaderboard</h3>
            <?php $rank = 1; foreach($leaders as $l): ?>
            <div class="leader-item">
              <span class="leader-rank"><?=$rank?></span>
              <span class="leader-name"><?=htmlspecialchars($l['fullname'])?></span>
              <span class="leader-points"><?=intval($l['points'])?></span>
            </div>
            <?php $rank++; endforeach; ?>
          </div>

          <!-- Related Posts -->
          <div class="sidebar-card">
            <h3><i class="fas fa-newspaper" style="color:#667eea;"></i> Related Posts</h3>
            <?php 
            $related = $pdo->query("SELECT blog_id, title FROM blogs WHERE status='published' AND blog_id != $bid ORDER BY RAND() LIMIT 3")->fetchAll();
            foreach($related as $r): 
            ?>
            <a href="view_post.php?blog_id=<?=$r['blog_id']?>" class="related-post">
              <h4><?=htmlspecialchars(substr($r['title'], 0, 40))?>...</h4>
              <p><i class="fas fa-arrow-right"></i> Read more</p>
            </a>
            <?php endforeach; ?>
          </div>

          <!-- Reading Stats -->
          <div class="sidebar-card">
            <h3><i class="fas fa-chart-line" style="color:#10b981;"></i> Your Stats</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; text-align: center;">
              <div style="background: #f9fafb; padding: 16px; border-radius: 12px;">
                <div style="font-size: 1.5rem; font-weight: 700; color: #1a1a2e;"><?=$points?></div>
                <div style="font-size: 0.8rem; color: #6b7280;">Points</div>
              </div>
              <div style="background: #f9fafb; padding: 16px; border-radius: 12px;">
                <div style="font-size: 1.5rem; font-weight: 700; color: #e94560;"><?=floor($points/15)?></div>
                <div style="font-size: 0.8rem; color: #6b7280;">Posts Read</div>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </main>
  </div>

  <script>
    document.getElementById('likeBtn').addEventListener('click', function(){
      var btn = this;
      var xhr = new XMLHttpRequest();
      xhr.open('POST','like_post.php',true);
      xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
      xhr.onload = function(){
        if(xhr.responseText.trim()=='OK') {
          location.reload();
        } else if(xhr.responseText.trim()=='ALREADY_LIKED') {
          alert('You already liked this post!');
        }
      };
      xhr.send('blog_id=<?=$bid?>');
    });
  </script>
</body>
</html>