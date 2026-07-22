<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/*
 * Cria o usuario de Admin Maximo.
 *
 * Alem deste login, o acesso ao painel maximo exige a chave criptografada no
 * link, validada contra o hash em config/admin_access.php.
 */

$passwordHash = password_hash('123456', PASSWORD_DEFAULT);

// O Admin Máximo fica ligado ao CTIC no banco, mas seu papel é global.
$stmt = db()->prepare(
    'INSERT INTO users (name, email, password_hash, sector, role)
     VALUES (:name, :email, :password_hash, :sector, :role)
     ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash), sector = VALUES(sector), role = VALUES(role)'
);

$stmt->execute([
    'name' => 'Admin Maximo',
    'email' => 'admin.maximo@sas.local',
    'password_hash' => $passwordHash,
    'sector' => 'ctic',
    'role' => 'super_admin',
]);

echo "Admin Maximo criado. E-mail: admin.maximo@sas.local | Senha: 123456\n";
