<?php $pageTitle = 'Admin Dashboard'; require __DIR__ . '/../../includes/layouts/admin-header.php';
$pm = platform_metrics(); ?>
<section class="card dashboard-hero"><div><p class="eyebrow">Platform Operations</p><h2>Command center</h2><p>Monitor users, deposits, withdrawals, orders, rewards, support, and system activity from one console.</p></div></section>
<section class="grid grid--4">
    <article class="card stat-card admin-kpi"><span>Total Users</span><strong><?= e((string) count($_SESSION['platform']['customers'] ?? [])) ?></strong></article>
    <article class="card stat-card admin-kpi"><span>Active Investments</span><strong><?= e((string) ($pm['active_investments'] ?? 0)) ?></strong></article>
    <article class="card stat-card admin-kpi"><span>Total Deposits</span><strong><?= e(money($pm['total_deposits'] ?? 0)) ?></strong></article>
    <article class="card stat-card admin-kpi"><span>Pending Withdrawals</span><strong><?= count(array_filter(all_withdrawals(), fn($row) => $row['status'] === 'Pending')) ?></strong></article>
</section>
<section class="grid grid--2">
    <article class="card"><h2>Latest Transactions</h2><div class="timeline"><?php foreach (array_slice(array_reverse(all_transactions()), 0, 5) as $row): ?><div class="timeline__item"><strong><?= e($row['id']) ?> - <?= e($row['type']) ?></strong><p class="muted"><?= e(money($row['amount'])) ?> / <?= e($row['status']) ?></p></div><?php endforeach; ?></div></article>
    <article class="card"><h2>Administrator Activity</h2><div class="timeline"><?php foreach (array_slice(array_reverse(all_activities()), 0, 5) as $row): ?><div class="timeline__item"><strong><?= e($row['actor']) ?></strong><p class="muted"><?= e($row['message']) ?> / <?= e($row['date']) ?></p></div><?php endforeach; ?></div></article>
</section>
<?php require __DIR__ . '/../../includes/layouts/admin-footer.php'; ?>
