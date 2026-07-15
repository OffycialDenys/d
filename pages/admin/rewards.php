<?php $pageTitle = 'Reward Management'; require __DIR__ . '/../../includes/layouts/admin-header.php'; ?>
<section class="grid grid--3"><?php foreach ($_SESSION['platform']['rewards_catalog'] as $row): ?><article class="card" data-search-row><h3><?= e($row['title']) ?></h3><p>Code: <?= e($row['code']) ?></p><p>Amount: <?= e(money($row['amount'])) ?></p><span class="<?= status_class($row['status']) ?>"><?= e($row['status']) ?></span></article><?php endforeach; ?></section>
<?php require __DIR__ . '/../../includes/layouts/admin-footer.php'; ?>
