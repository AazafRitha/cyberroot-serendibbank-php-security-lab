CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    display_name VARCHAR(120) NOT NULL
);

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NULL,
    display_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(16) NOT NULL,
    session_version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME NULL,
    CONSTRAINT fk_users_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT chk_users_role CHECK (role IN ('customer', 'staff'))
);

CREATE TABLE login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_key CHAR(64) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_attempts_key_time (attempt_key, attempted_at)
);

CREATE TABLE accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    account_number VARCHAR(32) NOT NULL UNIQUE,
    account_type VARCHAR(40) NOT NULL,
    currency CHAR(3) NOT NULL,
    balance DECIMAL(14, 2) NOT NULL,
    CONSTRAINT fk_accounts_customer FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    posted_at DATETIME NOT NULL,
    description VARCHAR(160) NOT NULL,
    amount DECIMAL(14, 2) NOT NULL,
    CONSTRAINT fk_transactions_account FOREIGN KEY (account_id) REFERENCES accounts(id),
    INDEX idx_transactions_account_posted (account_id, posted_at)
);

CREATE TABLE transfers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    from_account_id BIGINT UNSIGNED NOT NULL,
    to_account_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(14, 2) NOT NULL,
    reference VARCHAR(40) NOT NULL UNIQUE,
    request_token CHAR(64) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transfers_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_transfers_from FOREIGN KEY (from_account_id) REFERENCES accounts(id),
    CONSTRAINT fk_transfers_to FOREIGN KEY (to_account_id) REFERENCES accounts(id),
    CONSTRAINT chk_transfer_amount CHECK (amount > 0),
    CONSTRAINT chk_transfer_accounts CHECK (from_account_id <> to_account_id)
);

CREATE TABLE applications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    kind VARCHAR(16) NOT NULL,
    product VARCHAR(80) NOT NULL,
    linked_account_id BIGINT UNSIGNED NULL,
    employment_status VARCHAR(40) NULL,
    monthly_income DECIMAL(14, 2) NULL,
    notes VARCHAR(500) NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    CONSTRAINT fk_applications_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_applications_account FOREIGN KEY (linked_account_id) REFERENCES accounts(id),
    CONSTRAINT fk_applications_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id),
    CONSTRAINT chk_applications_kind CHECK (kind IN ('account', 'card')),
    CONSTRAINT chk_applications_status CHECK (status IN ('pending', 'reviewed')),
    INDEX idx_applications_customer (customer_id, created_at)
);

CREATE TABLE support_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    category VARCHAR(16) NOT NULL,
    subject VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    resolved_by BIGINT UNSIGNED NULL,
    CONSTRAINT fk_support_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_support_resolver FOREIGN KEY (resolved_by) REFERENCES users(id),
    CONSTRAINT chk_support_category CHECK (category IN ('account', 'card', 'digital', 'other')),
    CONSTRAINT chk_support_status CHECK (status IN ('open', 'resolved')),
    INDEX idx_support_customer (customer_id, created_at)
);

INSERT INTO customers (id, display_name) VALUES
    (1, 'N. Perera'),
    (2, 'M. Fernando');

INSERT INTO accounts (id, customer_id, account_number, account_type, currency, balance) VALUES
    (1, 1, '241000128453', 'Everyday account', 'LKR', 184250.75),
    (2, 1, '241000128454', 'Savings account', 'LKR', 520000.00),
    (3, 2, '241000128455', 'Everyday account', 'LKR', 92000.00),
    (4, 2, '241000128456', 'Savings account', 'LKR', 147500.00);

INSERT INTO transactions (account_id, posted_at, description, amount) VALUES
    (1, '2026-10-22 09:15:00', 'Salary payment', 125000.00),
    (1, '2026-10-20 14:38:00', 'Grocery purchase', -6850.50),
    (1, '2026-10-18 11:20:00', 'Electricity payment', -4300.00),
    (2, '2026-10-16 10:00:00', 'Transfer to savings', 25000.00),
    (1, '2026-10-15 16:42:00', 'Transport purchase', -1850.00),
    (3, '2026-10-21 09:00:00', 'Salary payment', 98000.00),
    (4, '2026-10-19 11:30:00', 'Savings deposit', 12500.00);
