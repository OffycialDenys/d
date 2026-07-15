<?php
/**
 * One-time database installer for Nivaro Capital.
 *
 * Visits:
 *   /install.php            -> shows the intro / run button
 *   POST /install.php       -> imports database/schema.sql, seeds admin + roles
 *
 * It is idempotent (CREATE TABLE IF NOT EXISTS + INSERT IGNORE) so you can
 * re-run it safely. Delete or password-protect this file after installation.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$config = require __DIR__ . '/includes/config/app.php';
require __DIR__ . '/includes/functions.php';

$errors = [];
$success = [];
$ran = false;

function install_pdo(array $config): PDO
{
    $db = $config['database'];
    $dsn = 'mysql:host=' . $db['host'] . ';port=' . $db['port'] . ';charset=' . $db['charset'];
    return new PDO($dsn, $db['user'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'install') {
    $ran = true;
    try {
        $pdo = install_pdo($config);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $config['database']['name']
            . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . $config['database']['name'] . '`');

        $schema = (string) file_get_contents(__DIR__ . '/database/schema.sql');
        // Make seed INSERTs idempotent so re-running never throws duplicates.
        $schema = preg_replace('/^INSERT INTO /m', 'INSERT IGNORE INTO ', $schema);
        $statements = array_filter(array_map('trim', explode(';', $schema)), function ($s) {
            return $s !== '' && $s !== '0';
        });

        foreach ($statements as $sql) {
            $pdo->exec($sql);
        }

        // Seed the default administrator from config (never the demo password in prod).
        $admin = $config['demo_admin'];
        $exists = $pdo->prepare('SELECT id FROM admin_users WHERE LOWER(email) = ?');
        $exists->execute([strtolower($admin['email'])]);
        if (!$exists->fetch()) {
            $pdo->prepare(
                'INSERT INTO admin_users (role_id, name, email, password_hash, status) '
                . 'VALUES (?, \'Platform Admin\', ?, ?, \'active\')'
            )->execute([1, $admin['email'], password_hash($admin['password'], PASSWORD_DEFAULT)]);
        }

        $success[] = 'Database installed successfully. Enable DB mode, then delete this file.';
    } catch (Throwable $e) {
        $errors[] = 'Installation failed: ' . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nivaro Capital &middot; Installer</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f7f9; color: #172026; margin: 0; padding: 2rem; }
        .card { max-width: 640px; margin: 3rem auto; background: #fff; border: 1px solid #dbe4e8; border-radius: 14px; padding: 2rem; box-shadow: 0 18px 42px rgba(24,39,75,.12); }
        h1 { margin-top: 0; }
        .msg { padding: .75rem 1rem; border-radius: 8px; margin: 1rem 0; font-weight: 700; }
        .ok { background: #e4f8f0; color: #0a6c4a; }
        .err { background: #fde8e8; color: #c2413b; }
        code { background: #eef3f6; padding: .15rem .4rem; border-radius: 6px; }
        button { background: #09756b; color: #fff; border: 0; border-radius: 8px; padding: .75rem 1.25rem; font-weight: 800; cursor: pointer; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Nivaro Capital &middot; Database Installer</h1>
        <p>This imports <code>database/schema.sql</code> into
            <code><?= e($config['database']['name']) ?></code> and seeds the default admin
            (<code><?= e($config['demo_admin']['email']) ?></code>). It is safe to run more than once.</p>

        <?php foreach ($success as $s): ?>
            <div class="msg ok"><?= e($s) ?></div>
        <?php endforeach; ?>
        <?php foreach ($errors as $e): ?>
            <div class="msg err"><?= e($e) ?></div>
        <?php endforeach; ?>

        <?php if (!$ran || $errors): ?>
            <form method="post">
                <input type="hidden" name="action" value="install">
                <button type="submit">Run installation</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
