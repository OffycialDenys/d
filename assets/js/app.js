(function () {
    const menuButton = document.querySelector('[data-menu-toggle]');
    const menu = document.querySelector('[data-menu]');
    if (menuButton && menu) {
        // Accessibility: keep aria-expanded in sync with the menu's open state
        const syncMenu = () => menuButton.setAttribute('aria-expanded', menu.classList.contains('is-open') ? 'true' : 'false');
        syncMenu();
        menuButton.addEventListener('click', () => {
            menu.classList.toggle('is-open');
            syncMenu();
        });
    }

    const sidebarButton = document.querySelector('[data-sidebar-toggle]');
    const sidebar = document.querySelector('[data-sidebar]');
    if (sidebarButton && sidebar) {
        // Accessibility: keep aria-expanded in sync
        sidebarButton.setAttribute('aria-expanded', sidebar.classList.contains('is-open') ? 'true' : 'false');

        sidebarButton.addEventListener('click', () => {
            sidebar.classList.toggle('is-open');
            const expanded = sidebar.classList.contains('is-open');
            sidebarButton.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });

        // Keyboard accessibility: allow Enter/Space (in case default button handling is altered)
        sidebarButton.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                sidebarButton.click();
            }
        });
    }

    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const text = button.getAttribute('data-copy') || '';
            try {
                await navigator.clipboard.writeText(text);
                button.textContent = 'Copied';
                window.setTimeout(() => { button.textContent = 'Copy'; }, 1600);
            } catch (error) {
                button.textContent = 'Select';
            }
        });
    });

    const searchInput = document.querySelector('[data-table-search]');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const term = searchInput.value.trim().toLowerCase();
            document.querySelectorAll('[data-search-row]').forEach((row) => {
                row.hidden = term !== '' && !row.textContent.toLowerCase().includes(term);
            });
        });
    }

    document.querySelectorAll('[data-preset]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.querySelector(button.getAttribute('data-preset-target'));
            if (target) {
                target.value = button.getAttribute('data-preset');
                target.focus();
            }
        });
    });

    document.querySelectorAll('[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.getAttribute('data-confirm') || 'Continue?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
    const planSelect = document.querySelector('#plan_id');
    const planForm = planSelect ? planSelect.closest('form') : null;
    const PLAN_EDITOR_RESERVED = new Set(['plan_id', 'action']);
    if (planSelect && planForm && Array.isArray(window.planEditorData)) {
        planSelect.addEventListener('change', () => {
            const plan = window.planEditorData.find((item) => String(item.id) === planSelect.value);

            // Bind every editable control to the matching plan key automatically.
            // Replaces the previous hardcoded 20+ field list, so schema changes no
            // longer require editing this script.
            planForm.querySelectorAll('input[name], select[name], textarea[name]').forEach((el) => {
                const key = el.getAttribute('name');
                if (!key || PLAN_EDITOR_RESERVED.has(key)) return;

                if (el.type === 'checkbox') {
                    el.checked = plan ? Boolean(Number(plan[key] ?? 0)) : false;
                } else {
                    el.value = plan ? (plan[key] ?? '') : '';
                }
            });
        });
    }

    // CHART TREND HELPERS
    // Map a trend direction to a CSS design-token color so every chart stays
    // consistent with the application theme instead of hardcoding hex values.
    const TREND_COLORS = {
        up: 'var(--color-success)',
        down: 'var(--color-danger)',
        flat: 'var(--color-muted)',
    };

    function resolveTrend(points) {
        if (!Array.isArray(points) || points.length < 2) return 'flat';
        const first = points[0];
        const last = points[points.length - 1];
        const relative = Math.abs(last - first) / (Math.abs(first) || 1);
        if (!Number.isFinite(relative) || relative < 0.001) return 'flat';
        return last > first ? 'up' : 'down';
    }

    // Coerce raw values into finite, positive numbers, dropping anything invalid.
    function safePoints(raw) {
        return Array.isArray(raw)
            ? raw.map(Number).filter((n) => Number.isFinite(n) && n > 0)
            : [];
    }

    // SPARKLINE CHARTS
    function renderSparklines() {
        document.querySelectorAll('[data-chart]').forEach((chart) => {
            const points = safePoints(
                (chart.getAttribute('data-points') || '')
                    .split(',')
                    .map((value) => value.trim())
            );

            if (points.length < 2) {
                return;
            }

            const trend = resolveTrend(points);
            chart.style.setProperty('--chart-trend-color', TREND_COLORS[trend]);

            const width = 320;
            const height = 120;
            const padding = 10;
            const max = Math.max(...points);
            const min = Math.min(...points);
            const range = Math.max(1e-6, max - min);
            const step = (width - padding * 2) / (points.length - 1);
            const coords = points.map((value, index) => {
                const x = padding + index * step;
                const y = height - padding - ((value - min) / range) * (height - padding * 2);
                return `${x.toFixed(1)},${y.toFixed(1)}`;
            });
            const area = `${padding},${height - padding} ${coords.join(' ')} ${width - padding},${height - padding}`;

            chart.innerHTML = `
                <svg viewBox="0 0 ${width} ${height}" role="img" aria-label="Investment performance trend: ${trend}">
                    <defs>
                        <linearGradient id="chartFill-${Math.random().toString(36).slice(2)}" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="currentColor" stop-opacity=".24"/>
                            <stop offset="100%" stop-color="currentColor" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <polyline class="mini-chart__grid" points="${padding},${height * .7} ${width - padding},${height * .7}"></polyline>
                    <polygon class="mini-chart__area" points="${area}"></polygon>
                    <polyline class="mini-chart__line" points="${coords.join(' ')}"></polyline>
                </svg>
            `;
        });
    }
    renderSparklines();

    // INTERACTIVE CHART SIMULATOR (DETAILS PAGE)
    const interactiveChart = document.querySelector('[data-interactive-chart]');
    if (interactiveChart) {
        const renderLargeChart = (points) => {
            const width = 800;
            const height = 300;
            const padding = 20;

            // Apply trend coloring through the shared theme token.
            const trend = resolveTrend(points);
            interactiveChart.style.setProperty('--chart-trend-color', TREND_COLORS[trend]);

            const max = Math.max(...points) * 1.02;
            const min = Math.min(...points) * 0.98;
            const range = (max - min) || 1;
            const step = (width - padding * 2) / (points.length - 1);

            const coords = points.map((value, index) => {
                const x = padding + index * step;
                const y = height - padding - ((value - min) / range) * (height - padding * 2);
                return `${x.toFixed(1)},${y.toFixed(1)}`;
            });
            const area = `${padding},${height - padding} ${coords.join(' ')} ${width - padding},${height - padding}`;

            interactiveChart.innerHTML = `
                <svg viewBox="0 0 ${width} ${height}" class="w-full h-full" role="img" aria-label="Simulated growth performance trend: ${trend}">
                    <defs>
                        <linearGradient id="largeChartFill" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="currentColor" stop-opacity="0.25"/>
                            <stop offset="100%" stop-color="currentColor" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <!-- Grid Lines -->
                    <line x1="${padding}" y1="${height * 0.25}" x2="${width - padding}" y2="${height * 0.25}" stroke="var(--color-border)" stroke-dasharray="4 6" stroke-width="1" />
                    <line x1="${padding}" y1="${height * 0.5}" x2="${width - padding}" y2="${height * 0.5}" stroke="var(--color-border)" stroke-dasharray="4 6" stroke-width="1" />
                    <line x1="${padding}" y1="${height * 0.75}" x2="${width - padding}" y2="${height * 0.75}" stroke="var(--color-border)" stroke-dasharray="4 6" stroke-width="1" />

                    <!-- Area & Line (stroke/gradient inherit currentColor = trend color) -->
                    <polygon fill="url(#largeChartFill)" points="${area}"></polygon>
                    <polyline fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" points="${coords.join(' ')}" class="mini-chart__line"></polyline>
                </svg>
            `;

            // Stash geometry + data so the crosshair can map a pointer
            // position back to the nearest underlying data point.
            chartPoints = points;
            chartGeo = { width, height, padding, step, min, max, range };
            buildCrosshair(interactiveChart.querySelector('svg'));
            // Mirror Apple Stocks: open on the latest point, then follow the cursor.
            showCrosshairAt(points.length - 1);
        };

        // --- Draggable crosshair (Apple Stocks-style) -------------------
        // A vertical + horizontal scrubber with a value bubble on the right
        // axis and a date bubble on the bottom axis. Persists after the
        // pointer leaves (like Apple), and is driven by hover or drag.
        const SVG_NS = 'http://www.w3.org/2000/svg';
        const RANGE_DAYS = { '1D': 1, '1W': 7, '1M': 30, '3M': 90, '1Y': 365, '5Y': 1825 };

        let chartPoints = [];
        let chartGeo = null;
        let crosshair = null;

        function buildCrosshair(svg) {
            if (!svg) return null;
            const g = document.createElementNS(SVG_NS, 'g');
            g.setAttribute('class', 'chart-crosshair');
            g.setAttribute('hidden', '');
            const vLine = document.createElementNS(SVG_NS, 'line');
            vLine.setAttribute('class', 'chart-crosshair__v');
            const hLine = document.createElementNS(SVG_NS, 'line');
            hLine.setAttribute('class', 'chart-crosshair__h');
            const dot = document.createElementNS(SVG_NS, 'circle');
            dot.setAttribute('class', 'chart-crosshair__dot');
            const price = document.createElementNS(SVG_NS, 'text');
            price.setAttribute('class', 'chart-crosshair__label chart-crosshair__price');
            price.setAttribute('text-anchor', 'end');
            const date = document.createElementNS(SVG_NS, 'text');
            date.setAttribute('class', 'chart-crosshair__label chart-crosshair__date');
            date.setAttribute('text-anchor', 'middle');
            g.append(vLine, hLine, price, date, dot);
            svg.appendChild(g);
            crosshair = { g, vLine, hLine, dot, price, date };
            return crosshair;
        }

        function pointDateLabel(index, count) {
            const totalDays = RANGE_DAYS[activeRange] ?? 30;
            const interval = count > 1 ? totalDays / (count - 1) : 0;
            const daysAgo = (count - 1 - index) * interval;
            const d = new Date();
            d.setDate(d.getDate() - Math.round(daysAgo));
            const opts = { month: 'short', day: 'numeric' };
            if (totalDays > 120) opts.year = 'numeric';
            return d.toLocaleDateString('en-US', opts);
        }

        function showCrosshairAt(index) {
            if (!crosshair || !chartGeo || !chartPoints.length) return;
            const { width, height, padding, step, min, max, range } = chartGeo;
            const clamped = Math.max(0, Math.min(chartPoints.length - 1, index));
            const value = chartPoints[clamped];
            const px = padding + clamped * step;
            const py = height - padding - ((value - min) / range) * (height - padding * 2);
            const { g, vLine, hLine, dot, price, date } = crosshair;
            g.removeAttribute('hidden');
            vLine.setAttribute('x1', px); vLine.setAttribute('x2', px);
            vLine.setAttribute('y1', padding); vLine.setAttribute('y2', height - padding);
            hLine.setAttribute('x1', padding); hLine.setAttribute('x2', width - padding);
            hLine.setAttribute('y1', py); hLine.setAttribute('y2', py);
            dot.setAttribute('cx', px); dot.setAttribute('cy', py);
            price.textContent = fmtMoney(value);
            price.setAttribute('x', width - padding - 6);
            price.setAttribute('y', Math.max(padding + 12, Math.min(height - padding - 6, py + 4)));
            date.textContent = pointDateLabel(clamped, chartPoints.length);
            const dateX = Math.max(padding + 28, Math.min(width - padding - 28, px));
            date.setAttribute('x', dateX);
            date.setAttribute('y', height - 6);
        }

        function clientToSvg(svg, clientX, clientY) {
            const ctm = svg.getScreenCTM();
            if (!ctm) return null;
            const pt = svg.createSVGPoint();
            pt.x = clientX; pt.y = clientY;
            return pt.matrixTransform(ctm.inverse());
        }

        function onPointerMove(event) {
            const svg = interactiveChart.querySelector('svg');
            if (!svg || !chartGeo) return;
            const p = clientToSvg(svg, event.clientX, event.clientY);
            if (!p) return;
            const { width, height, padding, step } = chartGeo;
            const ux = Math.max(padding, Math.min(width - padding, p.x));
            showCrosshairAt(Math.round((ux - padding) / step));
        }

        // Render a non-silent state overlay (loading / empty / error) so the
        // user always understands why data is (un)available.
        const showChartState = (type, title, message, onRetry) => {
            const icon = type === 'empty' ? '📊' : '⚠️';
            const spinner = type === 'loading'
                ? '<span class="chart-state__spinner" aria-hidden="true"></span>'
                : `<span class="chart-state__icon" aria-hidden="true">${icon}</span>`;
            const retryBtn = onRetry
                ? '<button type="button" class="button button--ghost" data-chart-retry>Retry</button>'
                : '';
            interactiveChart.innerHTML = `
                <div class="chart-state" role="status" aria-live="polite">
                    ${spinner}
                    <p class="chart-state__title">${title}</p>
                    <p class="chart-state__message">${message}</p>
                    ${retryBtn}
                </div>
            `;
            const retryEl = interactiveChart.querySelector('[data-chart-retry]');
            if (retryEl && onRetry) {
                retryEl.addEventListener('click', () => onRetry());
            }
        };

        // Asset id resolution: explicit attribute > container > URL (?id=)
        const assetId = interactiveChart.getAttribute('data-asset')
            || new URLSearchParams(window.location.search).get('id')
            || '0';

        let activeRange = '1M';

        const loadChart = async (range) => {
            activeRange = range;
            showChartState('loading', 'Loading chart…', 'Fetching simulated performance data for this asset.');
            try {
                const res = await fetch(`api/charts.php?asset=${encodeURIComponent(assetId)}&range=${encodeURIComponent(range)}`);
                if (!res.ok) {
                    throw new Error(`Chart request failed with HTTP ${res.status}`);
                }
                const data = await res.json();
                const points = safePoints(data && data.points);
                if (points.length < 2) {
                    showChartState('empty', 'No historical data', 'There is no performance history available for this timeframe yet.');
                    return;
                }
                renderLargeChart(points);
            } catch (err) {
                // Surface the failure instead of silently rendering a wrong line.
                console.error('Interactive chart failed to load:', err);
                showChartState(
                    'error',
                    'Chart unavailable',
                    'We could not load this chart. Please check your connection and try again.',
                    () => loadChart(activeRange)
                );
            }
        };

        // Timeframe selector interaction
        const buttons = document.querySelectorAll('.timeframe-btn');
        buttons.forEach((btn) => {
            btn.addEventListener('click', () => {
                buttons.forEach((b) => b.classList.remove('is-active'));
                btn.classList.add('is-active');
                loadChart(btn.getAttribute('data-timeframe') || '1M');
            });
        });

        // Initial render
        const activeBtn = document.querySelector('.timeframe-btn.is-active') || buttons[0];
        loadChart(activeBtn ? (activeBtn.getAttribute('data-timeframe') || '1M') : '1M');

        // Crosshair interaction (hover + touch drag), Apple Stocks-style.
        interactiveChart.addEventListener('pointermove', onPointerMove);
        interactiveChart.addEventListener('pointerdown', (event) => {
            try { interactiveChart.setPointerCapture(event.pointerId); } catch (_) { /* no-op */ }
            onPointerMove(event);
        });
        interactiveChart.addEventListener('pointerup', (event) => {
            try { interactiveChart.releasePointerCapture(event.pointerId); } catch (_) { /* no-op */ }
        });
        interactiveChart.addEventListener('pointercancel', () => {});
    }

    // INVESTMENT CALCULATOR
    const calcSlider = document.querySelector('[data-calc-slider]');
    const calcInput = document.querySelector('[data-calc-amount]');
    const calcProfit = document.querySelector('[data-calc-profit]');
    const calcTotal = document.querySelector('[data-calc-total]');

    if (calcSlider && calcInput) {
        const roiPercent = Number(document.querySelector('[data-calc-roi]')?.value || 100);

        const updateCalculator = (value) => {
            const amount = Math.max(0, Number(value));
            calcInput.value = amount;
            calcSlider.value = amount;

            const total = amount * (roiPercent / 100);
            const profit = total - amount;

            if (calcProfit) calcProfit.textContent = '$' + profit.toFixed(2);
            if (calcTotal) calcTotal.textContent = '$' + total.toFixed(2);
        };

        calcSlider.addEventListener('input', (e) => {
            updateCalculator(e.target.value);
        });

        calcInput.addEventListener('input', (e) => {
            updateCalculator(e.target.value);
        });

        // Initialize calculator
        updateCalculator(calcInput.value);
    }

    // DETAILS PAGE: TAB SWITCHING + CHECKOUT MODAL ORCHESTRATION (investment-details.php)
    const detailTabs = document.querySelectorAll('[data-tab-btn]');
    const tabContents = document.querySelectorAll('.tab-content');

    if (detailTabs && detailTabs.length) {
        detailTabs.forEach((btn) => {
            btn.addEventListener('click', () => {
                const targetTab = btn.getAttribute('data-tab-btn');
                if (!targetTab) return;

                // Toggle button state
                detailTabs.forEach((b) => b.classList.remove('is-active'));
                btn.classList.add('is-active');

                // Toggle content state
                tabContents.forEach((c) => c.classList.remove('is-active'));
                const active = document.querySelector(`#tab-${targetTab}`);
                if (active) active.classList.add('is-active');
            });
        });
    }

    const checkoutModal = document.getElementById('checkoutModal');
    const purchaseForm = document.getElementById('purchaseAssetForm');
    const confirmPurchaseBtn = document.getElementById('confirmPurchaseBtn');
    const closeCheckout = document.getElementById('closeCheckout');

    const modalReviewAmount = document.getElementById('modalReviewAmount');
    const modalReviewShares = document.getElementById('modalReviewShares');
    const modalReviewProfit = document.getElementById('modalReviewProfit');
    const modalReviewTotal = document.getElementById('modalReviewTotal');
    const reviewAvailable = document.getElementById('reviewAvailable');

    const checkoutStepReview = document.getElementById('checkoutStepReview');
    const checkoutStepLoading = document.getElementById('checkoutStepLoading');
    const checkoutStepSuccess = document.getElementById('checkoutStepSuccess');

    // Price/ROI/Duration inputs (from PHP)
    const calcAmountInput = document.getElementById('calc-amount');
    const amountInput = document.getElementById('amount');

    const chartContainer = document.querySelector('[data-interactive-chart]');
    const planPrice = chartContainer ? Number(chartContainer.getAttribute('data-price') || 0) : 0;

    const roiPercent = Number(document.querySelector('[data-calc-roi]')?.value || 0);
    const durationDays = document.querySelector('[data-review-duration]')?.value || null;

    function setCheckoutStep(step) {
        if (checkoutStepReview) checkoutStepReview.classList.remove('is-active');
        if (checkoutStepLoading) checkoutStepLoading.classList.remove('is-active');
        if (checkoutStepSuccess) checkoutStepSuccess.classList.remove('is-active');

        if (step === 'review' && checkoutStepReview) checkoutStepReview.classList.add('is-active');
        if (step === 'loading' && checkoutStepLoading) checkoutStepLoading.classList.add('is-active');
        if (step === 'success' && checkoutStepSuccess) checkoutStepSuccess.classList.add('is-active');
    }

    function showModal() {
        if (!checkoutModal) return;
        checkoutModal.classList.add('is-active');
        document.body.style.overflow = 'hidden';
        setCheckoutStep('review');
        updateModalReviewValues();
    }

    function hideModal() {
        if (!checkoutModal) return;
        checkoutModal.classList.remove('is-active');
        document.body.style.overflow = '';
        setCheckoutStep('review');
    }

    function fmtMoney(amount) {
        const n = Number(amount);
        if (!Number.isFinite(n)) return '$0.00';
        return '$' + n.toFixed(2);
    }

    function computeOrderModel(orderAmount) {
        // Align with PHP:
        // expected = amount * (roi / 100)
        // netProfit = expected - amount
        const expectedTotal = orderAmount * (roiPercent / 100);
        const netProfit = expectedTotal - orderAmount;
        const shares = planPrice > 0 ? orderAmount / planPrice : 0;

        return { expectedTotal, netProfit, shares };
    }

    function updateModalReviewValues() {
        if (!amountInput) return;

        const orderAmount = Number(amountInput.value || 0);
        const { expectedTotal, netProfit, shares } = computeOrderModel(orderAmount);

        if (modalReviewAmount) modalReviewAmount.textContent = fmtMoney(orderAmount);
        if (modalReviewShares) modalReviewShares.textContent = shares.toFixed(8);
        if (modalReviewProfit) modalReviewProfit.textContent = fmtMoney(netProfit);
        if (modalReviewTotal) modalReviewTotal.textContent = fmtMoney(expectedTotal);
    }

    // Intercept submit to show step-by-step modal (review -> loading -> success -> real submit)
    if (purchaseForm && checkoutModal) {
        purchaseForm.addEventListener('submit', (event) => {
            event.preventDefault();

            if (!amountInput) return;

            const orderAmount = Number(amountInput.value || 0);
            const amountField = purchaseForm.querySelector('input[name="amount"]');

            const min = Number(amountField?.getAttribute('min') || 0);
            const max = Number(amountField?.getAttribute('max') || 0);

            if (orderAmount <= 0 || (min && orderAmount < min) || (max && orderAmount > max)) {
                window.alert('Enter a valid investment amount.');
                return;
            }

            updateModalReviewValues();
            showModal();
        });
    }

    // Modal interactions
    if (closeCheckout && checkoutModal) closeCheckout.addEventListener('click', hideModal);

    if (checkoutModal) {
        checkoutModal.addEventListener('click', (e) => {
            if (e.target === checkoutModal) hideModal();
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && checkoutModal && checkoutModal.classList.contains('is-active')) {
            hideModal();
        }
    });

    // Live update values while modal is open
    if (amountInput) {
        amountInput.addEventListener('input', () => {
            if (
                checkoutModal &&
                checkoutModal.classList.contains('is-active') &&
                checkoutStepReview &&
                checkoutStepReview.classList.contains('is-active')
            ) {
                updateModalReviewValues();
            }
        });
    }

    // Confirm -> loading -> success -> submit for real server-side purchase
    if (confirmPurchaseBtn && checkoutStepLoading && purchaseForm) {
        confirmPurchaseBtn.addEventListener('click', () => {
            setCheckoutStep('loading');

            const statuses = [
                'Validating available balances...',
                'Routing order to broker desk...',
                'Securing contract on ledger...',
                'Finalizing settlement...'
            ];

            const statusEl = document.getElementById('loadingStatusText');
            let idx = 0;
            if (statusEl) statusEl.textContent = statuses[idx];

            const timers = [
                window.setTimeout(() => { idx = 1; if (statusEl) statusEl.textContent = statuses[idx]; }, 500),
                window.setTimeout(() => { idx = 2; if (statusEl) statusEl.textContent = statuses[idx]; }, 1000),
                window.setTimeout(() => { idx = 3; if (statusEl) statusEl.textContent = statuses[idx]; }, 1500),
            ];

            window.setTimeout(() => {
                setCheckoutStep('success');

                const successOrderId = document.getElementById('successOrderId');
                if (successOrderId) successOrderId.textContent = 'ORD-' + Math.floor(1000 + Math.random() * 9000);

                // Submit for real server-side purchase (redirect will occur)
                const cloned = purchaseForm.cloneNode(true);
                purchaseForm.replaceWith(cloned);
                cloned.submit();
            }, 2100);

            timers.forEach((t) => window.clearTimeout(t));
        });
    }

    // Keep calculator in sync if modal is opened from another path
    // (calcAmountInput is intentionally separate to avoid identifier collisions with earlier calculator elements)
    updateModalReviewValues();
    // PASSWORD VISIBILITY TOGGLE (reusable + accessible)
    // Uses: [data-password-toggle] button + [data-password-target="passwordId"]
    document.querySelectorAll('[data-password-toggle]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();

            const targetId = btn.getAttribute('data-password-target') || '';
            const input = targetId ? document.getElementById(targetId) : null;
            if (!input) return;

            const wasFocused = document.activeElement === input;

            // Capture cursor/value before type swap
            const cursorPos = input.selectionStart ?? 0;
            const value = input.value;

            const isHidden = input.type === 'password'; // true => password currently hidden
            input.type = isHidden ? 'text' : 'password';

            // Restore value & cursor (avoid caret jump)
            input.value = value;
            try {
                input.setSelectionRange(cursorPos, cursorPos);
            } catch (err) {
                // ignore if not supported
            }

            if (wasFocused) input.focus();

            // aria-label + aria-pressed reflect the resulting (post-click) state
            btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            btn.setAttribute('aria-pressed', String(!isHidden));

            // Swap the SVG eye icon via CSS class (no emoji fallback needed)
            btn.classList.toggle('is-active', !isHidden);
        });
    });

    // Crypto deposit wizard (pages/user/deposit.php)
    // Multi-step flow: 1) choose asset, 2) show address + confirm,
    // 3) pending review. Submits to api/deposit.php via fetch.
    (function depositFlow() {
        const flow = document.querySelector('[data-deposit-flow]');
        if (!flow) return;

    const DEPOSIT_ENDPOINT = 'api/deposit.php';
    const QR_SERVICE = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=';
    const FINAL_STEP = 3;
    const STATUS = { ERROR: 'error', SUCCESS: 'success', LOADING: 'loading' };

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        // Element references, resolved once.
        const steps = [...flow.querySelectorAll('.deposit-step')];
        const indicators = [...flow.querySelectorAll('[data-step-indicator]')];
        const options = [...flow.querySelectorAll('[data-crypto]')];
        const els = {
            name: flow.querySelector('[data-selected-name]'),
            symbol: flow.querySelector('[data-selected-symbol]'),
            network: flow.querySelector('[data-selected-network]'),
            icon: flow.querySelector('[data-selected-icon]'),
            address: flow.querySelector('[data-wallet-address]'),
            qr: flow.querySelector('[data-qr]'),
            qrFallback: flow.querySelector('[data-qr-fallback]'),
            confirmBtn: flow.querySelector('[data-confirm-deposit]'),
            status: flow.querySelector('[data-deposit-status]'),
            receipt: flow.querySelector('[data-receipt-id]'),
            amount: flow.querySelector('[data-deposit-amount]'),
            tx: flow.querySelector('[data-deposit-tx]'),
            history: flow.querySelector('[data-deposit-history]'),
        };
        const copyBtn = flow.querySelector('[data-copy-address]');

        // Current selection, populated when an asset card is chosen.
        const selected = { symbol: '', name: '', network: '', address: '' };
        let submitting = false;

        // --- Navigation -------------------------------------------------
        function showStep(step) {
            steps.forEach((section) => { section.hidden = section.dataset.step !== String(step); });
            indicators.forEach((indicator) => {
                indicator.classList.toggle('is-active', Number(indicator.dataset.stepIndicator) <= step);
            });
        }

        function focusStepHeading(step) {
            const heading = flow.querySelector(`[data-step="${step}"] h2, [data-step="${step}"] h3`);
            if (!heading) return;
            heading.setAttribute('tabindex', '-1');
            heading.focus();
        }

        // --- Asset selection -------------------------------------------
        function selectCrypto(btn) {
            selected.symbol = btn.dataset.crypto;
            selected.name = btn.dataset.name;
            selected.network = btn.dataset.network;
            selected.address = btn.dataset.address;

            options.forEach((option) => {
                const isActive = option === btn;
                option.setAttribute('aria-pressed', String(isActive));
                option.classList.toggle('is-selected', isActive);
            });

            if (els.name) els.name.textContent = selected.name;
            if (els.symbol) els.symbol.textContent = selected.symbol;
            if (els.network) els.network.textContent = selected.network;
            if (els.icon) els.icon.textContent = btn.querySelector('.crypto-option__icon')?.textContent || '';
            if (els.address) els.address.value = selected.address;

            renderQr(selected.address);
            showStep(2);
        }

        // --- QR code ----------------------------------------------------
        function renderQr(address) {
            if (!els.qr) return;
            els.qrFallback.hidden = true;
            els.qr.hidden = false;
            els.qr.onerror = () => {
                els.qr.hidden = true;
                els.qrFallback.hidden = false;
            };
            els.qr.src = QR_SERVICE + encodeURIComponent(address);
            els.qr.alt = `QR code for ${selected.symbol} deposit address ${address}`;
        }

        // --- Status messaging ------------------------------------------
        function setStatus(message, type) {
            if (!els.status) return;
            els.status.textContent = message;
            els.status.className = 'deposit-status' + (type ? ` deposit-status--${type}` : '');
        }

        // --- Submission -------------------------------------------------
        async function submitDeposit() {
            if (submitting) return;
            if (!selected.symbol) {
                setStatus('Please select a cryptocurrency first.', STATUS.ERROR);
                return;
            }

            submitting = true;
            setButtonBusy(true);
            setStatus('Submitting your deposit…', STATUS.LOADING);

            try {
                const payload = new URLSearchParams({
                    action: 'confirm_deposit',
                    csrf_token: csrfToken,
                    crypto: selected.symbol,
                });
                if (els.amount?.value) payload.set('amount', els.amount.value);
                if (els.tx?.value) payload.set('tx_hash', els.tx.value);

                const response = await fetch(DEPOSIT_ENDPOINT, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: payload.toString(),
                });
                const result = await response.json();

                if (!response.ok || !result.ok) {
                    throw new Error(result.error || 'Could not submit the deposit.');
                }

                if (els.receipt) els.receipt.textContent = result.deposit?.id ?? '';
                // An already-open deposit is already listed in history; avoid
                // prepending a duplicate card.
                if (!result.duplicate && result.deposit) prependHistory(result.deposit);

                showStep(FINAL_STEP);
                focusStepHeading(FINAL_STEP);
                setStatus(
                    result.duplicate
                        ? (result.message || 'You already have an open deposit for this asset.')
                        : (result.message || 'Deposit submitted for review.'),
                    result.duplicate ? STATUS.ERROR : STATUS.SUCCESS
                );
            } catch (error) {
                console.error('Deposit submission failed:', error);
                setStatus(error.message || 'Something went wrong. Please try again.', STATUS.ERROR);
            } finally {
                submitting = false;
                setButtonBusy(false);
            }
        }

        function setButtonBusy(busy) {
            if (!els.confirmBtn) return;
            els.confirmBtn.disabled = busy;
            els.confirmBtn.setAttribute('aria-busy', String(busy));
            const label = els.confirmBtn.querySelector('.btn-label');
            const spinner = els.confirmBtn.querySelector('.btn-spinner');
            if (label) label.hidden = !busy;
            if (spinner) spinner.hidden = busy;
        }

        // --- History ----------------------------------------------------
        function prependHistory(deposit) {
            if (!els.history) return;
            els.history.querySelector('[data-empty]')?.remove();

            const card = document.createElement('article');
            card.className = 'card deposit-history-card';
            const statusClass = (deposit.status || '').toLowerCase().replace(/\s+/g, '-');
            card.innerHTML = `
                <div class="deposit-history-card__head">
                    <span class="crypto-option__icon" aria-hidden="true">${escapeHtml(deposit.crypto || '◆')}</span>
                    <div><strong>${escapeHtml(deposit.crypto || 'Deposit')}</strong>
                        <span class="muted" style="font-size:.8rem; display:block;">${escapeHtml(deposit.network ?? '')}</span></div>
                    <span class="status status--${escapeHtml(statusClass)}">${escapeHtml(deposit.status ?? '')}</span>
                </div>
                <div class="plan-meta" style="margin-top:var(--space-3);">
                    <span>ID <strong>${escapeHtml(deposit.id)}</strong></span>
                    <span>Amount <strong>${deposit.amount > 0 ? '$' + Number(deposit.amount).toFixed(2) : '—'}</strong></span>
                    <span>Submitted <strong>${escapeHtml(deposit.created_at ?? '')}</strong></span>
                </div>`;
            els.history.prepend(card);
        }

        // --- Helpers ----------------------------------------------------
        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        async function copyAddress() {
            if (!copyBtn) return;
            try {
                await navigator.clipboard.writeText(els.address.value);
                copyBtn.textContent = 'Copied';
            } catch {
                copyBtn.textContent = 'Select';
            }
            window.setTimeout(() => { copyBtn.textContent = 'Copy'; }, 1600);
        }

        // --- Wiring ----------------------------------------------------
        options.forEach((btn) => btn.addEventListener('click', () => selectCrypto(btn)));
        copyBtn?.addEventListener('click', copyAddress);
        flow.querySelector('[data-step-back]')?.addEventListener('click', () => showStep(1));
        els.confirmBtn?.addEventListener('click', submitDeposit);
    })();
}());

