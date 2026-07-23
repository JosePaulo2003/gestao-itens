<?php

require_once __DIR__ . '/../includes/inventory.php';

// Editar item exige gestor/admin do proprio setor.
$user = require_login();

if (!can_manage_items($user)) {
    redirect_to_user_sector($user);
}

$targetItemId = (int) ($_GET['id'] ?? 0);
$targetItem = find_item_for_sector($targetItemId, $user['sector']);

if (!$targetItem) {
    http_response_code(404);
    exit('Item nao encontrado ou fora do seu setor.');
}

$sectorName = SECTORS[$user['sector']];
$activePage = 'estoque';
$backUrl = url_for('/setores/estoque.php');
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf_token();

        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $quantity = max(0, (int) ($_POST['quantity'] ?? 0));
        $brandModel = trim((string) ($_POST['brand_model'] ?? ''));
        $patrimonyNumber = trim((string) ($_POST['patrimony_number'] ?? ''));
        $serialNumber = trim((string) ($_POST['serial_number'] ?? ''));
        $otherMaterials = trim((string) ($_POST['other_materials'] ?? ''));
        $imagePath = save_item_image($_FILES['image'] ?? [], $user['sector']);

        update_item_details(
            $targetItemId,
            $user['sector'],
            $name,
            $description,
            $quantity,
            $imagePath,
            (int) $user['id'],
            $brandModel,
            $patrimonyNumber,
            $serialNumber,
            $otherMaterials
        );

        $message = 'Item atualizado com sucesso.';
        $targetItem = find_item_for_sector($targetItemId, $user['sector']);
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar item - Gestao de Recurso Setorial</title>
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
            <h2>Editar item</h2>
            <p class="muted">Item vinculado ao setor <?= e($sectorName) ?>.</p>

            <form method="post" class="form-grid" autocomplete="off" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <label>
                    Nome do item
                    <input name="name" type="text" value="<?= e((string) $targetItem['name']) ?>" required>
                </label>

                <label>
                    Quantidade
                    <input name="quantity" type="number" min="0" value="<?= (int) $targetItem['quantity'] ?>" required>
                    <span class="field-hint">Alterar este valor registra uma movimentacao no historico.</span>
                </label>

                <label class="full">
                    Descricao
                    <textarea name="description" rows="4"><?= e((string) ($targetItem['description'] ?? '')) ?></textarea>
                </label>

                <?php if ($user['sector'] === 'almoxarifado'): ?>
                    <label>
                        Marca / modelo
                        <input name="brand_model" type="text" value="<?= e((string) ($targetItem['brand_model'] ?? '')) ?>">
                    </label>

                    <label>
                        Patrimonio
                        <input name="patrimony_number" type="text" value="<?= e((string) ($targetItem['patrimony_number'] ?? '')) ?>">
                    </label>

                    <label>
                        N. de serie
                        <input name="serial_number" type="text" value="<?= e((string) ($targetItem['serial_number'] ?? '')) ?>">
                    </label>

                    <label class="full">
                        Outros materiais
                        <textarea name="other_materials" rows="3"><?= e((string) ($targetItem['other_materials'] ?? '')) ?></textarea>
                        <span class="field-hint">Usado para preencher automaticamente o Termo de Emprestimo/Devolucao.</span>
                    </label>
                <?php endif; ?>

                <label class="full">
                    Foto do item
                    <?php if (!empty($targetItem['image_path'])): ?>
                        <img class="item-photo item-photo-preview" src="<?= e((string) $targetItem['image_path']) ?>" alt="Foto atual de <?= e((string) $targetItem['name']) ?>">
                    <?php endif; ?>
                    <input name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                    <span class="field-hint">Envie uma nova foto apenas se quiser substituir a atual. Tamanho maximo: 10 MB.</span>
                </label>

                <button type="submit">Salvar alteracoes</button>
                <a class="secondary-action" href="<?= e($backUrl) ?>">Voltar</a>
            </form>
        </section>
    </main>
</body>
</html>
