<?php
require '../db.php';
require '../includes/functions.php';
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='admin') { header('Location: ../index.php'); exit; }

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalBlogs = $pdo->query("SELECT COUNT(*) FROM blogs")->fetchColumn();
$totalComments = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$totalLikes = $pdo->query("SELECT SUM(likes) FROM blogs")->fetchColumn() ?: 0;
$users = $pdo->query("SELECT user_id,fullname,email,role,points,created_at FROM users ORDER BY created_at DESC")->fetchAll();
$blogs = $pdo->query("SELECT b.*, u.fullname as author_name FROM blogs b LEFT JOIN users u ON b.author_id=u.user_id ORDER BY b.created_at DESC LIMIT 10")->fetchAll();

// Reader Leaderboard (only readers with points)
$readerLeaderboard = $pdo->query("SELECT fullname, points FROM users WHERE role='reader' ORDER BY points DESC LIMIT 5")->fetchAll();

// Daily blog activity
$dailyData = $pdo->query("SELECT DAYNAME(created_at) as day, COUNT(*) as count FROM blogs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DAYNAME(created_at)")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - BlogPublish</title>
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
    .upgrade-card { background: linear-gradient(135deg, #e94560, #ff6b6b); border-radius: 16px; padding: 20px; margin-top: 20px; color: white; }
    .upgrade-card h4 { margin-bottom: 8px; }
    .upgrade-card p { font-size: 0.85rem; opacity: 0.9; margin-bottom: 12px; }
    .upgrade-btn { background: white; color: #e94560; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; }

    .main { flex: 1; margin-left: 260px; padding: 24px 32px; }
    
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
    .header h1 { font-size: 1.75rem; font-weight: 700; color: #1f2937; }
    .header p { color: #6b7280; margin-top: 4px; }
    .header-actions { display: flex; align-items: center; gap: 16px; }
    .search-box { display: flex; align-items: center; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 16px; gap: 10px; }
    .search-box input { border: none; outline: none; font-size: 0.9rem; width: 200px; }
    .date-box { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 16px; font-size: 0.9rem; color: #1f2937; }
    .user-avatar { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, #e94560, #ff6b6b); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; }
    .notif-btn { width: 42px; height: 42px; border-radius: 10px; background: #fff; border: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; }
    .notif-badge { position: absolute; top: -4px; right: -4px; width: 18px; height: 18px; background: #e94560; color: white; font-size: 0.7rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; }

    .tabs { display: flex; gap: 8px; margin-bottom: 24px; }
    .tab { padding: 8px 20px; border: 1px solid #e5e7eb; background: #fff; border-radius: 20px; font-size: 0.85rem; font-weight: 500; color: #6b7280; cursor: pointer; transition: all 0.2s; }
    .tab:hover, .tab.active { background: #1a1a2e; color: white; border-color: #1a1a2e; }

    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px; }
    .stat-card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; transition: all 0.2s; }
    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); }
    .stat-card.highlight { background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; }
    .stat-card.highlight .stat-label { color: rgba(255,255,255,0.7); }
    .stat-label { font-size: 0.85rem; color: #6b7280; font-weight: 500; margin-bottom: 8px; }
    .stat-value { font-size: 2rem; font-weight: 700; margin-bottom: 8px; }
    .stat-change { font-size: 0.8rem; display: flex; align-items: center; gap: 4px; }
    .stat-change.up { color: #10b981; }
    .stat-change.down { color: #ef4444; }

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

    /* Leaderboard */
    .leaderboard { margin-bottom: 20px; }
    .leaderboard h3 { display: flex; align-items: center; gap: 8px; margin-bottom: 16px; font-size: 1rem; }
    .leaderboard h3 i { color: #f59e0b; }
    .leader-item { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f3f4f6; }
    .leader-item:last-child { border-bottom: none; }
    .leader-rank { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700; }
    .leader-rank.gold { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; }
    .leader-rank.silver { background: linear-gradient(135deg, #9ca3af, #6b7280); color: white; }
    .leader-rank.bronze { background: linear-gradient(135deg, #d97706, #b45309); color: white; }
    .leader-rank.normal { background: #f3f4f6; color: #6b7280; }
    .leader-name { flex: 1; font-weight: 600; font-size: 0.95rem; }
    .leader-points { background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }

    .calendar { }
    .cal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .cal-header h4 { font-size: 1rem; font-weight: 600; }
    .cal-nav { display: flex; gap: 8px; }
    .cal-nav button { width: 28px; height: 28px; border: 1px solid #e5e7eb; background: #fff; border-radius: 6px; cursor: pointer; }
    .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; text-align: center; }
    .cal-day-name { font-size: 0.75rem; color: #9ca3af; font-weight: 500; padding: 8px 0; }
    .cal-day { padding: 10px; font-size: 0.85rem; border-radius: 8px; cursor: pointer; transition: all 0.2s; }
    .cal-day:hover { background: #f5f7fa; }
    .cal-day.today { background: #1a1a2e; color: white; font-weight: 600; }

    .growth-card { background: #fff; border-radius: 16px; padding: 20px; margin-top: 20px; border: 1px solid #e5e7eb; display: flex; align-items: center; gap: 16px; }
    .growth-info h4 { font-size: 0.95rem; font-weight: 600; margin-bottom: 4px; }
    .growth-info p { font-size: 0.8rem; color: #10b981; }
    .growth-circle { width: 60px; height: 60px; border-radius: 50%; background: conic-gradient(#1a1a2e 65%, #e5e7eb 0); display: flex; align-items: center; justify-content: center; position: relative; }
    .growth-circle::before { content: ''; position: absolute; width: 48px; height: 48px; background: #fff; border-radius: 50%; }
    .growth-circle span { position: relative; font-weight: 700; font-size: 0.85rem; }

    .table-card { margin-bottom: 24px; }
    .btn { padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; border: none; }
    .btn-primary { background: #1a1a2e; color: white; }
    .btn-outline { background: transparent; border: 1px solid #e5e7eb; color: #1f2937; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th { text-align: left; padding: 12px 16px; font-size: 0.8rem; font-weight: 600; color: #6b7280; text-transform: uppercase; background: #f9fafb; }
    .data-table td { padding: 14px 16px; border-bottom: 1px solid #e5e7eb; }
    .data-table tr:hover { background: #f9fafb; }
    .table-item { display: flex; align-items: center; gap: 12px; }
    .table-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #e94560, #ff6b6b); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.85rem; }
    .table-icon { width: 40px; height: 40px; border-radius: 10px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #6b7280; }
    .table-info h4 { font-size: 0.9rem; font-weight: 600; }
    .table-info p { font-size: 0.8rem; color: #6b7280; }
    .status-badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: capitalize; }
    .status-badge.admin { background: #fef3c7; color: #d97706; }
    .status-badge.editor { background: #dbeafe; color: #2563eb; }
    .status-badge.author { background: #d1fae5; color: #059669; }
    .status-badge.reader { background: #f3f4f6; color: #6b7280; }
    .status-badge.published { background: #d1fae5; color: #059669; }
    .status-badge.pending { background: #fef3c7; color: #d97706; }
    .status-badge.draft { background: #dbeafe; color: #2563eb; }
    .delete-btn { width: 36px; height: 36px; border-radius: 8px; border: 1px solid #fee2e2; background: #fff; color: #ef4444; cursor: pointer; transition: all 0.2s; }
    .delete-btn:hover { background: #ef4444; color: white; }

    @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } .content-grid { grid-template-columns: 1fr; } }
    @media (max-width: 768px) { .sidebar { display: none; } .main { margin-left: 0; padding: 16px; } .stats-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="dashboard">
    <aside class="sidebar">
      <div class="logo">Blog<span>Publish</span></div>
      <nav>
        <a href="dashboard.php" class="nav-item active"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="#users" class="nav-item"><i class="fas fa-users"></i> Users</a>
        <a href="#blogs" class="nav-item"><i class="fas fa-file-alt"></i> All Blogs</a>
        <div class="nav-section">Analytics</div>
        <a href="#" class="nav-item"><i class="fas fa-chart-bar"></i> Reports</a>
        <a href="#" class="nav-item"><i class="fas fa-chart-line"></i> Statistics</a>
        <div class="nav-section">Settings</div>
        <a href="#" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
        <a href="../logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Log out</a>
      </nav>
      <div class="upgrade-card">
        <h4>Admin Pro</h4>
        <p>Get advanced analytics</p>
        <button class="upgrade-btn">Upgrade</button>
      </div>
    </aside>

    <main class="main">
      <div class="header">
        <div><h1>Dashboard</h1><p>Welcome back, <?=htmlspecialchars($_SESSION['fullname'])?></p></div>
        <div class="header-actions">
          <div class="search-box"><i class="fas fa-search" style="color:#9ca3af;"></i><input type="text" placeholder="Search..."></div>
          <div class="date-box"><i class="fas fa-calendar"></i><?=date('d M Y')?></div>
          <div class="notif-btn"><i class="fas fa-bell"></i><span class="notif-badge">3</span></div>
          <div class="user-avatar"><?=strtoupper(substr($_SESSION['fullname'], 0, 1))?></div>
        </div>
      </div>

      <div class="tabs">
        <button class="tab active">Day</button>
        <button class="tab">Week</button>
        <button class="tab">Month</button>
        <button class="tab">Year</button>
      </div>

      <div class="stats-grid">
        <div class="stat-card highlight">
          <div class="stat-label">Total Users</div>
          <div class="stat-value"><?=number_format($totalUsers)?></div>
          <div class="stat-change up"><i class="fas fa-arrow-up"></i> 12% from last month</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Total Blogs</div>
          <div class="stat-value"><?=number_format($totalBlogs)?></div>
          <div class="stat-change up"><i class="fas fa-arrow-up"></i> 8% from last month</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Total Comments</div>
          <div class="stat-value"><?=number_format($totalComments)?></div>
          <div class="stat-change up"><i class="fas fa-arrow-up"></i> 15% from last month</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Total Likes</div>
          <div class="stat-value"><?=number_format($totalLikes)?></div>
          <div class="stat-change down"><i class="fas fa-arrow-down"></i> 3% from last month</div>
        </div>
      </div>

      <div class="content-grid">
        <div class="card">
          <div class="card-header"><h3>Daily Blog Activity</h3></div>
          <div class="bar-chart">
            <?php 
            $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
            $daysFull = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
            foreach($days as $i => $d): 
              $count = $dailyData[$daysFull[$i]] ?? 0;
              $height = $count > 0 ? max(30, $count * 40) : 20;
              $isToday = date('D') == $d;
            ?>
            <div class="bar-item"><div class="bar <?=$isToday?'active':''?>" style="height:<?=$height?>px;" title="<?=$count?> blogs"></div><span class="bar-label"><?=$d?></span></div>
            <?php endforeach; ?>
          </div>
        </div>
        <div>
          <!-- Reader Leaderboard -->
          <div class="card leaderboard">
            <h3><i class="fas fa-trophy"></i> Reader Leaderboard</h3>
            <?php if(empty($readerLeaderboard)): ?>
            <p style="color:#6b7280; text-align:center; padding:20px;">No readers yet</p>
            <?php else: ?>
            <?php $rank = 1; foreach($readerLeaderboard as $l): 
              $rankClass = $rank == 1 ? 'gold' : ($rank == 2 ? 'silver' : ($rank == 3 ? 'bronze' : 'normal'));
            ?>
            <div class="leader-item">
              <span class="leader-rank <?=$rankClass?>"><?=$rank?></span>
              <span class="leader-name"><?=htmlspecialchars($l['fullname'])?></span>
              <span class="leader-points"><?=intval($l['points'])?> pts</span>
            </div>
            <?php $rank++; endforeach; ?>
            <?php endif; ?>
          </div>

          <div class="growth-card">
            <div class="growth-info"><h4>Community growth</h4><p><i class="fas fa-arrow-up"></i> 8.5% from last month</p></div>
            <div class="growth-circle"><span>65%</span></div>
          </div>
        </div>
      </div>

      <div class="card table-card" id="users">
        <div class="card-header"><h3>All Users</h3><button class="btn btn-primary"><i class="fas fa-plus"></i> Add User</button></div>
        <table class="data-table">
          <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach($users as $u): ?>
            <tr>
              <td><div class="table-item"><div class="table-avatar"><?=strtoupper(substr($u['fullname'],0,1))?></div><div class="table-info"><h4><?=htmlspecialchars($u['fullname'])?></h4></div></div></td>
              <td><?=htmlspecialchars($u['email'])?></td>
              <td><span class="status-badge <?=$u['role']?>"><?=ucfirst($u['role'])?></span></td>
              <td><form method="POST" action="delete_user.php" style="display:inline" onsubmit="return confirm('Delete?')"><input type="hidden" name="user_id" value="<?=$u['user_id']?>"><button type="submit" class="delete-btn"><i class="fas fa-trash"></i></button></form></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="card table-card" id="blogs">
        <div class="card-header"><h3>All Blogs</h3><button class="btn btn-outline"><i class="fas fa-sync"></i> Refresh</button></div>
        <table class="data-table">
          <thead><tr><th>Title</th><th>Author</th><th>Status</th><th>Likes</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach($blogs as $b): ?>
            <tr>
              <td><div class="table-item"><div class="table-icon"><i class="fas fa-file-alt"></i></div><div class="table-info"><h4><?=htmlspecialchars(substr($b['title'],0,35))?></h4><p><?=date('M d, Y', strtotime($b['created_at']))?></p></div></div></td>
              <td><?=htmlspecialchars($b['author_name'] ?? '—')?></td>
              <td><span class="status-badge <?=$b['status']?>"><?=ucfirst($b['status'])?></span></td>
              <td><strong><?=intval($b['likes'])?></strong></td>
              <td><form method="POST" action="delete_blog.php" style="display:inline" onsubmit="return confirm('Delete?')"><input type="hidden" name="blog_id" value="<?=$b['blog_id']?>"><button type="submit" class="delete-btn"><i class="fas fa-trash"></i></button></form></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html>