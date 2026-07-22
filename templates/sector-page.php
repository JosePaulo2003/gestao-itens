<?php

require_once __DIR__ . '/../includes/inventory.php';

// Home do setor. Mostra atalhos e um resumo rapido do estoque.
$activePage = 'home';
$items = list_items($user['sector']);
$totalItems = count($items);
$inStock = 0;

// Calcula quantos itens possuem quantidade maior que zero.
foreach ($items as $item) {
    if ((int) $item['in_stock'] > 0) {
        $inStock++;
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($sectorName) ?> - Gestão de Recurso Setorial</title>
    <link rel="stylesheet" href="<?= e(asset_url('/assets/css/style.css')) ?>">
</head>
<body class="<?= e(body_theme_class($user, $activePage)) ?>">
    <?php require __DIR__ . '/sector-header.php'; ?>

    <main class="dashboard">
        <section class="welcome sector-hero">
            <h2>Painel do setor <?= e($sectorName) ?></h2>
            <p>Escolha uma área para gerenciar os recursos do setor.</p>
        </section>

        <section class="summary-grid" aria-label="Resumo do setor">
            <article class="summary-card">
                <span>Total de itens</span>
                <strong><?= $totalItems ?></strong>
            </article>
            <article class="summary-card">
                <span>Em estoque</span>
                <strong><?= $inStock ?></strong>
            </article>
            <article class="summary-card">
                <span>Sem estoque</span>
                <strong><?= max(0, $totalItems - $inStock) ?></strong>
            </article>
        </section>

        <section class="actions-grid" aria-label="Modulos do setor">
            <?php if (can_manage_items($user)): ?>
                <!-- Atalho aparece somente para gestor/admin, que pode alterar o estoque. -->
                <a class="module-card module-link" href="<?= e(url_for('/setores/cadastrar-item.php')) ?>">
                    <h3>Cadastrar item</h3>
                    <p>Adicionar novos recursos ao estoque do setor.</p>
                </a>
            <?php endif; ?>
            <a class="module-card module-link" href="<?= e(url_for('/setores/estoque.php')) ?>">
                <h3>Estoque</h3>
                <p>Consultar itens e atualizar quantidades.</p>
            </a>
            <a class="module-card module-link" href="<?= e(url_for('/setores/relatorio.php')) ?>">
                <h3>Relatório</h3>
                <p>Gerar documento profissional com itens e movimentações.</p>
            </a>
            <?php if (is_admin($user)): ?>
                <?php if (can_manage_loan_requests($user)): ?>
                    <!-- Gestores de setores com empréstimo acessam solicitantes e pedidos. -->
                    <a class="module-card module-link" href="<?= e(url_for('/setores/setores-solicitantes.php')) ?>">
                        <h3>Setores solicitantes</h3>
                        <p>Cadastrar setores que podem solicitar retirada de materiais.</p>
                    </a>
                    <a class="module-card module-link" href="<?= e(url_for('/setores/solicitacoes.php')) ?>">
                        <h3>Solicitações</h3>
                        <p>Registrar retiradas, baixas e devoluções do setor.</p>
                    </a>
                <?php endif; ?>
                <!-- Cadastro de usuários é sempre função de administrador do setor. -->
                <a class="module-card module-link" href="<?= e(url_for('/setores/usuarios.php')) ?>">
                    <h3>Usuários</h3>
                    <p>Cadastrar administradores e estagiários do setor.</p>
                </a>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
