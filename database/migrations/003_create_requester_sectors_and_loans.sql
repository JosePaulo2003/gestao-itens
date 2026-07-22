-- Setores solicitantes e fluxo de solicitacao/retirada/devolucao do almoxarifado.
-- Amplia os perfis para permitir usuario solicitante sem criar tabela separada.
ALTER TABLE users
    MODIFY role ENUM('estagiario', 'admin', 'super_admin', 'solicitante') NOT NULL DEFAULT 'estagiario',
    ADD COLUMN requester_sector_id INT UNSIGNED NULL AFTER role;

CREATE TABLE IF NOT EXISTS requester_sectors (
    -- Setor externo/interno que aparece como origem do pedido.
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(140) NOT NULL,
    acronym VARCHAR(40) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS material_loans (
    -- Pedido de retirada com status e datas que acompanham o ciclo do empréstimo.
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requester_sector_id INT UNSIGNED NOT NULL,
    requester_user_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    manager_user_id INT UNSIGNED NULL,
    borrower_name VARCHAR(140) NOT NULL,
    registration_number VARCHAR(80) NULL,
    responsible_teacher VARCHAR(140) NULL,
    return_due_date DATE NULL,
    requested_quantity INT UNSIGNED NOT NULL DEFAULT 1,
    other_materials TEXT NULL,
    status ENUM('solicitada', 'retirada', 'devolvida', 'cancelada') NOT NULL DEFAULT 'solicitada',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    withdrawn_at TIMESTAMP NULL,
    returned_at TIMESTAMP NULL,
    notes VARCHAR(255) NULL,
    INDEX idx_material_loans_status (status),
    INDEX idx_material_loans_requester_sector (requester_sector_id),
    CONSTRAINT fk_material_loans_requester_sector FOREIGN KEY (requester_sector_id) REFERENCES requester_sectors(id),
    CONSTRAINT fk_material_loans_requester_user FOREIGN KEY (requester_user_id) REFERENCES users(id),
    CONSTRAINT fk_material_loans_item FOREIGN KEY (item_id) REFERENCES items(id),
    CONSTRAINT fk_material_loans_manager_user FOREIGN KEY (manager_user_id) REFERENCES users(id) ON DELETE SET NULL
);
