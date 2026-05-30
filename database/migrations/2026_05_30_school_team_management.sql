-- Migration: combined school + team-login management
-- Date: 2026-05-30
--
-- Run this on existing installs (cPanel -> phpMyAdmin -> Import). Idempotent.
--
-- Adds the `max_teams_per_school` setting, which caps how many team logins an
-- admin can create per school on the combined Schools management page.
-- (Password-reset emails reuse the existing `school_credentials` template and
--  the credentials_sent_at column, so no other schema changes are needed.)

INSERT INTO settings (setting_key, setting_value, value_type, description)
VALUES ('max_teams_per_school', '1', 'int',
        'Maximum number of team logins an admin can create per school.')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
