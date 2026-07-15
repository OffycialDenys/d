<?php
$pageTitle = 'Investment Performance';
require __DIR__ . '/../../includes/layouts/user-header.php';
$orders = customer_orders(current_customer_id());
?>
<section class="section-title">
    <div>
        <h2>Purchased Investments</h2>
        <p class="muted">Track the live value of every position you hold, review performance, and manage each holding.</p>
    </div>
</section>

<?php if (empty($orders)): ?>
<section class="empty-state">
    <span class="empty-state__icon">📭</span>
    <h3>No Active Investments</h3>
    <p>You haven't purchased any investments yet. Visit the marketplace to start building your portfolio.</p>
    <a class="button button--primary" href="index.php?route=investments">Explore Marketplace</a>
</section>
<?php else: ?>
<section class="grid orders-grid">
    <?php foreach ($orders as $row): ?>
        <?php
        // Resolve the marketplace plan behind this holding for symbol/category/link context.
        $plan = current(array_filter(
            $_SESSION['platform']['plans'] ?? [],
            fn($p) =>
                strtolower($p['name'] ?? '') === strtolower($row['plan'] ?? '')
                || strtolower($p['symbol'] ?? '') === strtolower($row['symbol'] ?? '')
        ));

        $costBasis    = (float) ($row['amount'] ?? 0);
        $unrealized   = (float) ($row['profit'] ?? 0);
        $marketValue  = $costBasis + $unrealized;
        $growth       = $costBasis > 0 ? ($unrealized / $costBasis) * 100 : 0;
        $status       = $row['status'] ?? 'pending';
        $isOpen       = $status === 'Active';

        $completionTs = !empty($row['completion_date']) ? strtotime((string) $row['completion_date']) : false;
        $remainingDays = ($completionTs !== false)
            ? max(0, (int) ceil(($completionTs - time()) / 86400))
            : null;

        $symbol      = strtoupper((string) ($row['symbol'] ?? ''));
        $initials    = $symbol !== '' ? substr($symbol, 0, 2) : strtoupper(substr((string) ($row['plan'] ?? ''), 0, 2));
        $category    = strtolower($plan['category'] ?? 'stock');
        $logoClass   = 'position-card__logo ' . e($category);
        $detailId    = (int) ($plan['id'] ?? 0);
        ?>
        <article class="card position-card" data-search-row>
            <header class="position-card__head">
                <div class="position-card__asset">
                    <div class="<?= $logoClass ?>"><?= e($initials) ?></div>
                    <div>
                        <p class="eyebrow"><?= e((string) ($row['id'] ?? '')) ?></p>
                        <h3 class="position-card__name"><?= e((string) ($row['plan'] ?? '')) ?></h3>
                        <?php if ($symbol !== ''): ?>
                            <p class="position-card__symbol muted"><?= e($symbol) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="<?= status_class($status) ?>"><?= e($status) ?></span>
            </header>

            <div class="mini-chart mini-chart--tall" data-chart data-points="22,27,34,42,51,59,67,75,84"></div>

            <div class="position-card__figures">
                <div class="position-fig">
                    <span class="position-fig__label">Market Value</span>
                    <span class="position-fig__value"><?= e(money($marketValue)) ?></span>
                </div>
                <div class="position-fig">
                    <span class="position-fig__label">Cost Basis</span>
                    <span class="position-fig__value"><?= e(money($costBasis)) ?></span>
                </div>
                <div class="position-fig">
                    <span class="position-fig__label">Unrealized P/L</span>
                    <span class="position-fig__value <?= $unrealized >= 0 ? 'is-up' : 'is-down' ?>">
                        <?= e(money($unrealized)) ?>
                        <span class="position-fig__delta">(<?= e(number_format($growth, 2)) ?>%)</span>
                    </span>
                </div>
                <div class="position-fig">
                    <span class="position-fig__label">Days to Maturity</span>
                    <span class="position-fig__value"><?= $remainingDays !== null ? e((string) $remainingDays) : '—' ?></span>
                </div>
            </div>

            <div class="position-card__progress">
                <progress class="progress" max="100" value="<?= e((string) ($row['progress'] ?? 0)) ?>"></progress>
                <p class="muted position-card__progress-note">
                    <?= e((string) ($row['progress'] ?? 0)) ?>% of term
                    &middot; Matures <?= e((string) ($row['completion_date'] ?? '—')) ?>
                </p>
            </div>

            <div class="position-card__actions">
                <a class="button button--primary"
                   href="index.php?route=owned-investment-details&id=<?= e((string) $detailId) ?>">
                    View Details
                </a>

                <div class="position-card__actions-row">
                    <?php if ($isOpen && $plan): ?>
                        <a class="button button--outline"
                           href="index.php?route=investment-details&id=<?= e((string) $detailId) ?>">
                            Buy
                        </a>
                    <?php else: ?>
                        <button class="button button--outline" type="button" disabled>
                            Buy
                        </button>
                    <?php endif; ?>

                    <?php if ($isOpen): ?>
                        <form method="post"
                              action="index.php?route=owned-investment-details&id=<?= e((string) $detailId) ?>"
                              data-confirm="Sell this position? The current market value will be returned to your wallet and this cannot be undone.">
                            <input type="hidden" name="action" value="sell">
                            <input type="hidden" name="order_id" value="<?= e((string) ($row['id'] ?? '')) ?>">
                            <button type="submit" class="button button--danger">Sell Position</button>
                        </form>
                    <?php else: ?>
                        <button class="button button--outline" type="button" disabled>
                            Sell Position
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/layouts/user-footer.php'; ?>
