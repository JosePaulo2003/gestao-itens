<?php

require_once __DIR__ . '/../includes/inventory.php';

// Relatorio protegido: cada usuario gera documento somente do proprio setor.
$user = require_login();
$sectorName = SECTORS[$user['sector']];
$activePage = 'relatorio';

$items = list_items($user['sector']);
$movements = list_item_movements($user['sector']);
$totalItems = count($items);
$inStock = count_items_in_stock($items);
$outOfStock = max(0, $totalItems - $inStock);
$generatedAt = date('d/m/Y H:i');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relatorio - Gestao de Recurso Setorial</title>
    <link rel="stylesheet" href="<?= e(url_for('/assets/css/style.css')) ?>">
</head>
<body class="<?= e(body_theme_class($user, $activePage)) ?>">
    <?php require __DIR__ . '/../templates/sector-header.php'; ?>

    <main class="dashboard report-dashboard">
        <div class="report-actions">
            <button type="button" onclick="window.print()">Imprimir / salvar PDF</button>
        </div>

        <article class="report-document">
            <header class="report-cover">
                <div>
                    <span class="report-kicker">Documento de controle patrimonial interno</span>
                    <h2>Relatorio de Itens e Movimentacoes</h2>
                    <p>Gestao de Recurso Setorial - <?= e($sectorName) ?></p>
                </div>
                <div class="report-meta">
                    <strong>Gerado em</strong>
                    <span><?= e($generatedAt) ?></span>
                    <strong>Responsavel</strong>
                    <span><?= e($user['name']) ?></span>
                </div>
            </header>

            <section class="report-section">
                <h3>Resumo executivo</h3>
                <div class="report-summary">
                    <div>
                        <span>Total de itens</span>
                        <strong><?= $totalItems ?></strong>
                    </div>
                    <div>
                        <span>Em estoque</span>
                        <strong><?= $inStock ?></strong>
                    </div>
                    <div>
                        <span>Sem estoque</span>
                        <strong><?= $outOfStock ?></strong>
                    </div>
                    <div>
                        <span>Movimentacoes</span>
                        <strong><?= count($movements) ?></strong>
                    </div>
                </div>
            </section>

            <section class="report-section">
                <h3>Itens existentes</h3>
                <?php if (!$items): ?>
                    <p class="empty">Nenhum item cadastrado neste setor.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Descricao</th>
                                    <th>Quantidade</th>
                                    <th>Status</th>
                                    <th>Atualizado em</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= e($item['name']) ?></td>
                                        <td><?= e((string) $item['description']) ?></td>
                                        <td><?= (int) $item['quantity'] ?></td>
                                        <td><?= (int) $item['in_stock'] > 0 ? 'Em estoque' : 'Sem estoque' ?></td>
                                        <td><?= e(date('d/m/Y H:i', strtotime((string) $item['updated_at']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="report-section">
                <h3>Historico de movimentacoes</h3>
                <?php if (!$movements): ?>
                    <p class="empty">Nenhuma movimentacao registrada ainda.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Item</th>
                                    <th>Movimento</th>
                                    <th>Anterior</th>
                                    <th>Atual</th>
                                    <th>Diferenca</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movements as $movement): ?>
                                    <tr>
                                        <td><?= e(date('d/m/Y H:i', strtotime((string) $movement['created_at']))) ?></td>
                                        <td><?= e($movement['item_name']) ?></td>
                                        <td><?= e(movement_label($movement['movement_type'])) ?></td>
                                        <td><?= (int) $movement['old_quantity'] ?></td>
                                        <td><?= (int) $movement['new_quantity'] ?></td>
                                        <td><?= (int) $movement['quantity_delta'] ?></td>
                                        <td><?= e((string) ($movement['user_name'] ?? 'Sistema')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <footer class="report-signature">
                <div>
                    <span></span>
                    <p>Responsavel pelo setor</p>
                </div>
                <div>
                    <span></span>
                    <p>CTIC CESIT</p>
                </div>
            </footer>
        </article>
    </main>
</body>
</html>
