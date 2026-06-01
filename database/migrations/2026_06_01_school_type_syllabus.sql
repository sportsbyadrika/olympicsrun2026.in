-- Migration: school type + syllabus lookup tables and FKs on schools
-- Date: 2026-06-01
--
-- Run on existing installs (cPanel -> phpMyAdmin -> Import). Idempotent.
--
-- Adds two small lookup tables (admin-managed value lists) and references them
-- from schools so Add/Edit School can offer dropdowns.

CREATE TABLE IF NOT EXISTS school_types (
    school_type_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name           VARCHAR(80)  NOT NULL,
    sort_order     INT          NOT NULL DEFAULT 0,
    PRIMARY KEY (school_type_id),
    UNIQUE KEY uniq_school_type_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS syllabuses (
    syllabus_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(80)  NOT NULL,
    sort_order  INT          NOT NULL DEFAULT 0,
    PRIMARY KEY (syllabus_id),
    UNIQUE KEY uniq_syllabus_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO school_types (name, sort_order) VALUES
    ('Government', 1), ('Aided', 2), ('Private', 3)
ON DUPLICATE KEY UPDATE name = name;

INSERT INTO syllabuses (name, sort_order) VALUES
    ('State', 1), ('CBSE', 2), ('ISC', 3)
ON DUPLICATE KEY UPDATE name = name;

-- Add nullable FK columns to schools (guarded so re-runs don't error).
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'schools'
               AND COLUMN_NAME = 'school_type_id');
SET @sql := IF(@col = 0,
    'ALTER TABLE schools ADD COLUMN school_type_id INT UNSIGNED NULL AFTER school_code',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'schools'
               AND COLUMN_NAME = 'syllabus_id');
SET @sql := IF(@col = 0,
    'ALTER TABLE schools ADD COLUMN syllabus_id INT UNSIGNED NULL AFTER school_type_id',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
