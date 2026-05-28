-- ============================================================================
-- 2026-05-28: Expert panelists become first-class actors on the question bank.
--
-- 1. association_question_bank.reviewed_by_panelist_id  — who from the panel
--    approved / rejected / asked for revision. Parallel to the existing
--    reviewed_by_user_id (which is still used when admin / association user
--    handles a review).
--
-- 2. master_questions.added_by_panelist_id — who from the panel inserted a
--    row directly into the master pool (vs the existing added_by_admin_id).
-- ============================================================================

ALTER TABLE association_question_bank
    ADD COLUMN reviewed_by_panelist_id INT UNSIGNED NULL
        AFTER reviewed_by_user_id,
    ADD KEY idx_qb_reviewer_pn (reviewed_by_panelist_id),
    ADD CONSTRAINT fk_qb_reviewer_pn
        FOREIGN KEY (reviewed_by_panelist_id)
            REFERENCES expert_panelists(panelist_id)
        ON DELETE SET NULL;

ALTER TABLE master_questions
    ADD COLUMN added_by_panelist_id INT UNSIGNED NULL
        AFTER added_by_admin_id,
    ADD KEY idx_mq_panelist (added_by_panelist_id),
    ADD CONSTRAINT fk_mq_panelist
        FOREIGN KEY (added_by_panelist_id)
            REFERENCES expert_panelists(panelist_id)
        ON DELETE SET NULL;
