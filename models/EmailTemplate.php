<?php
final class EmailTemplate
{
    public static function all(): array
    {
        return Database::fetchAll(
            'SELECT * FROM email_templates ORDER BY name ASC'
        );
    }

    public static function find(string $key): ?array
    {
        return Database::fetch(
            'SELECT * FROM email_templates WHERE template_key = ?',
            [$key]
        );
    }

    public static function update(string $key, array $d, ?int $byAdminId = null): void
    {
        Database::execute(
            'UPDATE email_templates
                SET name = ?, subject = ?, body_html = ?, body_text = ?,
                    placeholders = ?, updated_by_admin_id = ?
              WHERE template_key = ?',
            [
                $d['name'],
                $d['subject'],
                $d['body_html'],
                $d['body_text'] ?: null,
                $d['placeholders'] ?: null,
                $byAdminId,
                $key,
            ]
        );
    }
}
