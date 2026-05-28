-- ============================================================================
-- 2026-05-28: Email templates + per-login credential-sent tracking.
--
-- 1. email_templates: editable subject + HTML/text body per template_key.
--    Body uses {{placeholders}} that the Mailer expands at send time.
-- 2. school_logins.credentials_sent_at: when the last credential email was
--    delivered to this team account, so we can colour the "Send credentials"
--    button and avoid re-sending on every slot re-assignment.
-- 3. settings: SMTP host/port/user/pass and an auto-send-on-assign toggle.
-- ============================================================================

CREATE TABLE IF NOT EXISTS email_templates (
    template_key    VARCHAR(64)  NOT NULL,
    name            VARCHAR(150) NOT NULL,
    subject         VARCHAR(255) NOT NULL,
    body_html       MEDIUMTEXT   NOT NULL,
    body_text       MEDIUMTEXT   NULL,
    placeholders    VARCHAR(500) NULL,
    updated_by_admin_id INT UNSIGNED NULL,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                            ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (template_key),
    KEY idx_et_updated_by (updated_by_admin_id),
    CONSTRAINT fk_et_admin
        FOREIGN KEY (updated_by_admin_id) REFERENCES admins(admin_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE school_logins
    ADD COLUMN credentials_sent_at DATETIME NULL AFTER last_login_at;

INSERT INTO email_templates
    (template_key, name, subject, body_html, body_text, placeholders)
VALUES (
    'school_credentials',
    'School credentials',
    'Your Olympics Run 2026 quiz credentials',
    CONCAT(
'<p>Hello <strong>{{school_name}}</strong>,</p>',
'<p>Your team has been registered for the <strong>Olympics Run 2026</strong> ',
'quiz organised by {{association_name}}.</p>',
'<p><strong>Login URL:</strong> <a href="{{login_url}}">{{login_url}}</a></p>',
'<table style="border-collapse:collapse;font-family:Arial,sans-serif">',
'<tr><td style="padding:4px 12px 4px 0"><strong>Username</strong></td>',
'<td style="padding:4px 0;font-family:monospace">{{username}}</td></tr>',
'<tr><td style="padding:4px 12px 4px 0"><strong>Password</strong></td>',
'<td style="padding:4px 0;font-family:monospace">{{password}}</td></tr>',
'</table>',
'<p>Your slot: <strong>{{slot_label}}</strong> starting ',
'<strong>{{slot_starts}}</strong>. Please log in a few minutes before your ',
'window opens.</p>',
'<p>Best regards,<br>Kerala Olympic Association</p>'
    ),
    CONCAT(
'Hello {{school_name}},\n\n',
'Your team has been registered for Olympics Run 2026 by {{association_name}}.\n\n',
'Login URL: {{login_url}}\n',
'Username:  {{username}}\n',
'Password:  {{password}}\n\n',
'Slot: {{slot_label}}\nStarts: {{slot_starts}}\n\n',
'Best regards,\nKerala Olympic Association'
    ),
    'school_name, association_name, username, password, slot_label, slot_starts, slot_ends, round_name, login_url'
);

INSERT INTO settings (setting_key, setting_value, value_type, description)
VALUES
    ('mail_smtp_host',    'smtp.example.com',       'string', 'SMTP server host'),
    ('mail_smtp_port',    '587',                    'int',    'SMTP server port'),
    ('mail_smtp_user',    '',                       'string', 'SMTP username'),
    ('mail_smtp_pass',    '',                       'string', 'SMTP password (stored plain — restrict admin access)'),
    ('mail_smtp_secure',  'tls',                    'string', 'tls | ssl | (blank for none)'),
    ('mail_from_email',   'no-reply@olympicsrun2026.in', 'string', 'Default From: address'),
    ('mail_from_name',    'Olympics Run 2026',      'string', 'Default From: name'),
    ('mail_auto_send_on_assign', 'false',           'bool',   'Auto-send credentials when a school is assigned to a slot')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
