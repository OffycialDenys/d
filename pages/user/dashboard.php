<?php
$pageTitle = 'Investor Dashboard';
require __DIR__ . '/../../includes/layouts/user-header.php';
$wallet = customer_wallet(current_customer_id());
$activeOrders = array_filter(customer_orders(current_customer_id()), fn($row) => $row['status'] === 'Active');
?>

<section class="card dashboard-hero">
    <div>
        <p class="eyebrow">Welcome Back</p>
        <h2><?= e($user['full_name']) ?>, Your Portfolio is Active</h2>
        <p><?= date('l, F j, Y') ?>. Monitor simulated asset performance, manage balances, and track orders in real-time.</p>
    </div>
    <span class="membership-badge"><?= e($user['membership']) ?></span>
</section>

<!-- Stats Grid -->
<section class="grid grid--4">
    <article class="card stat-card"><span>Available Balance</span><strong><?= e(money($wallet['available'])) ?></strong></article>
    <article class="card stat-card"><span>Total Invested</span><strong><?= e(money($wallet['investment'])) ?></strong></article>
    <article class="card stat-card"><span>Monthly Profit</span><strong><?= e(money(app_metric('monthly_earnings'))) ?></strong></article>
    <article class="card stat-card"><span>Referral Commissions</span><strong><?= e(money($wallet['referral'])) ?></strong></article>
</section>

<!-- Portfolio Section Header -->
<div class="section-title" style="margin-top: var(--space-4);">
    <div>
        <h2>Your Holdings & Investments</h2>
        <p class="muted">Live performance tracking of your purchased assets</p>
    </div>
</div>

<?php if (empty($activeOrders)): ?>
    <section class="empty-state">
        <span class="empty-state__icon">💼</span>
        <h3>No Holdings Yet</h3>
        <p>You haven't opened any asset orders. Browse the marketplace to start investing.</p>
        <a class="button button--primary" href="index.php?route=investments">Open Marketplace</a>
    </section>
<?php else: ?>
    <div class="portfolio-grid">
        <?php foreach ($activeOrders as $order): 
            $planName = $order['plan'];
            $symbol = $order['symbol'] ?? 'ASSET';
            $imageKey = $order['image'] ?? 'aapl-logo';
            $category = (strpos(strtolower($planName), 'bitcoin') !== false || strpos(strtolower($planName), 'ethereum') !== false) ? 'crypto' : 'stock';
            
            // Find plan ID for linking to details
            $planId = null;
            foreach ($_SESSION['platform']['plans'] as $plan) {
                if (strtolower($plan['name'] ?? '') === strtolower($planName) || 
                    strtolower($plan['symbol'] ?? '') === strtolower($symbol)) {
                    $planId = $plan['id'];
                    break;
                }
            }
            
            // If not found by name/symbol, try to find by the order's plan field
            if (!$planId) {
                foreach ($_SESSION['platform']['plans'] as $plan) {
                    if (strtolower($plan['name'] ?? '') === strtolower($order['plan'] ?? '')) {
                        $planId = $plan['id'];
                        break;
                    }
                }
            }
            
            // Fallback to first plan if still not found (shouldn't happen in demo)
            if (!$planId && !empty($_SESSION['platform']['plans'])) {
                $planId = $_SESSION['platform']['plans'][0]['id'];
            }
            
            // Calculate simulated current value
            // We can add the accumulated simulated profit to purchase amount
            $profitAccumulated = (float)($order['profit'] > 0 ? $order['profit'] : $order['amount'] * 0.018); // fallback demo profit
            $currentValue = $order['amount'] + $profitAccumulated;
            $roiVal = ($currentValue / $order['amount']) * 100 - 100;
            
            // Calculate progress & remaining days
            $daysPassed = 12; // simulated
            $totalDays = 30; // simulated
            $progressPercent = $order['progress'] > 0 ? $order['progress'] : round(($daysPassed / $totalDays) * 100);
            $daysRemaining = $totalDays - $daysPassed;
            
            // Sparkline points
            $points = '40,42,41,43,45,44,47,48';
        ?>
            <a href="index.php?route=owned-investment-details&id=<?= e($planId) ?>" class="portfolio-card-link" style="text-decoration:none; display:block;">
                <article class="portfolio-card-premium">
                    <!-- Card Header -->
                    <div class="portfolio-card-header">
                        <div class="asset-header-cell">
                            <div class="asset-logo-wrap <?= $category ?>">
                                <?= strtoupper(substr($symbol, 0, 2)) ?>
                            </div>
                            <div class="asset-info">
                                <span class="asset-name" style="font-size:1.05rem;"><?= e($planName) ?></span>
                                <span class="asset-symbol"><?= e($symbol) ?></span>
                            </div>
                        </div>
                        <span class="status status--active"><?= e($order['status']) ?></span>
                    </div>

                    <!-- Live Performance Chart -->
                    <div class="mini-chart" style="min-height: 80px;" data-chart data-points="<?= $points ?>"></div>

                    <!-- Stats Summary -->
                    <div class="portfolio-card-stats">
                        <div>
                            <span class="muted" style="font-size:.78rem; font-weight:700;">Purchase Value</span>
                            <div style="font-size:1.15rem; font-weight:800;">$<?= number_format((float)$order['amount'], 2) ?></div>
                        </div>
                        <div style="text-align:right;">
                            <span class="muted" style="font-size:.78rem; font-weight:700;">Simulated Value</span>
                            <div style="font-size:1.15rem; font-weight:800; color:var(--color-primary);">$<?= number_format($currentValue, 2) ?></div>
                        </div>
                    </div>

                    <!-- ROI & Returns Details -->
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:.85rem;">
                        <div>
                            <span class="muted">Returns: </span>
                            <span class="change-up">+$<?= number_format($profitAccumulated, 2) ?></span>
                        </div>
                        <div>
                            <span class="muted">ROI: </span>
                            <span class="change-up">+<?= number_format($roiVal, 2) ?>%</span>
                        </div>
                    </div>

                    <!-- Progress & Duration -->
                    <div style="margin-top: 4px;">
                        <div style="display:flex; justify-content:space-between; font-size:.75rem; margin-bottom: 4px;" class="muted">
                            <span>Holding progress (<?= (int)$progressPercent ?>%)</span>
                            <span><?= $daysRemaining ?> days left</span>
                        </div>
                        <progress class="progress" value="<?= (int)$progressPercent ?>" max="100"></progress>
                    </div>

                    <!-- Footer details / updated time -->
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:.72rem; border-top: 1px solid var(--color-border); padding-top:var(--space-3);" class="muted">
                        <span>Maturity: <?= e($order['completion_date']) ?></span>
                        <span>Updated just now</span>
                    </div>
                </article>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Quick Actions Tiles -->
<section class="grid grid--4" style="margin-top: var(--space-4);">
    <?php foreach ([['deposit','Deposit'], ['withdraw','Withdraw'], ['investments','Marketplace'], ['rewards','Rewards'], ['transactions','Transactions'], ['orders','My Orders'], ['referral','My Team'], ['support','Support']] as [$routeName, $label]): ?>
        <a class="card action-tile card--interactive" href="index.php?route=<?= e($routeName) ?>"><span class="nav-icon"><?= strtoupper(substr($label, 0, 2)) ?></span><strong><?= e($label) ?></strong></a>
    <?php endforeach; ?>
</section>

<!-- Recent Deposits (Phase 9: dashboard integration) -->
<section class="card" style="margin-top: var(--space-4);">
    <div class="section-title" style="margin-bottom: var(--space-3);">
        <div>
            <h2>Recent Deposits</h2>
            <p class="muted">Track the status of every crypto deposit you submit.</p>
        </div>
        <a class="button button--outline" href="index.php?route=deposit">New Deposit</a>
    </div>
    <?php
    $userDeposits = array_reverse(customer_deposits(current_customer_id()));
    if (empty($userDeposits)):
    ?>
        <p class="muted" style="text-align:center; padding:1.5rem;">No deposits yet. Fund your account with cryptocurrency from the <a href="index.php?route=deposit">deposit page</a>.</p>
    <?php else: ?>
        <div class="timeline">
            <?php foreach (array_slice($userDeposits, 0, 5) as $dep):
                $wallet = $dep['crypto'] ?? $dep['method'] ?? 'Deposit';
                $netIcon = $_SESSION['platform']['crypto_wallets'][$dep['crypto'] ?? ''] ?? ['icon' => '◆'];
            ?>
                <div class="timeline__item">
                    <div style="display:flex; justify-content:space-between; gap: var(--space-3); align-items:flex-start;">
                        <div style="display:flex; gap: var(--space-3); align-items:center;">
                            <span class="crypto-option__icon" aria-hidden="true"><?= e($netIcon['icon']) ?></span>
                            <div>
                                <strong><?= e($wallet) ?></strong>
                                <span class="muted" style="font-size:.8rem; display:block;"><?= e($dep['network'] ?? '') ?></span>
                            </div>
                        </div>
                        <span class="<?= status_class($dep['status']) ?>"><?= e($dep['status']) ?></span>
                    </div>
                    <div class="plan-meta" style="margin-top: var(--space-2);">
                        <span>ID <strong><?= e($dep['id']) ?></strong></span>
                        <span>Amount <strong><?= e(($dep['amount'] > 0) ? money((float) $dep['amount']) : '—') ?></strong></span>
                        <span>Submitted <strong><?= e($dep['created_at'] ?? $dep['date'] ?? '-') ?></strong></span>
                    </div>
                    <?php if (!empty($dep['reviewer'])): ?>
                        <p class="muted" style="font-size:.8rem; margin:4px 0 0;">Decision: <strong><?= e($dep['status']) ?></strong> by <?= e($dep['reviewer']) ?> <?= e($dep['reviewed_at'] ?? '') ?></p>
                    <?php endif; ?>
                    <?php if (!empty($dep['notes'])): ?>
                        <p class="muted" style="font-size:.8rem; margin:4px 0 0;"><strong>Note:</strong> <?= e($dep['notes']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align:right; margin-top: var(--space-3);">
            <a class="muted" href="index.php?route=deposit">View all deposits &rarr;</a>
        </div>
    <?php endif; ?>
</section>

<!-- Hot Market Picks & Activities -->
<section class="grid grid--2" style="margin-top: var(--space-4);">
    <article class="card">
        <div class="section-title"><h2>Your Investments</h2><p class="muted">Your purchased investments for quick access</p></div>
        <div class="grid">
            <?php if (empty($activeOrders)): ?>
                <p class="muted" style="text-align:center; padding:2rem;">No investments yet. Visit the marketplace to get started.</p>
            <?php else: ?>
                <?php foreach ($activeOrders as $order): 
                    $planName = $order['plan'];
                    $symbol = $order['symbol'] ?? 'ASSET';
                    // Find plan ID for linking to details
                    $planId = null;
                    foreach ($_SESSION['platform']['plans'] as $plan) {
                        if (strtolower($plan['name'] ?? '') === strtolower($planName) || 
                            strtolower($plan['symbol'] ?? '') === strtolower($symbol)) {
                            $planId = $plan['id'];
                            break;
                        }
                    }
                    if (!$planId) {
                        foreach ($_SESSION['platform']['plans'] as $plan) {
                            if (strtolower($plan['name'] ?? '') === strtolower($order['plan'] ?? '')) {
                                $planId = $plan['id'];
                                break;
                            }
                        }
                    }
                    if (!$planId && !empty($_SESSION['platform']['plans'])) {
                        $planId = $_SESSION['platform']['plans'][0]['id'];
                    }
                ?>
                    <a href="index.php?route=owned-investment-details&id=<?= e($planId) ?>" 
                       class="card investment-item card--interactive" 
                       style="text-decoration:none; display:block; margin-bottom:1rem;">
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:var(--space-3); border-bottom:1px solid var(--color-border);">
                            <div style="display:flex; align-items:center; gap:var(--space-2);">
                                <div class="asset-logo-wrap <?= (strpos(strtolower($planName), 'bitcoin') !== false || strpos(strtolower($planName), 'ethereum') !== false) ? 'crypto' : 'stock' ?>">
                                    <?= strtoupper(substr($symbol, 0, 2)) ?>
                                </div>
                                <div>
                                    <span class="asset-name" style="font-weight:700;"><?= e($planName) ?></span>
                                    <span class="asset-symbol" style="font-size:.85rem;"><?= e($symbol) ?></span>
                                </div>
                            </div>
                            <div style="text-align:right; flex-direction:column; align-items:flex-end;">
                                <span class="status status--active" style="padding:2px 6px; font-size:.75rem;">Active</span>
                                <span class="muted" style="font-size:.85rem;">$<?= number_format((float)($order['amount'] + $order['profit']), 2) ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>

    <article class="card">
        <div class="section-title"><h2>Recent Transactions</h2><a href="index.php?route=transactions">All transactions</a></div>
        <div class="timeline">
            <?php foreach (array_slice(array_reverse(customer_transactions(current_customer_id())), 0, 3) as $row): ?>
                <div class="timeline__item"><strong><?= e($row['type']) ?></strong><p class="muted"><?= e($row['date']) ?> - <?= e(money($row['amount'])) ?></p></div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<?php require __DIR__ . '/../../includes/layouts/user-footer.php'; ?>
