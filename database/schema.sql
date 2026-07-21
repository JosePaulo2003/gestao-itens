-- Banco principal do sistema.
CREATE DATABASE IF NOT EXISTS sas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sas;

-- Usuarios do sistema.
-- Cada usuario pertence a um setor e possui um perfil de acesso.
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    sector ENUM('ctic', 'almoxarifado', 'lab-designer', 'lab-maker') NOT NULL,
    role ENUM('estagiario', 'admin', 'super_admin', 'solicitante') NOT NULL DEFAULT 'estagiario',
    requester_sector_id INT UNSIGNED NULL,
    loan_blocked_until DATETIME NULL,
    loan_infraction_count INT UNSIGNED NOT NULL DEFAULT 0,
    photo_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Setores externos que podem solicitar retirada de materiais do almoxarifado.
CREATE TABLE IF NOT EXISTS requester_sectors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(140) NOT NULL,
    acronym VARCHAR(40) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Itens controlados pelo estoque de cada setor.
-- image_path guarda o caminho publico da foto salva em /uploads/items.
CREATE TABLE IF NOT EXISTS items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sector ENUM('ctic', 'almoxarifado', 'lab-designer', 'lab-maker') NOT NULL,
    name VARCHAR(160) NOT NULL,
    description TEXT NULL,
    brand_model VARCHAR(180) NULL,
    patrimony_number VARCHAR(80) NULL,
    serial_number VARCHAR(80) NULL,
    other_materials TEXT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 0,
    in_stock TINYINT(1) NOT NULL DEFAULT 0,
    image_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Historico de movimentacoes de estoque.
-- Cada alteracao de quantidade fica registrada para compor documentos.
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

-- Solicitacoes feitas por setores solicitantes e controladas pelo gestor do almoxarifado.
CREATE TABLE IF NOT EXISTS material_loans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requester_sector_id INT UNSIGNED NOT NULL,
    requester_user_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    manager_user_id INT UNSIGNED NULL,
    borrower_name VARCHAR(140) NOT NULL,
    registration_number VARCHAR(80) NULL,
    responsible_teacher VARCHAR(140) NULL,
    return_due_date DATE NOT NULL,
    requested_quantity INT UNSIGNED NOT NULL DEFAULT 1,
    other_materials TEXT NULL,
    rules_accepted TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('solicitada', 'retirada', 'devolvida', 'cancelada') NOT NULL DEFAULT 'solicitada',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    withdrawn_at TIMESTAMP NULL,
    returned_at TIMESTAMP NULL,
    infraction_at TIMESTAMP NULL,
    infraction_reason VARCHAR(255) NULL,
    block_days INT UNSIGNED NULL,
    notes VARCHAR(255) NULL,
    INDEX idx_material_loans_status (status),
    INDEX idx_material_loans_requester_sector (requester_sector_id),
    CONSTRAINT fk_material_loans_requester_sector FOREIGN KEY (requester_sector_id) REFERENCES requester_sectors(id),
    CONSTRAINT fk_material_loans_requester_user FOREIGN KEY (requester_user_id) REFERENCES users(id),
    CONSTRAINT fk_material_loans_item FOREIGN KEY (item_id) REFERENCES items(id),
    CONSTRAINT fk_material_loans_manager_user FOREIGN KEY (manager_user_id) REFERENCES users(id) ON DELETE SET NULL
);
