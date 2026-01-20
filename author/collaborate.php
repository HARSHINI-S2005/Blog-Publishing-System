<?php
require '../db.php';
require '../includes/functions.php';
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='author') { header('Location: ../index.php'); exit; }

$bid = (int)($_GET['blog_id'] ?? 0);
$stmt = $pdo->prepare("SELECT b.*, u.fullname as author_name, c.fullname as coauthor_name FROM blogs b LEFT JOIN users u ON b.author_id=u.user_id LEFT JOIN users c ON b.co_author_id=c.user_id WHERE b.blog_id=? AND (b.author_id=? OR b.co_author_id=?)");
$stmt->execute([$bid, $_SESSION['user_id'], $_SESSION['user_id']]);
$post = $stmt->fetch();
if(!$post) { header('Location: dashboard.php'); exit; }

$isOwner = $post['author_id'] == $_SESSION['user_id'];
$isCoAuthor = $post['co_author_id'] == $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Collaborate - <?=htmlspecialchars($post['title'])?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', sans-serif; background: #f5f7fa; min-height: 100vh; }
    
    .container { max-width: 1200px; margin: 0 auto; padding: 24px; }
    
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .back-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; color: #1f2937; text-decoration: none; font-weight: 500; transition: all 0.2s; }
    .back-btn:hover { border-color: #1a1a2e; }
    .header-info h1 { font-size: 1.5rem; font-weight: 700; color: #1f2937; }
    .header-info p { color: #6b7280; font-size: 0.9rem; margin-top: 4px; }
    
    .collab-grid { display: grid; grid-template-columns: 1fr 320px; gap: 24px; }
    
    .editor-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; overflow: hidden; }
    .editor-header { background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; }
    .editor-header h3 { font-size: 1rem; display: flex; align-items: center; gap: 8px; }
    .status-indicator { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; }
    .status-dot { width: 10px; height: 10px; border-radius: 50%; background: #10b981; animation: pulse 2s infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    .status-dot.saving { background: #f59e0b; }
    .status-dot.offline { background: #ef4444; animation: none; }
    
    .editor-body { padding: 20px; }
    .editor-body textarea { width: 100%; min-height: 450px; padding: 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem; line-height: 1.8; resize: vertical; font-family: inherit; transition: border-color 0.2s; }
    .editor-body textarea:focus { outline: none; border-color: #6366f1; }
    
    .editor-footer { padding: 16px 20px; background: #f9fafb; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
    .save-info { font-size: 0.85rem; color: #6b7280; }
    .save-info i { margin-right: 6px; }
    .btn { padding: 10px 20px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s; border: none; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; text-decoration: none; }
    .btn-primary { background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(26,26,46,0.3); }
    .btn-outline { background: transparent; border: 1px solid #e5e7eb; color: #374151; }
    
    /* Sidebar */
    .sidebar-card { background: #fff; border-radius: 16px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; margin-bottom: 20px; }
    .sidebar-card h3 { font-size: 1rem; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    
    .collaborator { display: flex; align-items: center; gap: 12px; padding: 12px; background: #f9fafb; border-radius: 10px; margin-bottom: 10px; }
    .collaborator:last-child { margin-bottom: 0; }
    .collab-avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; }
    .collab-avatar.owner { background: linear-gradient(135deg, #e94560, #ff6b6b); }
    .collab-avatar.coauthor { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
    .collab-info { flex: 1; }
    .collab-info h4 { font-size: 0.9rem; font-weight: 600; }
    .collab-info p { font-size: 0.8rem; color: #6b7280; }
    .online-badge { width: 10px; height: 10px; border-radius: 50%; background: #10b981; }
    
    .activity-log { max-height: 200px; overflow-y: auto; }
    .activity-item { padding: 10px 0; border-bottom: 1px solid #f3f4f6; font-size: 0.85rem; }
    .activity-item:last-child { border-bottom: none; }
    .activity-item strong { color: #1f2937; }
    .activity-item span { color: #6b7280; }
    
    .tips-list { font-size: 0.85rem; color: #6b7280; }
    .tips-list li { padding: 8px 0; border-bottom: 1px solid #f3f4f6; display: flex; align-items: flex-start; gap: 8px; }
    .tips-list li:last-child { border-bottom: none; }
    .tips-list i { color: #10b981; margin-top: 2px; }
    
    .conflict-warning { background: #fef3c7; border: 1px solid #f59e0b; border-radius: 10px; padding: 16px; margin-bottom: 20px; }
    .conflict-warning h4 { color: #d97706; font-size: 0.9rem; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
    .conflict-warning p { color: #78350f; font-size: 0.85rem; line-height: 1.5; }

    @media (max-width: 900px) { .collab-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
      <div class="header-info" style="text-align: right;">
        <h1><?=htmlspecialchars($post['title'])?></h1>
        <p>Collaborative Editing</p>
      </div>
    </div>

    <div class="conflict-warning" id="conflictWarning" style="display: none;">
      <h4><i class="fas fa-exclamation-triangle"></i> Content Updated</h4>
      <p>The content has been updated by another collaborator. Click "Refresh" to see the latest version, or continue editing (your changes may overwrite theirs).</p>
      <button class="btn btn-outline" onclick="loadLatest()" style="margin-top: 10px;"><i class="fas fa-sync"></i> Refresh Content</button>
    </div>

    <div class="collab-grid">
      <div class="editor-card">
        <div class="editor-header">
          <h3><i class="fas fa-edit"></i> Document Editor</h3>
          <div class="status-indicator">
            <span class="status-dot" id="statusDot"></span>
            <span id="statusText">Connected</span>
          </div>
        </div>
        <div class="editor-body">
          <textarea id="content" placeholder="Start writing your content here..."><?=htmlspecialchars($post['content'])?></textarea>
        </div>
        <div class="editor-footer">
          <div class="save-info" id="saveInfo">
            <i class="fas fa-cloud-upload-alt"></i> Auto-saves every 3 seconds
          </div>
          <div style="display: flex; gap: 10px;">
            <button class="btn btn-outline" onclick="loadLatest()"><i class="fas fa-sync"></i> Refresh</button>
            <button class="btn btn-primary" onclick="saveNow()"><i class="fas fa-save"></i> Save Now</button>
          </div>
        </div>
      </div>

      <div>
        <div class="sidebar-card">
          <h3><i class="fas fa-users" style="color: #6366f1;"></i> Collaborators</h3>
          <div class="collaborator">
            <div class="collab-avatar owner"><?=strtoupper(substr($post['author_name'], 0, 1))?></div>
            <div class="collab-info">
              <h4><?=htmlspecialchars($post['author_name'])?></h4>
              <p>Owner</p>
            </div>
            <?php if($isOwner): ?><span class="online-badge"></span><?php endif; ?>
          </div>
          <?php if($post['coauthor_name']): ?>
          <div class="collaborator">
            <div class="collab-avatar coauthor"><?=strtoupper(substr($post['coauthor_name'], 0, 1))?></div>
            <div class="collab-info">
              <h4><?=htmlspecialchars($post['coauthor_name'])?></h4>
              <p>Co-Author</p>
            </div>
            <?php if($isCoAuthor): ?><span class="online-badge"></span><?php endif; ?>
          </div>
          <?php else: ?>
          <p style="font-size: 0.85rem; color: #6b7280; padding: 12px; background: #f9fafb; border-radius: 8px;">No co-author assigned yet.</p>
          <?php endif; ?>
        </div>

        <div class="sidebar-card">
          <h3><i class="fas fa-history" style="color: #f59e0b;"></i> Activity</h3>
          <div class="activity-log" id="activityLog">
            <div class="activity-item">
              <strong><?=htmlspecialchars($_SESSION['fullname'])?></strong> <span>joined the session</span>
              <div style="font-size: 0.75rem; color: #9ca3af; margin-top: 4px;"><?=date('h:i A')?></div>
            </div>
          </div>
        </div>

        <div class="sidebar-card">
          <h3><i class="fas fa-lightbulb" style="color: #10b981;"></i> Tips</h3>
          <ul class="tips-list" style="list-style: none;">
            <li><i class="fas fa-check"></i> Changes auto-save every 3 seconds</li>
            <li><i class="fas fa-check"></i> Click "Refresh" to see co-author's changes</li>
            <li><i class="fas fa-check"></i> Communicate before making big edits</li>
            <li><i class="fas fa-check"></i> Last save wins if editing same section</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <script>
    const blogId = <?=$bid?>;
    const currentUser = '<?=htmlspecialchars($_SESSION['fullname'])?>';
    let lastContent = document.getElementById('content').value;
    let lastServerContent = lastContent;
    let saveTimer;
    let checkTimer;
    
    // Save content to server
    function saveContent() {
      const content = document.getElementById('content').value;
      if(content === lastContent) return; // No changes
      
      lastContent = content;
      updateStatus('saving', 'Saving...');
      
      fetch('save_draft.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'blog_id=' + blogId + '&content=' + encodeURIComponent(content)
      })
      .then(res => res.text())
      .then(data => {
        if(data.trim() === 'Saved') {
          lastServerContent = content;
          updateStatus('online', 'Saved');
          addActivity(currentUser, 'saved changes');
          document.getElementById('saveInfo').innerHTML = '<i class="fas fa-check-circle" style="color:#10b981;"></i> Last saved: ' + new Date().toLocaleTimeString();
        } else {
          updateStatus('offline', 'Error saving');
        }
      })
      .catch(() => updateStatus('offline', 'Connection error'));
    }
    
    // Check for updates from server
    function checkUpdates() {
      fetch('get_content.php?blog_id=' + blogId)
      .then(res => res.json())
      .then(data => {
        if(data.content !== lastServerContent && data.content !== document.getElementById('content').value) {
          document.getElementById('conflictWarning').style.display = 'block';
          addActivity(data.last_editor || 'Someone', 'made changes');
        }
      })
      .catch(() => {});
    }
    
    // Load latest content
    function loadLatest() {
      fetch('get_content.php?blog_id=' + blogId)
      .then(res => res.json())
      .then(data => {
        document.getElementById('content').value = data.content;
        lastContent = data.content;
        lastServerContent = data.content;
        document.getElementById('conflictWarning').style.display = 'none';
        addActivity(currentUser, 'refreshed content');
        updateStatus('online', 'Content refreshed');
      });
    }
    
    // Manual save
    function saveNow() {
      saveContent();
    }
    
    // Update status indicator
    function updateStatus(status, text) {
      const dot = document.getElementById('statusDot');
      const statusText = document.getElementById('statusText');
      dot.className = 'status-dot ' + (status === 'saving' ? 'saving' : (status === 'offline' ? 'offline' : ''));
      statusText.textContent = text;
    }
    
    // Add activity to log
    function addActivity(user, action) {
      const log = document.getElementById('activityLog');
      const item = document.createElement('div');
      item.className = 'activity-item';
      item.innerHTML = '<strong>' + user + '</strong> <span>' + action + '</span><div style="font-size:0.75rem;color:#9ca3af;margin-top:4px;">' + new Date().toLocaleTimeString() + '</div>';
      log.insertBefore(item, log.firstChild);
    }
    
    // Auto-save every 3 seconds
    setInterval(saveContent, 3000);
    
    // Check for updates every 5 seconds
    setInterval(checkUpdates, 5000);
    
    // Save on typing stop (debounce)
    document.getElementById('content').addEventListener('input', function() {
      clearTimeout(saveTimer);
      updateStatus('saving', 'Typing...');
      saveTimer = setTimeout(saveContent, 1500);
    });
  </script>
</body>
</html>