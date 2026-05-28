-- ============================================================================
-- 2026-05-28: Per-response status to support the school quiz engine.
--
-- Each row in `responses` is editable while the school is taking the quiz
-- (status = 'draft') and locked once Finish or force-submit fires
-- (status = 'submitted'). Attempt-level state still lives on slot_schools.
-- ============================================================================

ALTER TABLE responses
    ADD COLUMN status ENUM('draft','submitted')
        NOT NULL DEFAULT 'draft'
        AFTER chosen_option,
    ADD KEY idx_resp_status (status);
