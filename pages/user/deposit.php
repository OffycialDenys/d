<?php
$pageTitle = 'Crypto Deposit';
require __DIR__ . '/../../includes/layouts/user-header.php';
$wallets = get_crypto_wallets();
$userDeposits = array_reverse(customer_deposits(current_customer_id()));
?>
<section class="deposit-flow" data-deposit-flow>
    <div class="section-title">
        <div>
            <h2>Deposit Cryptocurrency</h2>
            <p class="muted">Add funds to your account using BTC, USDT, or USDC. Every deposit is reviewed by our team and credited only after administrator verification.</p>
        </div>
    </div>

    <!-- Step indicator -->
    <ol class="steps" aria-label="Deposit progress">
        <li class="step is-active" data-step-indicator="1"><span class="step__num">1</span><span class="step__label">Select Asset</span></li>
        <li class="step" data-step-indicator="2"><span class="step__num">2</span><span class="step__label">Send &amp; Confirm</span></li>
        <li class="step" data-step-indicator="3"><span class="step__num">3</span><span class="step__label">Pending Review</span></li>
    </ol>

    <!-- Step 1: asset selection -->
    <section class="deposit-step is-active" data-step="1" aria-label="Select a cryptocurrency">
        <div class="grid grid--3">
            <?php foreach ($wallets as $symbol => $w): ?>
                <button type="button" class="card crypto-option" data-crypto="<?= e($symbol) ?>"
                        data-name="<?= e($w['name']) ?>" data-network="<?= e($w['network']) ?>"
                        data-address="<?= e($w['address']) ?>" aria-pressed="false">
                    <span class="crypto-option__icon" aria-hidden="true"><?= e($w['icon']) ?></span>
                    <span class="crypto-option__name"><?= e($w['name']) ?></span>
                    <span class="crypto-option__symbol"><?= e($symbol) ?></span>
                    <span class="crypto-option__network">Network: <?= e($w['network']) ?></span>
                    <p class="muted crypto-option__desc"><?= e($w['description']) ?></p>
                    <span class="crypto-option__min">Min: <?= e(money((float) $w['min'])) ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Step 2: instructions + address + QR -->
    <section class="deposit-step" data-step="2" aria-label="Send payment" hidden>
        <div class="grid grid--2">
            <article class="card">
                <div class="deposit-selected">
                    <span class="crypto-option__icon" data-selected-icon aria-hidden="true">₿</span>
                    <div>
                        <h3 style="margin:0;" data-selected-name>Bitcoin</h3>
                        <p class="muted" style="margin:2px 0 0;">Network: <span data-selected-network>Bitcoin</span></p>
                    </div>
                </div>

                <div class="field">
                    <label for="depositAddress">Wallet Address</label>
                    <div class="copy-field">
                        <input id="depositAddress" class="mono" readonly value="" data-wallet-address aria-label="Wallet address">
                        <button type="button" class="button button--ghost" data-copy-address aria-label="Copy wallet address">Copy</button>
                    </div>
                </div>

                <div class="qr-wrap">
                    <img class="qr-img" data-qr alt="QR code for the deposit wallet address" width="240" height="240">
                    <div class="qr-fallback" data-qr-fallback hidden>
                        <span class="qr-fallback__icon" aria-hidden="true">▦</span>
                        <p class="muted">QR code unavailable. Copy the address above and paste it into your wallet.</p>
                    </div>
                </div>
            </article>

            <article class="card">
                <h3 style="margin-top:0;">Payment Instructions</h3>
                <ul class="instruction-list">
                    <li>Send only <strong data-selected-symbol>BTC</strong> to the address shown.</li>
                    <li>Use the <strong data-selected-network>Bitcoin</strong> network.</li>
                    <li>Crypto transactions are <strong>irreversible</strong> — triple-check the address.</li>
                    <li>Deposits require administrator verification before funds are credited.</li>
                    <li>Confirmation times vary with network congestion.</li>
                </ul>

                <div class="field">
                    <label for="depositAmount">Amount sent (optional)</label>
                    <input id="depositAmount" type="number" step="any" min="0" inputmode="decimal" placeholder="0.00" data-deposit-amount>
                </div>
                <div class="field">
                    <label for="depositTx">Transaction hash / ID (optional)</label>
                    <input id="depositTx" placeholder="Paste your transaction hash if available" data-deposit-tx>
                </div>

                <div class="form-actions">
                    <button type="button" class="button button--ghost" data-step-back>Back</button>
                    <button type="button" class="button button--primary" data-confirm-deposit data-crypto-symbol="">
                        <span class="btn-label">I Have Sent Payment</span>
                        <span class="btn-spinner" aria-hidden="true" hidden></span>
                    </button>
                </div>
                <p class="deposit-status" data-deposit-status role="status" aria-live="polite"></p>
            </article>
        </div>
    </section>

    <!-- Step 3: pending confirmation -->
    <section class="deposit-step" data-step="3" aria-label="Deposit pending" hidden>
        <div class="empty-state">
            <span class="empty-state__icon" aria-hidden="true">⏳</span>
            <h3>Deposit Submitted</h3>
            <p>Your deposit (<strong data-receipt-id></strong>) is now <strong>Pending Review</strong>. We will notify you here as soon as an administrator verifies it.</p>
            <a class="button button--primary" href="index.php?route=deposit">Make Another Deposit</a>
        </div>
    </section>
</section>

<section class="section-title" style="margin-top: var(--space-4);">
    <div><h2>Deposit History</h2><p class="muted">Track the status of every deposit you submit.</p></div>
</section>
<section class="grid grid--2" data-deposit-history>
    <?php if (empty($userDeposits)): ?>
        <article class="card" data-empty style="grid-column:1/-1;">
            <p class="muted" style="margin:0; text-align:center; padding:1rem;">No deposits yet. Select an asset above to get started.</p>
        </article>
    <?php else: ?>
        <?php foreach ($userDeposits as $row): ?>
            <?php
            $icon = $wallets[$row['crypto'] ?? ''] ?? ['icon' => '◆'];
            ?>
            <article class="card deposit-history-card">
                <div class="deposit-history-card__head">
                    <span class="crypto-option__icon" aria-hidden="true"><?= e($icon['icon']) ?></span>
                    <div>
                        <strong><?= e($row['crypto'] ?? $row['method'] ?? 'Deposit') ?></strong>
                        <span class="muted" style="font-size:.8rem; display:block;"><?= e($row['network'] ?? '') ?></span>
                    </div>
                    <span class="<?= status_class($row['status']) ?>"><?= e($row['status']) ?></span>
                </div>
                <div class="plan-meta" style="margin-top:var(--space-3);">
                    <span>ID <strong><?= e($row['id']) ?></strong></span>
                    <span>Amount <strong><?= e(($row['amount'] > 0) ? money((float) $row['amount']) : '—') ?></strong></span>
                    <span>Submitted <strong><?= e($row['created_at'] ?? $row['date'] ?? '-') ?></strong></span>
                </div>
                <?php if (!empty($row['reviewer'])): ?>
                    <p class="muted" style="font-size:.8rem; margin:var(--space-2) 0 0;">Reviewed by <?= e($row['reviewer']) ?> <?= e($row['reviewed_at'] ?? '') ?></p>
                <?php endif; ?>
                <?php if (!empty($row['notes'])): ?>
                    <p class="muted" style="font-size:.8rem; margin:4px 0 0;"><strong>Note:</strong> <?= e($row['notes']) ?></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../../includes/layouts/user-footer.php'; ?>
