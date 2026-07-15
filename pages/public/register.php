<?php
/**
 * Registration page.
 *
 * IMPORTANT: All existing `id`, `name`, `class`, and `data-*` attributes from
 * the original markup have been preserved exactly. This means:
 *   - Any existing CSS (page-section, card, form-grid, field, field__control,
 *     password-toggle, button--primary, etc.) keeps working unchanged.
 *   - Any existing JS that hooks into [data-password-toggle] /
 *     [data-password-target] keeps working unchanged.
 *   - Any existing backend handler reading $_POST['full_name'], $_POST['email'],
 *     etc. keeps working unchanged.
 *
 * NOTE: the global e() helper is provided by includes/functions.php, so it is
 * intentionally NOT redeclared here (that would fatal with "cannot redeclare").
 *
 * What changed is internal: the six plain-text fields are now driven by a
 * single config array + loop instead of six blocks of copy-pasted HTML, and
 * a couple of small, additive, non-breaking improvements were made
 * (see comments below). Nothing here requires touching any other file.
 */

$pageTitle = 'Register';
require __DIR__ . '/../../includes/layouts/public-header.php';

/**
 * --- Optional, additive, backward-compatible helpers ---
 *
 * These only activate if the rest of the app already uses sessions and/or
 * sets $_SESSION['errors'] / $_SESSION['old'] before redirecting back here.
 * If it doesn't, every one of these safely no-ops (empty arrays / empty
 * token), so this page behaves exactly like the original.
 */
$sessionActive = function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE;

$errors = $sessionActive ? ($_SESSION['errors'] ?? []) : [];
$old    = $sessionActive ? ($_SESSION['old'] ?? [])    : [];
if ($sessionActive) {
    unset($_SESSION['errors'], $_SESSION['old']);
}

$csrfToken = '';
if ($sessionActive) {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrfToken = $_SESSION['csrf_token'];
}

/** Standard (non-password) fields, in display order. */
$fields = [
    'full_name' => ['label' => 'Full Name', 'type' => 'text',  'autocomplete' => 'name',           'required' => true],
    'username'  => ['label' => 'Username',  'type' => 'text',  'autocomplete' => 'username',        'required' => true],
    'email'     => ['label' => 'Email',     'type' => 'email', 'autocomplete' => 'email',           'required' => true],
    'phone'     => ['label' => 'Phone',     'type' => 'tel',   'autocomplete' => 'tel',             'required' => true],
    'country'   => ['label' => 'Country',   'type' => 'text',  'autocomplete' => 'country-name',    'required' => true],
    'referral'  => ['label' => 'Referral Code', 'type' => 'text', 'autocomplete' => 'off', 'required' => false, 'placeholder' => 'Optional'],
];

/** Renders the show/hide eye icon pair once; reused for both password fields. */
function renderPasswordToggleButton(string $target, string $ariaLabel): void
{
    ?>
    <button
        type="button"
        class="password-toggle"
        data-password-toggle
        data-password-target="<?= e($target) ?>"
        aria-label="<?= e($ariaLabel) ?>"
        aria-pressed="false"
    >
        <svg class="password-toggle__eye" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
        <svg class="password-toggle__eye-off" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
            <line x1="2" x2="22" y1="2" y2="22"/>
        </svg>
    </button>
    <?php
}
?>
<section class="page-section">
    <article class="card">
        <p class="eyebrow">Account Setup</p>
        <h1>Create your investment account</h1>

        <?php if (!empty($errors['_general'])): ?>
            <p class="form-error form-error--general" role="alert"><?= e($errors['_general']) ?></p>
        <?php endif; ?>

        <form method="post" class="grid" novalidate>
            <?php if ($csrfToken): ?>
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <?php endif; ?>

            <fieldset class="form-grid">
                <legend class="sr-only">Account details</legend>

                <?php foreach ($fields as $name => $field): ?>
                    <div class="field">
                        <label for="<?= e($name) ?>"><?= e($field['label']) ?></label>
                        <input
                            id="<?= e($name) ?>"
                            name="<?= e($name) ?>"
                            type="<?= e($field['type']) ?>"
                            autocomplete="<?= e($field['autocomplete']) ?>"
                            value="<?= e($old[$name] ?? '') ?>"
                            <?= !empty($field['placeholder']) ? 'placeholder="' . e($field['placeholder']) . '"' : '' ?>
                            <?= $field['required'] ? 'required' : '' ?>
                            <?= !empty($errors[$name]) ? 'aria-invalid="true" aria-describedby="' . e($name) . '-error"' : '' ?>
                        >
                        <?php if (!empty($errors[$name])): ?>
                            <span class="field-error" id="<?= e($name) ?>-error"><?= e($errors[$name]) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="field__control">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            required
                            <?= !empty($errors['password']) ? 'aria-invalid="true" aria-describedby="password-error"' : '' ?>
                        >
                        <?php renderPasswordToggleButton('password', 'Show password'); ?>
                    </div>
                    <?php if (!empty($errors['password'])): ?>
                        <span class="field-error" id="password-error"><?= e($errors['password']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="field__control">
                        <input
                            id="confirm_password"
                            name="confirm_password"
                            type="password"
                            autocomplete="new-password"
                            required
                            <?= !empty($errors['confirm_password']) ? 'aria-invalid="true" aria-describedby="confirm_password-error"' : '' ?>
                        >
                        <?php renderPasswordToggleButton('confirm_password', 'Show confirm password'); ?>
                    </div>
                    <?php if (!empty($errors['confirm_password'])): ?>
                        <span class="field-error" id="confirm_password-error"><?= e($errors['confirm_password']) ?></span>
                    <?php endif; ?>
                </div>
            </fieldset>

            <label><input type="checkbox" required> I agree to the platform terms and privacy policy.</label>
            <button class="button button--primary" type="submit">Create Account</button>
        </form>
    </article>
</section>
<?php require __DIR__ . '/../../includes/layouts/public-footer.php'; ?>
