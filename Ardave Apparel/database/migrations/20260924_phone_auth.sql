-- Additive migration: legacy contact phone numbers are NOT verified identities.
CREATE TABLE IF NOT EXISTS auth_phone_identities (
 user_id INT UNSIGNED NOT NULL PRIMARY KEY,
 phone VARCHAR(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
 verified_at DATETIME NOT NULL,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS auth_phone_challenges (
 id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 purpose VARCHAR(8) NOT NULL,
 user_id INT UNSIGNED NULL,
 phone VARCHAR(16) NOT NULL,
 verification_sid VARCHAR(34) NULL,
 expires_at BIGINT NOT NULL,
 next_send_at BIGINT NOT NULL,
 attempts INT NOT NULL DEFAULT 0,
 consumed TINYINT NOT NULL DEFAULT 0,
 INDEX (expires_at)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS auth_phone_limits (
 bucket CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 hits INT NOT NULL,
 expires_at BIGINT NOT NULL,
 INDEX (expires_at)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS auth_phone_consumed (
 verification_sid VARCHAR(34) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 consumed_at DATETIME NOT NULL
) ENGINE=InnoDB;
