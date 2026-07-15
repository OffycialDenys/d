<?php $pageTitle = 'Website Settings'; require __DIR__ . '/../../includes/layouts/admin-header.php'; $settings = $_SESSION['platform']['settings']; ?>
<section class="card">
    <div class="section-title"><div><h2>General Configuration</h2><p class="muted">Platform-wide operational limits and access controls.</p></div></div>
    <form method="post" class="grid">
        <input type="hidden" name="action" value="save_site_settings">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-grid">
            <div class="field"><label for="site_name">Website Name</label><input id="site_name" name="site_name" value="<?= e($settings['site_name']) ?>"></div>
            <div class="field"><label for="registration">Registration</label><select id="registration" name="registration"><option <?= $settings['registration'] === 'Enabled' ? 'selected' : '' ?>>Enabled</option><option <?= $settings['registration'] === 'Disabled' ? 'selected' : '' ?>>Disabled</option></select></div>
            <div class="field"><label for="maintenance">Maintenance Mode</label><select id="maintenance" name="maintenance"><option <?= $settings['maintenance'] === 'Disabled' ? 'selected' : '' ?>>Disabled</option><option <?= $settings['maintenance'] === 'Enabled' ? 'selected' : '' ?>>Enabled</option></select></div>
            <div class="field"><label for="min_deposit">Minimum Deposit</label><input id="min_deposit" name="min_deposit" type="number" min="0" step="0.01" value="<?= e((string) $settings['min_deposit']) ?>"></div>
            <div class="field"><label for="max_deposit">Maximum Deposit</label><input id="max_deposit" name="max_deposit" type="number" min="0" step="0.01" value="<?= e((string) $settings['max_deposit']) ?>"></div>
            <div class="field"><label for="min_withdrawal">Minimum Withdrawal</label><input id="min_withdrawal" name="min_withdrawal" type="number" min="0" step="0.01" value="<?= e((string) $settings['min_withdrawal']) ?>"></div>
            <div class="field"><label for="fee">Withdrawal Fee (%)</label><input id="fee" name="fee" type="number" min="0" step="0.01" value="<?= e((string) $settings['fee']) ?>"></div>
        </div>
        <button class="button button--primary" type="submit">Save Settings</button>
    </form>
</section>
<?php require __DIR__ . '/../../includes/layouts/admin-footer.php'; ?>
