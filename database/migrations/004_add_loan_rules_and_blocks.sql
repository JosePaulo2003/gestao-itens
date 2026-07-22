-- Regras do termo, prazo obrigatorio e bloqueio progressivo por infracao.
-- Guarda bloqueio no proprio usuario para barrar novas solicitacoes logo no login/tela.
ALTER TABLE users
    ADD COLUMN loan_blocked_until DATETIME NULL AFTER requester_sector_id,
    ADD COLUMN loan_infraction_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER loan_blocked_until;

-- Pedidos antigos sem prazo recebem amanhã para permitir tornar a coluna obrigatória.
UPDATE material_loans
SET return_due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
WHERE return_due_date IS NULL;

-- O aceite do termo e a infracao ficam registrados no proprio emprestimo.
ALTER TABLE material_loans
    MODIFY return_due_date DATE NOT NULL,
    ADD COLUMN rules_accepted TINYINT(1) NOT NULL DEFAULT 0 AFTER other_materials,
    ADD COLUMN infraction_at TIMESTAMP NULL AFTER returned_at,
    ADD COLUMN infraction_reason VARCHAR(255) NULL AFTER infraction_at,
    ADD COLUMN block_days INT UNSIGNED NULL AFTER infraction_reason;
