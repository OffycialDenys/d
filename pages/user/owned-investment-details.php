<?php
$pageTitle = 'Investment Details';
$planId = (int) ($_GET['id'] ?? 0);

/**
 * Owned details view expects a marketplace plan id and aggregates the user-owned
 * "orders" from session matching that plan.
 */
$plan = null;
if ($planId > 0) {
    $plan = current(array_filter($_SESSION['platform']['plans'], fn($row) => (int) ($row['id'] ?? 0) === $planId));
}
if (!$plan) {
    $plan = current($_SESSION['platform']['plans']) ?: null;
}

require __DIR__ . '/../../includes/layouts/user-header.php';

if (!$plan) {
    require __DIR__ . '/../../includes/layouts/user-footer.php';
    exit;
}

$categoryClass = strtolower($plan['category'] ?? 'stock');
$riskClass     = strtolower($plan['risk_level'] ?? 'medium');
$dailyChange   = ($plan['daily_return'] > 0) ? (float) $plan['daily_return'] : 0.45;
$keyPoints     = array_filter(explode("\n", str_replace("\r", '', (string) ($plan['key_points'] ?? ''))));

$activeOrders = array_values(array_filter(
    customer_orders(current_customer_id()),
    fn($o) => strtolower($o['plan'] ?? '') === strtolower($plan['name'] ?? '') && ($o['status'] ?? '') === 'Active'
));

$hasHoldings    = !empty($activeOrders);
$holdingsAmount = 0.0;
$holdingsProfit = 0.0;
foreach ($activeOrders as $order) {
    $holdingsAmount += (float) ($order['amount'] ?? 0);
    $holdingsProfit += (float) ($order['profit'] ?? 0);
}
$holdingsCurrentValue = $holdingsAmount + $holdingsProfit;
$holdingsRoi = $holdingsAmount > 0 ? (($holdingsCurrentValue / $holdingsAmount) * 100 - 100) : 0.0;

$totalProgress = 0.0;
$progressCount = 0;
$completionDates = [];
foreach ($activeOrders as $order) {
    if (isset($order['progress'])) {
        $totalProgress += (float) $order['progress'];
        $progressCount++;
    }
    if (!empty($order['completion_date'])) {
        $completionDates[] = $order['completion_date'];
    }
}
$avgProgress = $progressCount > 0 ? ($totalProgress / $progressCount) : 0.0;
$maxCompletion = !empty($completionDates) ? max(array_map(fn($d) => strtotime((string) $d), $completionDates)) : null;
$completionDateHuman = $maxCompletion ? date('Y-m-d', $maxCompletion) : '—';

$planPrice = (float) ($plan['price'] ?? 0);
$plClass   = $holdingsProfit >= 0 ? 'is-up' : 'is-down';
$plSign    = $holdingsProfit >= 0 ? '+' : '-';
$initials  = strtoupper(substr((string) ($plan['symbol'] ?? $plan['name']), 0, 2));
$symbol    = strtoupper((string) ($plan['symbol'] ?? ''));
?>
<div class="content-detail-page">

    <!-- Asset hero -->
    <section class="details-hero">
        <div class="details-hero__title">
            <div class="details-hero__logo <?= e($categoryClass) ?>"><?= e($initials) ?></div>
            <div>
                <div class="card-badges-row">
                    <span class="badge-category <?= e($categoryClass) ?>"><?= e((string) ($plan['category'] ?? '')) ?></span>
                    <span class="badge-risk <?= e($riskClass) ?>"><?= e((string) ($plan['risk_level'] ?? 'Medium')) ?> Risk</span>
                    <span class="status status--active">Owned</span>
                </div>
                <h2 style="margin:6px 0 0; font-size:1.8rem;"><?= e((string) ($plan['name'] ?? '')) ?></h2>
                <p class="muted" style="margin:2px 0 0; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">
                    <?= e((string) ($plan['symbol'] ?? '')) ?>
                </p>
            </div>
        </div>
        <div class="details-hero__price-info">
            <div class="details-hero__price"><?= e(money($planPrice)) ?></div>
            <div class="details-hero__change change-up">+<?= number_format((float) $dailyChange, 2) ?>% (24h)</div>
        </div>
    </section>

    <!-- Your position -->
    <section class="position-summary card">
        <div class="position-summary__head">
            <div class="position-summary__heading">
                <span class="position-summary__eyebrow">Your Position</span>
                <h3 class="position-summary__asset">
                    <?= e((string) ($plan['name'] ?? '')) ?>
                    <?php if ($symbol !== ''): ?>
                        <span class="position-summary__ticker"><?= e($symbol) ?></span>
                    <?php endif; ?>
                </h3>
            </div>
            <span class="<?= $hasHoldings ? 'status status--active' : 'status status--inactive' ?>">
                <?= $hasHoldings ? 'Active' : 'No Holdings' ?>
            </span>
        </div>

        <?php if ($hasHoldings): ?>
            <div class="position-summary__body">
                <div class="position-summary__value-block">
                    <span class="position-summary__label">Market Value</span>
                    <div class="position-summary__value"><?= e(money($holdingsCurrentValue)) ?></div>
                    <div class="position-summary__pl <?= $plClass ?>">
                        <span class="position-summary__pl-amount"><?= $plSign ?><?= e(money(abs($holdingsProfit))) ?></span>
                        <span class="position-summary__pl-pct">(<?= $plSign ?><?= number_format((float) $holdingsRoi, 2) ?>%)</span>
                    </div>
                    <div class="position-summary__term">
                        <div class="position-summary__term-bar">
                            <span style="width:<?= e(number_format((float) $avgProgress, 1)) ?>%"></span>
                        </div>
                        <div class="position-summary__term-meta">
                            <span><?= number_format((float) $avgProgress, 1) ?>% to maturity</span>
                            <span>Matures <?= e((string) $completionDateHuman) ?></span>
                        </div>
                    </div>
                </div>

                <div class="position-summary__stats">
                    <div class="stat-pill">
                        <span class="stat-pill__label">Invested</span>
                        <span class="stat-pill__value"><?= e(money($holdingsAmount)) ?></span>
                    </div>
                    <div class="stat-pill">
                        <span class="stat-pill__label">Earnings</span>
                        <span class="stat-pill__value <?= $plClass ?>"><?= e(money($holdingsProfit)) ?></span>
                    </div>
                    <div class="stat-pill">
                        <span class="stat-pill__label">Return</span>
                        <span class="stat-pill__value <?= $plClass ?>"><?= $plSign ?><?= number_format((float) $holdingsRoi, 2) ?>%</span>
                    </div>
                    <div class="stat-pill">
                        <span class="stat-pill__label">Maturity</span>
                        <span class="stat-pill__value"><?= e((string) $completionDateHuman) ?></span>
                    </div>
                </div>
            </div>

            <div class="position-summary__actions">
                <a class="button button--outline" href="index.php?route=investment-details&id=<?= e((string) $planId) ?>">
                    Buy
                </a>
                <form method="post" action="index.php?route=owned-investment-details&id=<?= e((string) $planId) ?>"
                      data-confirm="Sell this position? The current market value will be returned to your wallet and this cannot be undone.">
                    <input type="hidden" name="action" value="sell">
                    <input type="hidden" name="order_id" value="<?= e((string) ($activeOrders[0]['id'] ?? '')) ?>">
                    <button type="submit" class="button button--danger"
                            <?= count($activeOrders) > 1 ? 'disabled' : '' ?>>
                        Sell
                    </button>
                </form>
            </div>
            <?php if (count($activeOrders) > 1): ?>
                <p class="muted position-summary__note">
                    You hold <?= count($activeOrders) ?> active orders for this asset. Sell applies to the oldest order.
                </p>
            <?php endif; ?>
        <?php else: ?>
            <p class="muted">You don't currently hold an active position in this asset.
                <a href="index.php?route=investment-details&id=<?= e((string) $planId) ?>">Open a position</a> to get started.
            </p>
        <?php endif; ?>
    </section>

    <!-- Performance chart -->
    <section class="chart-card">
        <div class="chart-header">
            <div>
                <h3 style="margin:0;">Performance</h3>
                <p class="muted" style="margin:2px 0 0; font-size:.82rem;">Select a timeframe for simulated trends</p>
            </div>
            <div class="chart-timeframes">
                <button class="timeframe-btn" data-timeframe="1D">1D</button>
                <button class="timeframe-btn" data-timeframe="1W">1W</button>
                <button class="timeframe-btn is-active" data-timeframe="1M">1M</button>
                <button class="timeframe-btn" data-timeframe="3M">3M</button>
                <button class="timeframe-btn" data-timeframe="1Y">1Y</button>
                <button class="timeframe-btn" data-timeframe="5Y">5Y</button>
            </div>
        </div>
        <div class="chart-container-large" data-interactive-chart data-asset="<?= (int) $planId ?>" data-price="<?= (float) $planPrice ?>">
            <!-- injected by assets/js/app.js -->
        </div>
    </section>

    <!-- Tabbed selector -->
    <div class="detail-tabs">
        <button class="detail-tab is-active" data-tab-btn="overview">Overview</button>
        <button class="detail-tab" data-tab-btn="holdings">Holdings</button>
        <button class="detail-tab" data-tab-btn="activity">Activity</button>
    </div>

    <!-- Overview -->
    <div class="tab-content is-active" id="tab-overview">
        <div class="grid grid--2">
            <div class="grid" style="gap:var(--space-4);">
                <article class="stats-grid-card">
                    <div class="stat-item">
                        <span class="stat-item__label">Current Value</span>
                        <span class="stat-item__value" style="color:var(--color-primary); font-weight:900;">
                            <?= e(money($holdingsCurrentValue)) ?>
                        </span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-item__label">Total Return</span>
                        <span class="stat-item__value <?= $plClass ?>">
                            <?= $plSign ?><?= number_format((float) $holdingsRoi, 2) ?>%
                        </span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-item__label">Purchase Value</span>
                        <span class="stat-item__value"><?= e(money($holdingsAmount)) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-item__label">Earnings</span>
                        <span class="stat-item__value <?= $plClass ?>"><?= e(money($holdingsProfit)) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-item__label">Avg Progress</span>
                        <span class="stat-item__value"><?= number_format((float) $avgProgress, 2) ?>%</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-item__label">Maturity (Latest)</span>
                        <span class="stat-item__value"><?= e((string) $completionDateHuman) ?></span>
                    </div>
                </article>

                <article class="card" style="display:flex; flex-direction:column; gap:var(--space-4);">
                    <div>
                        <h3 style="margin-top:0;">About <?= e((string) ($plan['name'] ?? '')) ?></h3>
                        <p class="muted" style="line-height:1.6; font-size:.92rem;"><?= e((string) ($plan['description'] ?? '')) ?></p>
                    </div>
                    <?php if (!empty($keyPoints)): ?>
                        <div style="border-top:1px solid var(--color-border); padding-top:var(--space-4);">
                            <h4 style="margin:0 0 8px 0;">Key Investment Drivers</h4>
                            <ul style="margin:0; padding-left:20px; display:grid; gap:6px; font-size:.92rem;" class="muted">
                                <?php foreach ($keyPoints as $point): ?>
                                    <li><?= e(trim((string) $point)) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </article>
            </div>

            <div class="grid" style="align-content:flex-start; gap:var(--space-4);">
                <?php if (!empty($plan['ai_summary'])): ?>
                    <article class="card">
                        <h4 style="margin:0 0 8px 0; color:var(--color-primary); display:flex; align-items:center; gap:8px;">
                            <span>🪄</span> AI Performance Analysis
                        </h4>
                        <p class="muted" style="line-height:1.6; font-size:.92rem; font-style:italic; margin:0;">
                            "<?= e((string) $plan['ai_summary']) ?>"
                        </p>
                    </article>
                <?php endif; ?>

                <article class="card" style="border-left:4px solid var(--color-info); background:var(--color-surface-muted); padding:var(--space-4);">
                    <div style="display:flex; gap:8px; align-items:flex-start;">
                        <span style="font-size:1.1rem; line-height:1;">ℹ️</span>
                        <p class="muted" style="margin:0; font-size:.85rem; line-height:1.5;">
                            This is informational and does not constitute financial advice. Simulated growth outputs carry risk.
                        </p>
                    </div>
                </article>
            </div>
        </div>

        <article class="card" style="display:flex; flex-direction:column; gap:var(--space-4); margin-top:var(--space-4);">
            <h3 style="margin-top:0;">Key Statistics</h3>
            <div class="key-stats-grid">
                <div class="stat-item">
                    <span class="stat-item__label">Market Cap<button type="button" class="info-icon" aria-label="Market Cap. The total value of a company based on its stock price and number of shares. It shows how big the company is." data-tooltip="The total value of a company based on its stock price and number of shares. It shows how big the company is.">&#9432;</button></span>
                    <span class="stat-item__value">$<?= e((string) ($plan['market_cap'] ?? '—')) ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-item__label">Open<button type="button" class="info-icon" aria-label="Open. The price a stock starts trading at when the market opens for the day." data-tooltip="The price a stock starts trading at when the market opens for the day.">&#9432;</button></span>
                    <span class="stat-item__value"><?= money((float) ($plan['open'] ?? 0)) ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-item__label">High<button type="button" class="info-icon" aria-label="High. The highest price the stock has reached so far during the day's trading." data-tooltip="The highest price the stock has reached so far during the day's trading.">&#9432;</button></span>
                    <span class="stat-item__value"><?= money((float) ($plan['high'] ?? 0)) ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-item__label">Low<button type="button" class="info-icon" aria-label="Low. The lowest price the stock has dropped to during the day's trading." data-tooltip="The lowest price the stock has dropped to during the day's trading.">&#9432;</button></span>
                    <span class="stat-item__value"><?= money((float) ($plan['low'] ?? 0)) ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-item__label">Dividend Yield<button type="button" class="info-icon" aria-label="Dividend Yield. The annual dividend paid by the company expressed as a percentage of its share price." data-tooltip="The annual dividend paid by the company expressed as a percentage of its share price.">&#9432;</button></span>
                    <span class="stat-item__value"><?= !empty($plan['dividend_yield']) ? e(number_format((float) $plan['dividend_yield'], 2)) . '%' : '—' ?></span>
                </div>
            </div>
        </article>
    </div>

    <!-- Holdings -->
    <div class="tab-content" id="tab-holdings">
        <?php if ($hasHoldings): ?>
            <div class="holdings-overview">
                <div class="holdings-overview__item">
                    <span class="holdings-overview__label">Active Orders</span>
                    <span class="holdings-overview__value"><?= e((string) count($activeOrders)) ?></span>
                </div>
                <div class="holdings-overview__item">
                    <span class="holdings-overview__label">Total Invested</span>
                    <span class="holdings-overview__value"><?= e(money($holdingsAmount)) ?></span>
                </div>
                <div class="holdings-overview__item">
                    <span class="holdings-overview__label">Current Value</span>
                    <span class="holdings-overview__value"><?= e(money($holdingsCurrentValue)) ?></span>
                </div>
                <div class="holdings-overview__item">
                    <span class="holdings-overview__label">Total Earnings</span>
                    <span class="holdings-overview__value <?= $plClass ?>"><?= e(money($holdingsProfit)) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid--2">
            <article class="card holdings-list">
                <h3 style="margin-top:0; margin-bottom:var(--space-3);">Your Active Orders</h3>
                <?php if ($hasHoldings): ?>
                    <ul class="holding-rows">
                        <?php foreach ($activeOrders as $order): ?>
                            <?php
                            $orderProfit = (float) ($order['profit'] ?? 0);
                            $orderPl     = $orderProfit >= 0 ? 'is-up' : 'is-down';
                            $orderInit   = strtoupper(substr((string) ($order['symbol'] ?? $plan['symbol'] ?? $plan['name']), 0, 2));
                            ?>
                            <li class="holding-row">
                                <div class="holding-row__main">
                                    <div class="holding-row__avatar"><?= e($orderInit) ?></div>
                                    <div class="holding-row__id-block">
                                        <span class="holding-row__id"><?= e((string) ($order['id'] ?? '')) ?></span>
                                        <span class="holding-row__date muted">Opened <?= e(date('Y-m-d', strtotime((string) ($order['purchase_date'] ?? 'now')))) ?></span>
                                    </div>
                                </div>
                                <div class="holding-row__figs">
                                    <div>
                                        <span class="muted">Invested</span>
                                        <strong><?= e(money((float) ($order['amount'] ?? 0))) ?></strong>
                                    </div>
                                    <div>
                                        <span class="muted">Earnings</span>
                                        <strong class="<?= $orderPl ?>"><?= e(money($orderProfit)) ?></strong>
                                    </div>
                                </div>
                                <div class="holding-row__progress">
                                    <div class="holding-row__bar">
                                        <span style="width:<?= e((string) ($order['progress'] ?? 0)) ?>%"></span>
                                    </div>
                                    <span class="muted">Matures <?= e((string) ($order['completion_date'] ?? '-')) ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-state" style="min-height:140px; padding:var(--space-4);">
                        <span style="font-size:2rem;">💼</span>
                        <p class="muted" style="margin:0; font-size:.85rem;">No active holdings found for this asset.</p>
                    </div>
                <?php endif; ?>
            </article>

            <div class="grid" style="align-content:flex-start; gap:var(--space-4);">
                <article class="calculator-card">
                    <h3 style="margin-top:0;">Buy More Units</h3>
                    <p class="muted" style="font-size:.82rem;">
                        Add additional units of this investment plan. Purchase will validate your wallet balance.
                    </p>
                    <a class="button button--primary" style="width:100%; margin-top:var(--space-3);"
                       href="index.php?route=investment-details&id=<?= e((string) $planId) ?>">
                        Buy
                    </a>
                </article>
            </div>
        </div>
    </div>

    <!-- Activity -->
    <div class="tab-content" id="tab-activity">
        <article class="card" style="grid-column:1 / -1;">
            <h3 style="margin-top:0; margin-bottom:var(--space-3);">Recent Activity</h3>
            <div class="timeline">
                <?php
                $planNameLower = strtolower($plan['name'] ?? '');
                $transactions  = array_reverse(customer_transactions(current_customer_id()));
                $filtered = array_filter($transactions, function ($t) use ($planNameLower) {
                    return isset($t['description']) && strtolower((string) $t['description']) !== ''
                        && str_contains(strtolower((string) $t['description']), $planNameLower);
                });
                $shown = array_slice(array_values($filtered), 0, 10);
                if (empty($shown)):
                ?>
                    <p class="muted" style="margin:0;">No recent transactions found for this asset.</p>
                <?php else: ?>
                    <?php foreach ($shown as $t): ?>
                        <div class="timeline__item">
                            <div style="display:flex; justify-content:space-between; gap:var(--space-3);">
                                <strong><?= e((string) ($t['type'] ?? '')) ?></strong>
                                <span class="muted" style="font-size:.85rem;"><?= e((string) ($t['date'] ?? '')) ?></span>
                            </div>
                            <p class="muted" style="font-size:.85rem; margin:4px 0 0 0;">
                                Amount: <?= e(money((float) ($t['amount'] ?? 0))) ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>
    </div>

</div>

<?php if ($hasHoldings): ?>
    <!-- Mobile action bar — live position value plus thumb-reach Buy/Sell -->
    <div class="mobile-purchase-bar">
        <div class="mobile-purchase-bar__info">
            <span class="mobile-purchase-bar__label">Market Value</span>
            <span class="mobile-purchase-bar__value"><?= e(money($holdingsCurrentValue)) ?></span>
            <span class="mobile-purchase-bar__pl <?= $plClass ?>"><?= $plSign ?><?= e(money(abs($holdingsProfit))) ?></span>
        </div>
        <div class="mobile-purchase-bar__actions">
            <a class="button button--outline" href="index.php?route=investment-details&id=<?= e((string) $planId) ?>">
                Buy
            </a>
            <form method="post" action="index.php?route=owned-investment-details&id=<?= e((string) $planId) ?>"
                  data-confirm="Sell this position? The current market value will be returned to your wallet and this cannot be undone.">
                <input type="hidden" name="action" value="sell">
                <input type="hidden" name="order_id" value="<?= e((string) ($activeOrders[0]['id'] ?? '')) ?>">
                <button type="submit" class="button button--danger"
                        <?= count($activeOrders) > 1 ? 'disabled' : '' ?>>
                    Sell
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/layouts/user-footer.php'; ?>
