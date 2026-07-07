<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/*
 * Cria o usuario inicial do Almoxarifado.
 *
 * No banco o perfil continua como "admin", pois e esse valor que libera a
 * criacao de usuarios. Na interface do Almoxarifado ele aparece como "Gestor".
 */

$passwordHash = password_hash('123456', PASSWORD_DEFAULT);

$stmt = db()->prepare(
    'INSERT INTO users (name, email, password_hash, sector, role)
     VALUES (:name, :email, :password_hash, :sector, :role)
     ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash), sector = VALUES(sector), role = VALUES(role)'
);

$stmt->execute([
    'name' => 'Gestor Almoxarifado',
    'email' => 'almoxarifado@sas.local',
    'password_hash' => $passwordHash,
    'sector' => 'almoxarifado',
    'role' => 'admin',
]);

echo "Gestor do Almoxarifado criado. E-mail: almoxarifado@sas.local | Senha: 123456\n";
