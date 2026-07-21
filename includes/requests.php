<?php

declare(strict_types=1);

function list_requester_sectors(bool $activeOnly = false): array
{
    $sql = 'SELECT id, name, acronym, active, created_at FROM requester_sectors';

    if ($activeOnly) {
        $sql .= ' WHERE active = 1';
    }

    $sql .= ' ORDER BY name';

    return db()->query($sql)->fetchAll();
}

function create_requester_sector(string $name, string $acronym = ''): void
{
    if ($name === '') {
        throw new RuntimeException('Informe o nome do setor solicitante.');
    }

    $stmt = db()->prepare(
        'INSERT INTO requester_sectors (name, acronym)
         VALUES (:name, :acronym)'
    );
    $stmt->execute([
        'name' => $name,
        'acronym' => $acronym !== '' ? $acronym : null,
    ]);
}

function create_material_loan_request(
    array $user,
    int $itemId,
    string $borrowerName,
    string $registrationNumber,
    string $responsibleTeacher,
    ?string $returnDueDate,
    int $requestedQuantity,
    string $otherMaterials,
    bool $rulesAccepted
): void {
    if (!is_requester($user)) {
        throw new RuntimeException('Apenas usuarios solicitantes podem abrir solicitacoes.');
    }

    $requesterSectorId = (int) ($user['requester_sector_id'] ?? 0);

    if ($requesterSectorId <= 0) {
        throw new RuntimeException('Usuario solicitante sem setor vinculado.');
    }

    $blockState = requester_block_state((int) $user['id']);

    if ($blockState['blocked']) {
        throw new RuntimeException('Usuario bloqueado para novos emprestimos ate ' . $blockState['blocked_until_label'] . '.');
    }

    if ($borrowerName === '') {
        throw new RuntimeException('Informe o nome do requisitante.');
    }

    if (!$rulesAccepted) {
        throw new RuntimeException('Aceite as regras do termo para solicitar emprestimo.');
    }

    if (!$returnDueDate) {
        throw new RuntimeException('Informe a data para devolucao.');
    }

    $dueDate = DateTimeImmutable::createFromFormat('Y-m-d', $returnDueDate);
    $today = new DateTimeImmutable('today');

    if (!$dueDate || $dueDate < $today) {
        throw new RuntimeException('A data para devolucao deve ser hoje ou uma data futura.');
    }

    if ($itemId <= 0) {
        throw new RuntimeException('Selecione um item cadastrado.');
    }

    $requestedQuantity = max(1, $requestedQuantity);
    $item = find_item_for_sector($itemId, 'almoxarifado');

    if (!$item) {
        throw new RuntimeException('Item nao encontrado no almoxarifado.');
    }

    $stmt = db()->prepare(
        'INSERT INTO material_loans (
            requester_sector_id,
            requester_user_id,
            item_id,
            borrower_name,
            registration_number,
            responsible_teacher,
            return_due_date,
            requested_quantity,
            other_materials,
            rules_accepted
        ) VALUES (
            :requester_sector_id,
            :requester_user_id,
            :item_id,
            :borrower_name,
            :registration_number,
            :responsible_teacher,
            :return_due_date,
            :requested_quantity,
            :other_materials,
            :rules_accepted
        )'
    );

    $stmt->execute([
        'requester_sector_id' => $requesterSectorId,
        'requester_user_id' => (int) $user['id'],
        'item_id' => $itemId,
        'borrower_name' => $borrowerName,
        'registration_number' => $registrationNumber !== '' ? $registrationNumber : null,
        'responsible_teacher' => $responsibleTeacher !== '' ? $responsibleTeacher : null,
        'return_due_date' => $returnDueDate,
        'requested_quantity' => $requestedQuantity,
        'other_materials' => $otherMaterials !== '' ? $otherMaterials : null,
        'rules_accepted' => 1,
    ]);
}

function list_material_loans_for_user(array $user): array
{
    $params = [];
    $where = '';

    if (is_requester($user)) {
        $where = 'WHERE ml.requester_sector_id = :requester_sector_id';
        $params['requester_sector_id'] = (int) ($user['requester_sector_id'] ?? 0);
    } elseif (!is_almoxarifado_manager($user)) {
        throw new RuntimeException('Acesso negado.');
    }

    $stmt = db()->prepare(
        'SELECT
            ml.*,
            i.name AS item_name,
            i.brand_model,
            i.patrimony_number,
            i.serial_number,
            rs.name AS requester_sector_name,
            rs.acronym AS requester_sector_acronym,
            ru.name AS requester_user_name,
            ru.loan_blocked_until,
            ru.loan_infraction_count,
            mu.name AS manager_user_name
         FROM material_loans ml
         INNER JOIN items i ON i.id = ml.item_id
         INNER JOIN requester_sectors rs ON rs.id = ml.requester_sector_id
         INNER JOIN users ru ON ru.id = ml.requester_user_id
         LEFT JOIN users mu ON mu.id = ml.manager_user_id
         ' . $where . '
         ORDER BY ml.requested_at DESC'
    );
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function update_material_loan_status(int $loanId, array $manager, string $action): void
{
    if (!is_almoxarifado_manager($manager)) {
        throw new RuntimeException('Apenas o gestor do almoxarifado pode registrar retirada ou devolucao.');
    }

    $stmt = db()->prepare(
        'SELECT ml.*, i.quantity
         FROM material_loans ml
         INNER JOIN items i ON i.id = ml.item_id
         WHERE ml.id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $loanId]);
    $loan = $stmt->fetch();

    if (!$loan) {
        throw new RuntimeException('Solicitacao nao encontrada.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        if ($action === 'withdraw') {
            if ($loan['status'] !== 'solicitada') {
                throw new RuntimeException('Somente solicitacoes pendentes podem virar retirada.');
            }

            $newQuantity = (int) $loan['quantity'] - (int) $loan['requested_quantity'];

            if ($newQuantity < 0) {
                throw new RuntimeException('Estoque insuficiente para registrar a retirada.');
            }

            update_item_stock((int) $loan['item_id'], 'almoxarifado', $newQuantity, (int) $manager['id']);

            $update = db()->prepare(
                'UPDATE material_loans
                 SET status = \'retirada\', manager_user_id = :manager_user_id, withdrawn_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $update->execute([
                'id' => $loanId,
                'manager_user_id' => (int) $manager['id'],
            ]);
        } elseif ($action === 'return') {
            if ($loan['status'] !== 'retirada') {
                throw new RuntimeException('Somente itens retirados podem ser registrados como devolvidos.');
            }

            $newQuantity = (int) $loan['quantity'] + (int) $loan['requested_quantity'];
            update_item_stock((int) $loan['item_id'], 'almoxarifado', $newQuantity, (int) $manager['id']);

            $update = db()->prepare(
                'UPDATE material_loans
                 SET status = \'devolvida\', manager_user_id = :manager_user_id, returned_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $update->execute([
                'id' => $loanId,
                'manager_user_id' => (int) $manager['id'],
            ]);

            if (loan_is_overdue($loan) && empty($loan['infraction_at'])) {
                apply_requester_block($loan, $manager, 'Devolucao registrada fora do prazo.');
            }
        } else {
            throw new RuntimeException('Acao invalida.');
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function renew_material_loan_due_date(int $loanId, array $manager, string $newDueDate): void
{
    if (!is_almoxarifado_manager($manager)) {
        throw new RuntimeException('Apenas o gestor do almoxarifado pode renovar prazo.');
    }

    $dueDate = DateTimeImmutable::createFromFormat('Y-m-d', $newDueDate);
    $today = new DateTimeImmutable('today');

    if (!$dueDate || $dueDate < $today) {
        throw new RuntimeException('Informe uma nova data de devolucao valida.');
    }

    $stmt = db()->prepare(
        'UPDATE material_loans
         SET return_due_date = :return_due_date, manager_user_id = :manager_user_id
         WHERE id = :id
           AND status = \'retirada\''
    );
    $stmt->execute([
        'id' => $loanId,
        'return_due_date' => $newDueDate,
        'manager_user_id' => (int) $manager['id'],
    ]);

    if ($stmt->rowCount() === 0) {
        throw new RuntimeException('Somente emprestimos retirados podem ter prazo renovado.');
    }
}

function register_material_loan_infraction(int $loanId, array $manager, string $reason): int
{
    if (!is_almoxarifado_manager($manager)) {
        throw new RuntimeException('Apenas o gestor do almoxarifado pode registrar infracao.');
    }

    $reason = trim($reason);

    if ($reason === '') {
        $reason = 'Descumprimento das regras do termo de emprestimo.';
    }

    $stmt = db()->prepare(
        'SELECT ml.*, i.quantity
         FROM material_loans ml
         INNER JOIN items i ON i.id = ml.item_id
         WHERE ml.id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $loanId]);
    $loan = $stmt->fetch();

    if (!$loan) {
        throw new RuntimeException('Solicitacao nao encontrada.');
    }

    if ($loan['status'] !== 'retirada') {
        throw new RuntimeException('A infracao deve ser registrada em um emprestimo retirado.');
    }

    if (!empty($loan['infraction_at'])) {
        throw new RuntimeException('Esta solicitacao ja possui infracao registrada.');
    }

    return apply_requester_block($loan, $manager, $reason);
}

function apply_requester_block(array $loan, array $manager, string $reason): int
{
    $pdo = db();
    $userId = (int) $loan['requester_user_id'];
    $stmt = $pdo->prepare('SELECT loan_infraction_count FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $currentCount = (int) $stmt->fetchColumn();
    $newCount = $currentCount + 1;
    $blockDays = max(2, $newCount * 2);
    $blockedUntil = (new DateTimeImmutable())->modify('+' . $blockDays . ' days')->format('Y-m-d H:i:s');

    $updateUser = $pdo->prepare(
        'UPDATE users
         SET loan_infraction_count = :loan_infraction_count,
             loan_blocked_until = :loan_blocked_until
         WHERE id = :id'
    );
    $updateUser->execute([
        'id' => $userId,
        'loan_infraction_count' => $newCount,
        'loan_blocked_until' => $blockedUntil,
    ]);

    $updateLoan = $pdo->prepare(
        'UPDATE material_loans
         SET manager_user_id = :manager_user_id,
             infraction_at = CURRENT_TIMESTAMP,
             infraction_reason = :infraction_reason,
             block_days = :block_days
         WHERE id = :id'
    );
    $updateLoan->execute([
        'id' => (int) $loan['id'],
        'manager_user_id' => (int) $manager['id'],
        'infraction_reason' => $reason,
        'block_days' => $blockDays,
    ]);

    return $blockDays;
}

function requester_block_state(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT loan_blocked_until, loan_infraction_count
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch() ?: [];
    $blockedUntil = (string) ($row['loan_blocked_until'] ?? '');
    $blocked = false;
    $label = '';

    if ($blockedUntil !== '') {
        $blocked = strtotime($blockedUntil) > time();
        $label = date('d/m/Y H:i', strtotime($blockedUntil));
    }

    return [
        'blocked' => $blocked,
        'blocked_until' => $blockedUntil,
        'blocked_until_label' => $label,
        'infraction_count' => (int) ($row['loan_infraction_count'] ?? 0),
    ];
}

function loan_is_overdue(array $loan): bool
{
    if (empty($loan['return_due_date'])) {
        return false;
    }

    return strtotime((string) $loan['return_due_date'] . ' 23:59:59') < time();
}

function material_loan_status_label(string $status): string
{
    return [
        'solicitada' => 'Solicitada',
        'retirada' => 'Retirada',
        'devolvida' => 'Devolvida',
        'cancelada' => 'Cancelada',
    ][$status] ?? $status;
}
