<?php $pageTitle = 'Wallet'; require __DIR__ . '/../../includes/layouts/user-header.php'; $wallet = customer_wallet(current_customer_id()); ?>
<section class="grid grid--4">
    <?php foreach (['available' => 'Available Balance', 'bonus' => 'Bonus Balance', 'investment' => 'Investment Funds', 'referral' => 'Referral Earnings'] as $key => $label): ?>
        <article class="card stat-card"><span><?= e($label) ?></span><strong><?= e(money($wallet[$key])) ?></strong></article>
    <?php endforeach; ?>
</section>
<section class="grid grid--2">
    <article class="card hero-chart"><h2>Balance History</h2><div class="chart-bars"><span class="bar-42"></span><span class="bar-70"></span><span class="bar-52"></span><span class="bar-78"></span></div></article>
    <article class="card"><h2>Quick Wallet Actions</h2><div class="quick-actions"><a class="button button--primary" href="index.php?route=deposit">Quick Deposit</a><a class="button button--ghost" href="index.php?route=withdraw">Quick Withdraw</a><a class="button button--ghost" href="index.php?route=bank">Bind Bank</a></div></article>
</section>
<?php require __DIR__ . '/../../includes/layouts/user-footer.php'; ?>
