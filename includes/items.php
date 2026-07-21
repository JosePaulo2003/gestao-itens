<?php

declare(strict_types=1);

/*
 * Regras de negocio dos itens.
 *
 * Cada item pertence a um setor. Todas as consultas recebem o setor do usuario
 * logado para impedir que um setor altere estoque de outro setor.
 */

function list_items(string $sector): array
{
    $stmt = db()->prepare(
        'SELECT id, name, description, brand_model, patrimony_number, serial_number, other_materials, quantity, in_stock, image_path, updated_at
         FROM items
         WHERE sector = :sector
         ORDER BY name'
    );
    $stmt->execute(['sector' => $sector]);

    return $stmt->fetchAll();
}

function create_item(
    string $sector,
    string $name,
    string $description,
    int $quantity,
    ?string $imagePath = null,
    ?int $userId = null,
    string $brandModel = '',
    string $patrimonyNumber = '',
    string $serialNumber = '',
    string $otherMaterials = ''
): void {
    $pdo = db();
    $stmt = db()->prepare(
        'INSERT INTO items (sector, name, description, brand_model, patrimony_number, serial_number, other_materials, quantity, in_stock, image_path)
         VALUES (:sector, :name, :description, :brand_model, :patrimony_number, :serial_number, :other_materials, :quantity, :in_stock, :image_path)'
    );

    $stmt->execute([
        'sector' => $sector,
        'name' => $name,
        'description' => $description,
        'brand_model' => $brandModel !== '' ? $brandModel : null,
        'patrimony_number' => $patrimonyNumber !== '' ? $patrimonyNumber : null,
        'serial_number' => $serialNumber !== '' ? $serialNumber : null,
        'other_materials' => $otherMaterials !== '' ? $otherMaterials : null,
        'quantity' => $quantity,
        // Se a quantidade for maior que zero, o item ja entra como disponivel.
        'in_stock' => $quantity > 0 ? 1 : 0,
        'image_path' => $imagePath,
    ]);

    $itemId = (int) $pdo->lastInsertId();

    // Registra a primeira movimentacao do item para aparecer no relatorio.
    register_item_movement($itemId, $sector, $userId, 'cadastro', 0, $quantity, null);
}

function update_item_stock(int $itemId, string $sector, int $quantity, ?int $userId = null): void
{
    $currentItem = find_item_for_sector($itemId, $sector);

    if (!$currentItem) {
        throw new RuntimeException('Item nao encontrado para este setor.');
    }

    $oldQuantity = (int) $currentItem['quantity'];
    $movementType = resolve_movement_type($oldQuantity, $quantity);

    $stmt = db()->prepare(
        'UPDATE items
         SET quantity = :quantity, in_stock = :in_stock
         WHERE id = :id AND sector = :sector'
    );

    $stmt->execute([
        'id' => $itemId,
        'sector' => $sector,
        'quantity' => $quantity,
        // Quantidade zero significa "sem estoque".
        'in_stock' => $quantity > 0 ? 1 : 0,
    ]);

    // O historico guarda a quantidade anterior e a nova quantidade.
    register_item_movement($itemId, $sector, $userId, $movementType, $oldQuantity, $quantity, null);
}

function find_item_for_sector(int $itemId, string $sector): ?array
{
    $stmt = db()->prepare(
        'SELECT id, quantity
         FROM items
         WHERE id = :id AND sector = :sector
         LIMIT 1'
    );
    $stmt->execute([
        'id' => $itemId,
        'sector' => $sector,
    ]);

    $item = $stmt->fetch();

    return $item ?: null;
}

function resolve_movement_type(int $oldQuantity, int $newQuantity): string
{
    if ($newQuantity > $oldQuantity) {
        return 'entrada';
    }

    if ($newQuantity < $oldQuantity) {
        return 'saida';
    }

    return 'ajuste';
}

function register_item_movement(
    int $itemId,
    string $sector,
    ?int $userId,
    string $movementType,
    int $oldQuantity,
    int $newQuantity,
    ?string $notes
): void {
    $stmt = db()->prepare(
        'INSERT INTO item_movements (item_id, sector, user_id, movement_type, old_quantity, new_quantity, quantity_delta, notes)
         VALUES (:item_id, :sector, :user_id, :movement_type, :old_quantity, :new_quantity, :quantity_delta, :notes)'
    );

    $stmt->execute([
        'item_id' => $itemId,
        'sector' => $sector,
        'user_id' => $userId,
        'movement_type' => $movementType,
        'old_quantity' => $oldQuantity,
        'new_quantity' => $newQuantity,
        'quantity_delta' => $newQuantity - $oldQuantity,
        'notes' => $notes,
    ]);
}
