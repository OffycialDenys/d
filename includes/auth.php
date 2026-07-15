<?php
function require_login(): void
{
    if (empty($_SESSION['auth_user'])) {
        flash('warning', 'Please sign in to continue.');
        redirect('index.php?route=login');
    }
}

function require_admin(): void
{
    if (empty($_SESSION['auth_admin'])) {
        flash('warning', 'Administrator login is required.');
        redirect('admin/index.php?route=login');
    }
}

function login_admin(string $email, string $password): bool
{
    if (db_enabled()) {
        return db_login_admin($email, $password);
    }

    global $config;
    $demo = $config['demo_admin'];

    if (strtolower($email) === strtolower($demo['email']) && $password === $demo['password']) {
        $_SESSION['auth_admin'] = true;
        $_SESSION['admin_name'] = 'Platform Admin';
        record_activity('admin', 'Administrator signed in');
        return true;
    }

    return false;
}

function login_user(string $identity, string $password): bool
{
    if (db_enabled()) {
        return db_login_user($identity, $password);
    }

    global $config;
    $demo = $config['demo_user'];
    $user = $_SESSION['platform']['user'];
    $matchesIdentity = in_array(strtolower($identity), [strtolower($demo['email']), strtolower($user['username'])], true);

    if ($matchesIdentity && $password === $demo['password']) {
        $_SESSION['auth_user'] = $user['id'];
        $_SESSION['platform']['user']['last_login'] = date('Y-m-d H:i:s');
        record_activity('user', 'User signed in');
        return true;
    }

    return false;
}

function register_user(array $input): bool
{
    if (db_enabled()) {
        return db_register_user($input);
    }

    $required = ['full_name', 'username', 'email', 'phone', 'country', 'password', 'confirm_password'];
    foreach ($required as $field) {
        if (trim($input[$field] ?? '') === '') {
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

    $_SESSION['platform']['user'] = array_merge($_SESSION['platform']['user'], [
        'full_name' => trim($input['full_name']),
        'username' => trim($input['username']),
        'email' => trim($input['email']),
        'phone' => trim($input['phone']),
        'country' => trim($input['country']),
        'status' => 'Active',
    ]);
    $_SESSION['platform']['customers'][$_SESSION['platform']['user']['id']] = $_SESSION['platform']['user'];
    $_SESSION['auth_user'] = $_SESSION['platform']['user']['id'];
    record_activity('user', 'New user registration completed');
    return true;
}

function logout_user(): void
{
    unset($_SESSION['auth_user']);
    record_activity('user', 'User signed out');
}

function logout_admin(): void
{
    unset($_SESSION['auth_admin'], $_SESSION['admin_name']);
}
