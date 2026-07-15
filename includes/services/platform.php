<?php
/**
 * Deterministic PRNG (mulberry32) so a given asset+range always yields the same
 * series. Avoids Math.random()-style faux-live behaviour on the client.
 */
function mulberry32(int $seed): callable
{
    return function () use (&$seed): float {
        $seed = ($seed + 0x6D2B79F5) & 0xFFFFFFFF;
        $t = ($seed ^ ($seed >> 15)) & 0xFFFFFFFF;
        $t = ($t + (($t << 7) & 0xFFFFFFFF)) & 0xFFFFFFFF;
        $t = ($t ^ ($t >> 14)) & 0xFFFFFFFF;
        $t = ($t + (($t << 13) & 0xFFFFFFFF)) & 0xFFFFFFFF;
        $t = ($t ^ ($t >> 16)) & 0xFFFFFFFF;
        return ($t & 0xFFFFFFFF) / 0xFFFFFFFF;
    };
}

/**
 * Build a price series for an asset from its real configuration
 * (price + roi + daily_return). Grounded in actual plan parameters instead of
 * arbitrary arrays, while still deterministic per asset/range.
 */
function build_asset_chart_series(array $plan, string $range): array
{
    $ranges = [
        '1D' => ['points' => 24, 'weight' => 0.02],
        '1W' => ['points' => 28, 'weight' => 0.08],
        '1M' => ['points' => 30, 'weight' => 0.25],
        '3M' => ['points' => 36, 'weight' => 0.60],
        '1Y' => ['points' => 52, 'weight' => 1.00],
        '5Y' => ['points' => 60, 'weight' => 2.50],
    ];
    $config = $ranges[$range] ?? $ranges['1M'];
    $count = $config['points'];
    $weight = $config['weight'];

    $price = (float) ($plan['price'] ?? 0);
    $roiPercent = (float) ($plan['roi'] ?? 0);
    $netReturn = ($roiPercent / 100) - 1; // e.g. 104.5 => +4.5%

    $growth = max(0.0, 1 + $netReturn * $weight);
    $startPrice = $price > 0 ? $price / $growth : 0;

    $seedKey = (int) ($plan['id'] ?? 0) . strlen($range) . ord($range[0] ?? '1');
    $rand = mulberry32((int) $seedKey);

    $points = [];
    $amplitude = $price * 0.012; // ~1.2% deterministic wiggle
    for ($i = 0; $i < $count; $i++) {
        $frac = $count > 1 ? $i / ($count - 1) : 1;
        $trend = $startPrice + ($price - $startPrice) * $frac;
        $noise = (($rand() - 0.5) * 2) * $amplitude;
        $value = max(0.01, $trend + $noise);
        $points[] = (float) number_format($value, 2, '.', '');
    }

    // Anchor the final point exactly to the current price for consistency.
    if ($price > 0) {
        $points[$count - 1] = (float) number_format($price, 2, '.', '');
    }

    return $points;
}

/**
 * Build a short decorative sparkline series for an asset, derived from its real
 * price/roi so a newly added asset (e.g. Samsung, Tesla) renders correctly
 * without any hardcoded per-symbol logic on the frontend.
 */
function build_sparkline_points(array $plan, int $count = 8): array
{
    $price = (float) ($plan['price'] ?? 0);
    if ($price <= 0) {
        return array_fill(0, $count, 0.0);
    }

    $seedKey = ((int) ($plan['id'] ?? 0) * 2654435761) % 2147483647;
    $rand = mulberry32((int) $seedKey);
    $amplitude = $price * 0.04;

    $points = [];
    for ($i = 0; $i < $count; $i++) {
        $fraction = $count > 1 ? $i / ($count - 1) : 1;
        $trend = $price * (0.94 + 0.06 * $fraction);
        $noise = (($rand() - 0.5) * 2) * $amplitude;
        $value = max(0.01, $trend + $noise);
        $points[] = (float) number_format($value, 2, '.', '');
    }

    return $points;
}

function initialize_demo_state(): void
{
    if (!isset($_SESSION['platform'])) {
        $_SESSION['platform'] = seed_platform_data();
    }

    // Ensure the "current user" pointer (used by current_user()/login_user) exists
    // and defaults to the first seeded customer (the Apex demo account). This was
    // previously an uninitialized key, which made every read fatal on a fresh session.
    if (!isset($_SESSION['platform']['user']) && !empty($_SESSION['platform']['customers'])) {
        $_SESSION['platform']['user'] = array_values($_SESSION['platform']['customers'])[0];
    }
    $_SESSION['auth_user'] ??= (int) ($_SESSION['platform']['user']['id'] ?? 0);

    // Normalize every customer record so the accessors have a complete shape.
    foreach ($_SESSION['platform']['customers'] as $id => &$c) {
        $c['wallet'] = $c['wallet'] ?? default_wallet();
        $c['claimed_rewards'] = $c['claimed_rewards'] ?? [];
        $c['metrics'] = $c['metrics'] ?? [];
        recalculate_customer_metrics((int) $id);
    }
    unset($c);

    $_SESSION['platform']['crypto_wallets'] ??= default_crypto_wallets();

    foreach ($_SESSION['platform']['plans'] as &$plan) {
        $plan['description'] ??= $plan['category'] . ' investment with controlled duration and fixed daily return.';
        $plan['sort_order'] ??= $plan['id'] * 10;
    }
}

/**
 * Recompute platform metrics for the active write target. In a user context that
 * is the signed-in customer; in an admin context it is the managed customer.
 * The result is stored on the owning customer so the dashboard always reflects
 * that account's own numbers.
 */
function recalculate_metrics(): void
{
    recalculate_customer_metrics(write_target_id());
}

function record_activity(string $actor, string $message): void
{
    if (!isset($_SESSION['platform'])) {
        return;
    }

    add_customer_activity(write_target_id(), $actor, $message);
}

function add_notification(string $title, string $message, string $type = 'System'): void
{
    add_customer_notification(write_target_id(), $title, $message, $type);
}

function add_transaction(string $type, string $category, float $amount, float $oldBalance, float $newBalance, string $status, string $description): void
{
    add_customer_transaction(write_target_id(), $type, $category, $amount, $oldBalance, $newBalance, $status, $description);
}

function admin_name(): string
{
    return $_SESSION['admin_name'] ?? 'Platform Admin';
}

/**
 * Append a structured audit entry so administrative actions are traceable:
 * who performed it, when, on what entity, and the previous/new values.
 * This backs the Activity Logs screen and satisfies the audit-trail requirement.
 */
function audit_log(string $action, string $entityType, string $entityId, $oldValue = null, $newValue = null): void
{
    if (!isset($_SESSION['platform'])) {
        return;
    }

    $_SESSION['platform']['admin_logs'][] = [
        'actor' => admin_name(),
        'action' => $action,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'old_value' => $oldValue,
        'new_value' => $newValue,
        'date' => date('Y-m-d H:i:s'),
    ];
}

function managed_customer(): array
{
    return get_customer(selected_customer_id());
}

function selected_customer_id(): int
{
    return (int) ($_GET['user'] ?? $_POST['user_id'] ?? $_SESSION['platform']['user']['id']);
}

function handle_user_action(string $route, array $post, array $files): void
{
    if ($route === 'login') {
        if (login_user(trim($post['identity'] ?? ''), $post['password'] ?? '')) {
            flash('success', 'Welcome back. Your dashboard is ready.');
            redirect('index.php?route=dashboard');
        }
        flash('error', 'Invalid email, username, or password.');
        redirect('index.php?route=login');
    }

    if ($route === 'register') {
        if (register_user($post)) {
            flash('success', 'Account created. Your wallet and referral code are ready.');
            redirect('index.php?route=dashboard');
        }
        redirect('index.php?route=register');
    }

    require_login();

    match ($route) {
        'deposit' => submit_deposit($post),
        'withdraw' => submit_withdrawal($post),
        'investment-details', 'investments' => purchase_investment($post),
        'owned-investment-details' => handle_owned_investment_action($post),
        'rewards' => redeem_reward($post),
        'support' => create_ticket($post),
        'profile' => update_profile($post),
        'settings' => update_settings($post),
        'bank' => bind_bank($post),
        'notifications' => handle_notification_action($post),
        default => null,
    };
}

function submit_deposit(array $post): void
{
    $id = current_customer_id();
    $settings = $_SESSION['platform']['settings'];

    $amount = (float) ($post['amount'] ?? 0);
    $method = trim($post['method'] ?? 'USDT(TRC20)');

    // Rate limit: at most 10 deposit submissions per customer / 10 minutes.
    if (!rate_limit('deposit:' . $id, 10, 600)) {
        flash('error', 'Too many deposit requests. Please wait before submitting again.');
        redirect('index.php?route=deposit');
    }

    if (!is_valid_amount($amount, (float) ($settings['min_deposit'] ?? 0), (float) ($settings['max_deposit'] ?? 0))) {
        flash('error', 'Enter a valid deposit amount within the allowed range.');
        redirect('index.php?route=deposit');
    }

    $deposit = [
        'id' => 'DEP-' . random_int(1000, 9999),
        'user_id' => $id,
        'amount' => $amount,
        'method' => $method,
        'reference' => trim($post['reference'] ?? ''),
        'status' => 'Pending',
        'date' => date('Y-m-d H:i'),
    ];
    $customers = &customer($id);
    $customers['deposits'][] = $deposit;
    add_customer_notification($id, 'Deposit submitted', 'Your deposit is waiting for administrator approval.', 'Financial');
    add_customer_activity($id, 'user', 'Submitted deposit request ' . $deposit['id']);
    recalculate_customer_metrics($id);
    flash('success', 'Deposit submitted for review.');
    redirect('index.php?route=deposit');
}

function submit_withdrawal(array $post): void
{
    $id = current_customer_id();
    $wallet = &customer_wallet($id);

    $amount = (float) ($post['amount'] ?? 0);
    $settings = $_SESSION['platform']['settings'];

    if (is_wallet_frozen($id)) {
        flash('error', 'Wallet operations are temporarily frozen. Contact support.');
        redirect('index.php?route=withdraw');
    }

    if (!is_valid_amount($amount, (float) ($settings['min_withdrawal'] ?? 0), 0)) {
        flash('error', 'Enter a valid withdrawal amount.');
        redirect('index.php?route=withdraw');
    }

    // Rate limit: at most 10 withdrawal requests per customer / 10 minutes.
    if (!rate_limit('withdraw:' . $id, 10, 600)) {
        flash('error', 'Too many withdrawal requests. Please wait before submitting again.');
        redirect('index.php?route=withdraw');
    }

    if ($amount > (float) $wallet['withdrawable']) {
        flash('error', 'Withdrawal amount must be within your withdrawable balance.');
        redirect('index.php?route=withdraw');
    }

    $old = (float) $wallet['available'];
    $wallet['available'] -= $amount;
    $wallet['locked'] += $amount;
    $wallet['withdrawable'] = max(0, (float) $wallet['withdrawable'] - $amount);

    $withdrawal = [
        'id' => 'WDR-' . random_int(1000, 9999),
        'user_id' => $id,
        'amount' => $amount,
        'destination' => trim($post['destination'] ?? 'Linked bank account'),
        'status' => 'Pending',
        'date' => date('Y-m-d H:i'),
    ];
    $customers = &customer($id);
    $customers['withdrawals'][] = $withdrawal;
    add_transaction('Withdrawal', 'Debit', $amount, $old, (float) $wallet['available'], 'Pending', 'Withdrawal request locked funds');
    add_notification('Withdrawal requested', 'Your withdrawal request has been sent to operations.', 'Financial');
    record_activity('user', 'Requested withdrawal');
    recalculate_customer_metrics($id);
    flash('success', 'Withdrawal request submitted.');
    redirect('index.php?route=withdraw');
}

function purchase_investment(array $post): void
{
    if (($post['action'] ?? '') !== 'purchase') {
        return;
    }

    $planId = (int) ($post['plan_id'] ?? 0);
    $amount = (float) ($post['amount'] ?? 0);
    $plan = current(array_filter($_SESSION['platform']['plans'], fn($row) => $row['id'] === $planId));

    if (!$plan || $amount < $plan['min'] || $amount > $plan['max']) {
        flash('error', 'Choose a valid investment amount.');
        redirect('index.php?route=investments');
    }

    $wallet = &customer_wallet(current_customer_id());
    $customerId = current_customer_id();

    if (is_wallet_frozen($customerId)) {
        flash('error', 'Wallet operations are temporarily frozen. Contact support.');
        redirect('index.php?route=investment-details&id=' . $planId);
    }

    if ($wallet['available'] < $amount) {
        flash('error', 'Insufficient available balance for this investment.');
        redirect('index.php?route=investment-details&id=' . $planId);
    }

    $old = (float) $wallet['available'];
    $wallet['available'] -= $amount;
    $wallet['investment'] += $amount;
    $wallet['withdrawable'] = max(0, (float) $wallet['withdrawable'] - $amount);
    
    // roi field is e.g. 104.50 (meaning 4.5% net profit). Total expected earnings: amount * (roi/100)
    $expected = round($amount * ($plan['roi'] / 100), 2);
    $netProfit = $expected - $amount;

    $order = [
        'id' => 'ORD-' . random_int(3000, 9999),
        'user_id' => $customerId,
        'plan' => $plan['name'],
        'symbol' => $plan['symbol'] ?? '',
        'amount' => $amount,
        'profit' => 0,
        'progress' => 0,
        'status' => 'Active',
        'purchase_date' => date('Y-m-d'),
        'completion_date' => date('Y-m-d', strtotime('+' . $plan['duration'] . ' days')),
        'expected' => $expected,
        'net_profit' => $netProfit,
        'image' => $plan['image'] ?? 'aapl-logo'
    ];
    $customers = &customer($customerId);
    $customers['orders'][] = $order;
    add_transaction('Investment Purchase', 'Debit', $amount, $old, (float) $wallet['available'], 'Completed', $plan['name'] . ' (' . ($plan['symbol'] ?? '') . ') purchase');
    add_notification('Investment purchased', $plan['name'] . ' is now active.', 'Investment');
    record_activity('user', 'Purchased investment asset ' . $plan['name']);
    recalculate_customer_metrics($customerId);
    flash('success', 'Investment purchased successfully.');
    redirect('index.php?route=orders');
}

/**
 * Read the active cryptocurrency wallet configuration. This is the single
 * source of truth for deposit addresses consumed by the user workflow and the
 * admin review screen.
 */
function get_crypto_wallets(): array
{
    return $_SESSION['platform']['crypto_wallets'] ?? [];
}

/**
 * Allowed deposit lifecycle states and the transitions the backend enforces.
 */
const DEPOSIT_TRANSITIONS = [
    'Awaiting Payment' => ['Pending Review', 'Cancelled'],
    'Pending Review' => ['Approved', 'Rejected', 'Cancelled'],
    'Approved' => [],
    'Rejected' => [],
    'Cancelled' => [],
];

function confirm_crypto_deposit(array $post): array
{
    if (empty($_SESSION['platform'])) {
        return ['ok' => false, 'code' => 401, 'error' => 'Session expired. Please sign in again.'];
    }

    $wallets = get_crypto_wallets();
    $symbol = strtoupper(trim((string) ($post['crypto'] ?? '')));
    if (!isset($wallets[$symbol])) {
        return ['ok' => false, 'code' => 400, 'error' => 'Please select a supported cryptocurrency.'];
    }
    $wallet = $wallets[$symbol];
    $userId = current_customer_id();

    // Idempotency: do not create a second open deposit for the same asset,
    // scoped to THIS customer so other customers are unaffected.
    $customerDeposits = customer_deposits($userId);
    foreach ($customerDeposits as $existing) {
        if (($existing['crypto'] ?? '') === $symbol
            && in_array($existing['status'] ?? '', ['Awaiting Payment', 'Pending Review'], true)) {
            return [
                'ok' => true,
                'duplicate' => true,
                'deposit' => $existing,
                'message' => 'You already have an open deposit for ' . $symbol . '.',
            ];
        }
    }

    $amount = max(0, (float) ($post['amount'] ?? 0));
    $txHash = trim((string) ($post['tx_hash'] ?? ''));
    $notes = trim((string) ($post['notes'] ?? ''));
    $id = 'DEP-' . random_int(1000, 9999);
    $now = date('Y-m-d H:i:s');

    $deposit = [
        'id' => $id,
        'user_id' => $userId,
        'crypto' => $symbol,
        'network' => $wallet['network'] ?? '',
        'wallet_address' => $wallet['address'] ?? '',
        'amount' => $amount,
        'tx_hash' => $txHash,
        'reference' => $txHash !== '' ? $txHash : ('CRYPTO-' . $symbol),
        'method' => $symbol . ' (' . ($wallet['network'] ?? '') . ')',
        'status' => 'Pending Review',
        'created_at' => $now,
        'updated_at' => $now,
        'reviewer' => null,
        'reviewed_at' => null,
        'notes' => $notes,
    ];

    $customers = &customer($userId);
    $customers['deposits'][] = $deposit;
    add_customer_notification($userId, 'Deposit submitted', 'Your ' . $symbol . ' deposit is awaiting admin verification.', 'Financial');
    add_customer_activity($userId, 'user', 'Submitted ' . $symbol . ' deposit ' . $id);
    recalculate_customer_metrics($userId);

    return ['ok' => true, 'deposit' => $deposit, 'message' => 'Deposit submitted for review.'];
}

function save_crypto_wallets(array $post): void
{
    $wallets = get_crypto_wallets();
    $changed = false;

    foreach ($wallets as $symbol => $current) {
        $addressKey = 'wallet_' . $symbol;
        if (!isset($post[$addressKey])) {
            continue;
        }
        $newAddress = trim((string) ($post[$addressKey] ?? ''));
        $newNetwork = trim((string) ($post['network_' . $symbol] ?? $current['network'] ?? ''));
        if ($newAddress === '') {
            flash('error', 'Wallet address for ' . $symbol . ' cannot be empty.');
            redirect('index.php?route=payment-settings');
        }
        $wallets[$symbol]['address'] = $newAddress;
        if ($newNetwork !== '') {
            $wallets[$symbol]['network'] = $newNetwork;
        }
        $changed = true;
    }

    if (!$changed) {
        flash('warning', 'No wallet addresses were provided.');
        redirect('index.php?route=payment-settings');
    }

    $_SESSION['platform']['crypto_wallets'] = $wallets;
    record_activity('admin', admin_name() . ' updated crypto wallet addresses');
    audit_log('Updated crypto wallet addresses', 'Payment', 'crypto_wallets', null, implode(', ', array_keys($wallets)));
    flash('success', 'Crypto wallet addresses updated.');
    redirect('index.php?route=payment-settings');
}

function handle_owned_investment_action(array $post): void
{
    $action = $post['action'] ?? '';
    $orderId = $post['order_id'] ?? '';
    $planId = (int) ($post['plan_id'] ?? 0); // Used for redirecting back to the specific investment detail page

if (empty($orderId) && $action !== 'buy_more') {
         flash('error', 'Invalid order ID provided.');
         redirect('index.php?route=orders');
     }


     switch ($action) {
        case 'sell':
            sell_investment(current_customer_id(), $orderId, $planId);
            break;
        case 'liquidate':
            liquidate_investment(current_customer_id(), $orderId, $planId);
            break;
        case 'buy_more':
            // Redirect to investment-details for the purchase flow
            redirect('index.php?route=investment-details&id=' . $planId);
            break;
        default:
            flash('error', 'Invalid action for owned investment.');
            redirect('index.php?route=orders');
            break;
    }
}

function sell_investment(int $customerId, string $orderId, int $planId): void
{
    $order = &assert_order_owner($customerId, $orderId);
    if ($order === null || ($order['status'] ?? '') !== 'Active') {
        flash('error', 'Could not sell investment. Order not found or not active.');
        redirect('index.php?route=owned-investment-details&id=' . $planId);
    }

    // Simulate selling the investment
    $sellAmount = (float) ($order['amount'] ?? 0) + (float) ($order['profit'] ?? 0); // Purchase amount + generated profit
    $wallet = &customer_wallet($customerId);
    $oldAvailable = (float) $wallet['available'];

    $wallet['available'] += $sellAmount;
    $wallet['investment'] -= (float) ($order['amount'] ?? 0); // Reduce total invested balance
    $wallet['withdrawable'] += $sellAmount;

    $order['status'] = 'Sold';
    $order['completion_date'] = date('Y-m-d'); // Mark as completed today

    add_transaction('Investment Sale', 'Credit', $sellAmount, $oldAvailable, (float) $wallet['available'], 'Completed', 'Sale of ' . ($order['plan'] ?? ''));
    add_notification('Investment Sold', ($order['plan'] ?? 'Asset') . ' has been sold and funds credited.', 'Investment');
    record_activity('user', 'Sold investment ' . ($order['plan'] ?? '') . ' (ID: ' . $orderId . ')');
    recalculate_customer_metrics($customerId);
    flash('success', 'Investment sold successfully. Funds credited to your wallet.');
    redirect('index.php?route=owned-investment-details&id=' . $planId);
}

function liquidate_investment(int $customerId, string $orderId, int $planId): void
{
    $order = &assert_order_owner($customerId, $orderId);
    if ($order === null || ($order['status'] ?? '') !== 'Active') {
        flash('error', 'Could not liquidate investment. Order not found or not active.');
        redirect('index.php?route=owned-investment-details&id=' . $planId);
    }

    // Simulate full liquidation (return only original investment amount)
    $liquidationAmount = (float) ($order['amount'] ?? 0);
    $wallet = &customer_wallet($customerId);
    $oldAvailable = (float) $wallet['available'];

    $wallet['available'] += $liquidationAmount;
    $wallet['investment'] -= (float) ($order['amount'] ?? 0);
    $wallet['withdrawable'] += $liquidationAmount;

    $order['status'] = 'Liquidated';
    $order['completion_date'] = date('Y-m-d');

    add_transaction('Investment Liquidation', 'Credit', $liquidationAmount, $oldAvailable, (float) $wallet['available'], 'Completed', 'Liquidation of ' . ($order['plan'] ?? ''));
    add_notification('Investment Liquidated', ($order['plan'] ?? 'Asset') . ' has been liquidated. Original investment amount returned.', 'Investment');
    record_activity('user', 'Liquidated investment ' . ($order['plan'] ?? '') . ' (ID: ' . $orderId . ')');
    recalculate_customer_metrics($customerId);
    flash('success', 'Investment liquidated. Funds credited to your wallet.');
    redirect('index.php?route=owned-investment-details&id=' . $planId);
}

function mark_notification_read(int $index): void
{
    $notifications = &customer(current_customer_id())['notifications'];
    if (isset($notifications[$index])) {
        $notifications[$index]['read'] = true;
    }
}

function mark_notification_unread(int $index): void
{
    $notifications = &customer(current_customer_id())['notifications'];
    if (isset($notifications[$index])) {
        $notifications[$index]['read'] = false;
    }
}

function mark_all_notifications_read(): void
{
    $notifications = &customer(current_customer_id())['notifications'];
    foreach ($notifications as &$notification) {
        $notification['read'] = true;
    }
    unset($notification);
}

function handle_notification_action(array $post): void
{
    $action = $post['action'] ?? '';

    switch ($action) {
        case 'mark_read':
            mark_notification_read((int) ($post['index'] ?? -1));
            break;
        case 'mark_unread':
            mark_notification_unread((int) ($post['index'] ?? -1));
            break;
        case 'mark_all_read':
            mark_all_notifications_read();
            flash('success', 'All notifications marked as read.');
            break;
        default:
            flash('error', 'Invalid notification action.');
            break;
    }

    redirect('index.php?route=notifications');
}

function redeem_reward(array $post): void
{
    $code = strtoupper(trim($post['code'] ?? ''));
    $reward = current(array_filter($_SESSION['platform']['rewards_catalog'], fn($row) => $row['code'] === $code && $row['status'] === 'Available'));

    if (!$reward) {
        flash('error', 'Reward code is invalid, locked, or already used.');
        redirect('index.php?route=rewards');
    }

    $id = current_customer_id();
    $customer = &customer($id);
    if (in_array($code, $customer['claimed_rewards'] ?? [], true)) {
        flash('error', 'You have already claimed this reward.');
        redirect('index.php?route=rewards');
    }

    $wallet = &customer_wallet($id);
    $old = (float) $wallet['available'];
    $wallet['available'] += $reward['amount'];
    $wallet['bonus'] += $reward['amount'];
    $wallet['withdrawable'] += $reward['amount'];

    foreach ($_SESSION['platform']['rewards_catalog'] as &$row) {
        if ($row['code'] === $code) {
            $row['status'] = 'Claimed';
        }
    }
    unset($row);

    $customer['claimed_rewards'][] = $code;
    add_transaction('Reward Redemption', 'Credit', $reward['amount'], $old, (float) $wallet['available'], 'Completed', $reward['title']);
    add_notification('Reward claimed', $reward['title'] . ' has been credited.', 'Rewards');
    recalculate_customer_metrics($id);
    flash('success', 'Reward credited to your wallet.');
    redirect('index.php?route=rewards');
}

function create_ticket(array $post): void
{
    $subject = trim($post['subject'] ?? '');
    if ($subject === '') {
        flash('error', 'Enter a support subject.');
        redirect('index.php?route=support');
    }

    $id = current_customer_id();
    $customer = &customer($id);
    $customer['tickets'][] = [
        'id' => 'SUP-' . random_int(3000, 9999),
        'user_id' => $id,
        'subject' => $subject,
        'status' => 'Open',
        'priority' => $post['priority'] ?? 'Normal',
        'updated' => date('Y-m-d H:i'),
    ];
    add_notification('Support ticket opened', 'Support will review your request.', 'System');
    flash('success', 'Support ticket created.');
    redirect('index.php?route=support');
}

function update_profile(array $post): void
{
    $id = current_customer_id();
    $customer = &customer($id);
    foreach (['full_name', 'email', 'phone', 'country', 'city'] as $field) {
        $customer[$field] = trim($post[$field] ?? $customer[$field]);
    }
    // Keep the shared "current user" pointer in sync for the logged-in session.
    $_SESSION['platform']['user'] = $customer;
    flash('success', 'Profile updated.');
    redirect('index.php?route=profile');
}

function update_settings(array $post): void
{
    $_SESSION['platform']['preferences'] = [
        'language' => $post['language'] ?? 'English',
        'notifications' => isset($post['notifications']) ? 'Enabled' : 'Disabled',
        'privacy' => $post['privacy'] ?? 'Standard',
    ];
    flash('success', 'Settings saved.');
    redirect('index.php?route=settings');
}

function bind_bank(array $post): void
{
    $id = current_customer_id();
    $customer = &customer($id);
    $customer['bank'] = [
        'holder' => trim($post['holder'] ?? ''),
        'account' => trim($post['account'] ?? ''),
        'method' => trim($post['method'] ?? ''),
    ];
    flash('success', 'Bank details saved for withdrawal processing.');
    redirect('index.php?route=bank');
}

function handle_admin_action(string $route, array $post): void
{
    match ($post['action'] ?? '') {
        'approve_deposit' => approve_deposit($post['id'] ?? '', $post['notes'] ?? ''),
        'reject_deposit' => reject_deposit($post['id'] ?? '', $post['notes'] ?? ''),
        'approve_withdrawal' => approve_withdrawal($post['id'] ?? ''),
        'reject_withdrawal' => reject_withdrawal($post['id'] ?? ''),
        'toggle_user' => toggle_user_status(),
        'wallet_adjustment' => apply_wallet_adjustment($post),
        'save_admin_note' => save_admin_note($post),
        'save_plan' => save_investment_plan($post),
        'duplicate_plan' => duplicate_investment_plan((int) ($post['plan_id'] ?? 0)),
        'change_plan_status' => change_plan_status((int) ($post['plan_id'] ?? 0), $post['status'] ?? 'Open'),
        'delete_plan' => delete_investment_plan((int) ($post['plan_id'] ?? 0)),
        'save_site_settings' => save_site_settings($post),
        'save_crypto_wallets' => save_crypto_wallets($post),
        default => null,
    };
}

function approve_deposit(string $id, string $notes = ''): void
{
    $customerId = managed_customer_id();
    $deposit = &assert_deposit_owner($customerId, $id);
    if ($deposit === null || !in_array($deposit['status'], ['Pending', 'Awaiting Payment', 'Pending Review'], true)) {
        flash('warning', 'Deposit is not pending or does not exist.');
        redirect('index.php?route=deposits');
    }

    $wallet = &customer_wallet($customerId);
    $amount = (float) ($deposit['amount'] ?? 0);
    $old = (float) $wallet['available'];
    if ($amount > 0) {
        $wallet['available'] += $amount;
        $wallet['withdrawable'] = max(0, (float) $wallet['withdrawable'] + $amount);
    }
    $deposit['status'] = 'Approved';
    $deposit['reviewer'] = admin_name();
    $deposit['reviewed_at'] = date('Y-m-d H:i:s');
    $deposit['updated_at'] = $deposit['reviewed_at'];
    if ($notes !== '') {
        $deposit['notes'] = $notes;
    }
    $label = $deposit['crypto'] ?? 'Deposit';
    add_transaction('Deposit', 'Credit', $amount, $old, (float) $wallet['available'], 'Approved', ($deposit['method'] ?? $label) . ' deposit approved');
    add_notification('Deposit approved', 'Your ' . $label . ' deposit has been credited to your wallet.', 'Financial');
    record_activity('admin', 'Approved deposit ' . $id);
    audit_log('Approved deposit', 'Deposit', $id, 'Pending', 'Approved');
    recalculate_customer_metrics($customerId);
    flash('success', 'Deposit approved and wallet credited.');
    redirect('index.php?route=deposits');
}

function reject_deposit(string $id, string $notes = ''): void
{
    $customerId = managed_customer_id();
    $deposit = &assert_deposit_owner($customerId, $id);
    if ($deposit === null || !in_array($deposit['status'], ['Pending', 'Awaiting Payment', 'Pending Review'], true)) {
        flash('warning', 'Deposit is not pending or does not exist.');
        redirect('index.php?route=deposits');
    }

    $deposit['status'] = 'Rejected';
    $deposit['reviewer'] = admin_name();
    $deposit['reviewed_at'] = date('Y-m-d H:i:s');
    $deposit['updated_at'] = $deposit['reviewed_at'];
    $deposit['notes'] = $notes;
    $label = $deposit['crypto'] ?? 'Deposit';
    add_notification('Deposit rejected', 'Your ' . $label . ' deposit was rejected.' . ($notes !== '' ? ' Note: ' . $notes : ''), 'Financial');
    record_activity('admin', 'Rejected deposit ' . $id);
    audit_log('Rejected deposit', 'Deposit', $id, 'Pending', 'Rejected');
    flash('success', 'Deposit rejected.');
    redirect('index.php?route=deposits');
}

function approve_withdrawal(string $id): void
{
    $customerId = managed_customer_id();
    $withdrawal = &assert_withdrawal_owner($customerId, $id);
    if ($withdrawal === null || ($withdrawal['status'] ?? '') !== 'Pending') {
        flash('warning', 'Withdrawal is not pending or does not exist.');
        redirect('index.php?route=withdrawals');
    }

    $wallet = &customer_wallet($customerId);
    $oldLocked = (float) ($wallet['locked'] ?? 0);
    $wallet['locked'] = max(0, $oldLocked - (float) $withdrawal['amount']);
    $withdrawal['status'] = 'Approved';
    $withdrawal['reviewer'] = admin_name();
    $withdrawal['reviewed_at'] = date('Y-m-d H:i:s');
    add_notification('Withdrawal approved', 'Your withdrawal has been processed.', 'Financial');
    record_activity('admin', 'Approved withdrawal ' . $id);
    audit_log('Approved withdrawal', 'Withdrawal', $id, 'Pending', 'Approved');
    recalculate_customer_metrics($customerId);
    flash('success', 'Withdrawal approved.');
    redirect('index.php?route=withdrawals');
}

function reject_withdrawal(string $id): void
{
    $customerId = managed_customer_id();
    $withdrawal = &assert_withdrawal_owner($customerId, $id);
    if ($withdrawal === null || ($withdrawal['status'] ?? '') !== 'Pending') {
        flash('warning', 'Withdrawal is not pending or does not exist.');
        redirect('index.php?route=withdrawals');
    }

    $wallet = &customer_wallet($customerId);
    $amount = (float) $withdrawal['amount'];
    $oldAvailable = (float) $wallet['available'];
    // Return the locked funds to the customer's available/withdrawable balance.
    $wallet['available'] += $amount;
    $wallet['withdrawable'] = max(0, (float) $wallet['withdrawable'] + $amount);
    $wallet['locked'] = max(0, (float) $wallet['locked'] - $amount);
    $withdrawal['status'] = 'Rejected';
    $withdrawal['reviewer'] = admin_name();
    $withdrawal['reviewed_at'] = date('Y-m-d H:i:s');
    add_notification('Withdrawal rejected', 'Your withdrawal request was rejected and funds returned.', 'Financial');
    record_activity('admin', 'Rejected withdrawal ' . $id);
    audit_log('Rejected withdrawal', 'Withdrawal', $id, (string) $oldAvailable, (string) $wallet['available']);
    recalculate_customer_metrics($customerId);
    flash('success', 'Withdrawal rejected and funds returned.');
    redirect('index.php?route=withdrawals');
}

function toggle_user_status(): void
{
    $customerId = selected_customer_id();
    $customers = &$_SESSION['platform']['customers'];
    if (!isset($customers[$customerId])) {
        flash('warning', 'Selected customer does not exist.');
        redirect('index.php?route=users');
    }

    $newStatus = $customers[$customerId]['status'] === 'Active' ? 'Suspended' : 'Active';
    $oldStatus = $customers[$customerId]['status'];
    $customers[$customerId]['status'] = $newStatus;

    // Keep the demo "current user" mirror in sync when it is the managed account.
    if ((int) ($_SESSION['platform']['user']['id'] ?? 0) === $customerId) {
        $_SESSION['platform']['user']['status'] = $newStatus;
    }

    record_activity('admin', admin_name() . ' changed user account status to ' . $newStatus);
    audit_log('Changed account status', 'Customer', (string) $customerId, $oldStatus, $newStatus);
    flash('success', 'User status updated.');
    redirect('index.php?route=users');
}

function wallet_label(string $field): string
{
    return ucwords(str_replace('_', ' ', $field));
}

function apply_wallet_adjustment(array $post): void
{
    $operation = $post['operation'] ?? '';
    $amount = max(0, (float) ($post['amount'] ?? 0));
    $reason = trim($post['reason'] ?? 'Administrative wallet adjustment');
    $customerId = managed_customer_id();
    $managed = get_customer($customerId);
    $wallet = &customer_wallet($customerId);
    $field = null;
    $delta = 0.0;
    $type = 'Wallet Adjustment';
    $category = 'Adjustment';

    $map = [
        'increase_available' => ['available', 1, 'Available Balance Increase', 'Credit'],
        'decrease_available' => ['available', -1, 'Available Balance Decrease', 'Debit'],
        'increase_bonus' => ['bonus', 1, 'Bonus Balance Increase', 'Credit'],
        'decrease_bonus' => ['bonus', -1, 'Bonus Balance Decrease', 'Debit'],
        'lock_funds' => ['locked', 1, 'Funds Locked', 'Debit'],
        'unlock_funds' => ['locked', -1, 'Funds Unlocked', 'Credit'],
        'increase_investment' => ['investment', 1, 'Investment Balance Increase', 'Credit'],
        'decrease_investment' => ['investment', -1, 'Investment Balance Decrease', 'Debit'],
        'increase_referral' => ['referral', 1, 'Referral Earnings Increase', 'Credit'],
        'decrease_referral' => ['referral', -1, 'Referral Earnings Decrease', 'Debit'],
        'correct_available' => ['available', 0, 'Available Balance Correction', 'Adjustment'],
    ];

    if ($operation === 'freeze_wallet' || $operation === 'unfreeze_wallet') {
        $wallet['frozen'] = $operation === 'freeze_wallet';
        add_transaction($operation === 'freeze_wallet' ? 'Wallet Frozen' : 'Wallet Unfrozen', 'Adjustment', 0, 0, 0, 'Completed', $reason);
        add_notification('Wallet status updated', 'An administrator updated your wallet access status.', 'Financial');
        record_activity('admin', admin_name() . ' ' . strtolower(str_replace('_', ' ', $operation)) . ' for ' . managed_customer()['username']);
        audit_log(ucfirst(strtolower(str_replace('_', ' ', $operation))), 'Wallet', managed_customer()['username']);
        recalculate_metrics();
        flash('success', 'Wallet status updated.');
        redirect('index.php?route=users');
    }

    if (!isset($map[$operation]) || $amount <= 0) {
        flash('error', 'Choose a valid wallet operation and amount.');
        redirect('index.php?route=users');
    }

    [$field, $direction, $type, $category] = $map[$operation];
    $old = (float) $wallet[$field];
    $new = $direction === 0 ? $amount : max(0, $old + ($amount * $direction));
    $delta = abs($new - $old);
    $wallet[$field] = $new;

    if ($field === 'available') {
        $wallet['withdrawable'] = max(0, $wallet['withdrawable'] + ($new - $old));
    }

    add_transaction($type, $category, $delta, $old, $new, 'Completed', $reason . ' by ' . admin_name());
    add_notification('Wallet adjusted', wallet_label($field) . ' was updated by operations.', 'Financial');
    record_activity('admin', admin_name() . ' updated ' . wallet_label($field) . ' for ' . managed_customer()['username'] . ': ' . $reason);
    audit_log('Adjusted wallet', 'Wallet', managed_customer()['username'], (string) $old, (string) $new);
    recalculate_metrics();
    flash('success', 'Wallet adjustment completed and recorded.');
    redirect('index.php?route=users');
}

function save_admin_note(array $post): void
{
    $note = trim($post['note'] ?? '');
    if ($note === '') {
        flash('error', 'Enter a note before saving.');
        redirect('index.php?route=users');
    }

    $customerId = managed_customer_id();
    $customers = &customer($customerId);
    $customers['admin_notes'][] = [
        'user_id' => $customerId,
        'note' => $note,
        'admin' => admin_name(),
        'date' => date('Y-m-d H:i:s'),
    ];
    record_activity('admin', admin_name() . ' added a customer note');
    audit_log('Added customer note', 'Customer', (string) selected_customer_id(), null, mb_strimwidth($note, 0, 60, '…'));
    flash('success', 'Administrative note saved.');
    redirect('index.php?route=users');
}

function save_investment_plan(array $post): void
{
    $id = (int) ($post['plan_id'] ?? 0);
    $plan = [
        'id' => $id > 0 ? $id : next_plan_id(),
        'name' => trim($post['name'] ?? ''),
        'symbol' => strtoupper(trim($post['symbol'] ?? '')),
        'category' => trim($post['category'] ?? 'Stock'),
        'description' => trim($post['description'] ?? ''),
        'image' => trim($post['image'] ?? 'aapl-logo'),
        'banner_image' => trim($post['banner_image'] ?? 'aapl-banner'),
        'ai_summary' => trim($post['ai_summary'] ?? ''),
        'key_points' => trim($post['key_points'] ?? ''),
        'risk_level' => trim($post['risk_level'] ?? 'Medium'),
        'price' => max(0, (float) ($post['price'] ?? 0)),
        'min' => max(0, (float) ($post['min'] ?? 0)),
        'max' => max(0, (float) ($post['max'] ?? 0)),
        'daily' => max(0, (float) ($post['daily'] ?? 0)),
        'daily_return' => max(0, (float) ($post['daily_return'] ?? 0)),
        'monthly_return' => max(0, (float) ($post['monthly_return'] ?? 0)),
        'roi' => max(0, (float) ($post['roi'] ?? 0)),
        'duration' => max(1, (int) ($post['duration'] ?? 30)),
        'lock_period' => max(0, (int) ($post['lock_period'] ?? 0)),
        'status' => $post['status'] ?? 'Open',
        'featured' => isset($post['featured']),
        'is_trending' => isset($post['is_trending']),
        'is_beginner_friendly' => isset($post['is_beginner_friendly']),
        'has_dividend' => isset($post['has_dividend']),
        'is_popular' => isset($post['is_popular']),
        'market_status' => $post['market_status'] ?? 'Open',
        'sort_order' => (int) ($post['sort_order'] ?? 100),
        'market_cap' => trim($post['market_cap'] ?? ''),
        'open' => max(0, (float) ($post['open'] ?? 0)),
        'high' => max(0, (float) ($post['high'] ?? 0)),
        'low' => max(0, (float) ($post['low'] ?? 0)),
    ];

    if ($plan['name'] === '' || $plan['min'] <= 0 || $plan['max'] < $plan['min']) {
        flash('error', 'Asset name and minimum/maximum values must be valid.');
        redirect('index.php?route=investments');
    }

    $updated = false;
    foreach ($_SESSION['platform']['plans'] as &$existing) {
        if ((int) $existing['id'] === $id) {
            $existing = $plan;
            $updated = true;
            break;
        }
    }

    if (!$updated) {
        $_SESSION['platform']['plans'][] = $plan;
    }

    usort($_SESSION['platform']['plans'], fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));
    record_activity('admin', admin_name() . ($updated ? ' updated ' : ' created ') . 'investment asset ' . $plan['name']);
    audit_log($updated ? 'Updated investment asset' : 'Created investment asset', 'Investment', (string) $plan['id'], null, $plan['name'] . ' (' . $plan['status'] . ')');
    flash('success', 'Investment asset saved.');
    redirect('index.php?route=investments');
}

function next_plan_id(): int
{
    return max(array_map(fn($plan) => (int) $plan['id'], $_SESSION['platform']['plans'])) + 1;
}

function duplicate_investment_plan(int $planId): void
{
    foreach ($_SESSION['platform']['plans'] as $plan) {
        if ((int) $plan['id'] === $planId) {
            $plan['id'] = next_plan_id();
            $plan['name'] .= ' Copy';
            $plan['status'] = 'Draft';
            $plan['sort_order'] = ($plan['sort_order'] ?? 0) + 1;
            $_SESSION['platform']['plans'][] = $plan;
            record_activity('admin', admin_name() . ' duplicated investment plan ' . $plan['name']);
            audit_log('Duplicated investment asset', 'Investment', (string) $plan['id'], null, $plan['name']);
            flash('success', 'Investment plan duplicated.');
            redirect('index.php?route=investments');
        }
    }
    flash('warning', 'Plan not found.');
    redirect('index.php?route=investments');
}

function change_plan_status(int $planId, string $status): void
{
    foreach ($_SESSION['platform']['plans'] as &$plan) {
        if ((int) $plan['id'] === $planId) {
            $oldStatus = $plan['status'];
            $plan['status'] = $status;
            record_activity('admin', admin_name() . ' changed plan status to ' . $status);
            audit_log('Changed investment status', 'Investment', (string) $planId, $oldStatus, $status);
            flash('success', 'Investment plan status updated.');
            redirect('index.php?route=investments');
        }
    }
    flash('warning', 'Plan not found.');
    redirect('index.php?route=investments');
}

function delete_investment_plan(int $planId): void
{
    foreach ($_SESSION['platform']['plans'] as $index => $plan) {
        if ((int) $plan['id'] === $planId) {
            unset($_SESSION['platform']['plans'][$index]);
            $_SESSION['platform']['plans'] = array_values($_SESSION['platform']['plans']);
            record_activity('admin', admin_name() . ' deleted investment plan ' . $plan['name']);
            audit_log('Deleted investment asset', 'Investment', (string) $planId, $plan['name'], null);
            flash('success', 'Investment plan deleted.');
            redirect('index.php?route=investments');
        }
    }
    flash('warning', 'Plan not found.');
    redirect('index.php?route=investments');
}

function save_site_settings(array $post): void
{
    foreach (['site_name', 'registration', 'maintenance', 'min_deposit', 'max_deposit', 'min_withdrawal', 'fee'] as $field) {
        $_SESSION['platform']['settings'][$field] = $post[$field] ?? $_SESSION['platform']['settings'][$field] ?? '';
    }
    $settings = &$_SESSION['platform']['settings'];
    record_activity('admin', 'Website settings updated');
    audit_log('Updated website settings', 'Settings', 'platform', null, 'registration=' . ($settings['registration'] ?? '') . ', maintenance=' . ($settings['maintenance'] ?? ''));
    flash('success', 'Website settings saved.');
    redirect('index.php?route=settings');
}
