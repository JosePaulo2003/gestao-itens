<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

// Teste simples usado no desenvolvimento para validar login do Almoxarifado.
$user = find_user_by_email('almoxarifado@sas.local');

if ($user && password_verify('123456', $user['password_hash'])) {
    echo 'LOGIN_OK sector=' . $user['sector'] . ' label=' . role_label($user['role'], $user['sector']) . PHP_EOL;
    exit;
}

echo 'LOGIN_FAIL' . PHP_EOL;
