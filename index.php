<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

// Se ja existe usuario logado, ele nao precisa ver o login novamente.
$nextUrl = safe_local_redirect_path($_GET['next'] ?? null);
$loggedUser = current_user();

if ($loggedUser) {
    if ($nextUrl !== null) {
        header('Location: ' . $nextUrl);
        exit;
    }

    redirect_to_user_sector($loggedUser);
}

$error = '';
$notice = '';

if (($_GET['admin_maximo'] ?? '') === 'encerrado') {
    $notice = 'Sessao do Admin Maximo encerrada. Entre novamente com o usuario do setor desejado.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf_token();

        // Normaliza o e-mail antes de consultar o banco.
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $nextUrl = safe_local_redirect_path($_POST['next'] ?? null);
        $user = $email !== '' ? find_user_by_email($email) : null;

        if ($user && password_verify($password, $user['password_hash'])) {
            if (is_super_admin($user) && !is_admin_max_path($nextUrl)) {
                throw new RuntimeException('O Admin Maximo deve acessar pelo link protegido proprio.');
            }

            login_user($user);

            if ($nextUrl !== null) {
                header('Location: ' . $nextUrl);
                exit;
            }

            redirect_to_user_sector($user);
        }

        // Mensagem generica para nao revelar se o e-mail existe.
        $error = 'E-mail ou senha invalidos.';
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestão de Recurso Setorial - Login</title>
    <link rel="stylesheet" href="<?= e(asset_url('/assets/css/style.css')) ?>">
</head>
<body class="login-page">
    <header class="login-header clean-login-header">
        <div class="login-header-brand">
            <span class="login-header-mark">GRS</span>
            <div>
                <strong>Gestão de Recurso Setorial</strong>
                <small>Controle de recursos por setor</small>
            </div>
        </div>
    </header>

    <main class="login-shell institutional-login">
        <section class="login-panel" aria-labelledby="login-title">
            <div class="system-brand" aria-label="Gestão de Recurso Setorial">
                <span class="system-symbol"></span>
                <div>
                    <strong>GRS</strong>
                    <small>GESTÃO DE RECURSO SETORIAL</small>
                </div>
            </div>

            <h1 id="login-title">Login</h1>
            <p class="login-subtitle">Bem-vindo ao login do sistema <strong>Gestão de Recurso Setorial</strong></p>

            <?php if ($notice): ?>
                <div class="notice success" role="status"><?= e($notice) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" class="login-form" autocomplete="on">
                <?= csrf_field() ?>
                <?php if ($nextUrl !== null): ?>
                    <input type="hidden" name="next" value="<?= e($nextUrl) ?>">
                <?php endif; ?>

                <label class="sr-only" for="email">E-mail</label>
                <input id="email" name="email" type="email" placeholder="E-mail" required autofocus>

                <label class="sr-only" for="password">Senha</label>
                <input id="password" name="password" type="password" placeholder="Senha" required>

                <button type="submit">Entrar</button>
            </form>

            <p class="version">versão 1.0.0</p>

            <div class="login-divider"></div>

            <div class="login-actions">
                <a href="<?= e(url_for('/esqueceu-senha.php')) ?>">Esqueceu a senha?</a>
            </div>
        </section>
    </main>

    <footer class="login-footer">
        <span>Copyright © 2026 - CTIC CESIT</span>
        <span class="footer-badge" aria-hidden="true">v1.0</span>
    </footer>
</body>
</html>
