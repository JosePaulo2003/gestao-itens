<?php

require_once __DIR__ . '/../includes/inventory.php';

// A pagina de usuarios e protegida e exige perfil admin.
$user = require_login();

if (!is_admin($user)) {
    // Estagiario nao acessa cadastro de usuarios.
    redirect_to_user_sector($user);
}

$sectorName = SECTORS[$user['sector']];
$managerLabel = role_label('admin', $user['sector']);
$memberLabel = role_label('estagiario', $user['sector']);
$canManageLoanRequests = can_manage_loan_requests($user);
$requesterSectors = $canManageLoanRequests ? list_requester_sectors(true) : [];
$activePage = 'usuarios';
$message = '';
$error = '';

// A mesma tela cadastra usuários comuns, gestores e solicitantes quando o setor permite empréstimos.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf_token();

        if (($_POST['action'] ?? '') === 'delete_user') {
            // Exclusão passa pela regra de escopo em delete_user_account.
            $userId = (int) ($_POST['user_id'] ?? 0);

            delete_user_account($userId, $user);
            $message = 'Usuário apagado com sucesso.';
        } else {
        // Dados basicos do usuario que sera criado.
        $name = trim((string) ($_POST['user_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['role'] ?? 'estagiario');
        $requesterSectorId = (int) ($_POST['requester_sector_id'] ?? 0);

        // Nome, e-mail e senha sao obrigatorios para permitir login.
        if ($name === '' || $email === '' || $password === '') {
            throw new RuntimeException('Preencha nome, e-mail e senha do usuário.');
        }

        // Foto e opcional. Quando enviada, fica em /uploads/users.
        $photoPath = save_user_photo($_FILES['photo'] ?? [], $user['sector']);

        // O novo usuario sempre pertence ao mesmo setor do admin logado.
        create_user_account(
            $name,
            $email,
            $password,
            $user['sector'],
            $role,
            $photoPath,
            $requesterSectorId > 0 ? $requesterSectorId : null
        );
        $message = 'Usuário cadastrado com sucesso.';
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

// Carrega apos possivel cadastro para a tabela refletir o estado atualizado.
$sectorUsers = list_users_by_sector($user['sector']);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuários - Gestão de Recurso Setorial</title>
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
            <h2>Cadastrar usuário</h2>
            <p class="muted">O novo usuário será vinculado ao setor <?= e($sectorName) ?>.</p>

            <form method="post" class="form-grid" autocomplete="off" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <label>
                    Nome
                    <input name="user_name" type="text" autocomplete="off" required>
                </label>

                <label>
                    E-mail
                    <input name="email" type="email" autocomplete="off" required>
                </label>

                <label>
                    Senha
                    <input name="password" type="password" autocomplete="new-password" required>
                </label>

                <label>
                    Perfil
                    <select name="role" required>
                        <option value="estagiario"><?= e($memberLabel) ?></option>
                        <option value="admin"><?= e($managerLabel) ?></option>
                        <!-- Solicitante aparece apenas para setores que trabalham com empréstimos. -->
                        <?php if ($canManageLoanRequests): ?>
                            <option value="solicitante">Solicitante</option>
                        <?php endif; ?>
                    </select>
                </label>

                <?php if ($canManageLoanRequests): ?>
                    <!-- O vínculo define a origem institucional do solicitante. -->
                    <label>
                        Setor solicitante
                        <select name="requester_sector_id">
                            <option value="">Somente para perfil Solicitante</option>
                            <?php foreach ($requesterSectors as $requesterSector): ?>
                                <option value="<?= (int) $requesterSector['id'] ?>"><?= e($requesterSector['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="field-hint">Cadastre o setor solicitante antes de criar o usuário.</span>
                    </label>
                <?php endif; ?>

                <label class="full">
                    Foto do usuário
                    <input name="photo" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                    <span class="field-hint">Formatos aceitos: JPG, PNG, WEBP ou GIF. Tamanho máximo: 10 MB.</span>
                </label>

                <button type="submit">Cadastrar usuário</button>
            </form>
        </section>

        <section class="panel">
            <h2>Usuários do setor</h2>

            <?php if (!$sectorUsers): ?>
                <p class="empty">Nenhum usuário cadastrado neste setor.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Perfil</th>
                                <?php if ($canManageLoanRequests): ?>
                                    <th>Setor solicitante</th>
                                <?php endif; ?>
                                <th>Criado em</th>
                                <th class="action-cell">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sectorUsers as $sectorUser): ?>
                                <tr>
                                    <td><?= e($sectorUser['name']) ?></td>
                                    <td><?= e($sectorUser['email']) ?></td>
                                    <td><?= e(role_label($sectorUser['role'], $sectorUser['sector'])) ?></td>
                                    <?php if ($canManageLoanRequests): ?>
                                        <td><?= e((string) ($sectorUser['requester_sector_name'] ?? '')) ?></td>
                                    <?php endif; ?>
                                    <td><?= e(date('d/m/Y H:i', strtotime((string) $sectorUser['created_at']))) ?></td>
                                    <td class="action-cell">
                                        <a class="table-action" href="<?= e(url_for('/setores/editar-usuario.php?id=' . (int) $sectorUser['id'])) ?>">Editar</a>
                                        <!-- O confirm evita apagar usuário por clique acidental. -->
                                        <form method="post" class="inline-form" onsubmit="return confirm('Apagar este usuário? Esta ação não pode ser desfeita.');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?= (int) $sectorUser['id'] ?>">
                                            <button class="table-action danger-action" type="submit">Apagar</button>
                                        </form>
                                    </td>
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
