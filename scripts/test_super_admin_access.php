<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

/*
 * Confere se o usuario maximo e a chave secreta continuam validos.
 *
 * Uso:
 * php scripts/test_super_admin_access.php SUA_CHAVE_AQUI
 */

$token = (string) ($argv[1] ?? '');
$user = find_user_by_email('admin.maximo@sas.local');

if ($user && is_super_admin($user) && valid_admin_max_token($token)) {
    echo 'SUPER_ADMIN_OK' . PHP_EOL;
    exit;
}

echo 'SUPER_ADMIN_FAIL' . PHP_EOL;
