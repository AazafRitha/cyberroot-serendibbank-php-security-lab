ALTER TABLE users
    DROP CHECK chk_users_role,
    ADD CONSTRAINT chk_users_role CHECK (role IN ('customer', 'staff', 'admin')),
    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1;
