<?php $pageTitle = 'Payment Settings'; require __DIR__ . '/../../includes/layouts/admin-header.php'; $settings = $_SESSION['platform']['settings']; ?>
<section class="grid grid--2">
    <article class="card"><h2>Deposit Rules</h2><p>Minimum: <?= e(money((float) $settings['min_deposit'])) ?></p><p>Maximum: <?= e(money((float) $settings['max_deposit'])) ?></p><p>Supported: USDT(BEP20), USDT(TRC20), Binance Pay Id</p></article>
    <article class="card"><h2>Withdrawal Rules</h2><p>Minimum: <?= e(money((float) $settings['min_withdrawal'])) ?></p><p>Fee: <?= e((string) $settings['fee']) ?>%</p><p>Bank details must be bound before processing.</p></article>
</section>

<section class="card">
    <h2>Crypto Wallet Addresses</h2>
    <p class="muted">Update the deposit addresses users see. Changes apply immediately across the platform and are the single source of truth for new deposits.</p>
    <form method="post" class="grid">
        <input type="hidden" name="action" value="save_crypto_wallets">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php foreach (get_crypto_wallets() as $symbol => $w): ?>
            <div class="form-grid" style="grid-column: 1 / -1;">
                <div class="field">
                    <label for="wallet_<?= e($symbol) ?>"><?= e($w['name']) ?> (<?= e($symbol) ?>) Address</label>
                    <input id="wallet_<?= e($symbol) ?>" name="wallet_<?= e($symbol) ?>" value="<?= e($w['address']) ?>" required>
                </div>
                <div class="field">
                    <label for="network_<?= e($symbol) ?>">Network</label>
                    <input id="network_<?= e($symbol) ?>" name="network_<?= e($symbol) ?>" value="<?= e($w['network']) ?>">
                </div>
            </div>
        <?php endforeach; ?>
        <button class="button button--primary" type="submit">Save Wallet Addresses</button>
    </form>
</section>
<?php require __DIR__ . '/../../includes/layouts/admin-footer.php'; ?>
