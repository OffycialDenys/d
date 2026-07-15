<?php $pageTitle = 'Referral Management'; require __DIR__ . '/../../includes/layouts/admin-header.php'; ?>
<section class="grid grid--3"><?php foreach ($_SESSION['platform']['referral_levels'] as $level): ?><article class="card"><h3>Level <?= e((string) $level['level']) ?></h3><p><?= e($level['name']) ?></p><p>Commission: <?= e((string) $level['rate']) ?>%</p><p>Members: <?= e((string) $level['size']) ?></p></article><?php endforeach; ?></section>
<?php require __DIR__ . '/../../includes/layouts/admin-footer.php'; ?>
