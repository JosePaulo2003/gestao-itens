<?php

require_once __DIR__ . '/../includes/inventory.php';

// Pagina protegida: somente usuarios logados podem cadastrar itens.
$user = require_login();

if (!can_manage_items($user)) {
    redirect_to_user_sector($user);
}

$sectorName = SECTORS[$user['sector']];
$activePage = 'cadastrar-item';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf_token();

        // Entrada do formulario. trim remove espacos acidentais.
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $quantity = max(0, (int) ($_POST['quantity'] ?? 0));

        // Nome e o minimo necessario para o item existir no estoque.
        if ($name === '') {
            throw new RuntimeException('Informe o nome do item.');
        }

        // Foto e opcional. Quando enviada, e validada e salva em /uploads/items.
        $imagePath = save_item_image($_FILES['image'] ?? [], $user['sector']);

        // O item sempre entra vinculado ao setor do usuario logado.
        create_item($user['sector'], $name, $description, $quantity, $imagePath, (int) $user['id']);
        $message = 'Item cadastrado com sucesso.';
    } catch (Throwable $exception) {
        // Qualquer erro de validacao/upload/banco aparece no topo da pagina.
        $error = $exception->getMessage();
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastrar item - Gestao de Recurso Setorial</title>
    <link rel="stylesheet" href="<?= e(url_for('/assets/css/style.css')) ?>">
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
            <h2>Cadastrar item</h2>
            <form method="post" class="form-grid" autocomplete="off" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <label>
                    Nome do item
                    <input name="name" type="text" required>
                </label>

                <label>
                    Quantidade inicial
                    <input name="quantity" type="number" min="0" value="0" required>
                </label>

                <label class="full">
                    Descricao
                    <textarea name="description" rows="4"></textarea>
                </label>

                <label class="full">
                    Foto do item
                    <input name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                    <span class="field-hint">Formatos aceitos: JPG, PNG, WEBP ou GIF. Tamanho maximo: 2 MB.</span>
                </label>

                <button type="submit">Cadastrar item</button>
            </form>
        </section>
    </main>
</body>
</html>
