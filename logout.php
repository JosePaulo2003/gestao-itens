<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

/*
 * Logout seguro.
 *
 * Apagamos os dados da sessao, removemos o cookie do navegador e destruimos
 * a sessao no servidor antes de voltar para a tela de login.
 */

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    // Remove o cookie da sessão no navegador usando os mesmos parâmetros originais.
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}

session_destroy();

// Depois de encerrar a sessão, sempre volta para o login comum.
header('Location: ' . url_for('/index.php'));
exit;
