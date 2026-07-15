<?php $pageTitle = 'Support'; require __DIR__ . '/../../includes/layouts/user-header.php'; ?>
<section class="grid grid--2">
    <article class="card"><h2>Create Ticket</h2><form method="post" class="grid"><div class="field"><label for="subject">Subject</label><input id="subject" name="subject" required></div><div class="field"><label for="priority">Priority</label><select id="priority" name="priority"><option>Normal</option><option>High</option><option>Urgent</option></select></div><div class="field"><label for="message">Message</label><textarea id="message" name="message"></textarea></div><button class="button button--primary" type="submit">Open Ticket</button></form></article>
    <article class="card"><h2>Ticket History</h2><div class="timeline"><?php foreach (array_reverse(customer_tickets(current_customer_id())) as $row): ?><div class="timeline__item" data-search-row><strong><?= e($row['id']) ?> - <?= e($row['subject']) ?></strong><p class="muted"><?= e($row['priority']) ?> / <?= e($row['status']) ?> / <?= e($row['updated']) ?></p></div><?php endforeach; ?></div></article>
</section>
<?php require __DIR__ . '/../../includes/layouts/user-footer.php'; ?>
