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
    exit('Usuario nao encontrado ou fora do seu setor.');
}

$sectorName = is_super_admin($user) && isset($_GET['k']) ? 'ADMIN MAXIMO' : SECTORS[$user['sector']];
$activePage = is_super_admin($user) && isset($_GET['k']) ? 'admin' : 'usuarios';
$backUrl = is_super_admin($user) && isset($_GET['k'])
    ? url_for('/admin-maximo.php?k=' . urlencode((string) $_GET['k']))
    : url_for('/setores/usuarios.php');
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf_token();

        $name = trim((string) ($_POST['user_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['role'] ?? 'estagiario');
        $photoPath = save_user_photo($_FILES['photo'] ?? [], $targetUser['sector']);

        if ($name === '' || $email === '') {
            throw new RuntimeException('Preencha nome e e-mail do usuario.');
        }

        update_user_account($targetUserId, $user, $name, $email, $role, $password, $photoPath);
        $message = 'Usuario atualizado com sucesso.';
        $targetUser = find_user_for_edit($targetUserId, $user);
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$targetSectorName = SECTORS[$targetUser['sector']] ?? $targetUser['sector'];
$memberLabel = role_label('estagiario', $targetUser['sector']);
$managerLabel = role_label('admin', $targetUser['sector']);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar usuario - Gestao de Recurso Setorial</title>
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
            <h2>Editar usuario</h2>
            <p class="muted">Usuario vinculado ao setor <?= e($targetSectorName) ?>.</p>

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
                        <?php if (is_super_admin($user)): ?>
                            <option value="super_admin" <?= $targetUser['role'] === 'super_admin' ? 'selected' : '' ?>>Admin Maximo</option>
                        <?php endif; ?>
                    </select>
                </label>

                <label class="full">
                    Foto do usuario
                    <input name="photo" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                    <span class="field-hint">Envie uma nova foto apenas se quiser substituir a atual.</span>
                </label>

                <button type="submit">Salvar alteracoes</button>
                <a class="secondary-action" href="<?= e($backUrl) ?>">Voltar</a>
            </form>
        </section>
    </main>
</body>
</html>
