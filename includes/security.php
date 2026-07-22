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
    // Scripts de terminal não precisam abrir sessão; páginas web abrem uma vez só.
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
    // Headers só podem ser enviados antes de qualquer saída HTML.
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
    // Um token por sessão protege todos os formulários contra envio externo.
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    // Gera o campo oculto já escapado para ser usado diretamente nos formulários.
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf_token(): void
{
    // Compara token da sessão e do POST sem vazar tempo de comparação.
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $postedToken = (string) ($_POST['csrf_token'] ?? '');

    if ($sessionToken === '' || $postedToken === '' || !hash_equals($sessionToken, $postedToken)) {
        throw new RuntimeException('Sessao expirada. Recarregue a pagina e tente novamente.');
    }
}
