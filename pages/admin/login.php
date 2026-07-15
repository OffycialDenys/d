<?php $messages = consume_flash(); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | <?= e($config['name']) ?></title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<main class="page-section">
    <?php foreach ($messages as $message): ?>
        <div class="toast toast--<?= e($message['type']) ?>"><?= e($message['message']) ?></div>
    <?php endforeach; ?>
    <section class="grid grid--2">
        <article class="card">
            <p class="eyebrow">Administrator Portal</p>
            <h1>Sign in to the command center</h1>
            <form method="post" class="grid">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required autocomplete="username"></div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="field__control">
                        <input id="password" name="password" type="password" required autocomplete="current-password">
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
                </div>
                <button class="button button--secondary" type="submit">Login</button>
            </form>
        </article>
        <article class="card hero-chart">
            <h2>Operational Control</h2>
            <p class="muted">Manage users, wallets, deposits, withdrawals, investment plans, support, CMS, reports, and configuration.</p>
        </article>
    </section>
</main>
</body>
</html>
