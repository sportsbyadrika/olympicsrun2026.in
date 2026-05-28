<?php
/**
 * Credential-email service.
 *
 * sendCredentials() generates a fresh password, emails it to the school's
 * contact_email, and only persists the new hash on email success — so a
 * failed send doesn't strand the school with credentials they never saw.
 */
final class SchoolMail
{
    /**
     * @return array{ok:bool, error?:string, password?:string}
     */
    public static function sendCredentials(int $schoolLoginId): array
    {
        $login = Database::fetch(
            'SELECT sl.school_login_id, sl.school_id, sl.username,
                    sl.team_label,
                    s.school_name, s.contact_email,
                    a.name AS association_name
               FROM school_logins sl
               JOIN schools s      ON s.school_id = sl.school_id
               JOIN associations a ON a.association_id = s.association_id
              WHERE sl.school_login_id = ?',
            [$schoolLoginId]
        );
        if (!$login) {
            return ['ok' => false, 'error' => 'School login not found.'];
        }
        if (empty($login['contact_email'])) {
            return ['ok' => false,
                    'error' => 'School has no contact email — set one before sending.'];
        }

        // Pull the soonest upcoming assigned/in-progress slot for context.
        $slot = Database::fetch(
            'SELECT s.slot_label, s.starts_at, s.ends_at,
                    r.name AS round_name
               FROM slot_schools ss
               JOIN slots  s ON s.slot_id  = ss.slot_id
               JOIN rounds r ON r.round_id = s.round_id
              WHERE ss.school_id = ?
                AND ss.attempt_status IN ("assigned", "in_progress")
              ORDER BY s.starts_at ASC
              LIMIT 1',
            [(int)$login['school_id']]
        );

        $tpl = EmailTemplate::find('school_credentials');
        if (!$tpl) {
            return ['ok' => false, 'error' => 'Email template school_credentials missing.'];
        }

        $newPassword = SchoolLogin::generatePassword();
        $baseUrl     = ($GLOBALS['APP_CONFIG']['app']['base_url'] ?? '') ?: '';
        if ($baseUrl === '' && !empty($_SERVER['HTTP_HOST'])) {
            $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
        }

        $vars = [
            'school_name'      => (string)$login['school_name'],
            'association_name' => (string)$login['association_name'],
            'username'         => (string)$login['username'],
            'password'         => $newPassword,
            'slot_label'       => $slot['slot_label'] ?? 'To be announced',
            'slot_starts'      => $slot ? date('d M Y, h:i A', strtotime((string)$slot['starts_at'])) : 'TBA',
            'slot_ends'        => $slot ? date('d M Y, h:i A', strtotime((string)$slot['ends_at']))   : 'TBA',
            'round_name'       => $slot['round_name'] ?? '',
            'login_url'        => rtrim($baseUrl, '/') . '/login?role=school',
        ];

        $subject  = Mailer::render((string)$tpl['subject'],   $vars);
        $bodyHtml = Mailer::render((string)$tpl['body_html'], $vars);
        $bodyText = Mailer::render((string)($tpl['body_text'] ?? ''), $vars);

        $res = Mailer::send(
            (string)$login['contact_email'],
            (string)$login['school_name'],
            $subject,
            $bodyHtml,
            $bodyText !== '' ? $bodyText : null
        );

        if (!$res['ok']) {
            return $res;
        }

        // Only persist the new credentials if the email actually went out.
        SchoolLogin::setPassword($schoolLoginId, $newPassword);
        Database::execute(
            'UPDATE school_logins
                SET credentials_sent_at = NOW()
              WHERE school_login_id = ?',
            [$schoolLoginId]
        );

        return ['ok' => true, 'password' => $newPassword];
    }

    /**
     * Auto-send wrapper used after slot assignment.
     * Sends credentials for every active login of the school that has not
     * been sent before (or always, when $force is true).
     *
     * @return array{sent:int, skipped:int, failed:int}
     */
    public static function sendCredentialsForSchool(int $schoolId, bool $force = false): array
    {
        $logins = Database::fetchAll(
            'SELECT school_login_id, credentials_sent_at
               FROM school_logins
              WHERE school_id = ? AND status = "active"',
            [$schoolId]
        );
        $sent = $skipped = $failed = 0;
        foreach ($logins as $l) {
            if (!$force && !empty($l['credentials_sent_at'])) {
                $skipped++; continue;
            }
            $r = self::sendCredentials((int)$l['school_login_id']);
            if ($r['ok']) $sent++; else $failed++;
        }
        return ['sent' => $sent, 'skipped' => $skipped, 'failed' => $failed];
    }
}
