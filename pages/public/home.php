<?php $pageTitle = 'Investment Platform'; require __DIR__ . '/../../includes/layouts/public-header.php'; ?>
<section class="hero">
    <div>
        <p class="eyebrow">Commercial investment operations</p>
        <h1><?= e($config['name']) ?></h1>
        <p><?= e($config['tagline']) ?> with wallet controls, multi-asset marketplace, referral rewards, transparent transactions, and an administrator command center.</p>
        <div class="hero-actions">
            <a class="button button--primary" href="index.php?route=register">Start Investing</a>
            <a class="button button--ghost" href="index.php?route=login">Open Dashboard</a>
        </div>
    </div>
    <div class="hero-panel">
        <article class="card hero-chart">
            <p class="eyebrow">Portfolio Snapshot</p>
            <h2>Designed for clear investment tracking</h2>
            <div class="chart-bars" aria-hidden="true">
                <span class="bar-36"></span><span class="bar-48"></span><span class="bar-58"></span><span class="bar-74"></span><span class="bar-88"></span>
            </div>
        </article>
    </div>
</section>

<!-- Trust strip: credibility metrics that make the page read as a mature platform. -->
<section class="trust-strip" aria-label="Platform highlights">
    <div class="stat-card"><span>Funded account sizes</span><strong>$5K – $100K</strong></div>
    <div class="stat-card"><span>Traders funded</span><strong>10,000+</strong></div>
    <div class="stat-card"><span>Paid to traders</span><strong>$50M+</strong></div>
    <div class="stat-card"><span>Avg. payout time</span><strong>14 days</strong></div>
</section>

<section id="features" class="page-section">
    <div class="section-title"><h2>Platform Features</h2><p class="muted">Everything connected to one financial record.</p></div>
    <div class="grid grid--4">
        <?php foreach (['Wallet ledger', 'Investment marketplace', 'Referral levels', 'Reward codes', 'Deposit review', 'Withdrawal control', 'Support tickets', 'Admin reports'] as $feature): ?>
            <article class="card card--interactive"><strong><?= e($feature) ?></strong><p class="muted">Built as a reusable module with status tracking and activity records.</p></article>
        <?php endforeach; ?>
    </div>
</section>

<section id="plans" class="page-section">
    <div class="section-title"><h2>Investment Assets</h2><a class="button button--ghost" href="index.php?route=login">View all</a></div>
    <div class="grid grid--3">
        <?php foreach ($_SESSION['platform']['plans'] as $plan): ?>
            <article class="card investment-card card--interactive">
                <span class="plan-visual" style="border-radius:12px; background:#eef2ff; color:#4f46e5; font-size:1.25rem; font-weight:800;"><?= e($plan['symbol'] ?? '') ?></span>
                <div>
                    <span class="<?= status_class($plan['status']) ?>"><?= e($plan['status']) ?></span>
                    <h3><?= e($plan['name']) ?></h3>
                    <p class="muted"><?= e($plan['category']) ?> asset with <?= e($plan['duration']) ?> days lock period.</p>
                    <div class="plan-meta">
                        <span>Price <strong><?= e(money($plan['price'])) ?></strong></span>
                        <span>Daily <strong><?= e($plan['daily_return']) ?>%</strong></span>
                        <span>ROI <strong><?= e($plan['roi']) ?>%</strong></span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<!-- Funded account challenges: interactive size tabs + dynamic pricing cards. -->
<section id="challenges" class="page-section">
    <div class="section-title">
        <div>
            <p class="eyebrow">Funded trading accounts</p>
            <h2>Choose your account size</h2>
            <p class="muted">Pick a challenge, prove your strategy, and trade with our capital. Payouts every 14 days.</p>
        </div>
        <a class="button button--ghost" href="index.php?route=register">Open an account</a>
    </div>

    <div class="size-tabs" id="sizeTabs" role="tablist" aria-label="Account size">
        <button class="size-tab is-active" type="button" data-size="5k" role="tab" aria-selected="true">$5,000</button>
        <button class="size-tab" type="button" data-size="10k" role="tab" aria-selected="false">$10,000</button>
        <button class="size-tab" type="button" data-size="25k" role="tab" aria-selected="false">$25,000</button>
        <button class="size-tab" type="button" data-size="50k" role="tab" aria-selected="false">$50,000</button>
        <button class="size-tab" type="button" data-size="100k" role="tab" aria-selected="false">$100,000</button>
    </div>

    <div class="grid grid--3 price-cards" id="priceCards"></div>

    <p class="muted" style="margin-top: var(--space-4); text-align: center;">
        Every account includes scaling opportunities as you hit consistent targets.
    </p>
</section>

<section id="faq" class="page-section">
    <div class="section-title" style="justify-content: center; text-align: center;">
        <div>
            <p class="eyebrow">Questions</p>
            <h2>Frequently asked questions</h2>
            <p class="muted">Everything you need to know about funded accounts and payouts.</p>
        </div>
    </div>

    <div class="faq">
        <div class="faq-item">
            <button class="faq-btn" type="button" aria-expanded="false">
                How much capital is available?
                <span class="faq-icon" aria-hidden="true"></span>
            </button>
            <div class="faq-content"><div class="faq-content__inner">
                Accounts range from small starter sizes up to large funded accounts with scaling opportunities.
            </div></div>
        </div>

        <div class="faq-item">
            <button class="faq-btn" type="button" aria-expanded="false">
                Which challenge type should I choose?
                <span class="faq-icon" aria-hidden="true"></span>
            </button>
            <div class="faq-content"><div class="faq-content__inner">
                The 1-Step plan gets you funded fastest with a single profit target. The 2-Step plan splits the target across two phases for a lower entry fee. Instant Funding skips the challenge entirely and starts you trading immediately under a trailing drawdown.
            </div></div>
        </div>

        <div class="faq-item">
            <button class="faq-btn" type="button" aria-expanded="false">
                How does the profit split work?
                <span class="faq-icon" aria-hidden="true"></span>
            </button>
            <div class="faq-content"><div class="faq-content__inner">
                You keep the majority of profits generated on your funded account, with payouts released on a 14-day reward cycle once you meet the consistency rules.
            </div></div>
        </div>

        <div class="faq-item">
            <button class="faq-btn" type="button" aria-expanded="false">
                What is the drawdown rule?
                <span class="faq-icon" aria-hidden="true"></span>
            </button>
            <div class="faq-content"><div class="faq-content__inner">
                Each account has a defined maximum drawdown. The Instant Funding plan uses a trailing drawdown that follows your balance upward as you grow your profits.
            </div></div>
        </div>

        <div class="faq-item">
            <button class="faq-btn" type="button" aria-expanded="false">
                How fast are payouts processed?
                <span class="faq-icon" aria-hidden="true"></span>
            </button>
            <div class="faq-content"><div class="faq-content__inner">
                Approved withdrawals are paid on the 14-day reward cycle, with most processed within a few business days after verification.
            </div></div>
        </div>

        <div class="faq-item">
            <button class="faq-btn" type="button" aria-expanded="false">
                Is there a scaling plan?
                <span class="faq-icon" aria-hidden="true"></span>
            </button>
            <div class="faq-content"><div class="faq-content__inner">
                Yes. Consistently hitting your targets unlocks larger account sizes through our scaling program, so your capital grows alongside your performance.
            </div></div>
        </div>
    </div>

    <article class="card" id="contact" style="max-width: 860px; margin: var(--space-5) auto 0;">
        <h2>Still have questions?</h2>
        <p>Our support team responds to every ticket from your dashboard, and administrators follow up through the management console.</p>
        <a class="button button--primary" href="index.php?route=register">Contact support</a>
    </article>
</section>

<script>
/*
 * Home page interactions: funded-account pricing tabs + FAQ accordion.
 * Scoped inside an IIFE so no globals leak into the shared app.js scope.
 */
(function () {
    const prices = {
        "5k": [
            { name: "1-Step Standard", price: "$30.10", features: ["Profit Target: $500", "Max Drawdown: $300", "Reward Cycle: 14 days"], highlight: false },
            { name: "2-Step Standard", price: "$44.10", features: ["Phase 1 Target: $500", "Phase 2 Target: $250", "Reward Cycle: 14 days"], highlight: true },
            { name: "Instant Funding", price: "$52.50", features: ["No challenge phases", "Trailing drawdown", "Reward Cycle: 14 days"], highlight: false }
        ],
        "10k": [
            { name: "1-Step Standard", price: "$49.00", features: ["Profit Target: $1,000", "Max Drawdown: $600", "Reward Cycle: 14 days"], highlight: false },
            { name: "2-Step Standard", price: "$69.00", features: ["Phase 1 Target: $1,000", "Phase 2 Target: $500", "Reward Cycle: 14 days"], highlight: true },
            { name: "Instant Funding", price: "$89.00", features: ["No challenge phases", "Trailing drawdown", "Reward Cycle: 14 days"], highlight: false }
        ],
        "25k": [
            { name: "1-Step Standard", price: "$129.00", features: ["Profit Target: $2,500", "Max Drawdown: $1,500", "Reward Cycle: 14 days"], highlight: false },
            { name: "2-Step Standard", price: "$159.00", features: ["Phase 1 Target: $2,500", "Phase 2 Target: $1,250", "Reward Cycle: 14 days"], highlight: true },
            { name: "Instant Funding", price: "$199.00", features: ["No challenge phases", "Trailing drawdown", "Reward Cycle: 14 days"], highlight: false }
        ],
        "50k": [
            { name: "1-Step Standard", price: "$249.00", features: ["Profit Target: $5,000", "Max Drawdown: $3,000", "Reward Cycle: 14 days"], highlight: false },
            { name: "2-Step Standard", price: "$299.00", features: ["Phase 1 Target: $5,000", "Phase 2 Target: $2,500", "Reward Cycle: 14 days"], highlight: true },
            { name: "Instant Funding", price: "$369.00", features: ["No challenge phases", "Trailing drawdown", "Reward Cycle: 14 days"], highlight: false }
        ],
        "100k": [
            { name: "1-Step Standard", price: "$449.00", features: ["Profit Target: $10,000", "Max Drawdown: $6,000", "Reward Cycle: 14 days"], highlight: false },
            { name: "2-Step Standard", price: "$549.00", features: ["Phase 1 Target: $10,000", "Phase 2 Target: $5,000", "Reward Cycle: 14 days"], highlight: true },
            { name: "Instant Funding", price: "$699.00", features: ["No challenge phases", "Trailing drawdown", "Reward Cycle: 14 days"], highlight: false }
        ]
    };

    const cardsRoot = document.getElementById("priceCards");
    const tabRoot = document.getElementById("sizeTabs");

    function renderCards(sizeKey) {
        if (!cardsRoot || !prices[sizeKey]) return;
        cardsRoot.innerHTML = prices[sizeKey].map((plan) => {
            const featureHtml = plan.features.map((f) => "<li>" + f + "</li>").join("");
            const badge = plan.highlight ? '<span class="price-card__badge">Most popular</span>' : "";
            const cta = plan.highlight ? "Get funded" : "Start";
            return '<article class="card price-card ' + (plan.highlight ? "price-card--highlight" : "") + '">' +
                badge +
                "<h3>" + plan.name + "</h3>" +
                '<div class="price-card__price">' + plan.price + "<span>one-time fee</span></div>" +
                '<ul class="price-card__list">' + featureHtml + "</ul>" +
                '<a class="button button--primary" style="width:100%;" href="index.php?route=register">' + cta + "</a>" +
                "</article>";
        }).join("");
    }

    if (tabRoot && cardsRoot) {
        tabRoot.addEventListener("click", (e) => {
            const target = e.target.closest(".size-tab");
            if (!target) return;
            tabRoot.querySelectorAll(".size-tab").forEach((tab) => {
                const active = tab === target;
                tab.classList.toggle("is-active", active);
                tab.setAttribute("aria-selected", active ? "true" : "false");
            });
            renderCards(target.dataset.size);
        });

        renderCards("5k");
    }

    document.querySelectorAll(".faq-item").forEach((item) => {
        const btn = item.querySelector(".faq-btn");
        const content = item.querySelector(".faq-content");
        if (!btn || !content) return;
        btn.addEventListener("click", () => {
            const isOpen = item.classList.toggle("is-open");
            btn.setAttribute("aria-expanded", isOpen ? "true" : "false");
            content.style.maxHeight = isOpen ? content.scrollHeight + "px" : "0px";
        });
    });
})();
</script>

<?php require __DIR__ . '/../../includes/layouts/public-footer.php'; ?>
