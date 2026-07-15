<?php 
$pageTitle = 'Investment Marketplace'; 
require __DIR__ . '/../../includes/layouts/user-header.php'; 

$plans = $_SESSION['platform']['plans'];
// Filter active ones
    $plans = array_filter($plans, fn($p) => $p['status'] === 'Open');
    // Pre-compute deterministic, server-driven sparkline data per asset so the
    // marketplace never relies on hardcoded per-symbol definitions.
    $sparklineCache = [];
    foreach ($plans as $plan) {
        $sparklineCache[(int) $plan['id']] = build_sparkline_points($plan, 8);
    }
?>


<section class="section-title">
    <div>
        <h2>Investment Marketplace</h2>
        <p class="muted">Explore, compare, and build your digital asset portfolio.</p>
    </div>
</section>

<!-- Filter Navigation Tabs -->
<nav class="market-nav">
    <button class="market-tab is-active" onclick="filterMarket('all')">All Assets</button>
    <button class="market-tab" onclick="filterMarket('featured')">★ Featured</button>
    <button class="market-tab" onclick="filterMarket('trending')">🔥 Trending</button>
    <button class="market-tab" onclick="filterMarket('beginner')">🌱 Beginner Friendly</button>
    <button class="market-tab" onclick="filterMarket('stock')">Stocks</button>
    <button class="market-tab" onclick="filterMarket('cryptocurrency')">Cryptocurrency</button>
    <button class="market-tab" onclick="filterMarket('etf')">ETFs</button>
    <button class="market-tab" onclick="filterMarket('popular')">Popular Picks</button>
</nav>

<div class="premium-card-grid">
    <?php foreach ($plans as $plan): 
        $categoryClass = strtolower($plan['category']);
        $riskClass = strtolower($plan['risk_level'] ?? 'medium');

        // Server-driven sparkline (no hardcoded per-symbol branching).
        $sparkArr = $sparklineCache[(int) $plan['id']] ?? build_sparkline_points($plan, 8);
        $sparkpoints = implode(',', $sparkArr);

        // Mock daily change calculation (positive for trending/featured, mixed otherwise)
        $dailyChange = ($plan['daily_return'] > 0) ? $plan['daily_return'] : 0.45;
        // Tie the 24h indicator direction to the rendered sparkline trend for consistency.
        $rising = end($sparkArr) >= reset($sparkArr);
        $changeClass = $rising ? 'change-up' : 'change-down';
        $changeSign = $rising ? '+' : '-';
    ?>
        <article class="asset-premium-card" 
                 data-market-row 
                 data-category="<?= $categoryClass ?>"
                 data-featured="<?= !empty($plan['featured']) ? '1' : '0' ?>"
                 data-trending="<?= !empty($plan['is_trending']) ? '1' : '0' ?>"
                 data-beginner="<?= !empty($plan['is_beginner_friendly']) ? '1' : '0' ?>"
                 data-popular="<?= !empty($plan['is_popular']) ? '1' : '0' ?>">
            
            <div>
                <!-- Badge Row -->
                <div class="card-badges-row">
                    <?php if (!empty($plan['featured'])): ?><span class="tag-badge featured">Featured</span><?php endif; ?>
                    <?php if (!empty($plan['is_trending'])): ?><span class="tag-badge trending">Trending</span><?php endif; ?>
                    <?php if (!empty($plan['is_beginner_friendly'])): ?><span class="tag-badge beginner">Beginner</span><?php endif; ?>
                    <?php if (!empty($plan['has_dividend'])): ?><span class="tag-badge dividend">Dividend</span><?php endif; ?>
                    <?php if (!empty($plan['is_popular'])): ?><span class="tag-badge popular">Popular Picks</span><?php endif; ?>
                </div>

                <!-- Asset Header Row -->
                <div class="asset-header-cell" style="margin: .75rem 0;">
                    <div class="asset-logo-wrap <?= $categoryClass ?>">
                        <?= strtoupper(substr($plan['symbol'] ?? $plan['name'], 0, 2)) ?>
                    </div>
                    <div class="asset-info">
                        <span class="asset-name"><?= e($plan['name']) ?></span>
                        <span class="asset-symbol"><?= e($plan['symbol'] ?? '') ?></span>
                    </div>
                </div>

                <p class="muted" style="font-size: .88rem; margin-bottom: var(--space-4); line-height: 1.4;">
                    <?= e($plan['description'] ?? '') ?>
                </p>

                <!-- Sparkline -->
                <div class="mini-chart" style="margin-bottom: var(--space-4);" data-chart data-points="<?= $sparkpoints ?>"></div>
            </div>

            <div>
                <!-- Price and Info Row -->
                <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:var(--space-4);">
                    <div>
                        <p class="eyebrow" style="margin-bottom:2px;">Current Price</p>
                        <strong style="font-size: 1.35rem;">$<?= number_format((float)$plan['price'], 2) ?></strong>
                    </div>
                    <div style="text-align:right;">
                        <p class="eyebrow" style="margin-bottom:2px;">24h Change</p>
                        <span class="<?= $changeClass ?>"><?= $changeSign ?><?= number_format(abs($dailyChange), 2) ?>%</span>
                    </div>
                </div>

                <!-- Quick Metadata Row -->
                <div class="plan-meta" style="margin-bottom: var(--space-4);">
                    <span>Category <strong><?= e($plan['category']) ?></strong></span>
                    <span>Monthly ROI <strong><?= e((string)$plan['monthly_return']) ?>%</strong></span>
                    <span>Lock Period <strong><?= e((string)($plan['lock_period'] ?? 0)) ?>d</strong></span>
                </div>

                <!-- Buy Action Button -->
                <div class="form-actions" style="margin-top: 0;">
                    <a class="button button--primary" style="width: 100%;" href="index.php?route=investment-details&id=<?= e($plan['id']) ?>">View Details</a>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<script>
function filterMarket(filter) {
    // Update active tab styling
    const tabs = document.querySelectorAll('.market-tab');
    tabs.forEach(tab => tab.classList.remove('is-active'));
    event.currentTarget.classList.add('is-active');

    // Filter cards
    const cards = document.querySelectorAll('[data-market-row]');
    cards.forEach(card => {
        let show = false;
        if (filter === 'all') {
            show = true;
        } else if (filter === 'featured') {
            show = card.getAttribute('data-featured') === '1';
        } else if (filter === 'trending') {
            show = card.getAttribute('data-trending') === '1';
        } else if (filter === 'beginner') {
            show = card.getAttribute('data-beginner') === '1';
        } else if (filter === 'popular') {
            show = card.getAttribute('data-popular') === '1';
        } else {
            show = card.getAttribute('data-category') === filter;
        }
        card.style.display = show ? 'flex' : 'none';
    });
}
</script>

<?php require __DIR__ . '/../../includes/layouts/user-footer.php'; ?>
