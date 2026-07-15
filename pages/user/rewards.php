<?php $pageTitle = 'Claim Reward'; require __DIR__ . '/../../includes/layouts/user-header.php'; ?>
<section class="grid grid--2">
    <article class="card empty-state"><span class="empty-state__icon">RW</span><h2>Redeem Your Code</h2><p>Enter the code you received to claim an instant cash reward.</p><form method="post" class="grid narrow-form"><div class="field"><label for="code">Redemption Code</label><input id="code" name="code" placeholder="WELCOME50" required></div><button class="button button--primary" type="submit">Claim Bonus</button></form></article>
    <article class="card"><h2>Available Rewards</h2><div class="timeline"><?php foreach ($_SESSION['platform']['rewards_catalog'] as $row): ?><div class="timeline__item" data-search-row><strong><?= e($row['title']) ?> - <?= e(money($row['amount'])) ?></strong><p class="muted"><?= e($row['code']) ?> / <?= e($row['status']) ?> / expires <?= e($row['expires']) ?></p></div><?php endforeach; ?></div></article>
</section>
<?php require __DIR__ . '/../../includes/layouts/user-footer.php'; ?>
