<?php

/*
 * Cabecalho padrao das paginas internas.
 *
 * Todas as paginas dos setores usam este arquivo para manter o mesmo topo,
 * avatar, botao de sair e menu de navegacao.
 */

$userRole = $user['role'] ?? 'estagiario';
$roleName = role_label($userRole, $user['sector']);
$homeUrl = sector_url($user['sector']);
$activePage = $activePage ?? 'home';
$themeClass = ($activePage === 'admin' && is_super_admin($user)) ? 'sector-theme-admin' : 'sector-theme-' . $user['sector'];
$logoutUrl = is_super_admin($user) ? url_for('/sair-admin-maximo.php') : url_for('/logout.php');
$logoutLabel = is_super_admin($user) ? 'Sair do Admin Maximo' : 'Sair';

// Quando o usuario nao tem foto cadastrada, exibimos a inicial do nome.
$initial = strtoupper(substr((string) $user['name'], 0, 1));
?>
<?php if ($user['sector'] === 'lab-designer'): ?>
<style id="force-lab-designer-palette">
    /*
     * Camada de emergencia do Lab de Designer.
     * Fica no cabecalho para vencer cache ou regras antigas do CSS principal.
     */
    body.theme-lab-designer {
        background:
            radial-gradient(circle at 14% 4%, rgba(234, 7, 254, 0.28), transparent 28%),
            radial-gradient(circle at 88% 8%, rgba(61, 208, 255, 0.24), transparent 34%),
            linear-gradient(180deg, #000000 0%, #213172 300px, #081033 64%, #000000 100%) !important;
        color: #ffffff !important;
    }

    body.theme-lab-designer .sector-topbar.sector-theme-lab-designer {
        background: linear-gradient(135deg, #000000 0%, #213172 45%, #730dcb 80%, #ea07fe 130%) !important;
        color: #ffffff !important;
        box-shadow: 0 16px 42px rgba(0, 0, 0, 0.34) !important;
    }

    body.theme-lab-designer .sector-nav {
        background: #000000 !important;
        border-bottom-color: rgba(61, 208, 255, 0.28) !important;
    }

    body.theme-lab-designer .sector-nav a {
        color: #ffffff !important;
    }

    body.theme-lab-designer .sector-nav a.active,
    body.theme-lab-designer .sector-nav a:hover,
    body.theme-lab-designer .report-tabs a.active,
    body.theme-lab-designer .report-tabs a:hover {
        background: #3dd0ff !important;
        border-color: #3dd0ff !important;
        color: #000000 !important;
    }

    body.theme-lab-designer .welcome,
    body.theme-lab-designer .panel,
    body.theme-lab-designer .module-card,
    body.theme-lab-designer .summary-card,
    body.theme-lab-designer .admin-sector-card {
        background: linear-gradient(145deg, rgba(33, 49, 114, 0.96), rgba(0, 0, 0, 0.92)) !important;
        border-color: rgba(61, 208, 255, 0.34) !important;
        color: #ffffff !important;
        box-shadow: 0 18px 42px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(234, 7, 254, 0.08) !important;
    }

    body.theme-lab-designer .report-document {
        background: #ffffff !important;
        border-color: #d9e2ef !important;
        color: #000000 !important;
        box-shadow: 0 18px 50px rgba(23, 32, 51, 0.08) !important;
    }

    body.theme-lab-designer .report-document h2,
    body.theme-lab-designer .report-document h3,
    body.theme-lab-designer .report-document table,
    body.theme-lab-designer .report-document th,
    body.theme-lab-designer .report-document span,
    body.theme-lab-designer .report-document strong,
    body.theme-lab-designer .report-document p,
    body.theme-lab-designer .report-document div,
    body.theme-lab-designer .report-document td {
        color: #000000 !important;
    }

    body.theme-lab-designer .report-document .report-kicker,
    body.theme-lab-designer .report-document .report-summary strong {
        color: #000000 !important;
    }

    body.theme-lab-designer .report-document .report-meta,
    body.theme-lab-designer .report-document .report-summary div {
        background: rgba(61, 208, 255, 0.12) !important;
    }

    body.theme-lab-designer .sector-hero {
        background: linear-gradient(135deg, #000000 0%, #213172 42%, #730dcb 82%, #ea07fe 132%) !important;
        border: 1px solid rgba(61, 208, 255, 0.28) !important;
    }

    body.theme-lab-designer .module-card h3,
    body.theme-lab-designer .summary-card h3,
    body.theme-lab-designer .panel h2,
    body.theme-lab-designer .report-cover h2 {
        color: #ffffff !important;
    }

    body.theme-lab-designer .summary-card strong,
    body.theme-lab-designer .report-kicker,
    body.theme-lab-designer .eyebrow {
        color: #3dd0ff !important;
    }

    body.theme-lab-designer .muted,
    body.theme-lab-designer .empty,
    body.theme-lab-designer .module-card p,
    body.theme-lab-designer .summary-card span,
    body.theme-lab-designer .sector-hero p {
        color: rgba(255, 255, 255, 0.74) !important;
    }
</style>
<?php endif; ?>
<header class="topbar sector-topbar <?= e($themeClass) ?>">
    <div class="sector-userbar">
        <?php if (!empty($user['photo_path'])): ?>
            <img class="user-avatar" src="<?= e((string) $user['photo_path']) ?>" alt="Foto de <?= e($user['name']) ?>">
        <?php else: ?>
            <span class="user-avatar placeholder"><?= e($initial) ?></span>
        <?php endif; ?>

        <div>
            <span class="eyebrow">Gestao de Recurso Setorial</span>
            <h1><?= e($sectorName) ?></h1>
            <p><?= e($user['name']) ?> · <?= e($roleName) ?></p>
        </div>
    </div>

    <a class="logout <?= is_super_admin($user) ? 'super-admin-logout' : '' ?>" href="<?= e($logoutUrl) ?>"><?= e($logoutLabel) ?></a>
</header>

<?php if (!is_super_admin($user)): ?>
<nav class="sector-nav" aria-label="Navegacao do setor">
    <?php if (is_requester($user)): ?>
        <a class="<?= $activePage === 'solicitacoes' ? 'active' : '' ?>" href="<?= e(url_for('/setores/solicitacoes.php')) ?>">Solicitacoes</a>
    <?php else: ?>
        <a class="<?= $activePage === 'home' ? 'active' : '' ?>" href="<?= e($homeUrl) ?>">Inicio</a>
        <?php if (can_manage_items($user)): ?>
            <a class="<?= $activePage === 'cadastrar-item' ? 'active' : '' ?>" href="<?= e(url_for('/setores/cadastrar-item.php')) ?>">Cadastrar item</a>
        <?php endif; ?>
        <a class="<?= $activePage === 'estoque' ? 'active' : '' ?>" href="<?= e(url_for('/setores/estoque.php')) ?>">Estoque</a>
        <a class="<?= $activePage === 'relatorio' ? 'active' : '' ?>" href="<?= e(url_for('/setores/relatorio.php')) ?>">Relatorio</a>
        <?php if (is_almoxarifado_manager($user)): ?>
            <a class="<?= $activePage === 'setores-solicitantes' ? 'active' : '' ?>" href="<?= e(url_for('/setores/setores-solicitantes.php')) ?>">Setores solicitantes</a>
            <a class="<?= $activePage === 'solicitacoes' ? 'active' : '' ?>" href="<?= e(url_for('/setores/solicitacoes.php')) ?>">Solicitacoes</a>
        <?php endif; ?>
        <?php if (is_admin($user)): ?>
            <a class="<?= $activePage === 'usuarios' ? 'active' : '' ?>" href="<?= e(url_for('/setores/usuarios.php')) ?>">Usuarios</a>
        <?php endif; ?>
    <?php endif; ?>
</nav>
<?php endif; ?>
