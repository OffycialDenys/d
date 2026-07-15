<?php
/**
 * Customer-scoped data layer for multi-tenant isolation.
 *
 * Every financial record (wallet, orders, transactions, deposits, withdrawals,
 * notifications, tickets, activities, admin notes) lives INSIDE the owning
 * customer record at $_SESSION['platform']['customers'][$id]. There is no
 * longer a shared "global" wallet that every logged-in user overwrites. All
 * reads and writes go through the accessors below so the boundary is enforced
 * in exactly one place.
 *
 * Two scoping contexts exist:
 *   - current_customer_id(): the signed-in end user making a request
 *     (trustworthy: sourced from the session, never from POST input).
 *   - managed_customer_id()/selected_customer_id(): the customer an admin has
 *     explicitly selected (sourced from the admin form's user_id field).
 */

function ensure_customer(int $id): void
{
    if (isset($_SESSION['platform']['customers'][$id])) {
        return;
    }

    $wallet = default_wallet();
    $_SESSION['platform']['customers'][$id] = build_customer(
        [
            'id' => $id,
            'username' => 'guest_' . $id,
            'full_name' => 'Guest Customer',
            'email' => 'guest' . $id . '@example.com',
            'phone' => '',
            'country' => '',
            'city' => '',
            'membership' => 'Starter Account',
            'status' => 'Active',
            'referral_code' => 'guest' . $id,
            'sponsor' => 'Nivaro Partner Desk',
            'last_login' => date('Y-m-d H:i:s'),
        ],
        $wallet, [], [], [], [], [], [], [], []
    );
}

/**
 * Return the customer record by reference so callers can mutate nested fields
 * (e.g. $c['wallet']['available'] -= $amount) and have the change persist.
 */
function &customer(int $id): array
{
    ensure_customer($id);
    return $_SESSION['platform']['customers'][$id];
}

function get_customer(int $id): array
{
    ensure_customer($id);
    return $_SESSION['platform']['customers'][$id];
}

/**
 * The authenticated end user. Sourced from the session only, so a user can
 * never impersonate another customer by tampering with POST data.
 */
function current_customer_id(): int
{
    return (int) ($_SESSION['auth_user'] ?? $_SESSION['platform']['user']['id'] ?? 0);
}

/**
 * The customer an admin is currently managing (from the admin user picker).
 */
function managed_customer_id(): int
{
    return selected_customer_id();
}

/**
 * Wallet accessor by reference — the primary per-customer financial record.
 */
function &customer_wallet(int $id): array
{
    $c = &customer($id);
    if (!isset($c['wallet']) || !is_array($c['wallet'])) {
        $c['wallet'] = default_wallet();
    } else {
        // Guarantee every expected key (e.g. 'referral') exists even if the
        // stored wallet came from a source that omitted some columns.
        $c['wallet'] = array_merge(default_wallet(), $c['wallet']);
    }
    return $c['wallet'];
}

/**
 * Read a customer sub-record (orders, transactions, etc.) as a copy.
 */
function customer_field(int $id, string $key): array
{
    $c = get_customer($id);
    return isset($c[$key]) && is_array($c[$key]) ? $c[$key] : [];
}

function customer_orders(int $id): array
{
    return customer_field($id, 'orders');
}

function customer_transactions(int $id): array
{
    return customer_field($id, 'transactions');
}

function customer_deposits(int $id): array
{
    return customer_field($id, 'deposits');
}

function customer_withdrawals(int $id): array
{
    return customer_field($id, 'withdrawals');
}

function customer_notifications(int $id): array
{
    return customer_field($id, 'notifications');
}

function customer_activities(int $id): array
{
    return customer_field($id, 'activities');
}

function customer_admin_notes(int $id): array
{
    return customer_field($id, 'admin_notes');
}

function customer_tickets(int $id): array
{
    return customer_field($id, 'tickets');
}

/**
 * Resolve which customer a write-side helper (transaction/notification/activity)
 * should target. In an admin context the data belongs to the managed customer;
 * otherwise it belongs to the signed-in user.
 */
function write_target_id(): int
{
    return !empty($_SESSION['auth_admin']) ? selected_customer_id() : current_customer_id();
}

function add_customer_transaction(int $targetId, string $type, string $category, float $amount, float $oldBalance, float $newBalance, string $status, string $description): void
{
    $c = &customer($targetId);
    $c['transactions'][] = [
        'id' => 'TXN-' . random_int(2000, 9999),
        'type' => $type,
        'category' => $category,
        'amount' => $amount,
        'old' => $oldBalance,
        'new' => $newBalance,
        'status' => $status,
        'date' => date('Y-m-d H:i'),
        'description' => $description,
    ];
}

function add_customer_notification(int $targetId, string $title, string $message, string $type = 'System'): void
{
    $c = &customer($targetId);
    $c['notifications'][] = [
        'title' => $title,
        'message' => $message,
        'type' => $type,
        'read' => false,
        'date' => date('Y-m-d H:i'),
    ];
}

function add_customer_activity(int $targetId, string $actor, string $message): void
{
    $c = &customer($targetId);
    $c['activities'][] = [
        'actor' => $actor,
        'message' => $message,
        'date' => date('Y-m-d H:i:s'),
    ];
}

/**
 * Per-customer metrics, stored under the owning customer record so the user
 * dashboard and admin customer view always reflect that account's own numbers.
 */
function recalculate_customer_metrics(int $id): void
{
    $c = &customer($id);
    $orders = $c['orders'] ?? [];
    $transactions = $c['transactions'] ?? [];
    $wallet = $c['wallet'] ?? default_wallet();

    $c['metrics'] = [
        'total_deposits' => array_sum(array_map(fn($row) => (($row['category'] ?? '') === 'Credit') ? (float) $row['amount'] : 0, $transactions)),
        'total_withdrawals' => array_sum(array_map(fn($row) => (($row['type'] ?? '') === 'Withdrawal') ? (float) $row['amount'] : 0, $transactions)),
        'active_investments' => count(array_filter($orders, fn($row) => ($row['status'] ?? '') === 'Active')),
        'completed_investments' => count(array_filter($orders, fn($row) => ($row['status'] ?? '') === 'Completed')),
        'today_earnings' => array_sum(array_map(fn($row) => (float) $row['profit'], $orders)) / 30,
        'monthly_earnings' => array_sum(array_map(fn($row) => (float) $row['profit'], $orders)),
        'lifetime_earnings' => array_sum(array_map(fn($row) => (float) $row['profit'], $orders)) + (float) ($wallet['referral'] ?? 0) + (float) ($wallet['bonus'] ?? 0),
    ];
}

/**
 * Finds an order that belongs to a specific customer and returns it by reference.
 * Returns null when the order does not exist in that customer's record — this is
 * the ownership gate that prevents one user acting on another's order id.
 */
function &assert_order_owner(int $id, string $orderId): ?array
{
    $null = null;
    $c = &customer($id);
    foreach ($c['orders'] as $key => &$order) {
        if (($order['id'] ?? '') === $orderId) {
            return $order;
        }
    }
    unset($order);
    return $null;
}

function &assert_deposit_owner(int $id, string $depositId): ?array
{
    $null = null;
    $c = &customer($id);
    foreach ($c['deposits'] as $key => &$deposit) {
        if (($deposit['id'] ?? '') === $depositId) {
            return $deposit;
        }
    }
    unset($deposit);
    return $null;
}

function &assert_withdrawal_owner(int $id, string $withdrawalId): ?array
{
    $null = null;
    $c = &customer($id);
    foreach ($c['withdrawals'] as $key => &$withdrawal) {
        if (($withdrawal['id'] ?? '') === $withdrawalId) {
            return $withdrawal;
        }
    }
    unset($withdrawal);
    return $null;
}

/**
 * Sliding-window rate limiter. Key is caller-supplied (typically scoped to
 * the customer, e.g. "deposit:1221"). Returns true when the request is still
 * within the limit, false once the bucket is exhausted for this window.
 */
function rate_limit(string $key, int $max, int $windowSeconds): bool
{
    if (!isset($_SESSION['platform']['rate_limits']) || !is_array($_SESSION['platform']['rate_limits'])) {
        $_SESSION['platform']['rate_limits'] = [];
    }

    $now = time();
    $bucket = &$_SESSION['platform']['rate_limits'][$key];

    if (!is_array($bucket) || ($now - (int) ($bucket['first'] ?? 0)) > $windowSeconds) {
        $bucket = ['first' => $now, 'count' => 0];
    }

    if ((int) $bucket['count'] >= $max) {
        return false;
    }

    $bucket['count']++;
    return true;
}

function rate_limit_remaining(string $key, int $max, int $windowSeconds): int
{
    if (!isset($_SESSION['platform']['rate_limits'][$key]) || !is_array($_SESSION['platform']['rate_limits'][$key])) {
        return $max;
    }
    $bucket = $_SESSION['platform']['rate_limits'][$key];
    $now = time();
    if (($now - (int) ($bucket['first'] ?? 0)) > $windowSeconds) {
        return $max;
    }
    return max(0, $max - (int) $bucket['count']);
}

/**
 * Validate a money amount against inclusive bounds. Rejects NaN/INF, negatives,
 * and out-of-range values so downstream wallet math stays sound.
 */
function is_valid_amount(float $amount, float $min, float $max): bool
{
    if (!is_finite($amount)) {
        return false;
    }
    if ($amount < $min - 0.0001) {
        return false;
    }
    if ($max > 0 && $amount > $max + 0.0001) {
        return false;
    }
    return true;
}

function is_wallet_frozen(int $id): bool
{
    $wallet = customer_wallet($id);
    return !empty($wallet['frozen']);
}

/**
 * Cross-customer aggregations for admin list views. Each record is tagged with
 * the owning customer's id and username so the admin UI can show provenance.
 */
function all_orders(): array
{
    $out = [];
    foreach ($_SESSION['platform']['customers'] as $id => $c) {
        foreach ($c['orders'] ?? [] as $row) {
            $row['user_id'] = $id;
            $row['username'] = $c['username'] ?? 'customer';
            $out[] = $row;
        }
    }
    return $out;
}

function all_deposits(): array
{
    $out = [];
    foreach ($_SESSION['platform']['customers'] as $id => $c) {
        foreach ($c['deposits'] ?? [] as $row) {
            $row['user_id'] = $row['user_id'] ?? $id;
            $row['username'] = $c['username'] ?? 'customer';
            $out[] = $row;
        }
    }
    return $out;
}

function all_withdrawals(): array
{
    $out = [];
    foreach ($_SESSION['platform']['customers'] as $id => $c) {
        foreach ($c['withdrawals'] ?? [] as $row) {
            $row['user_id'] = $id;
            $row['username'] = $c['username'] ?? 'customer';
            $out[] = $row;
        }
    }
    return $out;
}

function all_transactions(): array
{
    $out = [];
    foreach ($_SESSION['platform']['customers'] as $id => $c) {
        foreach ($c['transactions'] ?? [] as $row) {
            $row['user_id'] = $id;
            $row['username'] = $c['username'] ?? 'customer';
            $out[] = $row;
        }
    }
    return $out;
}

function all_tickets(): array
{
    $out = [];
    foreach ($_SESSION['platform']['customers'] as $id => $c) {
        foreach ($c['tickets'] ?? [] as $row) {
            $row['user_id'] = $id;
            $row['username'] = $c['username'] ?? 'customer';
            $out[] = $row;
        }
    }
    return $out;
}

function all_activities(): array
{
    $out = [];
    foreach ($_SESSION['platform']['customers'] as $id => $c) {
        foreach ($c['activities'] ?? [] as $row) {
            $row['user_id'] = $id;
            $row['username'] = $c['username'] ?? 'customer';
            $out[] = $row;
        }
    }
    return $out;
}

function platform_metrics(): array
{
    $metrics = [
        'total_deposits' => 0.0,
        'total_withdrawals' => 0.0,
        'active_investments' => 0,
        'completed_investments' => 0,
        'monthly_earnings' => 0.0,
    ];

    foreach ($_SESSION['platform']['customers'] ?? [] as $c) {
        foreach (($c['transactions'] ?? []) as $row) {
            if (($row['category'] ?? '') === 'Credit') {
                $metrics['total_deposits'] += (float) ($row['amount'] ?? 0);
            }
            if (($row['type'] ?? '') === 'Withdrawal') {
                $metrics['total_withdrawals'] += (float) ($row['amount'] ?? 0);
            }
        }
        foreach (($c['orders'] ?? []) as $row) {
            $status = $row['status'] ?? '';
            if ($status === 'Active') {
                $metrics['active_investments']++;
                $metrics['monthly_earnings'] += (float) ($row['profit'] ?? 0);
            } elseif ($status === 'Completed') {
                $metrics['completed_investments']++;
            }
        }
    }

    return $metrics;
}

function default_wallet(): array
{
    return [
        'available' => 0.00,
        'locked' => 0.00,
        'bonus' => 0.00,
        'referral' => 0.00,
        'investment' => 0.00,
        'pending' => 0.00,
        'withdrawable' => 0.00,
        'frozen' => false,
    ];
}
