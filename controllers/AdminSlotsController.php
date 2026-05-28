<?php
final class AdminSlotsController
{
    private const STATUSES = ['scheduled', 'open', 'closed', 'cancelled'];

    public function index(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        render('admin/slots/index', [
            'title' => 'Slots — Admin',
            'slots' => Slot::all(),
        ]);
    }

    public function create(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        render('admin/slots/form', [
            'title'    => 'Add Slot',
            'slot'     => null,
            'rounds'   => Round::all(),
            'statuses' => self::STATUSES,
        ]);
    }

    public function store(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();

        // If admin left ends_at blank, derive from the round's slot_duration.
        $this->fillEndsAt($_POST);

        $v = $this->validate($_POST, null);
        if ($v->fails()) {
            flash_errors($v->errors());
            flash_old($_POST);
            redirect('/admin/slots/new');
        }

        $id = Slot::create($_POST);
        flash_set('success', 'Slot created. Now assign schools to it.');
        redirect('/admin/slots/' . $id . '/assign');
    }

    public function edit(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        $slot = Slot::find((int)$id);
        if (!$slot) { http_response_code(404); render('errors/404'); return; }
        render('admin/slots/form', [
            'title'    => 'Edit Slot',
            'slot'     => $slot,
            'rounds'   => Round::all(),
            'statuses' => self::STATUSES,
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();

        $slot = Slot::find((int)$id);
        if (!$slot) { http_response_code(404); render('errors/404'); return; }

        $this->fillEndsAt($_POST);

        $v = $this->validate($_POST, (int)$id);
        if ($v->fails()) {
            flash_errors($v->errors());
            flash_old($_POST);
            redirect('/admin/slots/' . $id . '/edit');
        }

        Slot::update((int)$id, $_POST);
        flash_set('success', 'Slot updated.');
        redirect('/admin/slots');
    }

    public function destroy(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();
        try {
            Slot::delete((int)$id);
            flash_set('success', 'Slot deleted.');
        } catch (Throwable $e) {
            flash_set('error', 'Cannot delete: slot has attached attempts or questions.');
        }
        redirect('/admin/slots');
    }

    /* ----- Assignment ----- */

    public function assignForm(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        $slot = Slot::find((int)$id);
        if (!$slot) { http_response_code(404); render('errors/404'); return; }

        render('admin/slots/assign', [
            'title'     => 'Assign Schools — ' . $slot['slot_label'],
            'slot'      => $slot,
            'assigned'  => Slot::assignedSchools((int)$id),
            'available' => Slot::availableSchools(
                (int)$id, (int)$slot['round_id'], (int)$slot['association_id']
            ),
        ]);
    }

    public function assignSave(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();

        $slot = Slot::find((int)$id);
        if (!$slot) { http_response_code(404); render('errors/404'); return; }

        $schoolIds = $_POST['school_ids'] ?? [];
        if (!is_array($schoolIds)) $schoolIds = [];

        // Capacity guard
        $current = Slot::assignedSchools((int)$id);
        $room = (int)$slot['capacity'] - count($current);
        if (count($schoolIds) > $room) {
            flash_set('error', 'This slot has only ' . max(0, $room) . ' seats left.');
            redirect('/admin/slots/' . $id . '/assign');
        }

        $assigned = 0;
        foreach ($schoolIds as $sid) {
            $sidInt = (int)$sid;
            if ($sidInt <= 0) continue;
            // Pick the school's first active login as the team account.
            $login = Database::fetch(
                "SELECT school_login_id FROM school_logins
                  WHERE school_id = ? AND status = 'active'
                  ORDER BY school_login_id LIMIT 1",
                [$sidInt]
            );
            Slot::assignSchool((int)$id, $sidInt, $login['school_login_id'] ?? null, Auth::id());
            $assigned++;
        }

        flash_set('success', $assigned . ' school' . ($assigned === 1 ? '' : 's') . ' assigned.');
        redirect('/admin/slots/' . $id . '/assign');
    }

    public function unassign(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();

        $schoolId = (int)($_POST['school_id'] ?? 0);
        if ($schoolId > 0) {
            Slot::unassignSchool((int)$id, $schoolId);
            flash_set('success', 'School removed from slot.');
        }
        redirect('/admin/slots/' . $id . '/assign');
    }

    /* ----- Helpers ----- */

    private function fillEndsAt(array &$d): void
    {
        if (!empty($d['ends_at']) || empty($d['starts_at']) || empty($d['round_id'])) return;
        $round = Round::find((int)$d['round_id']);
        if (!$round) return;
        $ts = strtotime((string)$d['starts_at']);
        if ($ts === false) return;
        $duration = (int)($round['slot_duration_minutes'] ?? 30);
        $d['ends_at'] = date('Y-m-d\TH:i', $ts + $duration * 60);
    }

    private function validate(array $d, ?int $exceptId): Validator
    {
        $v = (new Validator($d))
            ->required('round_id', 'Round')->integer('round_id')
            ->max('slot_label', 100)
            ->required('starts_at', 'Starts at')->datetime('starts_at')
            ->required('ends_at',   'Ends at')->datetime('ends_at')
            ->required('capacity',  'Capacity')->integer('capacity')
            ->in('status', self::STATUSES, 'Status');

        if (!empty($d['starts_at']) && !empty($d['ends_at'])
            && strtotime($d['starts_at']) !== false
            && strtotime($d['ends_at']) !== false
            && strtotime($d['ends_at']) <= strtotime($d['starts_at'])) {
            $v->addError('ends_at', 'Ends at must be after starts at.');
        }

        if ($v->passes() && !empty($d['round_id'])) {
            $conflict = Slot::findConflict(
                (int)$d['round_id'],
                (string)$d['starts_at'],
                (string)$d['ends_at'],
                $exceptId
            );
            if ($conflict) {
                $v->addError(
                    'starts_at',
                    'Conflicts with existing slot "' . ($conflict['slot_label'] ?? '#' . $conflict['slot_id'])
                    . '" (' . dt_display($conflict['starts_at'], 'd M, H:i') . ' – '
                    . dt_display($conflict['ends_at'], 'H:i') . ').'
                );
            }
        }

        return $v;
    }
}
