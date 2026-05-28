<?php
final class PanelistSlotsController
{
    public function index(): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        $associationId = $this->associationId();
        render('panelist/slots/index', [
            'title' => 'Slot Builder — Panel',
            'slots' => SlotQuestion::slotsForAssociation($associationId),
        ]);
    }

    public function build(string $id): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        $associationId = $this->associationId();
        $slot = Slot::find((int)$id);
        if (!$slot || (int)$slot['association_id'] !== $associationId) {
            http_response_code(404); render('errors/404'); return;
        }

        $filters = [
            'difficulty' => $_GET['difficulty'] ?? '',
            'sport'      => $_GET['sport']      ?? '',
            'round'      => $_GET['round']      ?? '',
        ];

        $sports = Database::fetchAll(
            "SELECT DISTINCT sport FROM master_questions
              WHERE association_id = ? AND status = 'active' AND sport IS NOT NULL
              ORDER BY sport",
            [$associationId]
        );

        render('panelist/slots/build', [
            'title'    => 'Build ' . ($slot['slot_label'] ?? '#' . $id),
            'slot'     => $slot,
            'bank'     => SlotQuestion::bankForSlot((int)$id, $associationId, $filters),
            'assigned' => SlotQuestion::forSlot((int)$id),
            'filters'  => $filters,
            'sports'   => array_column($sports, 'sport'),
        ]);
    }

    /* ----- JSON API (used by SortableJS via AJAX) ----- */

    public function apiAssign(): void
    {
        $this->guardApi();
        $slot = $this->slotForCurrentPanelist((int)($_POST['slot_id'] ?? 0));
        $qid  = (int)($_POST['master_question_id'] ?? 0);
        if ($qid <= 0) {
            $this->json(['ok' => false, 'error' => 'Missing master_question_id'], 422);
        }

        // Sanity: question must exist in this panelist's association
        $own = Database::fetch(
            "SELECT master_question_id FROM master_questions
              WHERE master_question_id = ? AND association_id = ?",
            [$qid, $slot['association_id']]
        );
        if (!$own) $this->json(['ok' => false, 'error' => 'Question not in association'], 422);

        SlotQuestion::assign((int)$slot['slot_id'], $qid);
        $this->json($this->summary($slot));
    }

    public function apiUnassign(): void
    {
        $this->guardApi();
        $slot = $this->slotForCurrentPanelist((int)($_POST['slot_id'] ?? 0));
        $qid  = (int)($_POST['master_question_id'] ?? 0);
        if ($qid <= 0) $this->json(['ok' => false, 'error' => 'Missing master_question_id'], 422);

        SlotQuestion::unassign((int)$slot['slot_id'], $qid);
        $this->json($this->summary($slot));
    }

    public function apiReorder(): void
    {
        $this->guardApi();
        $slot = $this->slotForCurrentPanelist((int)($_POST['slot_id'] ?? 0));
        $ids  = $_POST['master_question_ids'] ?? [];
        if (!is_array($ids)) $ids = [];

        SlotQuestion::reorder((int)$slot['slot_id'], $ids);
        $this->json($this->summary($slot));
    }

    /* ----- helpers ----- */

    private function associationId(): int
    {
        $id = Auth::associationId();
        if (!$id) { http_response_code(403); render('errors/403'); exit; }
        return $id;
    }

    private function guardApi(): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        Csrf::requireValidRequest();
    }

    private function slotForCurrentPanelist(int $slotId): array
    {
        $slot = $slotId > 0 ? Slot::find($slotId) : null;
        if (!$slot || (int)$slot['association_id'] !== (int)Auth::associationId()) {
            $this->json(['ok' => false, 'error' => 'Slot not accessible'], 403);
        }
        return $slot;
    }

    private function summary(array $slot): array
    {
        return [
            'ok'      => true,
            'slot_id' => (int)$slot['slot_id'],
            'count'   => SlotQuestion::countForSlot((int)$slot['slot_id']),
            'target'  => (int)$slot['questions_per_quiz'],
        ];
    }

    private function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
