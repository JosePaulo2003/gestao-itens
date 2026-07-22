<?php

declare(strict_types=1);

/*
 * Consultas usadas pelos documentos/relatorios.
 *
 * Mantemos o relatorio separado das paginas para facilitar criar outras
 * saidas no futuro, como PDF real, CSV ou filtros por periodo.
 */

function list_item_movements(string $sector): array
{
    // Retorna o histórico do setor com nome do item e do usuário responsável.
    $stmt = db()->prepare(
        'SELECT
            m.id,
            m.movement_type,
            m.old_quantity,
            m.new_quantity,
            m.quantity_delta,
            m.notes,
            m.created_at,
            i.name AS item_name,
            u.name AS user_name
         FROM item_movements m
         INNER JOIN items i ON i.id = m.item_id
         LEFT JOIN users u ON u.id = m.user_id
         WHERE m.sector = :sector
         ORDER BY m.created_at DESC, m.id DESC'
    );
    $stmt->execute(['sector' => $sector]);

    return $stmt->fetchAll();
}

function count_items_in_stock(array $items): int
{
    // Conta itens disponíveis sem depender de uma nova consulta ao banco.
    $total = 0;

    foreach ($items as $item) {
        if ((int) $item['in_stock'] > 0) {
            $total++;
        }
    }

    return $total;
}

function movement_label(string $movementType): string
{
    // Centraliza os rótulos para relatórios e painéis exibirem o mesmo texto.
    return match ($movementType) {
        'cadastro' => 'Cadastro',
        'entrada' => 'Entrada',
        'saida' => 'Saída',
        default => 'Ajuste',
    };
}

function system_admin_summary(): array
{
    // Monta os cartões globais do Admin Máximo com um registro por setor.
    $summary = [];

    foreach (SECTORS as $sectorKey => $sectorName) {
        $summary[$sectorKey] = [
            'name' => $sectorName,
            'items' => 0,
            'in_stock' => 0,
            'users' => 0,
            'movements' => 0,
        ];
    }

    $itemRows = db()->query(
        'SELECT sector, COUNT(*) AS total, SUM(CASE WHEN in_stock = 1 THEN 1 ELSE 0 END) AS in_stock
         FROM items
         GROUP BY sector'
    )->fetchAll();

    foreach ($itemRows as $row) {
        $sector = $row['sector'];
        $summary[$sector]['items'] = (int) $row['total'];
        $summary[$sector]['in_stock'] = (int) $row['in_stock'];
    }

    $userRows = db()->query(
        'SELECT sector, COUNT(*) AS total
         FROM users
         WHERE role <> \'super_admin\'
         GROUP BY sector'
    )->fetchAll();

    foreach ($userRows as $row) {
        $summary[$row['sector']]['users'] = (int) $row['total'];
    }

    $movementRows = db()->query(
        'SELECT sector, COUNT(*) AS total
         FROM item_movements
         GROUP BY sector'
    )->fetchAll();

    foreach ($movementRows as $row) {
        $summary[$row['sector']]['movements'] = (int) $row['total'];
    }

    return $summary;
}

function system_user_summary(): array
{
    // Agrupa usuários por setor e perfil para a visão administrativa.
    $stmt = db()->query(
        'SELECT sector, role, COUNT(*) AS total
         FROM users
         WHERE role <> \'super_admin\'
         GROUP BY sector, role
         ORDER BY sector, role'
    );

    return $stmt->fetchAll();
}

function list_all_users_for_admin(): array
{
    // Lista global usada só pelo Admin Máximo; usuários comuns têm filtro setorial.
    $stmt = db()->query(
        'SELECT id, name, email, sector, role, photo_path, created_at
         FROM users
         ORDER BY sector, role, name'
    );

    return $stmt->fetchAll();
}

function list_recent_movements_for_admin(int $limit = 20): array
{
    // Mostra as últimas alterações de estoque no painel global.
    $stmt = db()->prepare(
        'SELECT
            m.created_at,
            m.sector,
            m.movement_type,
            m.old_quantity,
            m.new_quantity,
            m.quantity_delta,
            i.name AS item_name,
            u.name AS user_name
         FROM item_movements m
         INNER JOIN items i ON i.id = m.item_id
         LEFT JOIN users u ON u.id = m.user_id
         ORDER BY m.created_at DESC, m.id DESC
         LIMIT :limit'
    );
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}
