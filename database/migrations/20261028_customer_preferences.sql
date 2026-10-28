CREATE TABLE IF NOT EXISTS customer_preferences (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    account_alerts TINYINT(1) NOT NULL DEFAULT 1,
    product_updates TINYINT(1) NOT NULL DEFAULT 0,
    preferred_contact VARCHAR(12) NOT NULL DEFAULT 'email',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_preferences_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_preferred_contact CHECK (preferred_contact IN ('email', 'phone'))
);
