<?php
require __DIR__ . '/includes/bootstrap.php';

$publicRoutes = ['home', 'login', 'register', 'logout'];
$userRoutes = [
    'dashboard', 'wallet', 'investments', 'investment-details', 'owned-investment-details', 'orders', 'deposit',
    'withdraw', 'transactions', 'referral', 'rewards', 'support', 'notifications',
    'downloads', 'profile', 'settings', 'bank',
];

$route = $_GET['route'] ?? 'home';

if ($route === 'logout') {
    logout_user();
    flash('success', 'You have been signed out.');
    redirect('index.php?route=home');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    handle_user_action($route, $_POST, $_FILES);
}

if (in_array($route, $publicRoutes, true)) {
    require __DIR__ . '/pages/public/' . $route . '.php';
    exit;
}

if (!in_array($route, $userRoutes, true)) {
    http_response_code(404);
    $pageTitle = 'Page not found';
    require __DIR__ . '/includes/layouts/user-header.php';
    echo '<section class="empty-state"><span class="empty-state__icon">404</span><h1>Page not found</h1><p>The page you requested is not available.</p><a class="button button--primary" href="index.php?route=dashboard">Return to dashboard</a></section>';
    require __DIR__ . '/includes/layouts/user-footer.php';
    exit;
}

require_login();
require __DIR__ . '/pages/user/' . $route . '.php';
