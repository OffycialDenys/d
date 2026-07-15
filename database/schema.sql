CREATE DATABASE IF NOT EXISTS investment_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE investment_platform;

CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(255) NULL
);

CREATE TABLE admin_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active','inactive','locked','disabled') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_admin_role FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    full_name VARCHAR(160) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    phone VARCHAR(40) NULL,
    password_hash VARCHAR(255) NOT NULL,
    referral_code VARCHAR(40) NOT NULL UNIQUE,
    sponsor_id BIGINT UNSIGNED NULL,
    profile_image VARCHAR(255) NULL,
    status ENUM('active','inactive','suspended','locked','disabled','pending_approval','banned','archived') NOT NULL DEFAULT 'active',
    verification_status ENUM('unverified','pending','verified','rejected') NOT NULL DEFAULT 'unverified',
    membership_level VARCHAR(80) NOT NULL DEFAULT 'Starter',
    account_type VARCHAR(80) NOT NULL DEFAULT 'customer',
    country VARCHAR(100) NULL,
    city VARCHAR(100) NULL,
    timezone VARCHAR(80) NULL,
    preferred_language VARCHAR(20) NOT NULL DEFAULT 'en',
    admin_notes TEXT NULL,
    deleted_at DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    INDEX idx_users_sponsor (sponsor_id),
    CONSTRAINT fk_users_sponsor FOREIGN KEY (sponsor_id) REFERENCES users(id)
);

CREATE TABLE user_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    biography TEXT NULL,
    payment_information TEXT NULL,
    notification_preferences JSON NULL,
    privacy_preferences JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE wallets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    available_balance DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    locked_balance DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    bonus_balance DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    referral_balance DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    investment_balance DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    pending_balance DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    withdrawable_balance DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    frozen_at DATETIME NULL,
    frozen_by BIGINT UNSIGNED NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_wallets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_wallets_frozen_by FOREIGN KEY (frozen_by) REFERENCES admin_users(id)
);

CREATE TABLE wallet_adjustments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    admin_user_id BIGINT UNSIGNED NOT NULL,
    wallet_field VARCHAR(80) NOT NULL,
    operation_type VARCHAR(120) NOT NULL,
    amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    old_value DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    new_value DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    reason TEXT NOT NULL,
    transaction_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_wallet_adjustments_user_created (user_id, created_at),
    CONSTRAINT fk_adjustments_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_adjustments_admin FOREIGN KEY (admin_user_id) REFERENCES admin_users(id)
);

CREATE TABLE investment_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(140) NOT NULL,
    symbol VARCHAR(20) NOT NULL DEFAULT '',
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    image VARCHAR(255) NULL,
    banner_image VARCHAR(255) NULL,
    ai_summary TEXT NULL,
    key_points TEXT NULL,
    risk_level VARCHAR(40) NOT NULL DEFAULT 'Medium',
    investment_price DECIMAL(18,2) NOT NULL,
    minimum_investment DECIMAL(18,2) NOT NULL,
    maximum_investment DECIMAL(18,2) NOT NULL,
    roi_percentage DECIMAL(8,2) NOT NULL,
    daily_profit DECIMAL(18,2) NOT NULL,
    weekly_profit DECIMAL(18,2) NULL,
    monthly_profit DECIMAL(18,2) NULL,
    daily_return DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    monthly_return DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    duration_days INT UNSIGNED NOT NULL,
    lock_period INT UNSIGNED NOT NULL DEFAULT 0,
    visibility ENUM('public','private') NOT NULL DEFAULT 'public',
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_trending TINYINT(1) NOT NULL DEFAULT 0,
    is_beginner_friendly TINYINT(1) NOT NULL DEFAULT 0,
    has_dividend TINYINT(1) NOT NULL DEFAULT 0,
    is_popular TINYINT(1) NOT NULL DEFAULT 0,
    market_status ENUM('Open','Closed') NOT NULL DEFAULT 'Open',
    sort_order INT NOT NULL DEFAULT 0,
    status ENUM('active','disabled','archived') NOT NULL DEFAULT 'active',
    admin_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL
);

CREATE TABLE investment_plan_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_id BIGINT UNSIGNED NOT NULL,
    admin_user_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(80) NOT NULL,
    snapshot JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_plan_versions_plan FOREIGN KEY (plan_id) REFERENCES investment_plans(id) ON DELETE CASCADE,
    CONSTRAINT fk_plan_versions_admin FOREIGN KEY (admin_user_id) REFERENCES admin_users(id)
);

CREATE TABLE investments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    purchase_amount DECIMAL(18,2) NOT NULL,
    purchase_date DATETIME NOT NULL,
    activation_date DATETIME NULL,
    completion_date DATETIME NULL,
    current_earnings DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    expected_earnings DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    remaining_days INT UNSIGNED NOT NULL DEFAULT 0,
    progress_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending','active','paused','completed','cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    INDEX idx_investments_user_status (user_id, status),
    CONSTRAINT fk_investments_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_investments_plan FOREIGN KEY (plan_id) REFERENCES investment_plans(id)
);

CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(40) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NOT NULL,
    investment_id BIGINT UNSIGNED NOT NULL,
    order_date DATETIME NOT NULL,
    amount DECIMAL(18,2) NOT NULL,
    current_status ENUM('pending','active','paused','completed','cancelled') NOT NULL DEFAULT 'pending',
    progress DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    profit_generated DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    admin_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_orders_investment FOREIGN KEY (investment_id) REFERENCES investments(id)
);

CREATE TABLE transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_number VARCHAR(40) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NOT NULL,
    wallet_id BIGINT UNSIGNED NOT NULL,
    category ENUM('credit','debit','adjustment') NOT NULL,
    operation_type VARCHAR(80) NOT NULL,
    amount DECIMAL(18,2) NOT NULL,
    old_balance DECIMAL(18,2) NOT NULL,
    new_balance DECIMAL(18,2) NOT NULL,
    status ENUM('pending','approved','rejected','completed','reversed') NOT NULL,
    reference VARCHAR(120) NULL,
    description VARCHAR(255) NULL,
    user_notes TEXT NULL,
    admin_notes TEXT NULL,
    associated_module VARCHAR(80) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_transactions_user_created (user_id, created_at),
    CONSTRAINT fk_transactions_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_transactions_wallet FOREIGN KEY (wallet_id) REFERENCES wallets(id)
);

CREATE TABLE deposits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    deposit_number VARCHAR(40) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(18,2) NOT NULL,
    payment_method VARCHAR(120) NOT NULL,
    reference_number VARCHAR(120) NULL,
    proof_image VARCHAR(255) NULL,
    status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    submitted_at DATETIME NOT NULL,
    approved_at DATETIME NULL,
    admin_notes TEXT NULL,
    CONSTRAINT fk_deposits_user FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE withdrawals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    withdrawal_number VARCHAR(40) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(18,2) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    processing_status ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
    approval_status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    submitted_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    admin_notes TEXT NULL,
    CONSTRAINT fk_withdrawals_user FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE referrals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    referrer_id BIGINT UNSIGNED NOT NULL,
    child_user_id BIGINT UNSIGNED NOT NULL,
    referral_level INT UNSIGNED NOT NULL,
    commission_generated DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    commission_paid DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    commission_status ENUM('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
    qualification_status ENUM('unqualified','qualified') NOT NULL DEFAULT 'unqualified',
    investment_status ENUM('none','active','completed') NOT NULL DEFAULT 'none',
    registered_at DATETIME NOT NULL,
    UNIQUE KEY uq_referral_pair (referrer_id, child_user_id),
    CONSTRAINT fk_referrals_referrer FOREIGN KEY (referrer_id) REFERENCES users(id),
    CONSTRAINT fk_referrals_child FOREIGN KEY (child_user_id) REFERENCES users(id)
);

CREATE TABLE referral_levels (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    level_number INT UNSIGNED NOT NULL UNIQUE,
    commission_percentage DECIMAL(8,2) NOT NULL,
    status ENUM('active','disabled') NOT NULL DEFAULT 'active'
);

CREATE TABLE referral_commissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    referral_id BIGINT UNSIGNED NOT NULL,
    transaction_id BIGINT UNSIGNED NULL,
    amount DECIMAL(18,2) NOT NULL,
    status ENUM('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ref_comm_referral FOREIGN KEY (referral_id) REFERENCES referrals(id),
    CONSTRAINT fk_ref_comm_transaction FOREIGN KEY (transaction_id) REFERENCES transactions(id)
);

CREATE TABLE reward_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(80) NOT NULL UNIQUE,
    title VARCHAR(140) NOT NULL,
    amount DECIMAL(18,2) NOT NULL,
    usage_limit INT UNSIGNED NULL,
    used_count INT UNSIGNED NOT NULL DEFAULT 0,
    expires_at DATETIME NULL,
    status ENUM('active','disabled','expired') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reward_redemptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reward_code_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(18,2) NOT NULL,
    status ENUM('pending','approved','rejected','paid') NOT NULL DEFAULT 'paid',
    redeemed_at DATETIME NOT NULL,
    CONSTRAINT fk_redemptions_code FOREIGN KEY (reward_code_id) REFERENCES reward_codes(id),
    CONSTRAINT fk_redemptions_user FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    title VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(60) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user_read (user_id, is_read),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE announcements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    body TEXT NOT NULL,
    audience VARCHAR(80) NOT NULL DEFAULT 'all',
    status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE support_tickets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(40) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NOT NULL,
    subject VARCHAR(180) NOT NULL,
    status ENUM('open','pending','resolved','closed') NOT NULL DEFAULT 'open',
    priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    category VARCHAR(80) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_tickets_user FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE ticket_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    sender_type ENUM('user','admin') NOT NULL,
    sender_id BIGINT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    attachment VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ticket_messages_ticket FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
);

CREATE TABLE bank_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    holder_name VARCHAR(160) NOT NULL,
    account_number VARCHAR(80) NOT NULL,
    bank_name VARCHAR(160) NOT NULL,
    status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bank_accounts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE payment_methods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    network VARCHAR(80) NULL,
    wallet_address VARCHAR(255) NULL,
    instructions TEXT NULL,
    min_deposit DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    max_deposit DECIMAL(18,2) NULL,
    status ENUM('active','disabled') NOT NULL DEFAULT 'active'
);

CREATE TABLE website_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE cms_pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(120) NOT NULL UNIQUE,
    title VARCHAR(160) NOT NULL,
    body MEDIUMTEXT NOT NULL,
    status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    updated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE downloads (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    status ENUM('active','disabled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    session_token VARCHAR(128) NOT NULL UNIQUE,
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE login_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    admin_user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    status ENUM('success','failed') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_login_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_login_admin FOREIGN KEY (admin_user_id) REFERENCES admin_users(id)
);

CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_type ENUM('user','admin','system') NOT NULL,
    actor_id BIGINT UNSIGNED NULL,
    action VARCHAR(160) NOT NULL,
    description TEXT NULL,
    ip_address VARCHAR(64) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_user_id BIGINT UNSIGNED NULL,
    entity_type VARCHAR(120) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_admin FOREIGN KEY (admin_user_id) REFERENCES admin_users(id)
);

CREATE TABLE administrative_notes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    admin_user_id BIGINT UNSIGNED NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_notes_user_created (user_id, created_at),
    CONSTRAINT fk_admin_notes_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_admin_notes_admin FOREIGN KEY (admin_user_id) REFERENCES admin_users(id)
);

CREATE TABLE reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(120) NOT NULL,
    filters JSON NULL,
    generated_by BIGINT UNSIGNED NULL,
    file_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reports_admin FOREIGN KEY (generated_by) REFERENCES admin_users(id)
);

CREATE TABLE statistics_cache (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    metric_key VARCHAR(120) NOT NULL,
    metric_date DATE NOT NULL,
    metric_value DECIMAL(20,4) NOT NULL DEFAULT 0,
    payload JSON NULL,
    UNIQUE KEY uq_statistics_metric_date (metric_key, metric_date)
);

CREATE TABLE banners (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    image_path VARCHAR(255) NULL,
    target_url VARCHAR(255) NULL,
    placement VARCHAR(80) NOT NULL,
    status ENUM('active','disabled') NOT NULL DEFAULT 'active'
);

CREATE TABLE system_configuration (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(120) NOT NULL UNIQUE,
    config_value TEXT NULL,
    updated_at TIMESTAMP NULL
);

INSERT INTO referral_levels (level_number, commission_percentage, status) VALUES
(1, 11.00, 'active'),
(2, 2.00, 'active'),
(3, 1.00, 'active');

INSERT INTO payment_methods (name, network, wallet_address, instructions, min_deposit, max_deposit, status) VALUES
('USDT(BEP20)', 'BEP20', 'demo-bep20-wallet-address', 'Send BEP20 USDT and upload payment proof.', 10.00, 5000.00, 'active'),
('USDT(TRC20)', 'TRC20', 'demo-trc20-wallet-address', 'Send TRC20 USDT and upload payment proof.', 10.00, 5000.00, 'active'),
('Binance Pay Id', 'Binance Pay', 'demo-binance-pay-id', 'Use the platform Binance Pay identifier.', 10.00, 5000.00, 'active');
