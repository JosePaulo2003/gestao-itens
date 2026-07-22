<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

/*
 * Saida propria do Admin Maximo.
 *
 * O Admin Maximo usa uma sessao real. Para voltar a acessar o sistema com
 * outro usuario, encerramos toda a sessao e removemos o cookie do navegador.
 */
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    // Remove o cookie para impedir reuso da sessão privilegiada.
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

// O parâmetro informa ao login que a saída foi intencional.
header('Location: ' . url_for('/index.php?admin_maximo=encerrado'));
exit;
