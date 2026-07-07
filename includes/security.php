<?php

declare(strict_types=1);

/*
 * Funcoes de seguranca compartilhadas.
 *
 * Este arquivo centraliza configuracoes que precisam ser aplicadas em varias
 * paginas: cookies de sessao, headers HTTP e protecao CSRF dos formularios.
 */

function configure_secure_session(): void
{
    if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    /*
     * httponly impede JavaScript de ler o cookie da sessao.
     * samesite=Lax reduz risco de envio de cookie em requisicoes externas.
     * secure fica ativo automaticamente quando o site estiver em HTTPS.
     */
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf_token(): void
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $postedToken = (string) ($_POST['csrf_token'] ?? '');

    if ($sessionToken === '' || $postedToken === '' || !hash_equals($sessionToken, $postedToken)) {
        throw new RuntimeException('Sessao expirada. Recarregue a pagina e tente novamente.');
    }
}
