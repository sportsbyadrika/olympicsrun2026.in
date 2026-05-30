<?php
/**
 * Global settings editor. Renders every row from the `settings` table grouped
 * for readability, and saves edits back through Settings::setMany() (which
 * whitelists by existing keys and normalises by declared type).
 */
final class AdminSettingsController
{
    public function index(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        render('admin/settings/index', [
            'title'    => 'Settings — Admin',
            'settings' => Settings::allWithMeta(),
        ]);
    }

    public function update(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();

        $values = $_POST['settings'] ?? [];
        if (!is_array($values)) {
            $values = [];
        }

        // Unchecked checkboxes don't appear in POST — fold them back in as
        // 'false' so booleans can be turned off.
        foreach (Settings::allWithMeta() as $row) {
            if ($row['value_type'] === 'bool' && !array_key_exists($row['setting_key'], $values)) {
                $values[$row['setting_key']] = 'false';
            }
        }

        $n = Settings::setMany($values, Auth::id());
        flash_set('success', $n . ' setting' . ($n === 1 ? '' : 's') . ' saved.');
        redirect('/admin/settings');
    }
}
