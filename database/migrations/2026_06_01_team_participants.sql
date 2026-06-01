-- Migration: team participants (2 per team) with passport photo
-- Date: 2026-06-01
--
-- Run on existing installs (cPanel -> phpMyAdmin -> Import). Idempotent.
--
-- Each team login can have up to two participants with personal details and an
-- optional passport photo (stored on disk under uploads/participants/, only
-- the filename is kept here).

CREATE TABLE IF NOT EXISTS team_participants (
    participant_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    school_login_id   INT UNSIGNED NOT NULL,
    slot              TINYINT UNSIGNED NOT NULL,          -- 1 or 2
    participant_name  VARCHAR(150) NOT NULL,
    studying_standard VARCHAR(50)  NULL,
    age               TINYINT UNSIGNED NULL,
    gender            ENUM('male','female','other') NULL,
    photo_path        VARCHAR(255) NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                                            ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (participant_id),
    UNIQUE KEY uniq_login_slot (school_login_id, slot),
    CONSTRAINT fk_tp_login
        FOREIGN KEY (school_login_id) REFERENCES school_logins(school_login_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
