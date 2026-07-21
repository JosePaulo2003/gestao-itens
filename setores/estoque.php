<?php

require_once __DIR__ . '/../includes/inventory.php';

// Estoque e uma pagina protegida para qualquer perfil logado.
$user = require_login();

if (is_requester($user)) {
    redirect_to_user_sector($user);
}

$sectorName = SECTORS[$user['sector']];
$activePage = 'estoque';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf_token();

        if (!can_manage_items($user)) {
            throw new RuntimeException('Seu perfil permite apenas consultar os itens.');
        }

        // Cada linha da tabela envia o ID do item e a nova quantidade.
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $quantity = max(0, (int) ($_POST['quantity'] ?? 0));

        // A funcao tambem confere o setor, evitando alterar item de outro setor.
        update_item_stock($itemId, $user['sector'], $quantity, (int) $user['id']);
        $message = 'Estoque atualizado com sucesso.';
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

// Lista somente os itens do setor do usuario logado.
$items = list_items($user['sector']);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estoque - Gestao de Recurso Setorial</title>
    <link rel="stylesheet" href="<?= e(asset_url('/assets/css/style.css')) ?>">
</head>
<body class="<?= e(body_theme_class($user, $activePage)) ?>">
    <?php require __DIR__ . '/../templates/sector-header.php'; ?>

    <main class="dashboard">
        <?php if ($message): ?>
            <div class="notice success"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="notice error"><?= e($error) ?></div>
        <?php endif; ?>

        <section class="panel">
            <h2>Estoque do setor</h2>

            <?php if (!$items): ?>
                <p class="empty">Nenhum item cadastrado ainda.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Item</th>
                                <th>Descricao</th>
                                <th>Quantidade</th>
                                <th>Status</th>
                                <?php if (can_manage_items($user)): ?>
                                    <th>Atualizar</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($item['image_path'])): ?>
                                            <img class="item-photo" src="<?= e((string) $item['image_path']) ?>" alt="Foto de <?= e($item['name']) ?>">
                                        <?php else: ?>
                                            <span class="item-photo placeholder">Sem foto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($item['name']) ?></td>
                                    <td>
                                        <?= e((string) $item['description']) ?>
                                        <?php if ($user['sector'] === 'almoxarifado' && (!empty($item['brand_model']) || !empty($item['patrimony_number']) || !empty($item['serial_number']))): ?>
                                            <dl class="item-document-details">
                                                <?php if (!empty($item['brand_model'])): ?>
                                                    <div><dt>Marca/modelo</dt><dd><?= e((string) $item['brand_model']) ?></dd></div>
                                                <?php endif; ?>
                                                <?php if (!empty($item['patrimony_number'])): ?>
                                                    <div><dt>Patrimonio</dt><dd><?= e((string) $item['patrimony_number']) ?></dd></div>
                                                <?php endif; ?>
                                                <?php if (!empty($item['serial_number'])): ?>
                                                    <div><dt>N. serie</dt><dd><?= e((string) $item['serial_number']) ?></dd></div>
                                                <?php endif; ?>
                                            </dl>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= (int) $item['quantity'] ?></td>
                                    <td>
                                        <span class="stock-badge <?= (int) $item['in_stock'] > 0 ? 'in' : 'out' ?>">
                                            <?= (int) $item['in_stock'] > 0 ? 'Em estoque' : 'Sem estoque' ?>
                                        </span>
                                    </td>
                                    <?php if (can_manage_items($user)): ?>
                                        <td>
                                        <form method="post" class="stock-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                                <input name="quantity" type="number" min="0" value="<?= (int) $item['quantity'] ?>" required>
                                                <button type="submit">Salvar</button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
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
