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
    role ENUM('estagiario', 'admin', 'super_admin') NOT NULL DEFAULT 'estagiario',
    photo_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Itens controlados pelo estoque de cada setor.
-- image_path guarda o caminho publico da foto salva em /uploads/items.
CREATE TABLE IF NOT EXISTS items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sector ENUM('ctic', 'almoxarifado', 'lab-designer', 'lab-maker') NOT NULL,
    name VARCHAR(160) NOT NULL,
    description TEXT NULL,
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
