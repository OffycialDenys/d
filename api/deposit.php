<?php
require __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
    exit;
}

// JSON-aware authentication: do not redirect, return a structured error.
if (empty($_SESSION['auth_user'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Your session has expired. Please sign in again.']);
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Security validation failed. Please refresh and try again.']);
    exit;
}

$action = $_POST['action'] ?? '';
if ($action !== 'confirm_deposit') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unknown action.']);
    exit;
}

$result = confirm_crypto_deposit($_POST);
$code = !$result['ok'] ? (int) ($result['code'] ?? 400) : 200;
http_response_code($code);
echo json_encode($result);
