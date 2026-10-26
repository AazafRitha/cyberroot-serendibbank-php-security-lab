CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    display_name VARCHAR(120) NOT NULL
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

INSERT INTO customers (id, display_name) VALUES
    (1, 'N. Perera');

INSERT INTO accounts (id, customer_id, account_number, account_type, currency, balance) VALUES
    (1, 1, '241000128453', 'Everyday account', 'LKR', 184250.75),
    (2, 1, '241000128454', 'Savings account', 'LKR', 520000.00);

INSERT INTO transactions (account_id, posted_at, description, amount) VALUES
    (1, '2026-10-22 09:15:00', 'Salary payment', 125000.00),
    (1, '2026-10-20 14:38:00', 'Grocery purchase', -6850.50),
    (1, '2026-10-18 11:20:00', 'Electricity payment', -4300.00),
    (2, '2026-10-16 10:00:00', 'Transfer to savings', 25000.00),
    (1, '2026-10-15 16:42:00', 'Transport purchase', -1850.00);
