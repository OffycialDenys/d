<?php $messages = consume_flash(); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? $config['name']) ?> | <?= e($config['name']) ?></title>
    <meta name="description" content="<?= e($config['tagline']) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
</head>
<body>
<header class="site-header">
    <a class="brand" href="index.php?route=home"><span class="brand__mark">NC</span><span><?= e($config['name']) ?></span></a>
    <button class="nav-toggle" type="button" data-menu-toggle aria-label="Menu" aria-controls="primary-nav" aria-expanded="false"><span></span><span></span><span></span></button>
    <nav class="site-nav" id="primary-nav" data-menu>
        <a href="index.php?route=home#plans">Plans</a>
        <a href="index.php?route=home#features">Features</a>
        <a href="index.php?route=home#faq">FAQ</a>
        <a href="index.php?route=login">Login</a>
        <a class="button button--primary" href="index.php?route=register">Create Account</a>
    </nav>
</header>
<?php foreach ($messages as $message): ?>
    <div class="toast toast--<?= e($message['type']) ?>"><?= e($message['message']) ?></div>
<?php endforeach; ?>
<main>
