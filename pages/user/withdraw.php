<?php $pageTitle = 'Withdraw'; require __DIR__ . '/../../includes/layouts/user-header.php'; ?>
<section class="grid grid--2">
    <article class="card">
        <h2>Withdrawal Request</h2>
        <p class="muted">Withdrawable balance: <strong><?= e(money(customer_wallet(current_customer_id())['withdrawable'])) ?></strong></p>
        <form method="post" class="grid">
            <div class="field"><label for="destination">Destination</label><input id="destination" name="destination" value="<?= e(customer_field(current_customer_id(), 'bank')['method'] ?? 'Linked bank account') ?>"></div>
            <div class="field"><label for="amount">Amount</label><input id="amount" name="amount" type="number" step="0.01" min="1" required></div>
            <div class="field"><label for="notes">Notes</label><textarea id="notes" name="notes"></textarea></div>
            <button class="button button--primary" type="submit">Submit Withdrawal</button>
            <a class="button button--ghost" href="index.php?route=bank">Bind Bank Card</a>
        </form>
    </article>
    <article class="card"><h2>Pending Requests</h2><?php if (!customer_withdrawals(current_customer_id())): ?><div class="empty-state"><span class="empty-state__icon">WD</span><p>No withdrawals yet.</p></div><?php else: ?><div class="timeline"><?php foreach (array_reverse(customer_withdrawals(current_customer_id())) as $row): ?><div class="timeline__item" data-search-row><strong><?= e($row['id']) ?> - <?= e(money($row['amount'])) ?></strong><p class="muted"><?= e($row['destination']) ?> / <?= e($row['status']) ?></p></div><?php endforeach; ?></div><?php endif; ?></article>
</section>
<?php require __DIR__ . '/../../includes/layouts/user-footer.php'; ?>
