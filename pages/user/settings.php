<?php $pageTitle = 'Settings'; require __DIR__ . '/../../includes/layouts/user-header.php'; ?>
<section class="card"><h2>Preferences</h2><form method="post" class="grid"><div class="form-grid"><div class="field"><label for="language">Language</label><select id="language" name="language"><option>English</option><option>French</option><option>Spanish</option></select></div><div class="field"><label for="privacy">Privacy</label><select id="privacy" name="privacy"><option>Standard</option><option>Strict</option></select></div></div><label><input type="checkbox" name="notifications" checked> Receive dashboard notifications</label><button class="button button--primary" type="submit">Save Settings</button></form></section>
<section class="card"><h2>Change Password</h2><div class="form-grid"><div class="field"><label>Current Password</label>
                <div class="field__control">
                    <input id="current_password" type="password">
                    <button
                        type="button"
                        class="password-toggle"
                        data-password-toggle
                        data-password-target="current_password"
                        aria-label="Show current password"
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
                </div>
            </div><div class="field"><label>New Password</label>
                <div class="field__control">
                    <input id="new_password" type="password">
                    <button
                        type="button"
                        class="password-toggle"
                        data-password-toggle
                        data-password-target="new_password"
                        aria-label="Show new password"
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
                </div>
            </div></div></section>
<?php require __DIR__ . '/../../includes/layouts/user-footer.php'; ?>
