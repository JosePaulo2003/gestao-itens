<?php

declare(strict_types=1);

/*
 * Gera uma nova chave para o link do Admin Maximo.
 *
 * Use o token no link:
 * /admin-maximo.php?k=TOKEN_GERADO
 *
 * Copie o hash gerado para config/admin_access.php.
 */
$token = bin2hex(random_bytes(24));
$hash = password_hash($token, PASSWORD_DEFAULT);

echo "Token do link: {$token}" . PHP_EOL;
echo "Hash para config/admin_access.php: {$hash}" . PHP_EOL;
