    </main>
</div>
<nav class="mobile-tabbar">
    <a class="<?= active_route('dashboard') ?>" href="index.php?route=dashboard"><span>DA</span>Home</a>
    <a class="<?= active_route('investments') ?>" href="index.php?route=investments"><span>IN</span>Product</a>
    <a class="<?= active_route('referral') ?>" href="index.php?route=referral"><span>TM</span>Team</a>
    <a class="<?= active_route('profile') ?>" href="index.php?route=profile"><span>PR</span>Mine</a>
</nav>
<script src="<?= e(asset_url('assets/js/app.js')) ?>"></script>
</body>
</html>
