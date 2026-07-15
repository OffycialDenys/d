<?php
$pageTitle = 'Reports';
$pm = platform_metrics();

if (($route ?? '') === 'reports' && ($_GET['export'] ?? '') === 'csv') {
    $transactions = all_transactions();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="transactions-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Reference', 'Type', 'Category', 'Amount', 'Old Balance', 'New Balance', 'Status', 'Date', 'Description']);
    foreach ($transactions as $row) {
        fputcsv($out, [
            $row['id'] ?? '',
            $row['type'] ?? '',
            $row['category'] ?? '',
            number_format((float) ($row['amount'] ?? 0), 2, '.', ''),
            number_format((float) ($row['old'] ?? 0), 2, '.', ''),
            number_format((float) ($row['new'] ?? 0), 2, '.', ''),
            $row['status'] ?? '',
            $row['date'] ?? '',
            $row['description'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

require __DIR__ . '/../../includes/layouts/admin-header.php';
?>
<section class="grid grid--4">
    <article class="card stat-card"><span>Revenue (Deposits)</span><strong><?= e(money($pm['total_deposits'] ?? 0)) ?></strong></article>
    <article class="card stat-card"><span>Withdrawals</span><strong><?= e(money($pm['total_withdrawals'] ?? 0)) ?></strong></article>
    <article class="card stat-card"><span>Orders</span><strong><?= e((string) count(all_orders())) ?></strong></article>
    <article class="card stat-card"><span>Pending Tickets</span><strong><?= e((string) count(array_filter(all_tickets(), fn($t) => ($t['status'] ?? '') === 'Open'))) ?></strong></article>
</section>
<section class="card hero-chart">
    <div class="section-title"><div><h2>Transaction Ledger</h2><p class="muted">Full export of platform transactions for reconciliation and reporting.</p></div></div>
    <a class="button button--ghost" href="index.php?route=reports&export=csv">Export Transactions (CSV)</a>
</section>
<?php require __DIR__ . '/../../includes/layouts/admin-footer.php'; ?>
