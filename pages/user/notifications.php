<?php $pageTitle = 'Notifications'; require __DIR__ . '/../../includes/layouts/user-header.php'; ?>
<?php $notifications = array_reverse(customer_notifications(current_customer_id()), true); ?>
<?php $hasUnread = count(array_filter(customer_notifications(current_customer_id()), fn($row) => !$row['read'])) > 0; ?>
<section class="grid">
    <div class="section-title" style="display:flex; justify-content:space-between; align-items:center; grid-column:1 / -1;">
        <div>
            <h2>Notifications</h2>
            <p class="muted">Toggle each alert between read and unread, or clear them all at once.</p>
        </div>
        <?php if ($hasUnread): ?>
        <form method="post" action="index.php?route=notifications" style="margin:0;">
            <input type="hidden" name="action" value="mark_all_read">
            <button class="button button--ghost" type="submit">Mark all as read</button>
        </form>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <article class="card" style="grid-column:1 / -1; text-align:center;">
            <p class="muted" style="margin:0;">You have no notifications yet.</p>
        </article>
    <?php else: ?>
        <?php foreach ($notifications as $index => $row): ?>
            <article class="card" data-search-row>
                <div class="section-title" style="margin-bottom:var(--space-2);">
                    <div>
                        <h3><?= e($row['title']) ?></h3>
                        <p class="muted"><?= e($row['message']) ?></p>
                    </div>
                    <span class="<?= status_class($row['read'] ? 'Completed' : 'Pending') ?>"><?= $row['read'] ? 'Read' : 'Unread' ?></span>
                </div>
                <p class="muted" style="margin:0 0 var(--space-3) 0;"><?= e($row['type']) ?> / <?= e($row['date']) ?></p>
                <form method="post" action="index.php?route=notifications" style="margin:0;">
                    <input type="hidden" name="action" value="<?= $row['read'] ? 'mark_unread' : 'mark_read' ?>">
                    <input type="hidden" name="index" value="<?= e((string) $index) ?>">
                    <button class="button button--ghost" type="submit"><?= $row['read'] ? 'Mark as unread' : 'Mark as read' ?></button>
                </form>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../../includes/layouts/user-footer.php'; ?>
