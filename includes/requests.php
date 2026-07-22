<?php

declare(strict_types=1);

/*
 * Regras de setores solicitantes e empréstimos.
 *
 * Este arquivo controla o ciclo completo de uma solicitação:
 * cadastro do setor solicitante, abertura do pedido, retirada,
 * devolução, renovação de prazo e bloqueio por infração.
 */

function list_requester_sectors(bool $activeOnly = false): array
{
    // Monta a consulta com filtro opcional para reutilizar em telas públicas e administrativas.
    $sql = 'SELECT id, name, acronym, active, created_at FROM requester_sectors';

    if ($activeOnly) {
        $sql .= ' WHERE active = 1';
    }

    $sql .= ' ORDER BY name';

    return db()->query($sql)->fetchAll();
}

function create_requester_sector(string $name, string $acronym = ''): void
{
    // O nome é obrigatório porque aparece nas solicitações e nos relatórios.
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
    // Somente usuários do perfil solicitante podem abrir empréstimos.
    if (!is_requester($user)) {
        throw new RuntimeException('Apenas usuários solicitantes podem abrir solicitações.');
    }

    // O setor do próprio usuário define de qual estoque o item será retirado.
    $ownerSector = (string) ($user['sector'] ?? '');

    // CTIC ficou fora da regra de empréstimos por definição do sistema.
    if ($ownerSector === '' || $ownerSector === 'ctic') {
        throw new RuntimeException('Este setor não recebe solicitações de empréstimo.');
    }

    // Todo solicitante precisa estar vinculado a um setor solicitante cadastrado.
    $requesterSectorId = (int) ($user['requester_sector_id'] ?? 0);

    if ($requesterSectorId <= 0) {
        throw new RuntimeException('Usuário solicitante sem setor vinculado.');
    }

    $blockState = requester_block_state((int) $user['id']);

    // Usuários bloqueados por atraso ou infração não podem abrir novos pedidos.
    if ($blockState['blocked']) {
        throw new RuntimeException('Usuário bloqueado para novos empréstimos até ' . $blockState['blocked_until_label'] . '.');
    }

    if ($borrowerName === '') {
        throw new RuntimeException('Informe o nome do requisitante.');
    }

    if (!$rulesAccepted) {
        throw new RuntimeException('Aceite as regras do termo para solicitar empréstimo.');
    }

    if (!$returnDueDate) {
        throw new RuntimeException('Informe a data para devolução.');
    }

    $dueDate = DateTimeImmutable::createFromFormat('Y-m-d', $returnDueDate);
    $today = new DateTimeImmutable('today');

    // A devolução nunca pode nascer vencida; pedidos vencidos são tratados depois como infração.
    if (!$dueDate || $dueDate < $today) {
        throw new RuntimeException('A data para devolução deve ser hoje ou uma data futura.');
    }

    if ($itemId <= 0) {
        throw new RuntimeException('Selecione um item cadastrado.');
    }

    $requestedQuantity = max(1, $requestedQuantity);
    $item = find_item_for_sector($itemId, $ownerSector);

    // Garante que o solicitante não peça item de outro setor alterando o HTML.
    if (!$item) {
        throw new RuntimeException('Item não encontrado neste setor.');
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
    // A mesma listagem serve para solicitante e gestor, mas com escopos diferentes.
    $params = [];
    $where = '';

    if (is_requester($user)) {
        // Solicitante vê apenas pedidos do próprio setor solicitante e do próprio setor dono.
        $where = 'WHERE ml.requester_sector_id = :requester_sector_id AND i.sector = :owner_sector';
        $params['requester_sector_id'] = (int) ($user['requester_sector_id'] ?? 0);
        $params['owner_sector'] = (string) ($user['sector'] ?? '');
    } elseif (can_manage_loan_requests($user)) {
        // Gestor vê todos os pedidos que movimentam o estoque do setor dele.
        $where = 'WHERE i.sector = :owner_sector';
        $params['owner_sector'] = (string) $user['sector'];
    } else {
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
    // Somente gestor de setor habilitado pode baixar estoque ou registrar devolução.
    if (!can_manage_loan_requests($manager)) {
        throw new RuntimeException('Apenas o gestor do setor pode registrar retirada ou devolução.');
    }

    $stmt = db()->prepare(
        'SELECT ml.*, i.quantity
         FROM material_loans ml
         INNER JOIN items i ON i.id = ml.item_id
         WHERE ml.id = :id
           AND i.sector = :owner_sector
         LIMIT 1'
    );
    $stmt->execute([
        'id' => $loanId,
        'owner_sector' => (string) $manager['sector'],
    ]);
    $loan = $stmt->fetch();

    // A busca já limita pelo setor do gestor; se não achou, o pedido não pertence a ele.
    if (!$loan) {
        throw new RuntimeException('Solicitacao nao encontrada.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        if ($action === 'withdraw') {
            // A retirada consome o estoque e só pode acontecer uma vez por pedido pendente.
            if ($loan['status'] !== 'solicitada') {
                throw new RuntimeException('Somente solicitações pendentes podem virar retirada.');
            }

            $newQuantity = (int) $loan['quantity'] - (int) $loan['requested_quantity'];

            // Impede o estoque de ficar negativo por retirada maior que o saldo atual.
            if ($newQuantity < 0) {
                throw new RuntimeException('Estoque insuficiente para registrar a retirada.');
            }

            update_item_stock((int) $loan['item_id'], (string) $manager['sector'], $newQuantity, (int) $manager['id']);

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
            // A devolução repõe o estoque somente quando o item realmente saiu antes.
            if ($loan['status'] !== 'retirada') {
                throw new RuntimeException('Somente itens retirados podem ser registrados como devolvidos.');
            }

            $newQuantity = (int) $loan['quantity'] + (int) $loan['requested_quantity'];
            update_item_stock((int) $loan['item_id'], (string) $manager['sector'], $newQuantity, (int) $manager['id']);

            $update = db()->prepare(
                'UPDATE material_loans
                 SET status = \'devolvida\', manager_user_id = :manager_user_id, returned_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $update->execute([
                'id' => $loanId,
                'manager_user_id' => (int) $manager['id'],
            ]);

            // Devolução fora do prazo bloqueia automaticamente o solicitante.
            if (loan_is_overdue($loan) && empty($loan['infraction_at'])) {
                apply_requester_block($loan, $manager, 'Devolução registrada fora do prazo.');
            }
        } else {
            throw new RuntimeException('Ação inválida.');
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        // Se qualquer etapa falhar, desfaz estoque e status para não deixar dados pela metade.
        $pdo->rollBack();
        throw $exception;
    }
}

function renew_material_loan_due_date(int $loanId, array $manager, string $newDueDate): void
{
    // Renovação de prazo é uma ação de gestor, porque altera a responsabilidade do pedido.
    if (!can_manage_loan_requests($manager)) {
        throw new RuntimeException('Apenas o gestor do setor pode renovar prazo.');
    }

    $dueDate = DateTimeImmutable::createFromFormat('Y-m-d', $newDueDate);
    $today = new DateTimeImmutable('today');

    // O novo prazo também precisa ser atual ou futuro.
    if (!$dueDate || $dueDate < $today) {
        throw new RuntimeException('Informe uma nova data de devolução válida.');
    }

    $stmt = db()->prepare(
        'UPDATE material_loans
         SET return_due_date = :return_due_date, manager_user_id = :manager_user_id
         WHERE id = :id
           AND status = \'retirada\'
           AND item_id IN (SELECT id FROM items WHERE sector = :owner_sector)'
    );
    $stmt->execute([
        'id' => $loanId,
        'return_due_date' => $newDueDate,
        'manager_user_id' => (int) $manager['id'],
        'owner_sector' => (string) $manager['sector'],
    ]);

    if ($stmt->rowCount() === 0) {
        throw new RuntimeException('Somente empréstimos retirados podem ter prazo renovado.');
    }
}

function register_material_loan_infraction(int $loanId, array $manager, string $reason): int
{
    // Infração manual existe para casos em que houve descumprimento além do atraso automático.
    if (!can_manage_loan_requests($manager)) {
        throw new RuntimeException('Apenas o gestor do setor pode registrar infração.');
    }

    $reason = trim($reason);

    // Mantém um motivo padrão para não registrar bloqueio sem explicação.
    if ($reason === '') {
        $reason = 'Descumprimento das regras do termo de empréstimo.';
    }

    $stmt = db()->prepare(
        'SELECT ml.*, i.quantity
         FROM material_loans ml
         INNER JOIN items i ON i.id = ml.item_id
         WHERE ml.id = :id
           AND i.sector = :owner_sector
         LIMIT 1'
    );
    $stmt->execute([
        'id' => $loanId,
        'owner_sector' => (string) $manager['sector'],
    ]);
    $loan = $stmt->fetch();

    // A consulta também garante que o gestor só mexa em empréstimo do próprio setor.
    if (!$loan) {
        throw new RuntimeException('Solicitação não encontrada.');
    }

    if ($loan['status'] !== 'retirada') {
        throw new RuntimeException('A infração deve ser registrada em um empréstimo retirado.');
    }

    if (!empty($loan['infraction_at'])) {
        throw new RuntimeException('Esta solicitação já possui infração registrada.');
    }

    return apply_requester_block($loan, $manager, $reason);
}

function apply_requester_block(array $loan, array $manager, string $reason): int
{
    // Cada nova infração aumenta a contagem do usuário e amplia o período de bloqueio.
    $pdo = db();
    $userId = (int) $loan['requester_user_id'];
    $stmt = $pdo->prepare('SELECT loan_infraction_count FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $currentCount = (int) $stmt->fetchColumn();
    $newCount = $currentCount + 1;
    $blockDays = max(2, $newCount * 2);
    $blockedUntil = (new DateTimeImmutable())->modify('+' . $blockDays . ' days')->format('Y-m-d H:i:s');

    // Primeiro bloqueia o usuário para impedir novos pedidos imediatamente.
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

    // Depois marca no empréstimo qual gestor registrou a infração e por quantos dias.
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
    // Retorna o estado de bloqueio já pronto para validação e exibição em tela.
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

    // Bloqueios vencidos continuam no histórico, mas deixam de impedir solicitações.
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
    // Considera o dia inteiro da devolução como válido até 23:59:59.
    if (empty($loan['return_due_date'])) {
        return false;
    }

    return strtotime((string) $loan['return_due_date'] . ' 23:59:59') < time();
}

function material_loan_status_label(string $status): string
{
    // Traduz os valores internos do banco para textos curtos na interface.
    return [
        'solicitada' => 'Solicitada',
        'retirada' => 'Retirada',
        'devolvida' => 'Devolvida',
        'cancelada' => 'Cancelada',
    ][$status] ?? $status;
}
