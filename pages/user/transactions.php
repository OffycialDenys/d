<?php $pageTitle = 'Transactions'; require __DIR__ . '/../../includes/layouts/user-header.php'; ?>
<section class="table-wrap responsive-table">
    <table><thead><tr><th>ID</th><th>Type</th><th>Category</th><th>Amount</th><th>Old</th><th>New</th><th>Status</th><th>Date</th></tr></thead><tbody>
    <?php foreach (array_reverse(customer_transactions(current_customer_id())) as $row): ?>
        <tr data-search-row><td data-label="ID"><?= e($row['id']) ?></td><td data-label="Type"><?= e($row['type']) ?></td><td data-label="Category"><?= e($row['category']) ?></td><td data-label="Amount"><?= e(money($row['amount'])) ?></td><td data-label="Old"><?= e(money($row['old'])) ?></td><td data-label="New"><?= e(money($row['new'])) ?></td><td data-label="Status"><span class="<?= status_class($row['status']) ?>"><?= e($row['status']) ?></span></td><td data-label="Date"><?= e($row['date']) ?></td></tr>
    <?php endforeach; ?>
    </tbody></table>
</section>
<?php require __DIR__ . '/../../includes/layouts/user-footer.php'; ?>
