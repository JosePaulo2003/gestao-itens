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
        <a class="<?= $activePage === 'home' ? 'active' : '' ?>" href="<?= e($homeUrl) ?>">Inicio</a>
        <?php if (can_manage_items($user)): ?>
            <a class="<?= $activePage === 'cadastrar-item' ? 'active' : '' ?>" href="<?= e(url_for('/setores/cadastrar-item.php')) ?>">Cadastrar item</a>
        <?php endif; ?>
        <a class="<?= $activePage === 'estoque' ? 'active' : '' ?>" href="<?= e(url_for('/setores/estoque.php')) ?>">Estoque</a>
        <a class="<?= $activePage === 'relatorio' ? 'active' : '' ?>" href="<?= e(url_for('/setores/relatorio.php')) ?>">Relatorio</a>
        <?php if (is_admin($user)): ?>
            <a class="<?= $activePage === 'usuarios' ? 'active' : '' ?>" href="<?= e(url_for('/setores/usuarios.php')) ?>">Usuarios</a>
        <?php endif; ?>
</nav>
<?php endif; ?>
