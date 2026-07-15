<?php $pageTitle = 'Activity Logs'; require __DIR__ . '/../../includes/layouts/admin-header.php'; ?>
<section class="card">
    <div class="section-title"><div><h2>Audit Trail</h2><p class="muted">Every privileged action: who performed it, when, on what, and the value change.</p></div></div>
    <?php $audit = array_reverse($_SESSION['platform']['admin_logs'] ?? []); ?>
    <?php if (empty($audit)): ?>
        <section class="empty-state"><span class="empty-state__icon">∅</span><h2>No audit entries yet</h2><p>Administrative actions will be recorded here as they occur.</p></section>
    <?php else: ?>
        <section class="table-wrap responsive-table">
            <table>
                <thead><tr><th>Actor</th><th>Action</th><th>Entity</th><th>Previous</th><th>New</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($audit as $row): ?>
                    <tr data-search-row>
                        <td data-label="Actor"><?= e($row['actor']) ?></td>
                        <td data-label="Action"><?= e($row['action']) ?></td>
                        <td data-label="Entity"><span class="mono"><?= e($row['entity_type']) ?>: <?= e((string) $row['entity_id']) ?></span></td>
                        <td data-label="Previous"><?= $row['old_value'] === null ? '—' : e((string) $row['old_value']) ?></td>
                        <td data-label="New"><?= $row['new_value'] === null ? '—' : e((string) $row['new_value']) ?></td>
                        <td data-label="Date"><?= e($row['date']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
</section>

<section class="card">
    <div class="section-title"><div><h2>Activity Feed</h2><p class="muted">Chronological record of platform and operator activity.</p></div></div>
    <section class="timeline">
        <?php foreach (array_reverse(all_activities()) as $row): ?>
            <article class="card timeline__item" data-search-row><strong><?= e($row['actor']) ?></strong><p class="muted"><?= e($row['message']) ?> / <?= e($row['date']) ?></p></article>
        <?php endforeach; ?>
    </section>
</section>
<?php require __DIR__ . '/../../includes/layouts/admin-footer.php'; ?>
