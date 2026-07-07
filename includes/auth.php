<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/admin_access.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/security.php';

/*
 * Sessao do sistema.
 *
 * No navegador a sessao guarda o usuario logado. No terminal (scripts PHP)
 * nao abrimos sessao para evitar erro de permissao na pasta tmp do XAMPP.
 */
configure_secure_session();
send_security_headers();

/*
 * Setores atendidos pelo sistema.
 * A chave e salva no banco; o valor e exibido para o usuario.
 */
const SECTORS = [
    'ctic' => 'CTIC',
    'almoxarifado' => 'ALMOXARIFADO',
    'lab-designer' => 'LAB DE DESIGNER',
    'lab-maker' => 'LAB MAKER',
];

/*
 * Perfis de acesso.
 * estagiario: gerencia itens e estoque.
 * admin: gerencia itens, estoque e usuarios.
 * super_admin: acesso maximo ao sistema.
 */
const ROLES = [
    'estagiario' => 'Estagiario',
    'admin' => 'Admin',
    'super_admin' => 'Admin Maximo',
];

function find_user_by_email(string $email): ?array
{
    // Busca todos os dados necessarios para validar login e montar a sessao.
    $stmt = db()->prepare('SELECT id, name, email, password_hash, sector, role, photo_path FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function find_user_by_id(int $id): ?array
{
    // Usado para atualizar sessoes antigas que nao tenham campos novos.
    $stmt = db()->prepare('SELECT id, name, email, sector, role, photo_path FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function login_user(array $user): void
{
    // Troca o ID da sessao apos login para reduzir risco de fixacao de sessao.
    session_regenerate_id(true);

    // Guardamos apenas dados essenciais. A senha nunca entra na sessao.
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'sector' => $user['sector'],
        'role' => $user['role'],
        'photo_path' => $user['photo_path'] ?? null,
    ];
}

function current_user(): ?array
{
    $user = $_SESSION['user'] ?? null;

    if (!$user) {
        return null;
    }

    // Se o sistema ganhou campos novos depois do login, atualiza a sessao.
    if ((!isset($user['role']) || !array_key_exists('photo_path', $user)) && isset($user['id'])) {
        $freshUser = find_user_by_id((int) $user['id']);

        if ($freshUser) {
            $_SESSION['user'] = $freshUser;
            return $freshUser;
        }
    }

    return $user;
}

function require_login(): array
{
    $user = current_user();

    if (!$user) {
        // Toda pagina protegida volta para o login quando nao ha usuario logado.
        $loginUrl = url_for('/index.php');
        $nextUrl = safe_local_redirect_path($_SERVER['REQUEST_URI'] ?? null);

        if ($nextUrl !== null) {
            $loginUrl .= '?next=' . rawurlencode($nextUrl);
        }

        header('Location: ' . $loginUrl);
        exit;
    }

    return $user;
}

function sector_url(string $sector): string
{
    // Padrao das homes dos setores: /setores/<setor>.php.
    return url_for('/setores/' . $sector . '.php');
}

function redirect_to_user_sector(array $user): void
{
    if (is_super_admin($user)) {
        header('Location: ' . url_for('/sair-admin-maximo.php'));
        exit;
    }

    header('Location: ' . sector_url($user['sector']));
    exit;
}

function require_sector(string $sector): array
{
    $user = require_login();

    if (is_super_admin($user)) {
        header('Location: ' . url_for('/sair-admin-maximo.php'));
        exit;
    }

    if ($user['sector'] !== $sector) {
        // Usuario logado nao pode abrir diretamente a home de outro setor.
        redirect_to_user_sector($user);
    }

    return $user;
}

function is_admin(array $user): bool
{
    return in_array($user['role'] ?? '', ['admin', 'super_admin'], true);
}

function is_super_admin(array $user): bool
{
    return ($user['role'] ?? '') === 'super_admin';
}

function is_lab_sector(string $sector): bool
{
    return in_array($sector, ['lab-designer', 'lab-maker'], true);
}

function can_manage_items(array $user): bool
{
    // Nos laboratorios, bolsistas apenas consultam; admin segue gerenciando.
    if (is_lab_sector($user['sector']) && !is_admin($user)) {
        return false;
    }

    return true;
}

function role_label(string $role, string $sector): string
{
    if ($role === 'super_admin') {
        return 'Admin Maximo';
    }

    // No Almoxarifado o perfil com permissao de admin deve aparecer como Gestor.
    if ($role === 'admin' && $sector === 'almoxarifado') {
        return 'Gestor';
    }

    // Nos laboratorios, o perfil operacional deve aparecer como Bolsista.
    if ($role === 'estagiario' && is_lab_sector($sector)) {
        return 'Bolsista';
    }

    return ROLES[$role] ?? $role;
}

function body_theme_class(array $user, string $activePage = ''): string
{
    if ($activePage === 'admin' && is_super_admin($user)) {
        return 'theme-admin';
    }

    return 'theme-' . $user['sector'];
}

function valid_admin_max_token(?string $token): bool
{
    return is_string($token) && $token !== '' && password_verify($token, ADMIN_MAX_ACCESS_HASH);
}

function url_for(string $path = ''): string
{
    /*
     * Monta URLs internas usando a base atual da aplicacao.
     * Se o projeto mudar de /sas para a raiz do servidor ou para outra pasta,
     * os links continuam apontando para o lugar certo.
     */
    $basePath = app_base_path();
    $cleanPath = '/' . ltrim($path, '/');

    return $basePath . $cleanPath;
}

function safe_local_redirect_path(?string $nextUrl): ?string
{
    /*
     * Aceita somente caminhos internos, nunca URL completa externa.
     * Isso permite voltar ao link protegido do Admin Maximo apos login sem
     * abrir brecha para redirecionamento malicioso.
     */
    if (!is_string($nextUrl) || $nextUrl === '') {
        return null;
    }

    $parts = parse_url($nextUrl);

    if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
        return null;
    }

    $path = (string) ($parts['path'] ?? '');

    if ($path === '' || $path[0] !== '/') {
        return null;
    }

    $basePath = app_base_path();

    if ($basePath !== '' && $path !== $basePath && !str_starts_with($path, $basePath . '/')) {
        return null;
    }

    $query = isset($parts['query']) ? '?' . $parts['query'] : '';

    return $path . $query;
}

function is_admin_max_path(?string $nextUrl): bool
{
    $path = parse_url((string) $nextUrl, PHP_URL_PATH);

    return is_string($path) && str_ends_with($path, '/admin-maximo.php');
}

function app_base_path(): string
{
    /*
     * APP_BASE_PATH null = deteccao automatica.
     * APP_BASE_PATH string = caminho fixo definido pelo responsavel do sistema.
     */
    if (APP_BASE_PATH !== null) {
        return rtrim((string) APP_BASE_PATH, '/');
    }

    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

    if ($scriptName === '') {
        return '';
    }

    $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

    if ($scriptDir === '' || $scriptDir === '.') {
        return '';
    }

    // Paginas dentro de /setores precisam voltar um nivel para achar a raiz.
    if (str_ends_with($scriptDir, '/setores')) {
        $basePath = substr($scriptDir, 0, -strlen('/setores'));

        return $basePath === false ? '' : rtrim($basePath, '/');
    }

    return $scriptDir === '/' ? '' : $scriptDir;
}

function require_admin_max_access(): array
{
    $user = require_login();

    if (!is_super_admin($user) || !valid_admin_max_token($_GET['k'] ?? null)) {
        http_response_code(403);
        exit('Acesso negado.');
    }

    return $user;
}

function e(string $value): string
{
    // Escape padrao para imprimir texto no HTML sem abrir brecha de XSS.
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
