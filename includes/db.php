<?php
/**
 * Database access layer (PDO / MySQL).
 *
 * The connection is lazy: it is only created the first time db() is called.
 * When db_enabled is false (the default) or MySQL is unavailable, every
 * helper returns null/empty so the rest of the app keeps running on the
 * PHP-session demo store. Nothing here changes behaviour until you opt in.
 */

function db_enabled(): bool
{
    global $config;
    return !empty($config['db_enabled']);
}

function db(): ?PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!db_enabled()) {
        return null;
    }

    global $config;
    $db = $config['database'];
    $dsn = 'mysql:host=' . $db['host']
        . ';port=' . $db['port']
        . ';dbname=' . $db['name']
        . ';charset=' . $db['charset'];

    try {
        $pdo = new PDO($dsn, $db['user'], $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        http_response_code(503);
        echo 'Database unavailable. Please check your database configuration.';
        exit;
    }

    return $pdo;
}

function db_fetch(string $sql, array $params = []): ?array
{
    $pdo = db();
    if (!$pdo) {
        return null;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

function db_fetch_all(string $sql, array $params = []): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function db_exec(string $sql, array $params = []): int
{
    $pdo = db();
    if (!$pdo) {
        return 0;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

function db_last_insert_id(): ?int
{
    $pdo = db();
    return $pdo ? (int) $pdo->lastInsertId() : null;
}
