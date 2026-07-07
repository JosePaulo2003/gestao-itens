<?php

require_once __DIR__ . '/../includes/inventory.php';

// Relatorio protegido: cada usuario gera documentos somente do proprio setor.
$user = require_login();
$sectorName = SECTORS[$user['sector']];
$activePage = 'relatorio';

$items = list_items($user['sector']);
$movements = list_item_movements($user['sector']);
$totalItems = count($items);
$inStock = count_items_in_stock($items);
$outOfStock = max(0, $totalItems - $inStock);
$generatedAt = date('d/m/Y H:i');
$today = date('d/m/Y');

$documents = [
    'itens' => 'Itens cadastrados',
    'movimentacoes' => 'Movimentacoes',
    'livro-registro' => 'Livro de registro',
    'cautela' => 'Cautela',
];

$documentType = (string) ($_GET['doc'] ?? 'itens');

if (!isset($documents[$documentType])) {
    $documentType = 'itens';
}

$cautionRows = array_slice($items, 0, 4);

while (count($cautionRows) < 4) {
    $cautionRows[] = null;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relatorio - Gestao de Recurso Setorial</title>
    <link rel="stylesheet" href="<?= e(asset_url('/assets/css/style.css')) ?>">
</head>
<body class="<?= e(body_theme_class($user, $activePage)) ?>">
    <?php require __DIR__ . '/../templates/sector-header.php'; ?>

    <main class="dashboard report-dashboard <?= $documentType === 'livro-registro' ? 'report-landscape' : '' ?>">
        <section class="report-toolbar no-print" aria-label="Tipos de documentos">
            <div class="report-tabs">
                <?php foreach ($documents as $key => $label): ?>
                    <a class="<?= $documentType === $key ? 'active' : '' ?>" href="<?= e(url_for('/setores/relatorio.php?doc=' . $key)) ?>">
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <button type="button" onclick="window.print()">Imprimir / salvar PDF</button>
        </section>

        <?php if ($documentType === 'itens'): ?>
            <article class="report-document">
                <header class="report-cover">
                    <div>
                        <span class="report-kicker">Documento de controle patrimonial interno</span>
                        <h2>Relatorio de Itens Cadastrados</h2>
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
                    <h3>Resumo do estoque</h3>
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
                    </div>
                </section>

                <section class="report-section">
                    <h3>Itens cadastrados</h3>
                    <?php if (!$items): ?>
                        <p class="empty">Nenhum item cadastrado neste setor.</p>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>N.</th>
                                        <th>Item</th>
                                        <th>Descricao</th>
                                        <th>Quantidade</th>
                                        <th>Status</th>
                                        <th>Atualizado em</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $index => $item): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
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
        <?php endif; ?>

        <?php if ($documentType === 'movimentacoes'): ?>
            <article class="report-document">
                <header class="report-cover">
                    <div>
                        <span class="report-kicker">Documento de controle patrimonial interno</span>
                        <h2>Relatorio de Movimentacoes</h2>
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
                    <h3>Resumo de movimentacoes</h3>
                    <div class="report-summary">
                        <div>
                            <span>Total</span>
                            <strong><?= count($movements) ?></strong>
                        </div>
                        <div>
                            <span>Setor</span>
                            <strong><?= e($sectorName) ?></strong>
                        </div>
                    </div>
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
        <?php endif; ?>

        <?php if ($documentType === 'livro-registro'): ?>
            <article class="registry-document document-sheet">
                <?php for ($record = 0; $record < 5; $record++): ?>
                    <section class="registry-slip">
                        <div class="registry-top">
                            <div class="registry-received">
                                <strong>RECEBIDO em ....../....../......</strong>
                                <span>Assinatura ou Carimbo</span>
                            </div>
                            <div class="registry-destination">
                                <strong>Destinatario</strong>
                                <span>Rua.........................................</span>
                            </div>
                        </div>
                        <div class="registry-body">
                            <strong>DESCRICAO</strong>
                            <span>N. ...............</span>
                            <p>............................................................................</p>
                            <p>............................................................................</p>
                            <p>............................................................................</p>
                            <p>............................................................................</p>
                        </div>
                    </section>
                <?php endfor; ?>
                <span class="registry-font">Fonte 22</span>
            </article>
        <?php endif; ?>

        <?php if ($documentType === 'cautela'): ?>
            <article class="caution-document document-sheet">
                <div class="caution-corner caution-corner-top-left"></div>
                <div class="caution-corner caution-corner-top-right"></div>
                <div class="caution-corner caution-corner-bottom-left"></div>
                <div class="caution-corner caution-corner-bottom-right"></div>

                <header class="caution-header">
                    <div class="caution-brand">
                        <span class="caution-seal">AM</span>
                        <div>
                            <strong>AMAZONAS</strong>
                            <small>GOVERNO DO ESTADO</small>
                        </div>
                    </div>
                    <span class="caution-colorbar"></span>
                    <h2>CENTRO DE ESTUDOS SUPERIORES DE ITACOATIARA - CESIT/UEA</h2>
                    <h3>Cautela de Emprestimo de Materiais/Equipamentos</h3>
                </header>

                <section class="caution-box">
                    <div class="caution-box-title">
                        <strong>CAUTELA/DIRECAO_D. I/CESIT/UEA-____/<?= e(date('Y')) ?></strong>
                        <span>Data Saida ___/___/______</span>
                    </div>
                    <div class="caution-field-row">
                        <span>Origem:</span>
                        <strong>D.I/CESIT/UEA</strong>
                    </div>
                    <div class="caution-field-row caution-large-line">
                        <span>Destinatario(a):</span>
                    </div>
                </section>

                <section class="caution-observation">
                    <strong>Observacao:</strong>
                </section>

                <table class="caution-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qtde</th>
                            <th>Unid.</th>
                            <th>Especificacao</th>
                            <th>Tombo / Serial</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cautionRows as $index => $item): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= $item ? (int) $item['quantity'] : '' ?></td>
                                <td><strong>Un.</strong></td>
                                <td><?= $item ? e($item['name']) : '' ?></td>
                                <td><?= $item ? 'Item ' . (int) $item['id'] : '' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <section class="caution-authorization">
                    <strong>Autorizado por:</strong>
                    <strong>Data:</strong>
                </section>

                <section class="caution-signatures">
                    <p>Entregue por: ____________________________________ <span>Data:</span></p>
                    <p>Recebido por: ____________________________________ <span>Data:</span></p>
                    <p>Devolvido por: ___________________________________ <span>Data:</span></p>
                </section>

                <footer class="caution-footer">
                    <div>
                        <span>www.amazonas.am.gov.br</span>
                        <span>twitter.com/GovernodoAM</span>
                        <span>youtube.com/governodoamazonas</span>
                        <span>facebook.com/governodoamazonas</span>
                    </div>
                    <div>
                        <span>Av. Djalma Batista, 3578 - Flores</span>
                        <span>Manaus - AM, 69050-10</span>
                    </div>
                    <strong>Universidade do Estado<br>do Amazonas</strong>
                </footer>
            </article>
        <?php endif; ?>
    </main>
</body>
</html>
