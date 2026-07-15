<?php
$messages = consume_flash();
$adminNav = [
    'dashboard' => 'Dashboard',
    'users' => 'Users',
    'investments' => 'Investments',
    'orders' => 'Orders',
    'deposits' => 'Deposits',
    'withdrawals' => 'Withdrawals',
    'transactions' => 'Transactions',
    'referrals' => 'Referrals',
    'rewards' => 'Rewards',
    'support' => 'Support',
    'reports' => 'Reports',
    'settings' => 'Website Settings',
    'payment-settings' => 'Payment Settings',
    'logs' => 'Activity Logs',
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle ?? 'Admin') ?> | Admin</title>
    <link rel="stylesheet" href="<?= e(asset_url('../assets/css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('../assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('../assets/css/admin.css')) ?>">
</head>
<body class="app-body admin-body">
<aside class="sidebar" data-sidebar>
    <a class="brand sidebar__brand" href="index.php?route=dashboard"><span class="brand__mark">AD</span><span>Console</span></a>
    <nav class="sidebar__nav">
        <?php foreach ($adminNav as $routeName => $label): ?>
            <a class="<?= active_route($routeName) ?>" href="index.php?route=<?= e($routeName) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
        <a href="index.php?route=logout">Logout</a>
    </nav>
</aside>
<div class="app-shell">
    <header class="topbar">
        <button class="icon-button" type="button" data-sidebar-toggle aria-label="Toggle navigation">Menu</button>
        <div>
            <p class="eyebrow">Administration</p>
            <h1><?= e($pageTitle ?? 'Admin') ?></h1>
        </div>
        <form class="topbar__search" role="search" aria-label="Search records">
            <input type="search" placeholder="Search records" aria-label="Search records" data-table-search>
        </form>
        <span class="wallet-pill">Admin</span>
    </header>
    <?php foreach ($messages as $message): ?>
        <div class="toast toast--<?= e($message['type']) ?>"><?= e($message['message']) ?></div>
    <?php endforeach; ?>
    <main class="content">
