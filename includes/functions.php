<?php
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function money(float $amount): string
{
    global $config;

    // Guard against NaN/INF coming from bad inputs
    if (!is_finite($amount)) {
        return '-';
    }

    $amountFormatted = number_format($amount, 2);

    // Standardize display across app:
    // - If currency is USDT: "$150.00 USDT"
    // - Otherwise: "$150.00" (or replace "$" with whatever your UI currency convention is later)
    $currency = (string) ($config['currency'] ?? '');
    if (strtoupper($currency) === 'USDT') {
        return '$' . $amountFormatted . ' USDT';
    }

    return '$' . $amountFormatted;
}

function active_route(string $route): string
{
    return ($_GET['route'] ?? 'home') === $route ? 'is-active' : '';
}

/**
 * Append a content-based cache-busting version to a static asset URL.
 *
 * Uses the file's last-modified time (filemtime) so the query string changes
 * automatically whenever the asset content changes. This guarantees browsers
 * and auditing tools always fetch the current CSS/JS instead of a stale copy.
 * The relative prefix used by the caller (e.g. "../assets/..") is preserved in
 * the emitted href while the filesystem lookup is resolved against the project
 * root, so it works for user, public, and admin layouts alike.
 */
function asset_url(string $href): string
{
    $relative = ltrim((string) preg_replace('#^(?:\.\./)+#', '', $href), '/');
    $path = dirname(__DIR__) . '/' . $relative;

    if (is_file($path)) {
        return $href . '?v=' . filemtime($path);
    }

    return $href;
}

function redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function consume_flash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function status_class(string $status): string
{
    return 'status status--' . strtolower(str_replace(' ', '-', $status));
}

function current_user(): array
{
    // Defensive fallback: a missing/cleared session user must not produce
    // "array offset on value of null" when views read $user['username'], etc.
    return $_SESSION['platform']['user'] ?? [];
}

/**
 * Read a platform metric for the signed-in customer. Metrics are stored
 * per-customer (see recalculate_customer_metrics) so each account sees only
 * its own figures.
 */
function app_metric(string $key): float
{
    $id = current_customer_id();
    return (float) ($_SESSION['platform']['customers'][$id]['metrics'][$key] ?? 0);
}

/**
 * Generate (and persist) a CSRF token bound to the session. Reused across the
 * deposit workflow and admin wallet management so state-changing requests can
 * be validated server-side.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf($token): bool
{
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}
