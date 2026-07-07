<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

// Teste simples usado no desenvolvimento para validar hash da senha do CTIC.
$user = find_user_by_email('ctic@sas.local');

if ($user && password_verify('123456', $user['password_hash'])) {
    echo 'LOGIN_OK sector=' . $user['sector'] . PHP_EOL;
    exit;
}

echo 'LOGIN_FAIL' . PHP_EOL;
