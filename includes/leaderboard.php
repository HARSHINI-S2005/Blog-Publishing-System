<?php
$leaders = $pdo->query("SELECT fullname, points FROM users WHERE role='reader' ORDER BY points DESC LIMIT 5")->fetchAll();
?>
<div class="card lb-card">
  <h5>Leaderboard</h5>
  <ol class="leader-list">
    <?php foreach($leaders as $l): ?>
      <li><span><?=htmlspecialchars($l['fullname'])?></span><span><?=intval($l['points'])?></span></li>
    <?php endforeach; ?>
  </ol>
</div>
