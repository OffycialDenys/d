<?php
/**
 * Build a single customer record. Each customer owns a completely isolated
 * set of financial sub-records (wallet, orders, transactions, deposits,
 * withdrawals, notifications, tickets, bank, admin notes, activity). This is
 * what makes multi-tenancy real: there is no longer one shared "global" wallet
 * that every registered user overwrites.
 */
function build_customer(array $profile, array $wallet, array $orders, array $transactions, array $deposits, array $withdrawals, array $notifications, array $tickets, array $bank, array $adminNotes): array
{
    return array_merge($profile, [
        'wallet' => $wallet,
        'orders' => $orders,
        'transactions' => $transactions,
        'deposits' => $deposits,
        'withdrawals' => $withdrawals,
        'notifications' => $notifications,
        'tickets' => $tickets,
        'bank' => $bank,
        'claimed_rewards' => [],
        'activities' => [],
        'admin_notes' => $adminNotes,
    ]);
}

function seed_platform_data(): array
{
    $demoWallet = [
        'available' => 300.00,
        'locked' => 0.00,
        'bonus' => 0.00,
        'referral' => 0.00,
        'investment' => 150.00,
        'pending' => 0.00,
        'withdrawable' => 300.00,
        'frozen' => false,
    ];

    $demoOrders = [
        [
            'id' => 'ORD-2401',
            'plan' => 'Apple Inc.',
            'symbol' => 'AAPL',
            'amount' => 150.00,
            'profit' => 2.70,
            'progress' => 40,
            'status' => 'Active',
            'user_id' => 1221,
            'purchase_date' => date('Y-m-d', strtotime('-12 days')),
            'completion_date' => date('Y-m-d', strtotime('+18 days')),
            'expected' => 156.75,
            'net_profit' => 6.75,
            'image' => 'aapl-logo',
        ],
    ];

    $demoTransactions = [
        ['id' => 'TXN-1001', 'type' => 'Investment Purchase', 'category' => 'Debit', 'amount' => 150.00, 'old' => 450.00, 'new' => 300.00, 'status' => 'Completed', 'date' => date('Y-m-d H:i', strtotime('-12 days')), 'description' => 'Apple Inc. purchase'],
        ['id' => 'TXN-1000', 'type' => 'Deposit', 'category' => 'Credit', 'amount' => 450.00, 'old' => 0.00, 'new' => 450.00, 'status' => 'Approved', 'date' => date('Y-m-d H:i', strtotime('-13 days')), 'description' => 'USDT TRC20 recharge'],
    ];

    $demoDeposits = [
        ['id' => 'DEP-9001', 'user_id' => 1221, 'amount' => 450.00, 'method' => 'USDT(TRC20)', 'reference' => 'TRC-DEMO-9001', 'status' => 'Approved', 'date' => date('Y-m-d H:i', strtotime('-13 days'))],
    ];

    $demoNotifications = [
        ['title' => 'Investment active', 'message' => 'Apple Inc. is generating daily earnings.', 'type' => 'Investment', 'read' => false, 'date' => date('Y-m-d H:i')],
        ['title' => 'Deposit approved', 'message' => 'Your USDT(TRC20) recharge was approved.', 'type' => 'Financial', 'read' => true, 'date' => date('Y-m-d H:i', strtotime('-13 days'))],
    ];

    $demoTickets = [
        ['id' => 'SUP-2101', 'subject' => 'Withdrawal verification', 'status' => 'Open', 'priority' => 'Normal', 'updated' => date('Y-m-d H:i', strtotime('-2 days'))],
    ];

    $demoBank = [
        'holder' => '',
        'account' => '',
        'method' => '',
    ];

    $demoAdminNotes = [
        ['user_id' => 1221, 'note' => 'Account reviewed during onboarding.', 'admin' => 'Platform Admin', 'date' => date('Y-m-d H:i:s', strtotime('-7 days'))],
    ];

    $apex = build_customer([
        'id' => 1221,
        'username' => 'Apex',
        'full_name' => 'Apex Investor',
        'email' => 'apex@example.com',
        'phone' => '7043340662',
        'country' => 'Nigeria',
        'city' => 'Lagos',
        'membership' => 'Pro Account',
        'status' => 'Active',
        'referral_code' => 'zn911yr229',
        'sponsor' => 'Nivaro Partner Desk',
        'last_login' => date('Y-m-d H:i:s'),
    ], $demoWallet, $demoOrders, $demoTransactions, $demoDeposits, [], $demoNotifications, $demoTickets, $demoBank, $demoAdminNotes);

    // A second seeded customer proves the data is isolated per-account.
    $novaWallet = [
        'available' => 120.00,
        'locked' => 0.00,
        'bonus' => 0.00,
        'referral' => 0.00,
        'investment' => 80.00,
        'pending' => 0.00,
        'withdrawable' => 120.00,
        'frozen' => false,
    ];

    $nova = build_customer([
        'id' => 1222,
        'username' => 'Nova',
        'full_name' => 'Nova Trader',
        'email' => 'nova@example.com',
        'phone' => '8012233445',
        'country' => 'Ghana',
        'city' => 'Accra',
        'membership' => 'Starter Account',
        'status' => 'Active',
        'referral_code' => 'nv552yk001',
        'sponsor' => 'Nivaro Partner Desk',
        'last_login' => date('Y-m-d H:i:s'),
    ], $novaWallet, [], [], [], [], [], [], $demoBank, []);

    return [
        'customers' => [
            1221 => $apex,
            1222 => $nova,
        ],
        'plans' => seed_investment_plans(),
        'referral_levels' => [
            ['level' => 1, 'name' => 'Direct Referrals', 'size' => 0, 'earn' => 0.00, 'rate' => 11],
            ['level' => 2, 'name' => 'Indirect Referrals', 'size' => 0, 'earn' => 0.00, 'rate' => 2],
            ['level' => 3, 'name' => 'Sub-Indirect Referrals', 'size' => 0, 'earn' => 0.00, 'rate' => 1],
        ],
        'rewards_catalog' => [
            ['code' => 'WELCOME50', 'title' => 'Welcome Bonus', 'amount' => 5.00, 'status' => 'Available', 'expires' => date('Y-m-d', strtotime('+30 days'))],
            ['code' => 'TEAMBOOST', 'title' => 'Team Growth Bonus', 'amount' => 15.00, 'status' => 'Locked', 'expires' => date('Y-m-d', strtotime('+45 days'))],
        ],
        'notifications' => [],
        'tickets' => [],
        'activities' => [],
        'admin_logs' => [],
        'settings' => [
            'site_name' => 'Nivaro Capital',
            'registration' => 'Enabled',
            'maintenance' => 'Disabled',
            'min_deposit' => 10,
            'max_deposit' => 5000,
            'min_withdrawal' => 10,
            'fee' => 1.5,
        ],
        'crypto_wallets' => default_crypto_wallets(),
        'metrics' => [],
    ];
}

function seed_investment_plans(): array
{
    return [
        [
            'id' => 1,
            'name' => 'Apple Inc.',
            'symbol' => 'AAPL',
            'category' => 'Stock',
            'description' => 'A global leader in consumer electronics, mobile communications, and software services.',
            'image' => 'aapl-logo',
            'banner_image' => 'aapl-banner',
            'ai_summary' => 'NVIDIA and Apple continue to dominate key indices. AAPL is projected to show stable growth of 4.5% over the next 30 days, backed by solid service-revenue growth.',
            'key_points' => "Steady institutional demand\nPremium consumer ecosystem\nExpanding services margin",
            'risk_level' => 'Low',
            'price' => 189.50,
            'min' => 50.00,
            'max' => 10000.00,
            'daily' => 0.28,
            'daily_return' => 0.15,
            'monthly_return' => 4.50,
            'roi' => 104.50,
            'duration' => 30,
            'lock_period' => 15,
            'status' => 'Open',
            'featured' => true,
            'is_trending' => true,
            'is_beginner_friendly' => true,
            'has_dividend' => true,
            'is_popular' => true,
            'market_status' => 'Open',
            'sort_order' => 10,
            'market_cap' => '1.91T',
            'open' => 149.99,
            'high' => 150.56,
            'low' => 145.11
        ],
        [
            'id' => 2,
            'name' => 'Bitcoin',
            'symbol' => 'BTC',
            'category' => 'Cryptocurrency',
            'description' => 'The first and largest decentralized digital asset, serving as digital gold and a store of value.',
            'image' => 'btc-logo',
            'banner_image' => 'btc-banner',
            'ai_summary' => 'Cryptocurrency markets are showing high momentum. BTC has strong support at $60k. Our AI forecasts a potential 12.5% return for the current lock period, with elevated volatility.',
            'key_points' => "High institutional ETF inflows\nHalving supply shock dynamics\nGlobal liquid macro hedge",
            'risk_level' => 'High',
            'price' => 64250.00,
            'min' => 100.00,
            'max' => 50000.00,
            'daily' => 2.67,
            'daily_return' => 0.42,
            'monthly_return' => 12.50,
            'roi' => 112.50,
            'duration' => 30,
            'lock_period' => 30,
            'status' => 'Open',
            'featured' => true,
            'is_trending' => true,
            'is_beginner_friendly' => false,
            'has_dividend' => false,
            'is_popular' => true,
            'market_status' => 'Open',
            'sort_order' => 20,
            'market_cap' => '1.27T',
            'open' => 64100.00,
            'high' => 65200.00,
            'low' => 63800.00
        ],
        [
            'id' => 3,
            'name' => 'NVIDIA Corporation',
            'symbol' => 'NVDA',
            'category' => 'Stock',
            'description' => 'Pioneer of GPU-accelerated computing, dominating AI chip manufacturing and enterprise hardware.',
            'image' => 'nvda-logo',
            'banner_image' => 'nvda-banner',
            'ai_summary' => 'AI infrastructure spending remains at historic highs. NVDA is the primary beneficiary. Expect high volatility but high returns, projected at 18.0% over the next 45 days.',
            'key_points' => "Near-monopoly in AI chips\nUnprecedented data center demand\nStrong cash flow generation",
            'risk_level' => 'Medium',
            'price' => 127.40,
            'min' => 150.00,
            'max' => 25000.00,
            'daily' => 0.51,
            'daily_return' => 0.40,
            'monthly_return' => 12.00,
            'roi' => 118.00,
            'duration' => 45,
            'lock_period' => 20,
            'status' => 'Open',
            'featured' => true,
            'is_trending' => false,
            'is_beginner_friendly' => false,
            'has_dividend' => true,
            'is_popular' => true,
            'market_status' => 'Open',
            'sort_order' => 30,
            'market_cap' => '3.12T',
            'open' => 126.80,
            'high' => 129.10,
            'low' => 125.40
        ],
        [
            'id' => 4,
            'name' => 'S&P 500 ETF',
            'symbol' => 'SPY',
            'category' => 'ETF',
            'description' => 'Exchange Traded Fund tracking the 500 largest US publicly traded companies by market capitalization.',
            'image' => 'spy-logo',
            'banner_image' => 'spy-banner',
            'ai_summary' => 'Broad-market exposure through SPY represents the lowest risk profile. AI recommends SPY as a foundational portfolio holding with an expected 2.8% monthly return.',
            'key_points' => "Instant diversification\nTracks the health of the US economy\nHighly liquid options chain",
            'risk_level' => 'Low',
            'price' => 542.10,
            'min' => 20.00,
            'max' => 50000.00,
            'daily' => 0.05,
            'daily_return' => 0.09,
            'monthly_return' => 2.80,
            'roi' => 102.80,
            'duration' => 30,
            'lock_period' => 5,
            'status' => 'Open',
            'featured' => false,
            'is_trending' => false,
            'is_beginner_friendly' => true,
            'has_dividend' => true,
            'is_popular' => false,
            'market_status' => 'Open',
            'sort_order' => 40,
            'market_cap' => '542.10B',
            'open' => 540.20,
            'high' => 544.80,
            'low' => 539.10
        ],
    ];
}

/**
 * Canonical list of supported cryptocurrencies and their deposit addresses.
 * The "address" values are demo placeholders; administrators can rotate them
 * at any time from the admin Payment Settings screen, and the change is the
 * single source of truth consumed by the deposit workflow.
 */
function default_crypto_wallets(): array
{
    return [
        'BTC' => [
            'symbol' => 'BTC',
            'name' => 'Bitcoin',
            'network' => 'Bitcoin',
            'icon' => '₿',
            'address' => 'bc1qexamplebtcwalletaddressfornivarocapital000',
            'description' => 'Send only Bitcoin (BTC) over the native Bitcoin network.',
            'min' => 0.0005,
        ],
        'USDT' => [
            'symbol' => 'USDT',
            'name' => 'Tether',
            'network' => 'TRC20',
            'icon' => '₮',
            'address' => 'TQn9Y2khEsLJW1j9xkfqP7U7bL3nV6mZqX',
            'description' => 'Send only Tether (USDT) over the TRC20 (Tron) network.',
            'min' => 10.00,
        ],
        'USDC' => [
            'symbol' => 'USDC',
            'name' => 'USD Coin',
            'network' => 'ERC20',
            'icon' => '$',
            'address' => '0x4E8337d43c741C4e8613bE6f260C4D9Cff1d7E2B',
            'description' => 'Send only USD Coin (USDC) over the ERC20 (Ethereum) network.',
            'min' => 10.00,
        ],
    ];
}
