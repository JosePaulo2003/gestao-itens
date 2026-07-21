<?php

require_once __DIR__ . '/../includes/inventory.php';

$user = require_login();

if (!is_almoxarifado_manager($user)) {
    redirect_to_user_sector($user);
}

$sectorName = SECTORS[$user['sector']];
$activePage = 'setores-solicitantes';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf_token();

        $name = trim((string) ($_POST['name'] ?? ''));
        $acronym = trim((string) ($_POST['acronym'] ?? ''));

        create_requester_sector($name, $acronym);
        $message = 'Setor solicitante cadastrado com sucesso.';
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$requesterSectors = list_requester_sectors();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setores solicitantes - Gestao de Recurso Setorial</title>
    <link rel="stylesheet" href="<?= e(asset_url('/assets/css/style.css')) ?>">
</head>
<body class="<?= e(body_theme_class($user, $activePage)) ?>">
    <?php require __DIR__ . '/../templates/sector-header.php'; ?>

    <main class="dashboard narrow-dashboard">
        <?php if ($message): ?>
            <div class="notice success"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="notice error"><?= e($error) ?></div>
        <?php endif; ?>

        <section class="panel">
            <h2>Cadastrar setor solicitante</h2>
            <p class="muted">Cada setor cadastrado pode ter usuarios do perfil Solicitante.</p>

            <form method="post" class="form-grid" autocomplete="off">
                <?= csrf_field() ?>

                <label>
                    Nome do setor
                    <input name="name" type="text" required>
                </label>

                <label>
                    Sigla
                    <input name="acronym" type="text">
                </label>

                <button type="submit">Cadastrar setor</button>
            </form>
        </section>

        <section class="panel">
            <h2>Setores cadastrados</h2>

            <?php if (!$requesterSectors): ?>
                <p class="empty">Nenhum setor solicitante cadastrado ainda.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Setor</th>
                                <th>Sigla</th>
                                <th>Status</th>
                                <th>Criado em</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requesterSectors as $requesterSector): ?>
                                <tr>
                                    <td><?= e($requesterSector['name']) ?></td>
                                    <td><?= e((string) ($requesterSector['acronym'] ?? '')) ?></td>
                                    <td><?= (int) $requesterSector['active'] === 1 ? 'Ativo' : 'Inativo' ?></td>
                                    <td><?= e(date('d/m/Y H:i', strtotime((string) $requesterSector['created_at']))) ?></td>
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
