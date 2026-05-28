-- ============================================================================
-- 2026-05-28: Association users can author questions and submit them to the
-- expert panel for review.
--
-- 1. Add `created_by_assoc_user_id` (nullable) so we know which association
--    user authored a question. Panelist-authored rows still use
--    `submitted_by_panelist_id`.
-- 2. Add `submitted_at` to record when a draft was pushed into the review
--    queue.
-- 3. Add `draft` to the status enum (initial state for association-authored
--    questions before they're submitted to the panel).
-- ============================================================================

ALTER TABLE association_question_bank
    ADD COLUMN created_by_assoc_user_id INT UNSIGNED NULL
        AFTER submitted_by_panelist_id,
    ADD COLUMN submitted_at DATETIME NULL
        AFTER reject_reason,
    MODIFY COLUMN status
        ENUM('draft','pending','approved','rejected','needs_revision')
        NOT NULL DEFAULT 'draft',
    ADD KEY idx_qb_created_by_au (created_by_assoc_user_id),
    ADD CONSTRAINT fk_qb_created_by_au
        FOREIGN KEY (created_by_assoc_user_id)
            REFERENCES association_users(association_user_id)
        ON DELETE SET NULL;
