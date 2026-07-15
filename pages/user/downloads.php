<?php $pageTitle = 'Downloads'; require __DIR__ . '/../../includes/layouts/user-header.php'; ?>
<section class="grid grid--3">
    <?php foreach (['Investment guide', 'Terms summary', 'Payment instructions'] as $doc): ?>
        <article class="card"><h3><?= e($doc) ?></h3><p class="muted">PDF-ready document placeholder managed by the CMS module.</p><button class="button button--ghost" type="button">Download</button></article>
    <?php endforeach; ?>
</section>
<?php require __DIR__ . '/../../includes/layouts/user-footer.php'; ?>
