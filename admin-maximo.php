<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/inventory.php';

// Admin Maximo: exige usuario super_admin e chave secreta no link.
$user = require_admin_max_access();

$sectorName = 'ADMIN MAXIMO';
$activePage = 'admin';
$adminKey = (string) ($_GET['k'] ?? '');
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf_token();

        if (($_POST['action'] ?? '') === 'delete_user') {
            $userId = (int) ($_POST['user_id'] ?? 0);

            delete_user_account($userId, $user);
            $message = 'Usuario apagado com sucesso.';
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

// O Admin Maximo enxerga dados globais de todos os setores.
$summary = system_admin_summary();
$users = list_all_users_for_admin();
$recentMovements = list_recent_movements_for_admin();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Maximo - Gestao de Recurso Setorial</title>
    <link rel="stylesheet" href="<?= e(url_for('/assets/css/style.css')) ?>">
</head>
<body class="<?= e(body_theme_class($user, $activePage)) ?>">
    <?php require __DIR__ . '/templates/sector-header.php'; ?>

    <main class="dashboard">
        <?php if ($message): ?>
            <div class="notice success"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="notice error"><?= e($error) ?></div>
        <?php endif; ?>

        <section class="welcome sector-hero admin-hero">
            <h2>Admin Maximo</h2>
            <p>Acesso global protegido por login e chave secreta no link.</p>
        </section>

        <section class="panel">
            <h2>Resumo global por setor</h2>
            <div class="admin-sector-grid">
                <?php foreach ($summary as $sectorKey => $row): ?>
                    <article class="admin-sector-card">
                        <h3><?= e($row['name']) ?></h3>
                        <dl>
                            <div><dt>Itens</dt><dd><?= (int) $row['items'] ?></dd></div>
                            <div><dt>Em estoque</dt><dd><?= (int) $row['in_stock'] ?></dd></div>
                            <div><dt>Usuarios</dt><dd><?= (int) $row['users'] ?></dd></div>
                            <div><dt>Movimentos</dt><dd><?= (int) $row['movements'] ?></dd></div>
                        </dl>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel">
            <h2>Todos os usuarios</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Setor</th>
                            <th>Perfil</th>
                            <th>Criado em</th>
                            <th class="action-cell">Acao</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $row): ?>
                            <tr>
                                <td><?= e($row['name']) ?></td>
                                <td><?= e($row['email']) ?></td>
                                <td><?= e(SECTORS[$row['sector']] ?? $row['sector']) ?></td>
                                <td><?= e(role_label($row['role'], $row['sector'])) ?></td>
                                <td><?= e(date('d/m/Y H:i', strtotime((string) $row['created_at']))) ?></td>
                                <td class="action-cell">
                                    <a class="table-action" href="<?= e(url_for('/setores/editar-usuario.php?id=' . (int) $row['id'] . '&k=' . urlencode($adminKey))) ?>">Editar</a>
                                    <?php if ($row['role'] !== 'super_admin'): ?>
                                        <form method="post" class="inline-form" onsubmit="return confirm('Apagar este usuario? Esta acao nao pode ser desfeita.');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?= (int) $row['id'] ?>">
                                            <button class="table-action danger-action" type="submit">Apagar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <h2>Ultimas movimentacoes</h2>
            <?php if (!$recentMovements): ?>
                <p class="empty">Nenhuma movimentacao registrada ainda.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Setor</th>
                                <th>Item</th>
                                <th>Movimento</th>
                                <th>Anterior</th>
                                <th>Atual</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentMovements as $row): ?>
                                <tr>
                                    <td><?= e(date('d/m/Y H:i', strtotime((string) $row['created_at']))) ?></td>
                                    <td><?= e(SECTORS[$row['sector']] ?? $row['sector']) ?></td>
                                    <td><?= e($row['item_name']) ?></td>
                                    <td><?= e(movement_label($row['movement_type'])) ?></td>
                                    <td><?= (int) $row['old_quantity'] ?></td>
                                    <td><?= (int) $row['new_quantity'] ?></td>
                                    <td><?= e((string) ($row['user_name'] ?? 'Sistema')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
