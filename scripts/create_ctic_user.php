<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/*
 * Cria o usuario administrador inicial do CTIC.
 *
 * Pode ser executado mais de uma vez: ON DUPLICATE KEY UPDATE atualiza o
 * registro existente sem criar usuario duplicado.
 */

$passwordHash = password_hash('123456', PASSWORD_DEFAULT);

// Mantém o usuário padrão do CTIC sempre consistente pelo e-mail.
$stmt = db()->prepare(
    'INSERT INTO users (name, email, password_hash, sector, role)
     VALUES (:name, :email, :password_hash, :sector, :role)
     ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash), sector = VALUES(sector), role = VALUES(role)'
);

$stmt->execute([
    'name' => 'CTIC',
    'email' => 'ctic@sas.local',
    'password_hash' => $passwordHash,
    'sector' => 'ctic',
    'role' => 'admin',
]);

echo "Admin CTIC criado. E-mail: ctic@sas.local | Senha: 123456\n";
