<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

/*
 * Pagina informativa de recuperacao de senha.
 *
 * O sistema nao redefine senha automaticamente; o usuario deve procurar o
 * CTIC CESIT para validacao presencial/administrativa.
 */
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar senha - Gestão de Recurso Setorial</title>
    <link rel="stylesheet" href="<?= e(asset_url('/assets/css/style.css')) ?>">
</head>
<body class="login-page">
    <header class="login-header">
        <div class="gov-brand">
            <span class="gov-symbol">GRS</span>
            <div>
                <strong>GESTÃO DE RECURSO SETORIAL</strong>
                <small>CONTROLE DE SETORES</small>
            </div>
        </div>
    </header>

    <main class="login-shell institutional-login">
        <section class="login-panel message-panel">
            <div class="system-brand" aria-label="Gestão de Recurso Setorial">
                <span class="system-symbol"></span>
                <div>
                    <strong>GRS</strong>
                    <small>GESTÃO DE RECURSO SETORIAL</small>
                </div>
            </div>

            <h1>Recuperar senha</h1>
            <p class="login-subtitle">Para recuperar ou alterar sua senha, procure o <strong>CTIC CESIT</strong>.</p>

            <a class="back-login" href="<?= e(url_for('/index.php')) ?>">Voltar ao login</a>
        </section>
    </main>

    <footer class="login-footer">
        <span>Copyright © 2026 - CTIC CESIT</span>
        <span class="footer-badge" aria-hidden="true">v1.0</span>
    </footer>
</body>
</html>
