<?php
require __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$assetId = (int) ($_GET['asset'] ?? 0);
$range = preg_replace('/[^A-Za-z0-9]/', '', (string) ($_GET['range'] ?? '1M'));

$send = static function (int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
};

if ($assetId <= 0) {
    $send(400, ['status' => 'error', 'error' => 'Invalid asset identifier.']);
}

$validRanges = ['1D', '1W', '1M', '3M', '1Y', '5Y'];
if (!in_array($range, $validRanges, true)) {
    $range = '1M';
}

$plan = null;
foreach (($_SESSION['platform']['plans'] ?? []) as $row) {
    if ((int) ($row['id'] ?? 0) === $assetId) {
        $plan = $row;
        break;
    }
}

if ($plan === null) {
    $send(404, ['status' => 'error', 'error' => 'Asset not found.', 'asset' => $assetId]);
}

$points = build_asset_chart_series($plan, $range);

if (empty($points)) {
    $send(200, [
        'status' => 'empty',
        'asset' => (int) $plan['id'],
        'range' => $range,
        'points' => [],
    ]);
}

echo json_encode([
    'status' => 'ok',
    'asset' => (int) $plan['id'],
    'range' => $range,
    'points' => $points,
]);
