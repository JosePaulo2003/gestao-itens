-- Migração para projetos que já possuem as tabelas users e items.
-- Cria o histórico de movimentações usado pela página de relatório.

CREATE TABLE IF NOT EXISTS item_movements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    sector ENUM('ctic', 'almoxarifado', 'lab-designer', 'lab-maker') NOT NULL,
    user_id INT UNSIGNED NULL,
    movement_type ENUM('cadastro', 'entrada', 'saida', 'ajuste') NOT NULL,
    old_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    new_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    quantity_delta INT NOT NULL DEFAULT 0,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_item_movements_sector (sector),
    INDEX idx_item_movements_item_id (item_id),
    CONSTRAINT fk_item_movements_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    CONSTRAINT fk_item_movements_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO item_movements (item_id, sector, user_id, movement_type, old_quantity, new_quantity, quantity_delta, notes)
SELECT i.id, i.sector, NULL, 'cadastro', 0, i.quantity, i.quantity, 'Registro inicial gerado pelo sistema'
FROM items i
WHERE NOT EXISTS (
    SELECT 1
    FROM item_movements m
    WHERE m.item_id = i.id
);
