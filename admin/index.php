<?php
require __DIR__ . '/../includes/bootstrap.php';

$route = $_GET['route'] ?? 'dashboard';
$routes = ['dashboard', 'users', 'investments', 'orders', 'deposits', 'withdrawals', 'transactions', 'referrals', 'rewards', 'support', 'reports', 'settings', 'payment-settings', 'logs'];

if ($route === 'login') {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('error', 'Security check failed. Please retry.');
            redirect('index.php?route=login');
        }
        if (login_admin(trim($_POST['email'] ?? ''), $_POST['password'] ?? '')) {
            flash('success', 'Administrator session started.');
            redirect('index.php?route=dashboard');
        }
        flash('error', 'Invalid administrator credentials.');
        redirect('index.php?route=login');
    }
    require __DIR__ . '/../pages/admin/login.php';
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'Security check failed. Please retry.');
        redirect('index.php?route=' . $route);
    }
    handle_admin_action($route, $_POST);
}

if ($route === 'logout') {
    logout_admin();
    flash('success', 'Administrator session ended.');
    redirect('index.php?route=login');
}

require_admin();

if (!in_array($route, $routes, true)) {
    http_response_code(404);
    $pageTitle = 'Admin page not found';
    require __DIR__ . '/../includes/layouts/admin-header.php';
    echo '<section class="empty-state"><span class="empty-state__icon">404</span><h1>Admin page not found</h1><p>The requested management screen does not exist.</p><a class="button button--primary" href="index.php?route=dashboard">Return to admin dashboard</a></section>';
    require __DIR__ . '/../includes/layouts/admin-footer.php';
    exit;
}

require __DIR__ . '/../pages/admin/' . $route . '.php';
