<?php $pageTitle = 'Login'; require __DIR__ . '/../../includes/layouts/public-header.php'; ?>
<section class="auth-page">
    <article class="auth-card card">
        <p class="eyebrow"><?= e($config['name']) ?></p>
        <h1>Welcome back to your investment workspace</h1>
        <p class="muted">Access your wallet, active investments, rewards, transactions, and support from one secure dashboard.</p>
<form method="post" class="grid narrow-form narrow-form--auth">
            <div class="field">
                <label for="identity">Email or Username</label>
                <input id="identity" name="identity" required autocomplete="username">
            </div>

            <div class="field">
                <label for="password">Password</label>

                <div class="field__control">
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        aria-describedby="passwordHelp"
                    >
                    <button
                        type="button"
                        class="password-toggle"
                        data-password-toggle
                        data-password-target="password"
                        aria-label="Show password"
                        aria-pressed="false"
                    >
                        <svg class="password-toggle__eye" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg class="password-toggle__eye-off" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
                            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                            <line x1="2" x2="22" y1="2" y2="22"/>
                        </svg>
                    </button>
                </div>
                <p id="passwordHelp" class="muted" style="margin:0; font-size:.8rem;">Use at least 8 characters. Toggle visibility if needed.</p>
            </div>

            <div class="auth-row">
                <label class="check-row"><input type="checkbox" name="remember"> Remember this device</label>
                <a href="index.php?route=login">Forgot password?</a>
            </div>

            <button class="button button--primary" type="submit">Sign In</button>
        </form>
        <p class="muted">New to <?= e($config['name']) ?>? <a href="index.php?route=register">Create an account</a></p>
    </article>
    <aside class="auth-visual card">
        <div class="mini-chart mini-chart--hero" data-chart data-points="18,22,30,37,49,57,69,78,86,94"></div>
        <div class="auth-visual__stats">
            <span>Portfolio tracking</span>
            <strong>Live growth simulation</strong>
            <p>Monitor progress, expected return, and daily performance in a focused customer dashboard.</p>
        </div>
    </aside>
</section>
<?php require __DIR__ . '/../../includes/layouts/public-footer.php'; ?>
