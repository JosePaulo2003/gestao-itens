<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/*
 * Cria usuarios iniciais dos laboratorios.
 *
 * O perfil "estagiario" continua sendo o valor salvo no banco, mas nos
 * laboratorios a interface mostra esse perfil como "Bolsista".
 */

$passwordHash = password_hash('123456', PASSWORD_DEFAULT);

// Define um gestor e um bolsista para cada laboratório.
$users = [
    ['Admin Lab Designer', 'designer@sas.local', 'lab-designer', 'admin'],
    ['Bolsista Lab Designer', 'bolsista.designer@sas.local', 'lab-designer', 'estagiario'],
    ['Admin Lab Maker', 'maker@sas.local', 'lab-maker', 'admin'],
    ['Bolsista Lab Maker', 'bolsista.maker@sas.local', 'lab-maker', 'estagiario'],
];

$stmt = db()->prepare(
    'INSERT INTO users (name, email, password_hash, sector, role)
     VALUES (:name, :email, :password_hash, :sector, :role)
     ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash), sector = VALUES(sector), role = VALUES(role)'
);

foreach ($users as [$name, $email, $sector, $role]) {
    // ON DUPLICATE KEY torna a criação idempotente pelo e-mail.
    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => $passwordHash,
        'sector' => $sector,
        'role' => $role,
    ]);
}

echo "Usuarios dos laboratorios criados. Senha padrao: 123456\n";
