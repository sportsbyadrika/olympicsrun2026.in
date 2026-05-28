-- ============================================================================
-- Olympics Run 2026 — Database Schema
-- Kerala Olympic Association | Inter-school sports quiz platform
--
-- Engine : InnoDB
-- Charset: utf8mb4 / utf8mb4_unicode_ci
-- Server : MySQL 8.x
--
-- Load order:
--   mysql -u root -p < database/schema.sql
--
-- This file is idempotent: it drops and recreates every table, then loads
-- seed data. DO NOT run it against a populated production database.
--
-- Default seed password for ALL accounts below is:  password
-- (bcrypt hash: $2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy)
-- These credentials are for local dev only — rotate before going live.
-- ============================================================================

SET NAMES utf8mb4;
SET time_zone = '+05:30';
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------------------------
-- Drop tables (reverse dependency order)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS responses;
DROP TABLE IF EXISTS results;
DROP TABLE IF EXISTS slot_questions;
DROP TABLE IF EXISTS slot_schools;
DROP TABLE IF EXISTS slots;
DROP TABLE IF EXISTS rounds;
DROP TABLE IF EXISTS master_questions;
DROP TABLE IF EXISTS association_question_bank;
DROP TABLE IF EXISTS school_logins;
DROP TABLE IF EXISTS schools;
DROP TABLE IF EXISTS expert_panelists;
DROP TABLE IF EXISTS association_users;
DROP TABLE IF EXISTS associations;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS settings;

-- ==========================================================================
-- 1. admins  — system super-users (own the whole platform)
-- ==========================================================================
CREATE TABLE admins (
    admin_id        INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    username        VARCHAR(64)   NOT NULL,
    email           VARCHAR(190)  NOT NULL,
    password_hash   VARCHAR(255)  NOT NULL,
    full_name       VARCHAR(150)  NOT NULL,
    phone           VARCHAR(20)   NULL,
    status          ENUM('active','suspended') NOT NULL DEFAULT 'active',
    last_login_at   DATETIME      NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (admin_id),
    UNIQUE KEY uniq_admins_username (username),
    UNIQUE KEY uniq_admins_email (email),
    KEY idx_admins_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- 2. associations  — Kerala Olympic Association + any sub-associations
-- ==========================================================================
CREATE TABLE associations (
    association_id  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name            VARCHAR(200)  NOT NULL,
    short_code      VARCHAR(20)   NOT NULL,
    region          VARCHAR(100)  NULL,
    contact_email   VARCHAR(190)  NULL,
    contact_phone   VARCHAR(20)   NULL,
    address         TEXT          NULL,
    logo_path       VARCHAR(255)  NULL,
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by_admin_id INT UNSIGNED NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (association_id),
    UNIQUE KEY uniq_assoc_code (short_code),
    KEY idx_assoc_status (status),
    KEY idx_assoc_region (region),
    CONSTRAINT fk_assoc_created_by
        FOREIGN KEY (created_by_admin_id) REFERENCES admins(admin_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- 3. association_users  — operational users for an association
-- ==========================================================================
CREATE TABLE association_users (
    association_user_id INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    association_id      INT UNSIGNED  NOT NULL,
    username            VARCHAR(64)   NOT NULL,
    email               VARCHAR(190)  NOT NULL,
    password_hash       VARCHAR(255)  NOT NULL,
    full_name           VARCHAR(150)  NOT NULL,
    phone               VARCHAR(20)   NULL,
    role_label          VARCHAR(60)   NOT NULL DEFAULT 'operator',
    status              ENUM('active','suspended') NOT NULL DEFAULT 'active',
    last_login_at       DATETIME      NULL,
    created_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (association_user_id),
    UNIQUE KEY uniq_au_username (username),
    UNIQUE KEY uniq_au_email (email),
    KEY idx_au_assoc (association_id),
    KEY idx_au_status (status),
    CONSTRAINT fk_au_assoc
        FOREIGN KEY (association_id) REFERENCES associations(association_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- 4. expert_panelists  — subject-matter experts who submit questions
-- ==========================================================================
CREATE TABLE expert_panelists (
    panelist_id     INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    association_id  INT UNSIGNED  NOT NULL,
    username        VARCHAR(64)   NOT NULL,
    email           VARCHAR(190)  NOT NULL,
    password_hash   VARCHAR(255)  NOT NULL,
    full_name       VARCHAR(150)  NOT NULL,
    phone           VARCHAR(20)   NULL,
    expertise       VARCHAR(150)  NULL,
    bio             TEXT          NULL,
    status          ENUM('active','suspended') NOT NULL DEFAULT 'active',
    last_login_at   DATETIME      NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (panelist_id),
    UNIQUE KEY uniq_panelist_username (username),
    UNIQUE KEY uniq_panelist_email (email),
    KEY idx_panelist_assoc (association_id),
    KEY idx_panelist_status (status),
    CONSTRAINT fk_panelist_assoc
        FOREIGN KEY (association_id) REFERENCES associations(association_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- 5. schools  — participating schools (registration → approval)
-- ==========================================================================
CREATE TABLE schools (
    school_id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    association_id      INT UNSIGNED  NOT NULL,
    school_name         VARCHAR(200)  NOT NULL,
    school_code         VARCHAR(50)   NULL,
    region              VARCHAR(100)  NULL,
    address             TEXT          NULL,
    principal_name      VARCHAR(150)  NULL,
    coach_name          VARCHAR(150)  NULL,
    contact_email       VARCHAR(190)  NULL,
    contact_phone       VARCHAR(20)   NULL,
    status              ENUM('pending','approved','rejected','suspended')
                                NOT NULL DEFAULT 'pending',
    approved_by_user_id INT UNSIGNED  NULL,
    approved_at         DATETIME      NULL,
    created_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (school_id),
    UNIQUE KEY uniq_school_code (school_code),
    KEY idx_school_assoc (association_id),
    KEY idx_school_status (status),
    KEY idx_school_region (region),
    CONSTRAINT fk_school_assoc
        FOREIGN KEY (association_id) REFERENCES associations(association_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_school_approver
        FOREIGN KEY (approved_by_user_id)
            REFERENCES association_users(association_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- 6. school_logins  — login accounts for school teams
--    One school may have multiple team logins (e.g. boys / girls team).
-- ==========================================================================
CREATE TABLE school_logins (
    school_login_id INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    school_id       INT UNSIGNED  NOT NULL,
    username        VARCHAR(64)   NOT NULL,
    password_hash   VARCHAR(255)  NOT NULL,
    team_label      VARCHAR(100)  NULL,
    status          ENUM('active','suspended') NOT NULL DEFAULT 'active',
    last_login_at   DATETIME      NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (school_login_id),
    UNIQUE KEY uniq_sl_username (username),
    KEY idx_sl_school (school_id),
    KEY idx_sl_status (status),
    CONSTRAINT fk_sl_school
        FOREIGN KEY (school_id) REFERENCES schools(school_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- 7. rounds  — Round 1 (qualifier) and Round 2 (final), per association
--    Per-round defaults for quiz duration / slot length / question count
--    override the global `settings` table when present.
-- ==========================================================================
CREATE TABLE rounds (
    round_id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    association_id        INT UNSIGNED  NOT NULL,
    round_number          TINYINT UNSIGNED NOT NULL,
    name                  VARCHAR(100)  NOT NULL,
    description           TEXT          NULL,
    slot_duration_minutes INT UNSIGNED  NOT NULL DEFAULT 30,
    quiz_duration_minutes INT UNSIGNED  NOT NULL DEFAULT 15,
    questions_per_quiz    INT UNSIGNED  NOT NULL DEFAULT 30,
    marks_correct         DECIMAL(5,2)  NOT NULL DEFAULT 1.00,
    marks_wrong           DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    marks_unanswered      DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    qualifiers_count      INT UNSIGNED  NULL,
    status                ENUM('draft','open','closed','published')
                                NOT NULL DEFAULT 'draft',
    starts_at             DATETIME      NULL,
    ends_at               DATETIME      NULL,
    created_at            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (round_id),
    UNIQUE KEY uniq_assoc_round (association_id, round_number),
    KEY idx_round_status (status),
    CONSTRAINT fk_round_assoc
        FOREIGN KEY (association_id) REFERENCES associations(association_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- 8. slots  — wall-clock windows in which assigned teams take the quiz
-- ==========================================================================
CREATE TABLE slots (
    slot_id        INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    round_id       INT UNSIGNED  NOT NULL,
    slot_label     VARCHAR(100)  NULL,
    starts_at      DATETIME      NOT NULL,
    ends_at        DATETIME      NOT NULL,
    capacity       INT UNSIGNED  NOT NULL DEFAULT 50,
    status         ENUM('scheduled','open','closed','cancelled')
                            NOT NULL DEFAULT 'scheduled',
    created_by_user_id INT UNSIGNED NULL,
    created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (slot_id),
    KEY idx_slot_round (round_id),
    KEY idx_slot_starts (starts_at),
    KEY idx_slot_status (status),
    CONSTRAINT fk_slot_round
        FOREIGN KEY (round_id) REFERENCES rounds(round_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_slot_creator
        FOREIGN KEY (created_by_user_id)
            REFERENCES association_users(association_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- 9. slot_schools  — which school sits in which slot, and the attempt state
--    This row also doubles as the per-school quiz "attempt" record.
-- ==========================================================================
CREATE TABLE slot_schools (
    slot_school_id   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    slot_id          INT UNSIGNED  NOT NULL,
    school_id        INT UNSIGNED  NOT NULL,
    school_login_id  INT UNSIGNED  NULL,
    attempt_status   ENUM('assigned','in_progress','submitted',
                          'no_show','disqualified')
                            NOT NULL DEFAULT 'assigned',
    started_at       DATETIME      NULL,
    submitted_at     DATETIME      NULL,
    time_taken_seconds INT UNSIGNED NULL,
    assigned_by_user_id INT UNSIGNED NULL,
    assigned_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (slot_school_id),
    UNIQUE KEY uniq_slot_school (slot_id, school_id),
    KEY idx_ss_school (school_id),
    KEY idx_ss_status (attempt_status),
    CONSTRAINT fk_ss_slot
        FOREIGN KEY (slot_id) REFERENCES slots(slot_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ss_school
        FOREIGN KEY (school_id) REFERENCES schools(school_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ss_login
        FOREIGN KEY (school_login_id) REFERENCES school_logins(school_login_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ss_assigner
        FOREIGN KEY (assigned_by_user_id)
            REFERENCES association_users(association_user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- 10. association_question_bank  — raw panelist submissions
--     Lifecycle: pending → approved | rejected | needs_revision
--     Approved questions may be promoted to master_questions.
-- ==========================================================================
CREATE TABLE association_question_bank (
    question_id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    association_id           INT UNSIGNED  NOT NULL,
    submitted_by_panelist_id INT UNSIGNED  NULL,
    question_text            TEXT          NOT NULL,
    option_a                 VARCHAR(500)  NOT NULL,
    option_b                 VARCHAR(500)  NOT NULL,
    option_c                 VARCHAR(500)  NOT NULL,
    option_d                 VARCHAR(500)  NOT NULL,
    correct_option           ENUM('A','B','C','D') NOT NULL,
    explanation              TEXT          NULL,
    sport                    VARCHAR(100)  NULL,
    category                 VARCHAR(100)  NULL,
    difficulty               ENUM('easy','medium','hard')
                                       NOT NULL DEFAULT 'medium',
    reference_source         VARCHAR(255)  NULL,
    status                   ENUM('pending','approved','rejected',
                                  'needs_revision')
                                       NOT NULL DEFAULT 'pending',
    reviewed_by_user_id      INT UNSIGNED  NULL,
    reviewed_at              DATETIME      NULL,
    reject_reason            TEXT          NULL,
    promoted_to_master       TINYINT(1)    NOT NULL DEFAULT 0,
    master_question_id       INT UNSIGNED  NULL,
    created_at               DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at               DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (question_id),
    KEY idx_qb_assoc (association_id),
    KEY idx_qb_panelist (submitted_by_panelist_id),
    KEY idx_qb_status (status),
    KEY idx_qb_difficulty (difficulty),
    KEY idx_qb_sport (sport),
    KEY idx_qb_master (master_question_id),
    CONSTRAINT fk_qb_assoc
        FOREIGN KEY (association_id) REFERENCES associations(association_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_qb_panelist
        FOREIGN KEY (submitted_by_panelist_id)
            REFERENCES expert_panelists(panelist_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_qb_reviewer
        FOREIGN KEY (reviewed_by_user_id)
            REFERENCES association_users(association_user_id)
        ON DELETE SET NULL
    -- fk_qb_master added after master_questions exists (see ALTER below)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- 11. master_questions  — curated pool, the source for actual quizzes
-- ==========================================================================
CREATE TABLE master_questions (
    master_question_id   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    source_question_id   INT UNSIGNED  NULL,
    association_id       INT UNSIGNED  NOT NULL,
    question_text        TEXT          NOT NULL,
    option_a             VARCHAR(500)  NOT NULL,
    option_b             VARCHAR(500)  NOT NULL,
    option_c             VARCHAR(500)  NOT NULL,
    option_d             VARCHAR(500)  NOT NULL,
    correct_option       ENUM('A','B','C','D') NOT NULL,
    explanation          TEXT          NULL,
    sport                VARCHAR(100)  NULL,
    category             VARCHAR(100)  NULL,
    difficulty           ENUM('easy','medium','hard')
                                NOT NULL DEFAULT 'medium',
    intended_round       TINYINT UNSIGNED NULL,
    status               ENUM('active','retired') NOT NULL DEFAULT 'active',
    added_by_admin_id    INT UNSIGNED  NULL,
    created_at           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (master_question_id),
    KEY idx_mq_assoc (association_id),
    KEY idx_mq_round (intended_round),
    KEY idx_mq_status (status),
    KEY idx_mq_difficulty (difficulty),
    KEY idx_mq_sport (sport),
    CONSTRAINT fk_mq_source
        FOREIGN KEY (source_question_id)
            REFERENCES association_question_bank(question_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_mq_assoc
        FOREIGN KEY (association_id) REFERENCES associations(association_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_mq_admin
        FOREIGN KEY (added_by_admin_id) REFERENCES admins(admin_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Back-reference now that master_questions exists.
ALTER TABLE association_question_bank
    ADD CONSTRAINT fk_qb_master
        FOREIGN KEY (master_question_id)
            REFERENCES master_questions(master_question_id)
        ON DELETE SET NULL;

-- ==========================================================================
-- 12. slot_questions  — ordered list of questions for a slot
--     Different slots can deliver different sets (anti-cheat).
-- ==========================================================================
CREATE TABLE slot_questions (
    slot_question_id    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    slot_id             INT UNSIGNED  NOT NULL,
    master_question_id  INT UNSIGNED  NOT NULL,
    position            INT UNSIGNED  NOT NULL,
    added_by_admin_id   INT UNSIGNED  NULL,
    added_at            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (slot_question_id),
    UNIQUE KEY uniq_slot_question (slot_id, master_question_id),
    UNIQUE KEY uniq_slot_position (slot_id, position),
    KEY idx_sq_master (master_question_id),
    CONSTRAINT fk_sq_slot
        FOREIGN KEY (slot_id) REFERENCES slots(slot_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_sq_master
        FOREIGN KEY (master_question_id)
            REFERENCES master_questions(master_question_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_sq_admin
        FOREIGN KEY (added_by_admin_id) REFERENCES admins(admin_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- 13. responses  — one row per question per school attempt
-- ==========================================================================
CREATE TABLE responses (
    response_id        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slot_school_id     INT UNSIGNED   NOT NULL,
    slot_question_id   INT UNSIGNED   NOT NULL,
    master_question_id INT UNSIGNED   NOT NULL,
    chosen_option      ENUM('A','B','C','D') NULL,
    is_correct         TINYINT(1)     NULL,
    marks_awarded      DECIMAL(5,2)   NULL,
    answered_at        DATETIME       NULL,
    time_taken_seconds INT UNSIGNED   NULL,
    PRIMARY KEY (response_id),
    UNIQUE KEY uniq_attempt_q (slot_school_id, slot_question_id),
    KEY idx_resp_master (master_question_id),
    KEY idx_resp_correct (is_correct),
    CONSTRAINT fk_resp_attempt
        FOREIGN KEY (slot_school_id) REFERENCES slot_schools(slot_school_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_resp_slot_q
        FOREIGN KEY (slot_question_id) REFERENCES slot_questions(slot_question_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_resp_master
        FOREIGN KEY (master_question_id)
            REFERENCES master_questions(master_question_id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- 14. results  — one row per completed attempt (per slot_school)
--     Computed at submission time; rank assigned when round closes.
-- ==========================================================================
CREATE TABLE results (
    result_id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    slot_school_id       INT UNSIGNED  NOT NULL,
    round_id             INT UNSIGNED  NOT NULL,
    school_id            INT UNSIGNED  NOT NULL,
    total_questions      INT UNSIGNED  NOT NULL,
    correct_count        INT UNSIGNED  NOT NULL DEFAULT 0,
    wrong_count          INT UNSIGNED  NOT NULL DEFAULT 0,
    unanswered_count     INT UNSIGNED  NOT NULL DEFAULT 0,
    total_score          DECIMAL(7,2)  NOT NULL DEFAULT 0.00,
    time_taken_seconds   INT UNSIGNED  NULL,
    rank_in_round        INT UNSIGNED  NULL,
    qualified_next_round TINYINT(1)    NOT NULL DEFAULT 0,
    published            TINYINT(1)    NOT NULL DEFAULT 0,
    published_at         DATETIME      NULL,
    computed_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (result_id),
    UNIQUE KEY uniq_res_attempt (slot_school_id),
    KEY idx_res_round (round_id),
    KEY idx_res_school (school_id),
    KEY idx_res_score (total_score),
    KEY idx_res_rank (round_id, rank_in_round),
    CONSTRAINT fk_res_attempt
        FOREIGN KEY (slot_school_id) REFERENCES slot_schools(slot_school_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_res_round
        FOREIGN KEY (round_id) REFERENCES rounds(round_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_res_school
        FOREIGN KEY (school_id) REFERENCES schools(school_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- 15. settings  — global, typed key/value store for configurable values
-- ==========================================================================
CREATE TABLE settings (
    setting_key    VARCHAR(100) NOT NULL,
    setting_value  TEXT         NULL,
    value_type     ENUM('string','int','float','bool','datetime','json')
                          NOT NULL DEFAULT 'string',
    description    VARCHAR(255) NULL,
    updated_by_admin_id INT UNSIGNED NULL,
    updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key),
    KEY idx_settings_type (value_type),
    CONSTRAINT fk_settings_admin
        FOREIGN KEY (updated_by_admin_id) REFERENCES admins(admin_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- SEED DATA
-- All passwords below are bcrypt of the literal string: password
-- Hash: $2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy
-- ROTATE BEFORE PRODUCTION.
-- ============================================================================

-- 1 admin
INSERT INTO admins (admin_id, username, email, password_hash, full_name, phone)
VALUES
    (1, 'admin', 'admin@olympicsrun2026.in',
     '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',
     'System Administrator', '+91-9000000000');

-- 1 association
INSERT INTO associations (association_id, name, short_code, region,
                          contact_email, contact_phone, created_by_admin_id)
VALUES
    (1, 'Kerala Olympic Association', 'KOA', 'Kerala',
     'office@keralaolympic.in', '+91-471-1234567', 1);

-- 2 association users
INSERT INTO association_users (association_user_id, association_id, username,
                               email, password_hash, full_name, role_label)
VALUES
    (1, 1, 'koa.secretary', 'secretary@keralaolympic.in',
     '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',
     'K. Menon', 'secretary'),
    (2, 1, 'koa.coordinator', 'coordinator@keralaolympic.in',
     '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',
     'P. Nair', 'event_coordinator');

-- 2 expert panelists
INSERT INTO expert_panelists (panelist_id, association_id, username, email,
                              password_hash, full_name, expertise)
VALUES
    (1, 1, 'panelist.athletics', 'athletics@keralaolympic.in',
     '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',
     'Dr. R. Kumar', 'Athletics & Track'),
    (2, 1, 'panelist.aquatics', 'aquatics@keralaolympic.in',
     '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',
     'Coach S. Thomas', 'Aquatics & Swimming');

-- 3 schools
INSERT INTO schools (school_id, association_id, school_name, school_code,
                     region, principal_name, coach_name,
                     contact_email, contact_phone, status,
                     approved_by_user_id, approved_at)
VALUES
    (1, 1, 'St. Joseph''s Higher Secondary School', 'SJHSS-TVM',
     'Thiruvananthapuram', 'Sr. M. Joseph', 'A. Pillai',
     'admin@sjhss.example', '+91-9000000001', 'approved',
     1, NOW()),
    (2, 1, 'Govt. Model School Kochi', 'GMS-KOC',
     'Ernakulam', 'Mr. T. Varghese', 'B. Kurian',
     'admin@gmskoc.example', '+91-9000000002', 'approved',
     1, NOW()),
    (3, 1, 'Holy Family Convent School', 'HFCS-CLT',
     'Kozhikode', 'Sr. C. Maria', 'D. Mohan',
     'admin@hfcs.example', '+91-9000000003', 'approved',
     1, NOW());

-- 3 school logins (one team per school)
INSERT INTO school_logins (school_login_id, school_id, username,
                           password_hash, team_label)
VALUES
    (1, 1, 'sjhss-team',
     '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',
     'St. Joseph''s — Senior Team'),
    (2, 2, 'gmskoc-team',
     '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',
     'GMS Kochi — Senior Team'),
    (3, 3, 'hfcs-team',
     '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',
     'HFCS Kozhikode — Senior Team');

-- 2 rounds
INSERT INTO rounds (round_id, association_id, round_number, name, description,
                    slot_duration_minutes, quiz_duration_minutes,
                    questions_per_quiz,
                    marks_correct, marks_wrong, marks_unanswered,
                    qualifiers_count, status,
                    starts_at, ends_at)
VALUES
    (1, 1, 1, 'Round 1 — Qualifier',
     'Open round; top 50 schools advance.',
     30, 15, 30,
     1.00, 0.00, 0.00, 50, 'open',
     '2026-07-15 09:00:00', '2026-07-15 18:00:00'),
    (2, 1, 2, 'Round 2 — Final',
     'Finals for qualifying schools.',
     30, 15, 30,
     2.00, -0.50, 0.00, NULL, 'draft',
     '2026-08-15 09:00:00', '2026-08-15 18:00:00');

-- 2 slots (one per round)
INSERT INTO slots (slot_id, round_id, slot_label, starts_at, ends_at,
                   capacity, status, created_by_user_id)
VALUES
    (1, 1, 'R1 Slot A — Morning', '2026-07-15 09:00:00',
     '2026-07-15 09:30:00', 50, 'open', 2),
    (2, 2, 'R2 Slot A — Morning', '2026-08-15 09:00:00',
     '2026-08-15 09:30:00', 50, 'scheduled', 2);

-- Assign all 3 schools to Round 1 slot
INSERT INTO slot_schools (slot_school_id, slot_id, school_id, school_login_id,
                          attempt_status, assigned_by_user_id)
VALUES
    (1, 1, 1, 1, 'assigned', 2),
    (2, 1, 2, 2, 'assigned', 2),
    (3, 1, 3, 3, 'assigned', 2);

-- 5 panelist submissions in the bank (mix of statuses)
INSERT INTO association_question_bank
    (question_id, association_id, submitted_by_panelist_id, question_text,
     option_a, option_b, option_c, option_d, correct_option,
     explanation, sport, category, difficulty, status,
     reviewed_by_user_id, reviewed_at, promoted_to_master, master_question_id)
VALUES
    (1, 1, 1,
     'In which year did India first participate in the Modern Olympic Games?',
     '1900', '1920', '1928', '1948', 'A',
     'India sent a single athlete, Norman Pritchard, in 1900.',
     'Athletics', 'History', 'medium', 'approved', 1, NOW(), 1, 1),
    (2, 1, 1,
     'How many laps of a standard 400m track make one mile?',
     '3', '4', '4.02', '5', 'C',
     'A mile is 1609.34 m; 4 laps = 1600 m, so about 4.02 laps.',
     'Athletics', 'Track', 'easy', 'approved', 1, NOW(), 1, 2),
    (3, 1, 2,
     'How many lanes are there in a standard Olympic swimming pool?',
     '6', '8', '10', '12', 'C',
     'FINA standard is 10 lanes (8 raced lanes + 2 buffer lanes).',
     'Aquatics', 'Pools', 'medium', 'approved', 1, NOW(), 1, 3),
    (4, 1, 2,
     'The Fosbury Flop is a technique used in which event?',
     'Long jump', 'High jump', 'Pole vault', 'Triple jump', 'B',
     'Introduced by Dick Fosbury at the 1968 Mexico Olympics.',
     'Athletics', 'Field', 'easy', 'pending', NULL, NULL, 0, NULL),
    (5, 1, 1,
     'Which Indian woman won an Olympic medal in badminton at Rio 2016?',
     'Saina Nehwal', 'P. V. Sindhu', 'Jwala Gutta', 'Ashwini Ponnappa', 'B',
     'P. V. Sindhu won silver at Rio 2016.',
     'Badminton', 'History', 'medium', 'needs_revision', 1, NOW(), 0, NULL);

-- 3 master questions (curated from the approved bank rows above)
INSERT INTO master_questions
    (master_question_id, source_question_id, association_id, question_text,
     option_a, option_b, option_c, option_d, correct_option, explanation,
     sport, category, difficulty, intended_round, status, added_by_admin_id)
VALUES
    (1, 1, 1,
     'In which year did India first participate in the Modern Olympic Games?',
     '1900', '1920', '1928', '1948', 'A',
     'India sent a single athlete, Norman Pritchard, in 1900.',
     'Athletics', 'History', 'medium', 1, 'active', 1),
    (2, 2, 1,
     'How many laps of a standard 400m track make one mile?',
     '3', '4', '4.02', '5', 'C',
     'A mile is 1609.34 m; 4 laps = 1600 m, so about 4.02 laps.',
     'Athletics', 'Track', 'easy', 1, 'active', 1),
    (3, 3, 1,
     'How many lanes are there in a standard Olympic swimming pool?',
     '6', '8', '10', '12', 'C',
     'FINA standard is 10 lanes (8 raced lanes + 2 buffer lanes).',
     'Aquatics', 'Pools', 'medium', 1, 'active', 1);

-- Place those 3 master questions into Round 1 slot 1 (positions 1..3)
INSERT INTO slot_questions (slot_question_id, slot_id, master_question_id,
                            position, added_by_admin_id)
VALUES
    (1, 1, 1, 1, 1),
    (2, 1, 2, 2, 1),
    (3, 1, 3, 3, 1);

-- Settings — every value the app reads at runtime lives here.
INSERT INTO settings (setting_key, setting_value, value_type, description,
                      updated_by_admin_id)
VALUES
    -- Timing
    ('slot_duration_minutes',   '30',   'int',   'Wall-clock length of a slot window.', 1),
    ('quiz_duration_minutes',   '15',   'int',   'Per-team countdown timer.',           1),
    ('questions_per_quiz',      '30',   'int',   'Questions delivered per attempt.',    1),
    ('slot_grace_minutes',      '5',    'int',   'Latest start within a slot.',         1),
    -- Scoring (used as fallback if rounds.* not set)
    ('marks_correct_default',   '1.00', 'float', 'Default marks per correct answer.',   1),
    ('marks_wrong_default',     '0.00', 'float', 'Default negative marks per wrong.',   1),
    ('marks_unanswered_default','0.00', 'float', 'Default marks per skipped question.', 1),
    -- Qualification
    ('r1_qualifiers_count',     '50',   'int',   'Top N schools advancing from R1.',    1),
    ('r1_qualifiers_mode',      'topN', 'string','topN | percentage | min_score.',      1),
    ('r1_min_score',            '0',    'float', 'Cut-off when mode = min_score.',      1),
    -- Event windows
    ('registration_open_at',  '2026-06-01 00:00:00', 'datetime', 'Reg opens.',          1),
    ('registration_close_at', '2026-07-10 23:59:59', 'datetime', 'Reg closes.',         1),
    ('r1_window_start',       '2026-07-15 09:00:00', 'datetime', 'R1 earliest start.',  1),
    ('r1_window_end',         '2026-07-15 18:00:00', 'datetime', 'R1 latest start.',    1),
    ('r2_window_start',       '2026-08-15 09:00:00', 'datetime', 'R2 earliest start.',  1),
    ('r2_window_end',         '2026-08-15 18:00:00', 'datetime', 'R2 latest start.',    1),
    -- Branding / display
    ('site_name',             'Olympics Run 2026',           'string', 'Header title.', 1),
    ('organising_body',       'Kerala Olympic Association',  'string', 'Footer body.',  1),
    ('support_email',         'support@olympicsrun2026.in',  'string', 'Contact link.', 1),
    ('result_publish_round1', 'false', 'bool', 'Publish R1 results to schools.',        1),
    ('result_publish_round2', 'false', 'bool', 'Publish R2 results to schools.',        1);

-- ============================================================================
-- End of schema.sql
-- ============================================================================
