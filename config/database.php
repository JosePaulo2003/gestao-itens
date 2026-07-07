<?php

declare(strict_types=1);

/*
 * Configuracao do banco MySQL.
 *
 * Por padrao funciona no XAMPP local. Em servidor, voce pode definir
 * variaveis de ambiente sem precisar alterar o codigo versionado:
 *
 * DB_HOST, DB_NAME, DB_USER, DB_PASS
 */
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'sas');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

function db(): PDO
{
    static $pdo = null;

    // Reutiliza a mesma conexao durante a requisicao.
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    // PDO com excecoes facilita capturar e exibir erros controlados.
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
