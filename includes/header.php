<?php if(session_status()===PHP_SESSION_NONE) session_start(); ?>
<div class="navbar">
  <div class="brand">BlogPublish</div>
  <div class="nav-actions">
    <?php if(isset($_SESSION['fullname'])): ?>
      <span class="user"><?=htmlspecialchars($_SESSION['fullname'])?> (<?=htmlspecialchars($_SESSION['role'])?>)</span>
      <a class="btn-outline" href="/blog_system/dashboard.php">Home</a>
      <a class="btn-outline" href="/blog_system/logout.php">Logout</a>
    <?php else: ?>
      <a class="btn-outline" href="/blog_system/index.php">Login</a>
      <a class="btn-outline" href="/blog_system/register.php">Register</a>
    <?php endif; ?>
  </div>
</div>
