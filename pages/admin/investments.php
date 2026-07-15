<?php
$pageTitle = 'Investment Assets CMS';
require __DIR__ . '/../../includes/layouts/admin-header.php';

$plans = $_SESSION['platform']['plans'];
usort($plans, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));
?>
<section class="card management-panel">
    <div>
        <p class="eyebrow">Investment Marketplace CMS</p>
        <h2>Create or Update Investment Assets</h2>
        <p class="muted">Configure stocks, crypto, ETFs, commodities, and custom assets. Everything updates the client experience in real-time.</p>
    </div>
    
    <form method="post" class="grid" data-confirm="Save this asset configuration?">
        <input type="hidden" name="action" value="save_plan">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        
        <div class="form-grid">
            <div class="field">
                <label for="plan_id">Select Asset to Edit</label>
                <select id="plan_id" name="plan_id">
                    <option value="0">Create New Asset</option>
                    <?php foreach ($plans as $plan): ?>
                        <option value="<?= e((string) $plan['id']) ?>"><?= e($plan['name']) ?> (<?= e($plan['symbol'] ?? '') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="field">
                <label for="name">Asset Name</label>
                <input id="name" name="name" required placeholder="Apple Inc. or Bitcoin">
            </div>
            
            <div class="field">
                <label for="symbol">Symbol</label>
                <input id="symbol" name="symbol" required placeholder="AAPL, BTC, ETH, etc.">
            </div>
            
            <div class="field">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option>Stock</option>
                    <option>Cryptocurrency</option>
                    <option>ETF</option>
                    <option>Commodity</option>
                    <option>Forex</option>
                    <option>Index</option>
                    <option>Bond</option>
                    <option>Custom</option>
                </select>
            </div>

            <div class="field">
                <label for="price">Current Price ($)</label>
                <input id="price" name="price" type="number" min="0" step="0.01" required placeholder="185.20">
            </div>

            <div class="field">
                <label for="market_cap">Market Cap</label>
                <input id="market_cap" name="market_cap" placeholder="1.91T">
            </div>

            <div class="field">
                <label for="open">Open Price ($)</label>
                <input id="open" name="open" type="number" min="0" step="0.01" placeholder="149.99">
            </div>

            <div class="field">
                <label for="high">Day High ($)</label>
                <input id="high" name="high" type="number" min="0" step="0.01" placeholder="150.56">
            </div>

            <div class="field">
                <label for="low">Day Low ($)</label>
                <input id="low" name="low" type="number" min="0" step="0.01" placeholder="145.11">
            </div>

            <div class="field">
                <label for="dividend_yield">Dividend Yield (%)</label>
                <input id="dividend_yield" name="dividend_yield" type="number" min="0" step="0.01" placeholder="0.33">
            </div>

            <div class="field">
                <label for="roi">ROI Percentage (%)</label>
                <input id="roi" name="roi" type="number" min="0" step="0.01" required placeholder="104.5">
            </div>

            <div class="field">
                <label for="min">Minimum Investment ($)</label>
                <input id="min" name="min" type="number" min="0" step="0.01" required placeholder="50.00">
            </div>

            <div class="field">
                <label for="max">Maximum Investment ($)</label>
                <input id="max" name="max" type="number" min="0" step="0.01" required placeholder="10000.00">
            </div>

            <div class="field">
                <label for="daily_return">Daily Return (%)</label>
                <input id="daily_return" name="daily_return" type="number" min="0" step="0.01" required placeholder="0.15">
            </div>

            <div class="field">
                <label for="monthly_return">Monthly Return (%)</label>
                <input id="monthly_return" name="monthly_return" type="number" min="0" step="0.01" required placeholder="4.50">
            </div>

            <div class="field">
                <label for="daily">Daily Profit Return (Amt $)</label>
                <input id="daily" name="daily" type="number" min="0" step="0.01" required placeholder="0.25">
            </div>

            <div class="field">
                <label for="duration">Investment Duration (Days)</label>
                <input id="duration" name="duration" type="number" min="1" step="1" required placeholder="30">
            </div>

            <div class="field">
                <label for="lock_period">Lock Period (Days)</label>
                <input id="lock_period" name="lock_period" type="number" min="0" step="1" required placeholder="15">
            </div>

            <div class="field">
                <label for="risk_level">Risk Level</label>
                <select id="risk_level" name="risk_level">
                    <option>Low</option>
                    <option>Medium</option>
                    <option>High</option>
                </select>
            </div>

            <div class="field">
                <label for="market_status">Market Status</label>
                <select id="market_status" name="market_status">
                    <option>Open</option>
                    <option>Closed</option>
                </select>
            </div>

            <div class="field">
                <label for="status">Publication Status</label>
                <select id="status" name="status">
                    <option value="Open">Active (Open)</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Archived">Archived</option>
                    <option value="Draft">Draft</option>
                </select>
            </div>

            <div class="field">
                <label for="image">Asset Logo Key / Icon</label>
                <input id="image" name="image" placeholder="aapl-logo">
            </div>

            <div class="field">
                <label for="banner_image">Banner Image Key</label>
                <input id="banner_image" name="banner_image" placeholder="aapl-banner">
            </div>

            <div class="field">
                <label for="sort_order">Display Order Index</label>
                <input id="sort_order" name="sort_order" type="number" step="1" value="100">
            </div>
        </div>

        <div class="field">
            <label for="description">Short Description</label>
            <textarea id="description" name="description" required placeholder="Provide a brief summary of the business or technology..."></textarea>
        </div>

        <div class="field">
            <label for="ai_summary">AI Performance Summary</label>
            <textarea id="ai_summary" name="ai_summary" placeholder="AI analysis on market positioning and projected indicators..."></textarea>
        </div>

        <div class="field">
            <label for="key_points">Key Bullet Points (One per line)</label>
            <textarea id="key_points" name="key_points" placeholder="Institutional demand high&#10;Supply shock dynamics&#10;Macro asset hedge"></textarea>
        </div>

        <div style="display: flex; gap: var(--space-4); flex-wrap: wrap;">
            <label class="check-row"><input type="checkbox" name="featured"> Featured Badge</label>
            <label class="check-row"><input type="checkbox" name="is_trending"> Trending Badge</label>
            <label class="check-row"><input type="checkbox" name="is_beginner_friendly"> Beginner Friendly Badge</label>
            <label class="check-row"><input type="checkbox" name="has_dividend"> Dividend Badge</label>
            <label class="check-row"><input type="checkbox" name="is_popular"> Popular Badge</label>
        </div>

        <button class="button button--primary" type="submit">Publish / Save Asset</button>
    </form>
</section>

<div class="section-title">
    <div>
        <h2>Active Marketplace Assets</h2>
        <p class="muted">Live listing of configured assets in database</p>
    </div>
</div>

<section class="grid grid--3">
    <?php foreach ($plans as $plan): ?>
        <article class="card plan-admin-card" data-search-row>
            <div class="section-title" style="align-items: flex-start; margin-bottom: 0;">
                <div>
                    <span class="badge-category <?= strtolower(e($plan['category'])) ?>"><?= e($plan['category']) ?></span>
                    <h3 style="margin-top: 4px;"><?= e($plan['name']) ?> <span class="muted">(<?= e($plan['symbol'] ?? '') ?>)</span></h3>
                    <p class="muted">Order: <?= e((string) ($plan['sort_order'] ?? 0)) ?> | Risk: <span class="badge-risk <?= strtolower(e($plan['risk_level'] ?? 'medium')) ?>"><?= e($plan['risk_level'] ?? 'Medium') ?></span></p>
                </div>
                <span class="<?= status_class($plan['status']) ?>"><?= e($plan['status']) ?></span>
            </div>

            <div class="card-badges-row">
                <?php if (!empty($plan['featured'])): ?><span class="tag-badge featured">Featured</span><?php endif; ?>
                <?php if (!empty($plan['is_trending'])): ?><span class="tag-badge trending">Trending</span><?php endif; ?>
                <?php if (!empty($plan['is_beginner_friendly'])): ?><span class="tag-badge beginner">Beginner</span><?php endif; ?>
                <?php if (!empty($plan['has_dividend'])): ?><span class="tag-badge dividend">Dividend</span><?php endif; ?>
                <?php if (!empty($plan['is_popular'])): ?><span class="tag-badge popular">Popular</span><?php endif; ?>
                <span class="tag-badge" style="background:#555;"><?= e($plan['market_status'] ?? 'Open') ?></span>
            </div>

            <p class="muted" style="font-size: .88rem; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                <?= e($plan['description'] ?? '') ?>
            </p>

            <div class="plan-meta">
                <span>Price <strong>$<?= number_format((float)$plan['price'], 2) ?></strong></span>
                <span>ROI <strong><?= e((string) $plan['roi']) ?>%</strong></span>
                <span>Lock <strong><?= e((string) ($plan['lock_period'] ?? 0)) ?> Days</strong></span>
            </div>

            <div class="admin-actions">
                <form method="post" data-confirm="Duplicate this asset?"><input type="hidden" name="action" value="duplicate_plan"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="plan_id" value="<?= e((string) $plan['id']) ?>"><button class="button button--ghost" type="submit">Duplicate</button></form>
                <form method="post" data-confirm="Activate this asset?"><input type="hidden" name="action" value="change_plan_status"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="plan_id" value="<?= e((string) $plan['id']) ?>"><input type="hidden" name="status" value="Open"><button class="button button--ghost" type="submit">Activate</button></form>
                <form method="post" data-confirm="Deactivate this asset?"><input type="hidden" name="action" value="change_plan_status"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="plan_id" value="<?= e((string) $plan['id']) ?>"><input type="hidden" name="status" value="Inactive"><button class="button button--ghost" type="submit">Deactivate</button></form>
                <form method="post" data-confirm="Archive this asset?"><input type="hidden" name="action" value="change_plan_status"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="plan_id" value="<?= e((string) $plan['id']) ?>"><input type="hidden" name="status" value="Archived"><button class="button button--danger" type="submit">Archive</button></form>
                <form method="post" data-confirm="Delete this asset permanently?"><input type="hidden" name="action" value="delete_plan"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="plan_id" value="<?= e((string) $plan['id']) ?>"><button class="button button--danger" type="submit">Delete</button></form>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<script>
window.planEditorData = <?= json_encode(array_values($plans), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
</script>
<?php require __DIR__ . '/../../includes/layouts/admin-footer.php'; ?>
