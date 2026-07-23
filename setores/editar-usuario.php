<?php

require_once __DIR__ . '/../includes/inventory.php';

// Editar usuario exige admin/gestor do setor ou Admin Maximo.
$user = is_string($_GET['k'] ?? null) && valid_admin_max_token($_GET['k'])
    ? require_admin_max_access()
    : require_login();

if (!is_admin($user)) {
    redirect_to_user_sector($user);
}

$targetUserId = (int) ($_GET['id'] ?? 0);
$targetUser = find_user_for_edit($targetUserId, $user);

if (!$targetUser) {
    http_response_code(404);
    exit('Usuário não encontrado ou fora do seu setor.');
}

$sectorName = is_super_admin($user) && isset($_GET['k']) ? 'ADMIN MAXIMO' : SECTORS[$user['sector']];
$activePage = is_super_admin($user) && isset($_GET['k']) ? 'admin' : 'usuarios';
$backUrl = is_super_admin($user) && isset($_GET['k'])
    ? url_for('/admin-maximo.php?k=' . urlencode((string) $_GET['k']))
    : url_for('/setores/usuarios.php');
$message = '';
$error = '';

// Atualiza dados básicos, perfil, senha opcional e foto opcional do usuário selecionado.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf_token();

        $name = trim((string) ($_POST['user_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['role'] ?? 'estagiario');
        $requesterSectorId = (int) ($_POST['requester_sector_id'] ?? 0);
        $photoPath = save_user_photo($_FILES['photo'] ?? [], $targetUser['sector']);

        if ($name === '' || $email === '') {
            throw new RuntimeException('Preencha nome e e-mail do usuário.');
        }

        update_user_account(
            $targetUserId,
            $user,
            $name,
            $email,
            $role,
            $password,
            $photoPath,
            $requesterSectorId > 0 ? $requesterSectorId : null
        );
        $message = 'Usuário atualizado com sucesso.';
        $targetUser = find_user_for_edit($targetUserId, $user);
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$targetSectorName = SECTORS[$targetUser['sector']] ?? $targetUser['sector'];
$memberLabel = role_label('estagiario', $targetUser['sector']);
$managerLabel = role_label('admin', $targetUser['sector']);
$targetCanUseRequester = $targetUser['sector'] !== 'ctic';
$requesterSectors = $targetCanUseRequester ? list_requester_sectors(true) : [];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar usuário - Gestão de Recurso Setorial</title>
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
            <h2>Editar usuário</h2>
            <p class="muted">Usuário vinculado ao setor <?= e($targetSectorName) ?>.</p>

            <form method="post" class="form-grid" autocomplete="off" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <label>
                    Nome
                    <input name="user_name" type="text" value="<?= e($targetUser['name']) ?>" required>
                </label>

                <label>
                    E-mail
                    <input name="email" type="email" value="<?= e($targetUser['email']) ?>" required>
                </label>

                <label>
                    Nova senha
                    <input name="password" type="password" autocomplete="new-password">
                    <span class="field-hint">Deixe em branco para manter a senha atual.</span>
                </label>

                <label>
                    Perfil
                    <select name="role" required>
                        <option value="estagiario" <?= $targetUser['role'] === 'estagiario' ? 'selected' : '' ?>><?= e($memberLabel) ?></option>
                        <option value="admin" <?= $targetUser['role'] === 'admin' ? 'selected' : '' ?>><?= e($managerLabel) ?></option>
                        <!-- Perfil solicitante exige vínculo com setor solicitante. -->
                        <?php if ($targetCanUseRequester): ?>
                            <option value="solicitante" <?= $targetUser['role'] === 'solicitante' ? 'selected' : '' ?>>Solicitante</option>
                        <?php endif; ?>
                        <?php if (is_super_admin($user)): ?>
                            <!-- Só o Admin Máximo pode promover outro usuário para acesso global. -->
                            <option value="super_admin" <?= $targetUser['role'] === 'super_admin' ? 'selected' : '' ?>>Admin Máximo</option>
                        <?php endif; ?>
                    </select>
                </label>

                <?php if ($targetCanUseRequester): ?>
                    <label>
                        Setor solicitante
                        <select name="requester_sector_id">
                            <option value="">Somente para perfil Solicitante</option>
                            <?php foreach ($requesterSectors as $requesterSector): ?>
                                <option value="<?= (int) $requesterSector['id'] ?>" <?= (int) ($targetUser['requester_sector_id'] ?? 0) === (int) $requesterSector['id'] ? 'selected' : '' ?>>
                                    <?= e($requesterSector['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>

                <label class="full">
                    Foto do usuário
                    <input name="photo" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                    <span class="field-hint">Envie uma nova foto apenas se quiser substituir a atual. Tamanho maximo: 10 MB.</span>
                </label>

                <button type="submit">Salvar alterações</button>
                <a class="secondary-action" href="<?= e($backUrl) ?>">Voltar</a>
            </form>
        </section>
    </main>
</body>
</html>
