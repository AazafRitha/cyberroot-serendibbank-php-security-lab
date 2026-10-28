CREATE TABLE IF NOT EXISTS customer_registration_details (
    customer_id BIGINT UNSIGNED PRIMARY KEY,
    identity_type VARCHAR(8) NOT NULL,
    identity_fingerprint CHAR(64) NOT NULL UNIQUE,
    identity_last_four CHAR(4) NOT NULL,
    address_line1 VARCHAR(160) NOT NULL,
    address_line2 VARCHAR(160) NULL,
    city VARCHAR(80) NOT NULL,
    postal_code VARCHAR(12) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_registration_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    CONSTRAINT chk_registration_identity_type CHECK (identity_type IN ('nic', 'passport'))
);
