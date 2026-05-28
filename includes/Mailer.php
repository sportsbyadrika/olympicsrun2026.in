<?php
/**
 * Thin SMTP wrapper around PHPMailer. Reads SMTP credentials from the
 * settings table at send time so admins can rotate them without redeploying.
 *
 * If PHPMailer isn't installed (Composer not yet run) this class logs and
 * returns ['ok' => false, 'error' => '...'] so the rest of the app keeps
 * working — no hard dependency at boot.
 */
final class Mailer
{
    /**
     * @return array{ok:bool, error?:string}
     */
    public static function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $bodyHtml,
        ?string $bodyText = null
    ): array {
        if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            return ['ok' => false,
                    'error' => 'PHPMailer not installed. Run `composer install` in the project root.'];
        }
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Recipient email is not valid.'];
        }

        $host   = Settings::get('mail_smtp_host', '');
        $port   = Settings::int('mail_smtp_port', 587);
        $user   = Settings::get('mail_smtp_user', '');
        $pass   = Settings::get('mail_smtp_pass', '');
        $secure = Settings::get('mail_smtp_secure', 'tls');
        $fromEmail = Settings::get('mail_from_email', 'no-reply@olympicsrun2026.in');
        $fromName  = Settings::get('mail_from_name', 'Olympics Run 2026');

        if ($host === '' || $host === 'smtp.example.com') {
            return ['ok' => false,
                    'error' => 'SMTP is not configured. Set mail_smtp_* in Settings first.'];
        }

        $m = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $m->isSMTP();
            $m->Host        = $host;
            $m->Port        = $port;
            if ($user !== '') {
                $m->SMTPAuth = true;
                $m->Username = $user;
                $m->Password = (string)$pass;
            }
            if ($secure === 'ssl') {
                $m->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($secure === 'tls') {
                $m->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            $m->CharSet = 'UTF-8';
            $m->setFrom($fromEmail, $fromName);
            $m->addAddress($toEmail, $toName);
            $m->Subject = $subject;
            $m->isHTML(true);
            $m->Body    = $bodyHtml;
            $m->AltBody = $bodyText ?? strip_tags($bodyHtml);

            $m->send();
            return ['ok' => true];
        } catch (Throwable $e) {
            error_log('Mailer: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /** Replace {{name}} tokens with values from $vars (empty for unknown keys). */
    public static function render(string $template, array $vars): string
    {
        return preg_replace_callback(
            '/\{\{\s*(\w+)\s*\}\}/',
            static fn($m) => isset($vars[$m[1]]) ? (string)$vars[$m[1]] : '',
            $template
        );
    }
}
