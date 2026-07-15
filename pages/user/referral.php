<?php $pageTitle = 'Network Team'; require __DIR__ . '/../../includes/layouts/user-header.php'; $code = $user['referral_code']; ?>
<section class="card dashboard-hero"><div><p class="eyebrow">Referral Center</p><h2>Grow your network team</h2><p>Share your code and earn from configured referral levels.</p></div></section>
<section class="card"><h2>Share Your Referral Link</h2><div class="copy-box"><span><?= e($code) ?></span><button class="button button--primary" type="button" data-copy="<?= e($code) ?>">Copy</button></div></section>
<section class="grid grid--2"><article class="card stat-card"><span>Team Size</span><strong>0</strong></article><article class="card stat-card"><span>Total Earn</span><strong><?= e(money(customer_wallet(current_customer_id())['referral'])) ?></strong></article></section>
<section class="grid">
    <h2>My Network Levels</h2>
    <?php foreach ($_SESSION['platform']['referral_levels'] as $level): ?>
        <article class="card"><div class="section-title"><div><h3>Level <?= e((string) $level['level']) ?></h3><p class="muted"><?= e($level['name']) ?></p></div><div><strong>Size: <?= e((string) $level['size']) ?></strong><br><strong>Earn: <?= e(money($level['earn'])) ?></strong></div></div></article>
    <?php endforeach; ?>
    <article class="card"><h3>Referral Rewards</h3><p>LV1 Investment Rebate: 11%</p><p>LV2 Investment Rebate: 2%</p><p>LV3 Investment Rebate: 1%</p></article>
</section>
<?php require __DIR__ . '/../../includes/layouts/user-footer.php'; ?>
