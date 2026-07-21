<?php

declare(strict_types=1);

/*
 * Regras de negocio dos usuarios.
 *
 * Somente administradores chegam na pagina que chama esta funcao.
 * O usuario criado sempre fica preso ao setor do admin logado.
 */

function create_user_account(
    string $name,
    string $email,
    string $password,
    string $sector,
    string $role,
    ?string $photoPath = null,
    ?int $requesterSectorId = null
): void
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Informe um e-mail valido.');
    }

    if (strlen($password) < 6) {
        throw new RuntimeException('A senha deve ter pelo menos 6 caracteres.');
    }

    if (!array_key_exists($role, ROLES)) {
        throw new RuntimeException('Perfil invalido.');
    }

    if ($role === 'solicitante') {
        if ($sector !== 'almoxarifado' || $requesterSectorId === null) {
            throw new RuntimeException('Vincule o solicitante a um setor solicitante do almoxarifado.');
        }

        if (!requester_sector_is_active($requesterSectorId)) {
            throw new RuntimeException('Setor solicitante invalido ou inativo.');
        }
    } else {
        $requesterSectorId = null;
    }

    $stmt = db()->prepare(
        'INSERT INTO users (name, email, password_hash, sector, role, requester_sector_id, photo_path)
         VALUES (:name, :email, :password_hash, :sector, :role, :requester_sector_id, :photo_path)'
    );

    $stmt->execute([
        'name' => $name,
        'email' => $email,
        // Nunca salvar senha pura no banco. password_hash gera o hash seguro.
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'sector' => $sector,
        'role' => $role,
        'requester_sector_id' => $requesterSectorId,
        'photo_path' => $photoPath,
    ]);
}

function list_users_by_sector(string $sector): array
{
    $stmt = db()->prepare(
        'SELECT u.id, u.name, u.email, u.sector, u.role, u.requester_sector_id, u.loan_blocked_until, u.loan_infraction_count, u.photo_path, u.created_at, rs.name AS requester_sector_name
         FROM users u
         LEFT JOIN requester_sectors rs ON rs.id = u.requester_sector_id
         WHERE u.sector = :sector
           AND u.role <> \'super_admin\'
         ORDER BY u.role, u.name'
    );
    $stmt->execute(['sector' => $sector]);

    return $stmt->fetchAll();
}

function delete_user_account(int $userId, array $editor): void
{
    /*
     * Exclusao de usuario com as mesmas barreiras da edicao:
     * - gestor/admin setorial so apaga usuarios do proprio setor;
     * - Admin Maximo pode apagar usuarios globais;
     * - ninguem apaga a propria sessao logada por acidente.
     */
    $targetUser = find_user_for_edit($userId, $editor);

    if (!$targetUser) {
        throw new RuntimeException('Usuario nao encontrado ou fora do seu setor.');
    }

    if ((int) ($editor['id'] ?? 0) === $userId) {
        throw new RuntimeException('Nao e permitido apagar o proprio usuario logado.');
    }

    if ($targetUser['role'] === 'super_admin') {
        throw new RuntimeException('O Admin Maximo nao pode ser apagado por esta tela.');
    }

    $stmt = db()->prepare('DELETE FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
}

function find_user_for_edit(int $userId, array $editor): ?array
{
    $stmt = db()->prepare(
        'SELECT u.id, u.name, u.email, u.sector, u.role, u.requester_sector_id, u.loan_blocked_until, u.loan_infraction_count, u.photo_path, u.created_at, rs.name AS requester_sector_name
         FROM users u
         LEFT JOIN requester_sectors rs ON rs.id = u.requester_sector_id
         WHERE u.id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $userId]);
    $targetUser = $stmt->fetch();

    if (!$targetUser) {
        return null;
    }

    // Admin Maximo pode editar qualquer usuario; gestores apenas do proprio setor.
    if (!is_super_admin($editor) && $targetUser['sector'] !== $editor['sector']) {
        return null;
    }

    // Admin Maximo so pode ser editado por outro acesso de Admin Maximo.
    if ($targetUser['role'] === 'super_admin' && !is_super_admin($editor)) {
        return null;
    }

    return $targetUser;
}

function update_user_account(
    int $userId,
    array $editor,
    string $name,
    string $email,
    string $role,
    ?string $password = null,
    ?string $photoPath = null,
    ?int $requesterSectorId = null
): void {
    $targetUser = find_user_for_edit($userId, $editor);

    if (!$targetUser) {
        throw new RuntimeException('Usuario nao encontrado ou fora do seu setor.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Informe um e-mail valido.');
    }

    if (!array_key_exists($role, ROLES)) {
        throw new RuntimeException('Perfil invalido.');
    }

    if ($role === 'solicitante') {
        if ($targetUser['sector'] !== 'almoxarifado' || $requesterSectorId === null) {
            throw new RuntimeException('Vincule o solicitante a um setor solicitante do almoxarifado.');
        }

        if (!requester_sector_is_active($requesterSectorId)) {
            throw new RuntimeException('Setor solicitante invalido ou inativo.');
        }
    } else {
        $requesterSectorId = null;
    }

    if ($password !== null && $password !== '' && strlen($password) < 6) {
        throw new RuntimeException('A senha deve ter pelo menos 6 caracteres.');
    }

    // Gestores setoriais nao podem promover usuarios para Admin Maximo.
    if ($role === 'super_admin' && !is_super_admin($editor)) {
        throw new RuntimeException('Apenas o Admin Maximo pode definir este perfil.');
    }

    $fields = [
        'name = :name',
        'email = :email',
        'role = :role',
        'requester_sector_id = :requester_sector_id',
    ];
    $params = [
        'id' => $userId,
        'name' => $name,
        'email' => $email,
        'role' => $role,
        'requester_sector_id' => $requesterSectorId,
    ];

    if ($password !== null && $password !== '') {
        $fields[] = 'password_hash = :password_hash';
        $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    }

    if ($photoPath !== null) {
        $fields[] = 'photo_path = :photo_path';
        $params['photo_path'] = $photoPath;
    }

    $stmt = db()->prepare(
        'UPDATE users
         SET ' . implode(', ', $fields) . '
         WHERE id = :id'
    );
    $stmt->execute($params);

    // Se o usuario editou o proprio cadastro, atualiza a sessao atual.
    if ((int) ($editor['id'] ?? 0) === $userId) {
        $freshUser = find_user_by_id($userId);

        if ($freshUser) {
            $_SESSION['user'] = $freshUser;
        }
    }
}

function requester_sector_is_active(int $requesterSectorId): bool
{
    $stmt = db()->prepare(
        'SELECT id
         FROM requester_sectors
         WHERE id = :id
           AND active = 1
         LIMIT 1'
    );
    $stmt->execute(['id' => $requesterSectorId]);

    return (bool) $stmt->fetch();
}
