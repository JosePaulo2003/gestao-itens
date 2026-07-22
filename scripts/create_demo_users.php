<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/*
 * Cria usuarios administradores de demonstracao para todos os setores.
 *
 * Use somente em ambiente de teste/desenvolvimento. Em producao, prefira criar
 * usuarios manualmente pela pagina de usuarios com senhas individuais.
 */

$users = [
    ['CTIC', 'ctic@sas.local', 'ctic', 'admin'],
    ['Almoxarifado', 'almoxarifado@sas.local', 'almoxarifado', 'admin'],
    ['Lab de Designer', 'designer@sas.local', 'lab-designer', 'admin'],
    ['Lab Maker', 'maker@sas.local', 'lab-maker', 'admin'],
];

$passwordHash = password_hash('123456', PASSWORD_DEFAULT);

// Upsert permite rodar o script de novo sem criar registros duplicados.
$stmt = db()->prepare(
    'INSERT INTO users (name, email, password_hash, sector, role)
     VALUES (:name, :email, :password_hash, :sector, :role)
     ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash), sector = VALUES(sector), role = VALUES(role)'
);

foreach ($users as [$name, $email, $sector, $role]) {
    // Cada linha representa um usuário administrativo básico por setor.
    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => $passwordHash,
        'sector' => $sector,
        'role' => $role,
    ]);
}

echo "Usuários de demonstração criados. Senha padrão: 123456\n";
