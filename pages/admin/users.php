<?php
$pageTitle = 'Customer Management';
require __DIR__ . '/../../includes/layouts/admin-header.php';

$managedUser = managed_customer();

$customers = $_SESSION['platform']['customers'] ?? [];
$customersSorted = array_values($customers);
usort($customersSorted, fn($a, $b) => ($a['id'] ?? 0) <=> ($b['id'] ?? 0));
$managedId = (int) ($managedUser['id'] ?? 0);
$wallet = customer_wallet($managedId);
$orders = customer_orders($managedId);
$transactions = array_reverse(customer_transactions($managedId));
$notes = array_reverse(customer_admin_notes($managedId));
$walletActions = [
    'increase_available' => 'Increase available balance',
    'decrease_available' => 'Decrease available balance',
    'increase_bonus' => 'Increase bonus balance',
    'decrease_bonus' => 'Decrease bonus balance',
    'lock_funds' => 'Lock funds',
    'unlock_funds' => 'Unlock funds',
    'freeze_wallet' => 'Freeze wallet',
    'unfreeze_wallet' => 'Unfreeze wallet',
    'increase_investment' => 'Increase investment balance',
    'decrease_investment' => 'Decrease investment balance',
    'increase_referral' => 'Increase referral earnings',
    'decrease_referral' => 'Decrease referral earnings',
    'correct_available' => 'Correct available balance',
];
?>
<section class="profile-hero card">
    <div class="profile-identity">
        <?php if (!empty($customersSorted)): ?>
            <form method="get" class="field" style="max-width: 420px; margin-bottom: var(--space-4);">
                <input type="hidden" name="route" value="users">
                <div class="field" style="grid-template-columns: 1fr; margin: 0;">
                    <label for="admin_customer_id">Customer</label>
                    <select id="admin_customer_id" name="user_id" onchange="this.form.submit()">
                        <?php foreach ($customersSorted as $c): ?>
                            <option value="<?= e((string) ($c['id'] ?? '')) ?>" <?= ((int) ($c['id'] ?? 0)) === (int) ($managedUser['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= e(($c['username'] ?? 'customer') . ' - ' . ($c['email'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        <?php endif; ?>
        <span class="avatar avatar--xl"><?= e(strtoupper(substr($managedUser['username'], 0, 1))) ?></span>
        <div>
            <p class="eyebrow">Customer profile</p>
            <h2><?= e($managedUser['full_name']) ?></h2>
            <p class="muted"><?= e($managedUser['email']) ?> / <?= e($managedUser['phone']) ?></p>
        </div>
    </div>
    <div class="profile-actions">
        <span class="<?= status_class($managedUser['status']) ?>"><?= e($managedUser['status']) ?></span>
        <form method="post" data-confirm="Change this customer's account status?">
            <input type="hidden" name="action" value="toggle_user">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <button class="button button--danger" type="submit">Toggle Status</button>
        </form>
    </div>
</section>

<nav class="subnav">
    <?php foreach (['overview', 'wallet', 'investments', 'orders', 'transactions', 'deposits', 'withdrawals', 'referrals', 'support', 'activity', 'notes'] as $section): ?>
        <a href="#<?= e($section) ?>"><?= e(ucwords($section)) ?></a>
    <?php endforeach; ?>
</nav>

<section id="overview" class="grid grid--4">
    <article class="card stat-card admin-kpi"><span>Available</span><strong><?= e(money((float) $wallet['available'])) ?></strong></article>
    <article class="card stat-card admin-kpi"><span>Investment Balance</span><strong><?= e(money((float) $wallet['investment'])) ?></strong></article>
    <article class="card stat-card admin-kpi"><span>Active Orders</span><strong><?= count(array_filter($orders, fn($row) => $row['status'] === 'Active')) ?></strong></article>
    <article class="card stat-card admin-kpi"><span>Wallet Status</span><strong><?= !empty($wallet['frozen']) ? 'Frozen' : 'Operational' ?></strong></article>
</section>

<section class="grid grid--2">
    <article id="wallet" class="card">
        <div class="section-title">
            <div>
                <h2>Wallet Operations</h2>
                <p class="muted">Every action creates a transaction, notification, and activity entry.</p>
            </div>
        </div>
        <form method="post" class="grid" data-confirm="Apply this wallet adjustment?">
            <input type="hidden" name="action" value="wallet_adjustment">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="user_id" value="<?= e((string) $managedUser['id']) ?>">
            <div class="form-grid">
                <div class="field">
                    <label for="operation">Operation</label>
                    <select id="operation" name="operation" required>
                        <?php foreach ($walletActions as $value => $label): ?>
                            <option value="<?= e($value) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="amount">Amount</label>
                    <input id="amount" name="amount" type="number" min="0" step="0.01" value="0.00">
                </div>
            </div>
            <div class="field">
                <label for="reason">Administrative reason</label>
                <textarea id="reason" name="reason" required placeholder="Reference, approval note, or correction reason"></textarea>
            </div>
            <button class="button button--primary" type="submit">Apply Adjustment</button>
        </form>
    </article>

    <article class="card">
        <h2>Wallet Ledger</h2>
        <div class="wallet-grid">
            <?php foreach ($wallet as $key => $value): ?>
                <div>
                    <span><?= e(wallet_label($key)) ?></span>
                    <strong><?= is_bool($value) ? ($value ? 'Yes' : 'No') : e(money((float) $value)) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section id="investments" class="card">
    <div class="section-title"><h2>Investment Performance</h2><a href="index.php?route=orders">Manage orders</a></div>
    <div class="grid grid--2">
        <?php foreach ($orders as $order): ?>
            <article class="portfolio-card">
                <div class="section-title">
                    <div><h3><?= e($order['plan']) ?></h3><p class="muted">Purchased <?= e($order['purchase_date']) ?></p></div>
                    <span class="<?= status_class($order['status']) ?>"><?= e($order['status']) ?></span>
                </div>
                <div class="mini-chart" data-chart data-points="28,34,39,46,53,61,68,74,82"></div>
                <div class="plan-meta">
                    <span>Amount <strong><?= e(money((float) $order['amount'])) ?></strong></span>
                    <span>Earnings <strong><?= e(money((float) $order['profit'])) ?></strong></span>
                    <span>Progress <strong><?= e((string) $order['progress']) ?>%</strong></span>
                </div>
                <progress class="progress" max="100" value="<?= e((string) $order['progress']) ?>"></progress>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section id="transactions" class="table-wrap responsive-table">
    <table>
        <thead><tr><th>Reference</th><th>Type</th><th>Amount</th><th>Old</th><th>New</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach (array_slice($transactions, 0, 10) as $row): ?>
            <tr data-search-row><td data-label="Reference"><?= e($row['id']) ?></td><td data-label="Type"><?= e($row['type']) ?></td><td data-label="Amount"><?= e(money((float) $row['amount'])) ?></td><td data-label="Old"><?= e(money((float) $row['old'])) ?></td><td data-label="New"><?= e(money((float) $row['new'])) ?></td><td data-label="Status"><span class="<?= status_class($row['status']) ?>"><?= e($row['status']) ?></span></td><td data-label="Date"><?= e($row['date']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="grid grid--3">
    <article id="orders" class="card"><h2>Orders</h2><p class="muted"><?= count($orders) ?> recorded order(s).</p><a class="button button--ghost" href="index.php?route=orders">Open Orders</a></article>
    <article id="deposits" class="card"><h2>Deposits</h2><p class="muted"><?= count(customer_deposits($managedId)) ?> deposit request(s).</p><a class="button button--ghost" href="index.php?route=deposits">Review Deposits</a></article>
    <article id="withdrawals" class="card"><h2>Withdrawals</h2><p class="muted"><?= count(customer_withdrawals($managedId)) ?> withdrawal request(s).</p><a class="button button--ghost" href="index.php?route=withdrawals">Review Withdrawals</a></article>
    <article id="support" class="card"><h2>Support Tickets</h2><p class="muted"><?= count(customer_tickets($managedId)) ?> support ticket(s).</p><a class="button button--ghost" href="index.php?route=support">Open Support</a></article>
    <article id="activity" class="card"><h2>Activity History</h2><div class="timeline"><?php foreach (array_slice(array_reverse(customer_activities($managedId)), 0, 4) as $row): ?><div class="timeline__item"><strong><?= e($row['actor']) ?></strong><p class="muted"><?= e($row['message']) ?> / <?= e($row['date']) ?></p></div><?php endforeach; ?></div></article>
</section>

<section id="notes" class="grid grid--2">
    <article class="card">
        <h2>Administrative Notes</h2>
        <form method="post" class="grid">
            <input type="hidden" name="action" value="save_admin_note">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="user_id" value="<?= e((string) $managedUser['id']) ?>">
            <div class="field"><label for="note">New note</label><textarea id="note" name="note" required></textarea></div>
            <button class="button button--primary" type="submit">Save Note</button>
        </form>
    </article>
    <article class="card">
        <h2>Note History</h2>
        <div class="timeline">
            <?php foreach ($notes as $note): ?>
                <div class="timeline__item"><strong><?= e($note['admin']) ?></strong><p><?= e($note['note']) ?></p><p class="muted"><?= e($note['date']) ?></p></div>
            <?php endforeach; ?>
        </div>
    </article>
</section>
<?php require __DIR__ . '/../../includes/layouts/admin-footer.php'; ?>
