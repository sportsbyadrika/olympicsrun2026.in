<?php
/**
 * Two participants per team (school_login). Each has personal details and an
 * optional cropped passport photo stored under uploads/participants/ (outside
 * the web root, served via AdminSchoolsController::participantPhoto()).
 */
final class TeamParticipant
{
    public const GENDERS = ['male', 'female', 'other'];

    public static function photoDir(): string
    {
        return dirname(__DIR__) . '/uploads/participants';
    }

    /** @return array<int,array<string,mixed>> rows ordered by slot */
    public static function forLogin(int $loginId): array
    {
        return Database::fetchAll(
            'SELECT * FROM team_participants WHERE school_login_id = ? ORDER BY slot',
            [$loginId]
        );
    }

    /** @return array<int,array<string,mixed>> map slot => row */
    public static function mapForLogin(int $loginId): array
    {
        $out = [];
        foreach (self::forLogin($loginId) as $row) {
            $out[(int)$row['slot']] = $row;
        }
        return $out;
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT * FROM team_participants WHERE participant_id = ?',
            [$id]
        );
    }

    private static function getSlot(int $loginId, int $slot): ?array
    {
        return Database::fetch(
            'SELECT * FROM team_participants WHERE school_login_id = ? AND slot = ?',
            [$loginId, $slot]
        );
    }

    /**
     * Upsert one participant slot (1 or 2) for a team.
     *
     * $d keys: name, standard, age, gender.
     * $photoData is an optional base64 data-URL (only when the admin uploaded
     * a new crop); otherwise the existing photo is kept.
     */
    public static function save(int $loginId, int $slot, array $d, ?string $photoData): void
    {
        $name     = trim((string)($d['name'] ?? ''));
        $existing = self::getSlot($loginId, $slot);

        // Resolve photo: keep existing unless a fresh crop was submitted.
        $photo = $existing['photo_path'] ?? null;
        if ($photoData !== null && $photoData !== '') {
            $new = self::processPhoto($photoData, $loginId, $slot);
            if ($new !== null) {
                if (!empty($photo)) self::deletePhotoFile($photo);
                $photo = $new;
            }
        }

        // Nothing to create if there's no name and no existing row.
        if ($name === '' && !$existing) {
            return;
        }

        $standard = trim((string)($d['standard'] ?? ''));
        $standard = $standard !== '' ? $standard : null;
        $age      = isset($d['age']) && $d['age'] !== '' ? (int)$d['age'] : null;
        $gender   = in_array($d['gender'] ?? '', self::GENDERS, true) ? $d['gender'] : null;

        if ($existing) {
            Database::execute(
                'UPDATE team_participants
                    SET participant_name = ?, studying_standard = ?, age = ?,
                        gender = ?, photo_path = ?
                  WHERE participant_id = ?',
                [
                    $name !== '' ? $name : $existing['participant_name'],
                    $standard, $age, $gender, $photo,
                    (int)$existing['participant_id'],
                ]
            );
        } else {
            Database::insert(
                'INSERT INTO team_participants
                    (school_login_id, slot, participant_name, studying_standard,
                     age, gender, photo_path)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$loginId, $slot, $name, $standard, $age, $gender, $photo]
            );
        }
    }

    /**
     * Decode a base64 image data-URL, re-encode through GD (which also
     * validates it's a real image) and write a passport-ratio JPEG.
     * @return string|null saved filename, or null on failure.
     */
    private static function processPhoto(string $dataUrl, int $loginId, int $slot): ?string
    {
        if (!preg_match('#^data:image/[\w.+-]+;base64,#i', $dataUrl)) {
            return null;
        }
        $raw = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);
        if ($raw === false || $raw === '') {
            return null;
        }
        if (!function_exists('imagecreatefromstring')) {
            return null; // GD missing — skip photo, keep the rest.
        }
        $img = @imagecreatefromstring($raw);
        if (!$img) {
            return null;
        }

        // Cap to a sane passport size (keeps files small).
        $w = imagesx($img);
        $h = imagesy($img);
        $maxW = 480;
        if ($w > $maxW) {
            $nh  = (int)round($h * $maxW / $w);
            $dst = imagecreatetruecolor($maxW, $nh);
            imagecopyresampled($dst, $img, 0, 0, 0, 0, $maxW, $nh, $w, $h);
            imagedestroy($img);
            $img = $dst;
        }

        $dir = self::photoDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $fname = sprintf('p_%d_%d_%s.jpg', $loginId, $slot, bin2hex(random_bytes(3)));
        $ok = imagejpeg($img, $dir . '/' . $fname, 85);
        imagedestroy($img);

        return $ok ? $fname : null;
    }

    private static function deletePhotoFile(string $filename): void
    {
        $path = self::photoDir() . '/' . basename($filename);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
