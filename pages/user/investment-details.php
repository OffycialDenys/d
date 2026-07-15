<?php
$pageTitle = 'Asset Details';
$planId = (int) ($_GET['id'] ?? 1);
$plan = current(array_filter($_SESSION['platform']['plans'], fn($row) => $row['id'] === $planId));
if (!$plan) {
    $plan = $_SESSION['platform']['plans'][0];
}

require __DIR__ . '/../../includes/layouts/user-header.php';

$categoryClass = strtolower($plan['category'] ?? 'stock');
$riskClass = strtolower($plan['risk_level'] ?? 'medium');
$dailyChange = ($plan['daily_return'] > 0) ? (float) $plan['daily_return'] : 0.45;
$keyPoints = array_filter(explode("\n", str_replace("\r", "", $plan['key_points'] ?? '')));
$planPrice = (float) ($plan['price'] ?? 0);

// Marketplace detail: NO sell buttons, NO holdings calculation — purely discovery & purchase
$ownedCheck = current(array_filter(customer_orders(current_customer_id()), fn($o) =>
    strtolower((string)($o['plan'] ?? '')) === strtolower((string)($plan['name'] ?? '')) &&
    ($o['status'] ?? '') === 'Active'
));
$userAlreadyOwns = $ownedCheck !== false && !empty($ownedCheck);

?>

<div class="content-detail-page" style="display:flex; flex-direction:column; gap: var(--space-4);">
    
    <!-- Asset Details Header -->
    <div class="details-hero">
        <div class="details-hero__title">
            <div class="details-hero__logo <?= $categoryClass ?>">
                <?= strtoupper(substr($plan['symbol'] ?? $plan['name'], 0, 2)) ?>
            </div>
            <div>
                <div style="display:flex; align-items:center; gap:8px; flex-wrap: wrap;">
                    <span class="badge-category <?= $categoryClass ?>"><?= e($plan['category']) ?></span>
                    <span class="badge-risk <?= $riskClass ?>"><?= e($plan['risk_level'] ?? 'Medium') ?> Risk</span>
                    <span class="status status--<?= ($plan['market_status'] ?? 'Open') === 'Open' ? 'active' : 'inactive' ?>">
                        Market <?= e($plan['market_status'] ?? 'Open') ?>
                    </span>
                </div>
                <h2 style="margin: 6px 0 0 0; font-size:1.8rem;"><?= e($plan['name']) ?></h2>
                <p class="muted" style="margin:2px 0 0 0; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;"><?= e($plan['symbol'] ?? '') ?></p>
            </div>
        </div>
        <div class="details-hero__price-info">
            <div class="details-hero__price"><?= e(money((float) $plan['price'])) ?></div>
            <div class="details-hero__change change-up">+<?= number_format($dailyChange, 2) ?>% (24h)</div>
        </div>
    </div>

    <!-- Tabbed Selector -->
    <div class="detail-tabs">
        <button class="detail-tab is-active" data-tab-btn="overview">Overview</button>
        <button class="detail-tab" data-tab-btn="investment">Investment</button>
    </div>

    <!-- Overview Tab Content -->
    <div class="tab-content is-active" id="tab-overview">
        <div class="grid grid--2">
            <div class="grid" style="gap: var(--space-4);">
                <!-- Stats Grid Card -->
                <article class="stats-grid-card">
                    <div class="stat-item">
                        <span class="stat-item__label">Min Investment</span>
                        <span class="stat-item__value">$<?= number_format((float)$plan['min'], 2) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-item__label">Max Investment</span>
                        <span class="stat-item__value">$<?= number_format((float)$plan['max'], 2) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-item__label">Daily Profit Rate</span>
                        <span class="stat-item__value"><?= e((string)($plan['daily_return'] ?? 0)) ?>%</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-item__label">Expected ROI</span>
                        <span class="stat-item__value"><?= e((string)$plan['roi']) ?>%</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-item__label">Lock Period</span>
                        <span class="stat-item__value"><?= e((string)($plan['lock_period'] ?? 0)) ?> Days</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-item__label">Total Duration</span>
                        <span class="stat-item__value"><?= e((string)$plan['duration']) ?> Days</span>
                    </div>
                </article>

                <!-- About & Drivers -->
                <article class="card" style="display:flex; flex-direction:column; gap: var(--space-4);">
                    <div>
                        <h3 style="margin-top:0;">About <?= e($plan['name']) ?></h3>
                        <p class="muted" style="line-height:1.6; font-size:.92rem;"><?= e($plan['description'] ?? '') ?></p>
                    </div>
                    
                    <?php if (!empty($keyPoints)): ?>
                        <div style="border-top:1px solid var(--color-border); padding-top:var(--space-4);">
                            <h4 style="margin:0 0 8px 0;">Key Investment Drivers</h4>
                            <ul style="margin:0; padding-left:20px; display:grid; gap:6px; font-size:.92rem;" class="muted">
                                <?php foreach ($keyPoints as $point): ?>
                                    <li><?= e(trim($point)) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </article>
            </div>

            <div class="grid" style="align-content: flex-start; gap: var(--space-4);">
                <!-- Simulated Growth Performance -->
                <article class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h3 style="margin:0;">Simulated Growth Performance</h3>
                            <p class="muted" style="margin:2px 0 0 0; font-size:.82rem;">Select timeframe for simulated trends</p>
                        </div>
                        <div class="chart-timeframes">
                            <button class="timeframe-btn is-active" data-timeframe="1D">1D</button>
                            <button class="timeframe-btn" data-timeframe="1W">1W</button>
                            <button class="timeframe-btn" data-timeframe="1M">1M</button>
                            <button class="timeframe-btn" data-timeframe="3M">3M</button>
                            <button class="timeframe-btn" data-timeframe="1Y">1Y</button>
                            <button class="timeframe-btn" data-timeframe="5Y">5Y</button>
                        </div>
                    </div>

                    <div class="chart-container-large" data-interactive-chart data-asset="<?= (int) $planId ?>" data-price="<?= (float) $planPrice ?>">
                        <!-- injected by assets/js/app.js -->
                    </div>
                </article>

                <!-- AI Analysis -->
                <?php if (!empty($plan['ai_summary'])): ?>
                    <article class="card">
                        <h4 style="margin:0 0 8px 0; color: var(--color-primary); display:flex; align-items:center; gap:8px;">
                            <span>🪄</span> AI Performance Analysis
                        </h4>
                        <p class="muted" style="line-height:1.6; font-size:.92rem; font-style:italic; margin:0;">
                            "<?= e($plan['ai_summary']) ?>"
                        </p>
                    </article>
                <?php endif; ?>

                <!-- Disclosure Info Box -->
                <article class="card" style="border-left: 4px solid var(--color-info); background: var(--color-surface-muted); padding: var(--space-4);">
                    <div style="display:flex; gap: 8px; align-items: flex-start;">
                        <span style="font-size:1.1rem; line-height: 1;">ℹ️</span>
                        <p class="muted" style="margin: 0; font-size: .85rem; line-height: 1.5;">
                            This content is generated for informational purposes only and does not constitute financial, investment, or legal advice. All market indices and simulated growth returns carry risk.
                        </p>
                    </div>
                </article>
            </div>
        </div>

        <!-- Key Statistics -->
        <article class="card" style="display:flex; flex-direction:column; gap: var(--space-4);">
            <h3 style="margin-top:0;">Key Statistics</h3>
            <div class="key-stats-grid">
                <div class="stat-item">
                    <span class="stat-item__label">Market Cap<button type="button" class="info-icon" aria-label="Market Cap. The total value of a company based on its stock price and number of shares. It shows how big the company is." data-tooltip="The total value of a company based on its stock price and number of shares. It shows how big the company is.">&#9432;</button></span>
                    <span class="stat-item__value">$<?= e($plan['market_cap'] ?? '—') ?></span>
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

    <!-- Investment Tab Content -->
    <div class="tab-content" id="tab-investment">
        <div class="grid grid--2">
            <!-- Right Column: Calculator & Purchase -->
            <div class="grid" style="align-content: flex-start; gap: var(--space-4);">
                <!-- Calculator Card -->
                <article class="calculator-card">
                    <h3 style="margin-top:0;">Returns Calculator</h3>
                    <p class="muted" style="font-size:.82rem;">Simulate earnings instantly by adjusting the slider.</p>
                    
                    <input type="hidden" id="roi-val" data-calc-roi value="<?= e((string)$plan['roi']) ?>">
                    
                    <div class="field" style="margin-top: 1rem;">
                        <label for="calc-amount">Investment Amount</label>
                        <input id="calc-amount" data-calc-amount type="number" min="<?= e((string) $plan['min']) ?>" max="<?= e((string) $plan['max']) ?>" step="10" value="<?= e((string) $plan['min']) ?>">
                    </div>

                    <input class="calculator-slider" data-calc-slider type="range" min="<?= e((string) $plan['min']) ?>" max="<?= e((string) $plan['max']) ?>" step="10" value="<?= e((string) $plan['min']) ?>">

                    <div class="calc-results">
                        <div class="calc-result-box">
                            <span class="muted" style="font-size:.78rem; font-weight:700;">Net Profit Projection</span>
                            <strong id="calc-profit" data-calc-profit style="font-size:1.4rem; color:var(--color-primary);"><?= e(money(0.0)) ?></strong>
                        </div>
                        <div class="calc-result-box" style="text-align:right;">
                            <span class="muted" style="font-size:.78rem; font-weight:700;">Total Payout</span>
                            <strong id="calc-total" data-calc-total style="font-size:1.4rem; color:var(--color-text);"><?= e(money(0.0)) ?></strong>
                        </div>
                    </div>
                </article>

                <!-- Purchase Card -->
                <article class="card" id="desktopPurchaseCard">
                    <h3 style="margin-top:0;">Open Investment Order</h3>
                    <p class="muted" style="font-size:.82rem;">Verify availability in your wallet balance before buying.</p>

                    <form method="post" id="purchaseAssetForm" style="margin-top: 1rem;">
                        <input type="hidden" name="action" value="purchase">
                        <input type="hidden" name="plan_id" value="<?= e((string)$plan['id']) ?>">

                        <div class="field">
                            <label for="amount">Investment Amount ($)</label>
                            <input id="amount" name="amount" type="number"
                                   min="<?= e((string)$plan['min']) ?>"
                                   max="<?= e((string)$plan['max']) ?>"
                                   step="1"
                                   value="<?= e((string)$plan['min']) ?>"
                                   required>
                        </div>

                        <div style="display:flex; justify-content:space-between; font-size:.85rem; margin: var(--space-3) 0;" class="muted">
                            <span>Available Balance:</span>
                            <strong><?= e(money((float) customer_wallet(current_customer_id())['available'])) ?></strong>
                        </div>

                        <?php if ($userAlreadyOwns): ?>
                        <p class="muted" style="font-size:.8rem; margin-bottom:var(--space-3); color:var(--color-primary);">
                            You already hold this asset in your portfolio.
                        </p>
                        <?php endif; ?>

                        <div class="form-actions">
                            <button class="button button--primary" type="submit" style="width:100%;">Buy Now</button>
                        </div>
                    </form>
                </article>
            </div>

<!-- Mobile Bottom Fixed Action Bar -->
<div class="mobile-purchase-bar">
    <button class="button button--primary" id="mobileBuyBtn">Buy</button>
</div>

<!-- Step-by-Step Checkout Modal -->
<div class="checkout-modal" id="checkoutModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="checkout-modal-content">
        <div class="checkout-modal-header">
            <h3 id="modalTitle">Confirm Order</h3>
            <button class="checkout-modal-close" id="closeCheckout" aria-label="Close modal">&times;</button>
        </div>
        <div class="checkout-modal-body">
            <!-- Step 1: Review -->
            <div class="checkout-step is-active" id="checkoutStepReview">
                <p class="muted" style="margin-bottom: var(--space-4); font-size: .88rem; line-height: 1.5;">Please review your investment details before committing funds. Your funds will lock for <strong><?= e((string)$plan['duration']) ?> days</strong>.</p>
                <div style="background: var(--color-surface-muted); padding: var(--space-4); border-radius: var(--radius-md); margin-bottom: var(--space-4); display: flex; flex-direction: column; gap: var(--space-2);">
                    <div class="checkout-review-row">
                        <span>Asset Name</span>
                        <strong><?= e($plan['name']) ?> (<?= e($plan['symbol']) ?>)</strong>
                    </div>
                    <div class="checkout-review-row">
                        <span>Unit Price</span>
                        <strong><?= e(money((float) $plan['price'])) ?></strong>
                    </div>
                    <div class="checkout-review-row">
                        <span>Investment Amount</span>
                        <strong id="modalReviewAmount">$0.00</strong>
                    </div>
                    <div class="checkout-review-row">
                        <span>Est. Share Quantity</span>
                        <strong id="modalReviewShares">0.00000000</strong>
                    </div>
                    <div class="checkout-review-row">
                        <span>Expected ROI</span>
                        <strong><?= e((string)$plan['roi']) ?>%</strong>
                    </div>
                    <div class="checkout-review-row">
                        <span>Projected Net Profit</span>
                        <strong id="modalReviewProfit" style="color:var(--color-primary);">$0.00</strong>
                    </div>
                    <div class="checkout-review-row">
                        <span>Total Payout</span>
                        <strong id="modalReviewTotal">$0.00</strong>
                    </div>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:.85rem; margin-bottom: var(--space-4);" class="muted">
                    <span>Available Balance:</span>
                    <strong id="reviewAvailable"><?= e(money(customer_wallet(current_customer_id())['available'])) ?></strong>
                </div>
                <button class="button button--primary" style="width:100%;" id="confirmPurchaseBtn">Confirm & Purchase</button>
            </div>
            
            <!-- Step 2: Loading State -->
            <div class="checkout-step" id="checkoutStepLoading">
                <div class="checkout-loader-wrap">
                    <div class="checkout-spinner"></div>
                    <h4 style="margin: 0; font-size:1.1rem;" id="loadingStatusText">Validating available balances...</h4>
                    <p class="muted" style="font-size: .85rem; margin: 0; max-width: 320px;">Securing contract agreement and locking digital assets on platform ledger.</p>
                </div>
            </div>
            
            <!-- Step 3: Success State -->
            <div class="checkout-step" id="checkoutStepSuccess">
                <div class="checkout-success-wrap">
                    <div class="checkout-success-icon">✓</div>
                    <h3 style="margin: 0 0 var(--space-2) 0; color: var(--color-success); font-size: 1.35rem;">Order Executed Successfully!</h3>
                    <p class="muted" style="font-size: .88rem; margin-bottom: var(--space-4); line-height: 1.5;">Your investment asset has been added to your portfolio and is currently active.</p>
                    <div style="background: var(--color-surface-muted); padding: var(--space-4); border-radius: var(--radius-md); width:100%; margin-bottom: var(--space-5); display: flex; flex-direction: column; gap: var(--space-2); text-align: left;">
                        <div class="checkout-review-row">
                            <span>Order Number</span>
                            <strong id="successOrderId">ORD-XXXX</strong>
                        </div>
                        <div class="checkout-review-row">
                            <span>Maturity Date</span>
                            <strong><?= e(date('Y-m-d', strtotime('+' . $plan['duration'] . ' days'))) ?></strong>
                        </div>
                        <div class="checkout-review-row">
                            <span>Asset Locked</span>
                            <strong><?= e($plan['name']) ?></strong>
                        </div>
                    </div>
                    <a href="index.php?route=orders" class="button button--primary" style="width:100%;">View My Portfolio</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/layouts/user-footer.php'; ?>
