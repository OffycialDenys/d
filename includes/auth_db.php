<?php
/**
 * Database-backed authentication.
 *
 * These mirror the session/demo auth functions in auth.php and are used only
 * when db_enabled() is true. They are deliberately written so the rest of the
 * app (which still reads the session-backed "platform" shape) keeps working:
 * on success we also populate $_SESSION['platform']['user'] from the DB row.
 *
 * This is the first, self-contained slice of the session -> MySQL migration.
 * The wallet / investment / deposit / withdrawal / referral services in
 * platform.php and customer.php must be migrated next before db_enabled can
 * safely be turned on in production.
 */

function db_user_to_session(array $user): array
{
    return [
        'id' => (int) $user['id'],
        'username' => $user['username'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'phone' => $user['phone'] ?? '',
        'country' => $user['country'] ?? '',
        'city' => $user['city'] ?? '',
        'membership' => $user['membership_level'] ?? 'Starter',
        'status' => ucfirst($user['status'] ?? 'Active'),
        'referral_code' => $user['referral_code'] ?? '',
        'sponsor' => '',
        'last_login' => date('Y-m-d H:i:s'),
    ];
}

function db_find_user_by_identity(string $identity): ?array
{
    $pdo = db();
    if (!$pdo) {
        return null;
    }
    $identity = strtolower(trim($identity));
    return db_fetch(
        'SELECT * FROM users WHERE LOWER(email) = ? OR LOWER(username) = ? LIMIT 1',
        [$identity, $identity]
    );
}

function db_login_user(string $identity, string $password): bool
{
    $user = db_find_user_by_identity($identity);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    $_SESSION['auth_user'] = (int) $user['id'];
    $_SESSION['platform']['user'] = db_user_to_session($user);
    $_SESSION['platform']['customers'][$user['id']] = $_SESSION['platform']['user'];
    record_activity('user', 'User signed in');
    return true;
}

function db_register_user(array $input): bool
{
    $required = ['full_name', 'username', 'email', 'phone', 'country', 'password', 'confirm_password'];
    foreach ($required as $field) {
        if (trim((string) ($input[$field] ?? '')) === '') {
            flash('error', 'Please complete all required registration fields.');
            return false;
        }
    }

    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Enter a valid email address.');
        return false;
    }

    if ($input['password'] !== $input['confirm_password']) {
        flash('error', 'Passwords do not match.');
        return false;
    }

    $pdo = db();
    if (!$pdo) {
        flash('error', 'Database is not available.');
        return false;
    }

    $email = strtolower(trim($input['email']));
    $username = trim($input['username']);

    if (db_fetch(
        'SELECT id FROM users WHERE LOWER(email) = ? OR LOWER(username) = ?',
        [$email, strtolower($username)]
    )) {
        flash('error', 'An account with that email or username already exists.');
        return false;
    }

    $referralCode = substr(str_replace(['=', '/', '+'], '', base64_encode(random_bytes(9))), 0, 12);
    $hash = password_hash($input['password'], PASSWORD_DEFAULT);

    db_exec(
        'INSERT INTO users (username, full_name, email, phone, password_hash, referral_code, country, status, membership_level) '
        . 'VALUES (?, ?, ?, ?, ?, ?, ?, \'active\', \'Starter\')',
        [$username, trim($input['full_name']), $email, trim($input['phone']), $hash, $referralCode, trim($input['country'])]
    );

    $id = db_last_insert_id();
    db_exec('INSERT IGNORE INTO wallets (user_id) VALUES (?)', [$id]);

    $sessionUser = db_user_to_session([
        'id' => $id,
        'username' => $username,
        'full_name' => trim($input['full_name']),
        'email' => $email,
        'phone' => trim($input['phone']),
        'country' => trim($input['country']),
        'referral_code' => $referralCode,
        'status' => 'active',
        'membership_level' => 'Starter',
    ]);
    $_SESSION['platform']['user'] = $sessionUser;
    $_SESSION['platform']['customers'][$id] = $sessionUser;
    $_SESSION['auth_user'] = (int) $id;
    record_activity('user', 'New user registration completed');
    return true;
}

function db_login_admin(string $email, string $password): bool
{
    $pdo = db();
    if (!$pdo) {
        return false;
    }
    $admin = db_fetch('SELECT * FROM admin_users WHERE LOWER(email) = ? LIMIT 1', [strtolower(trim($email))]);
    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        return false;
    }
    $_SESSION['auth_admin'] = true;
    $_SESSION['admin_name'] = $admin['name'];
    record_activity('admin', 'Administrator signed in');
    return true;
}
