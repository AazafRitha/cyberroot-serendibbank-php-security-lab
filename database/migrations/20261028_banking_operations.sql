ALTER TABLE users
    DROP CHECK chk_users_role,
    ADD CONSTRAINT chk_users_role CHECK (role IN ('customer', 'staff', 'admin', 'super_admin'));

ALTER TABLE accounts
    ADD COLUMN status VARCHAR(12) NOT NULL DEFAULT 'active',
    ADD CONSTRAINT chk_accounts_status CHECK (status IN ('active', 'frozen'));

CREATE TABLE transaction_reviews (
    transaction_id BIGINT UNSIGNED PRIMARY KEY,
    status VARCHAR(12) NOT NULL,
    note VARCHAR(250) NOT NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transaction_reviews_transaction FOREIGN KEY (transaction_id) REFERENCES transactions(id),
    CONSTRAINT fk_transaction_reviews_user FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_transaction_reviews_status CHECK (status IN ('flagged', 'cleared'))
);

CREATE TABLE management_audit (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_user_id BIGINT UNSIGNED NULL,
    action VARCHAR(48) NOT NULL,
    entity_type VARCHAR(32) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    details VARCHAR(250) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_management_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_management_audit_created (created_at)
);

CREATE TABLE ledger_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    direction VARCHAR(6) NOT NULL,
    amount DECIMAL(14, 2) NOT NULL,
    reason VARCHAR(160) NOT NULL,
    status VARCHAR(12) NOT NULL DEFAULT 'pending',
    requested_by BIGINT UNSIGNED NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    CONSTRAINT fk_ledger_request_account FOREIGN KEY (account_id) REFERENCES accounts(id),
    CONSTRAINT fk_ledger_request_maker FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_ledger_request_checker FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_ledger_request_direction CHECK (direction IN ('credit', 'debit')),
    CONSTRAINT chk_ledger_request_amount CHECK (amount > 0),
    CONSTRAINT chk_ledger_request_status CHECK (status IN ('pending', 'approved', 'rejected')),
    INDEX idx_ledger_request_status (status, created_at)
);
