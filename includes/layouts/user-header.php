<?php
$messages = consume_flash();
$user = current_user();
$unread = count(array_filter(customer_notifications(current_customer_id()), fn($row) => !$row['read']));
$nav = [
    'dashboard' => ['Dashboard', 'DA'],
    'wallet' => ['Wallet', 'WA'],
    'investments' => ['Investments', 'IN'],
    'orders' => ['Orders', 'OR'],
    'deposit' => ['Deposit', 'DE'],
    'withdraw' => ['Withdraw', 'WD'],
    'transactions' => ['Transactions', 'TX'],
    'referral' => ['Team', 'TM'],
    'rewards' => ['Rewards', 'RW'],
    'support' => ['Support', 'SP'],
    'downloads' => ['Downloads', 'DL'],
    'profile' => ['Profile', 'PR'],
    'settings' => ['Settings', 'ST'],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle ?? 'Dashboard') ?> | <?= e($config['name']) ?></title>
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
</head>
<body class="app-body">
<aside class="sidebar" data-sidebar>
    <a class="brand sidebar__brand" href="index.php?route=dashboard"><span class="brand__mark">NC</span><span>Nivaro</span></a>
    <nav class="sidebar__nav">
        <?php foreach ($nav as $routeName => [$label, $icon]): ?>
            <a class="<?= active_route($routeName) ?>" href="index.php?route=<?= e($routeName) ?>"><span class="nav-icon"><?= e($icon) ?></span><?= e($label) ?></a>
        <?php endforeach; ?>
        <a href="index.php?route=logout"><span class="nav-icon">LO</span>Logout</a>
    </nav>
</aside>
<div class="app-shell">
    <header class="topbar">
        <button class="icon-button" type="button" data-sidebar-toggle aria-label="Menu">Menu</button>
        <div>
            <p class="eyebrow">User Portal</p>
            <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
        </div>
        <form class="topbar__search" role="search">
            <input type="search" placeholder="Search platform" data-table-search>
        </form>
        <a class="wallet-pill" href="index.php?route=wallet"><?= e(money(customer_wallet(current_customer_id())['available'])) ?></a>
        <a class="notification-button" href="index.php?route=notifications">Alerts <span><?= $unread ?></span></a>
        <a class="avatar" href="index.php?route=profile" aria-label="Open profile"><?= strtoupper(substr($user['username'], 0, 1)) ?></a>
    </header>
    <?php foreach ($messages as $message): ?>
        <div class="toast toast--<?= e($message['type']) ?>"><?= e($message['message']) ?></div>
    <?php endforeach; ?>
    <main class="content">
