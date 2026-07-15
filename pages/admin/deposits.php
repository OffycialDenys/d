<?php $pageTitle = 'Deposit Management'; require __DIR__ . '/../../includes/layouts/admin-header.php'; ?>
<section class="table-wrap responsive-table">
    <table>
        <thead>
            <tr>
                <th>ID</th><th>User</th><th>Asset</th><th>Network</th><th>Wallet</th>
                <th>Amount</th><th>Status</th><th>Reviewer</th><th>Notes</th><th>Submitted</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach (array_reverse(all_deposits()) as $row): ?>
            <tr data-search-row>
                <td data-label="ID"><?= e($row['id']) ?></td>
                <td data-label="User"><?= e($row['username'] ?? 'customer') ?></td>
                <td data-label="Asset"><?= e($row['crypto'] ?? $row['method'] ?? '-') ?></td>
                <td data-label="Network"><?= e($row['network'] ?? '-') ?></td>
                <td data-label="Wallet"><span class="mono"><?= e($row['wallet_address'] ?? '-') ?></span></td>
                <td data-label="Amount"><?= e(($row['amount'] > 0) ? money((float) $row['amount']) : '—') ?></td>
                <td data-label="Status"><span class="<?= status_class($row['status']) ?>"><?= e($row['status']) ?></span></td>
                <td data-label="Reviewer"><?= e($row['reviewer'] ?? '—') ?></td>
                <td data-label="Notes"><?= e($row['notes'] ?? '—') ?></td>
                <td data-label="Submitted"><?= e($row['created_at'] ?? $row['date'] ?? '-') ?></td>
                <td data-label="Action">
                    <?php if (in_array($row['status'], ['Pending', 'Awaiting Payment', 'Pending Review'], true)): ?>
                        <div class="admin-actions admin-actions--stack">
                            <form method="post" class="inline-form" data-confirm="Approve this deposit and credit the wallet?">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="user_id" value="<?= e((string) ($row['user_id'] ?? 0)) ?>">
                                <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                                <input type="hidden" name="notes" value="">
                                <button class="button button--primary" name="action" value="approve_deposit">Approve</button>
                            </form>
                            <form method="post" class="inline-form" data-confirm="Reject this deposit?">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="user_id" value="<?= e((string) ($row['user_id'] ?? 0)) ?>">
                                <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                                <input class="input-sm" name="notes" placeholder="Rejection note" aria-label="Rejection note">
                                <button class="button button--danger" name="action" value="reject_deposit">Reject</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <span class="muted">Reviewed</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php require __DIR__ . '/../../includes/layouts/admin-footer.php'; ?>
