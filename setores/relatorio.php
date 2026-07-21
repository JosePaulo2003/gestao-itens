<?php

require_once __DIR__ . '/../includes/inventory.php';

// Relatorio protegido: cada usuario gera documentos somente do proprio setor.
$user = require_login();

if (is_requester($user)) {
    redirect_to_user_sector($user);
}

$sectorName = SECTORS[$user['sector']];
$activePage = 'relatorio';

$items = list_items($user['sector']);
$movements = list_item_movements($user['sector']);
$totalItems = count($items);
$inStock = count_items_in_stock($items);
$outOfStock = max(0, $totalItems - $inStock);
$generatedAt = date('d/m/Y H:i');
$today = date('d/m/Y');
$showCticSignature = $user['sector'] !== 'almoxarifado';

$documents = [
    'itens' => 'Itens cadastrados',
    'movimentacoes' => 'Movimentacoes',
    'livro-registro' => 'Livro de registro',
    'cautela' => 'Cautela',
];

if ($user['sector'] === 'almoxarifado') {
    $documents['requisicao-materiais'] = 'Requisicao de materiais';
    $documents['termo-emprestimo'] = 'Termo emprestimo/devolucao';
}

$documentType = (string) ($_GET['doc'] ?? 'itens');

if (!isset($documents[$documentType])) {
    $documentType = 'itens';
}

$cautionRows = array_slice($items, 0, 4);

while (count($cautionRows) < 4) {
    $cautionRows[] = null;
}

$loanItemOptions = array_map(
    static fn (array $item): array => [
        'id' => (int) $item['id'],
        'name' => (string) $item['name'],
        'brandModel' => (string) ($item['brand_model'] ?? ''),
        'patrimonyNumber' => (string) ($item['patrimony_number'] ?? ''),
        'serialNumber' => (string) ($item['serial_number'] ?? ''),
        'otherMaterials' => (string) ($item['other_materials'] ?? ''),
    ],
    $items
);
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

    <main class="dashboard report-dashboard">
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

        <?php if (in_array($documentType, ['livro-registro', 'cautela', 'requisicao-materiais', 'termo-emprestimo'], true)): ?>
            <p class="document-hint no-print">Clique nos campos pontilhados para preencher o documento antes de imprimir.</p>
        <?php endif; ?>

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
                    <?php if ($showCticSignature): ?>
                        <div>
                            <span></span>
                            <p>CTIC CESIT</p>
                        </div>
                    <?php endif; ?>
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
                    <?php if ($showCticSignature): ?>
                        <div>
                            <span></span>
                            <p>CTIC CESIT</p>
                        </div>
                    <?php endif; ?>
                </footer>
            </article>
        <?php endif; ?>

        <?php if ($documentType === 'livro-registro'): ?>
            <div class="registry-controls no-print">
                <button type="button" id="add-registry-record">Adicionar registro na pagina</button>
            </div>

            <article class="registry-page-document document-sheet" id="registry-document">
                <section class="registry-record">
                    <header class="registry-record-header">
                        <div>
                            <strong>RECEBIDO em</strong>
                            <span class="editable-field registry-date" contenteditable="true">____/____/______</span>
                        </div>
                        <span class="editable-field registry-signature" contenteditable="true">Assinatura ou Carimbo</span>
                    </header>

                    <div class="registry-recipient">
                        <label>Destinatario Nome:</label>
                        <span class="editable-field" contenteditable="true"></span>
                        <label>Rua:</label>
                        <span class="editable-field" contenteditable="true"></span>
                    </div>

                    <div class="registry-description">
                        <div class="registry-description-title">
                            <strong>DESCRICAO</strong>
                            <span>N. <span class="editable-field registry-number" contenteditable="true"></span></span>
                        </div>
                        <div class="registry-lines">
                            <span class="editable-field" contenteditable="true"></span>
                            <span class="editable-field" contenteditable="true"></span>
                            <span class="editable-field" contenteditable="true"></span>
                            <span class="editable-field" contenteditable="true"></span>
                        </div>
                    </div>
                </section>
            </article>

            <template id="registry-record-template">
                <section class="registry-record">
                    <header class="registry-record-header">
                        <div>
                            <strong>RECEBIDO em</strong>
                            <span class="editable-field registry-date" contenteditable="true">____/____/______</span>
                        </div>
                        <span class="editable-field registry-signature" contenteditable="true">Assinatura ou Carimbo</span>
                    </header>

                    <div class="registry-recipient">
                        <label>Destinatario Nome:</label>
                        <span class="editable-field" contenteditable="true"></span>
                        <label>Rua:</label>
                        <span class="editable-field" contenteditable="true"></span>
                    </div>

                    <div class="registry-description">
                        <div class="registry-description-title">
                            <strong>DESCRICAO</strong>
                            <span>N. <span class="editable-field registry-number" contenteditable="true"></span></span>
                        </div>
                        <div class="registry-lines">
                            <span class="editable-field" contenteditable="true"></span>
                            <span class="editable-field" contenteditable="true"></span>
                            <span class="editable-field" contenteditable="true"></span>
                            <span class="editable-field" contenteditable="true"></span>
                        </div>
                    </div>
                </section>
            </template>

            <script>
                (() => {
                    const addButton = document.getElementById('add-registry-record');
                    const registryDocument = document.getElementById('registry-document');
                    const recordTemplate = document.getElementById('registry-record-template');

                    if (!addButton || !registryDocument || !recordTemplate) {
                        return;
                    }

                    addButton.addEventListener('click', () => {
                        registryDocument.appendChild(recordTemplate.content.cloneNode(true));
                    });
                })();
            </script>
        <?php endif; ?>

        <?php if ($documentType === 'cautela'): ?>
            <article class="caution-document document-sheet">
                <div class="caution-corner caution-corner-top-left"></div>
                <div class="caution-corner caution-corner-top-right"></div>
                <div class="caution-corner caution-corner-bottom-left"></div>
                <div class="caution-corner caution-corner-bottom-right"></div>

                <header class="caution-header">
                    <img class="caution-logo" src="<?= e(asset_url('/assets/img/logo-amazonas.png')) ?>" alt="Governo do Estado do Amazonas">
                    <span class="caution-colorbar"></span>
                    <h2>CENTRO DE ESTUDOS SUPERIORES DE ITACOATIARA - CESIT/UEA</h2>
                    <h3>Cautela de Emprestimo de Materiais/Equipamentos</h3>
                </header>

                <section class="caution-box">
                    <div class="caution-box-title">
                        <strong>CAUTELA <span class="editable-field" contenteditable="true"></span>/<?= e(date('Y')) ?></strong>
                        <span>Data Saida <span class="editable-field caution-date-field" contenteditable="true">/  /</span></span>
                    </div>
                    <div class="caution-field-row">
                        <span>Origem:</span>
                        <strong class="editable-field" contenteditable="true"></strong>
                    </div>
                    <div class="caution-field-row caution-large-line">
                        <span>Destinatario(a):</span>
                        <span class="editable-field caution-wide-field" contenteditable="true"></span>
                    </div>
                </section>

                <section class="caution-observation">
                    <strong>Observacao:</strong>
                    <span class="editable-field caution-wide-field" contenteditable="true"></span>
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
                                <td><span class="editable-field table-fill" contenteditable="true"><?= $item ? (int) $item['quantity'] : '' ?></span></td>
                                <td><strong class="editable-field table-fill" contenteditable="true">Un.</strong></td>
                                <td><span class="editable-field table-fill" contenteditable="true"><?= $item ? e($item['name']) : '' ?></span></td>
                                <td><span class="editable-field table-fill" contenteditable="true"><?= $item ? 'Item ' . (int) $item['id'] : '' ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <section class="caution-authorization">
                    <strong>Autorizado por: <span class="editable-field" contenteditable="true"></span></strong>
                    <strong>Data: <span class="editable-field caution-date-field" contenteditable="true">/  /</span></strong>
                </section>

                <section class="caution-signatures">
                    <p>Entregue por: <span class="editable-field" contenteditable="true"></span> <span>Data: <span class="editable-field caution-date-field" contenteditable="true">/  /</span></span></p>
                    <p>Recebido por: <span class="editable-field" contenteditable="true"></span> <span>Data: <span class="editable-field caution-date-field" contenteditable="true">/  /</span></span></p>
                    <p>Devolvido por: <span class="editable-field" contenteditable="true"></span> <span>Data: <span class="editable-field caution-date-field" contenteditable="true">/  /</span></span></p>
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

        <?php if ($documentType === 'requisicao-materiais' && $user['sector'] === 'almoxarifado'): ?>
            <article class="material-request-document editable-template-document document-sheet">
                <header class="material-request-header">
                    <div class="request-black-corner"></div>
                    <div>
                        <h2>REQUISICAO DE MATERIAIS</h2>
                        <p>SETOR SOLICITANTE: <span class="editable-field request-sector-field" contenteditable="true"></span></p>
                    </div>
                    <strong class="uea-mark">UEA</strong>
                </header>

                <table class="material-request-table">
                    <thead>
                        <tr>
                            <th>Solicitado por</th>
                            <th>Codigo</th>
                            <th>Descricao</th>
                            <th>Quantidade</th>
                            <th>Entregue por</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($row = 0; $row < 15; $row++): ?>
                            <tr>
                                <td><span class="editable-field table-fill" contenteditable="true"></span></td>
                                <td><span class="editable-field table-fill" contenteditable="true"></span></td>
                                <td><input class="material-description-input" list="material-request-items" type="text" aria-label="Descricao"></td>
                                <td><span class="editable-field table-fill" contenteditable="true"></span></td>
                                <td><span class="editable-field table-fill" contenteditable="true"></span></td>
                                <td><span class="editable-field table-fill" contenteditable="true"></span></td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
                <datalist id="material-request-items">
                    <?php foreach ($items as $item): ?>
                        <option value="<?= e($item['name']) ?>"></option>
                    <?php endforeach; ?>
                </datalist>

                <footer class="material-request-footer">
                    <strong>Responsavel setor solicitante: <span class="editable-field request-signature-field" contenteditable="true"></span></strong>
                    <strong>Data: <span class="editable-field request-date-field" contenteditable="true">___/___/___</span></strong>
                </footer>
            </article>
        <?php endif; ?>

        <?php if ($documentType === 'termo-emprestimo' && $user['sector'] === 'almoxarifado'): ?>
            <article class="loan-term-document editable-template-document document-sheet">
                <header class="loan-term-header">
                    <img class="loan-term-logo" src="<?= e(asset_url('/assets/img/brasao-amazonas.png')) ?>" alt="Brasao do Estado do Amazonas">
                    <strong>UNIVERSIDADE DO ESTADO DO AMAZONAS</strong>
                    <span>CENTRO DE ESTUDOS SUPERIORES DE ITACOATIARA</span>
                </header>

                <h2>TERMO DE EMPRESTIMO/DEVOLUCAO</h2>

                <section class="loan-term-grid borrower-grid">
                    <div>
                        <strong>Nome do Requisitante</strong>
                        <span id="loan-requester" class="editable-field" contenteditable="true"></span>
                    </div>
                    <div>
                        <strong>Matricula:</strong>
                        <span id="loan-registration" class="editable-field" contenteditable="true"></span>
                    </div>
                    <div>
                        <strong>Prof. Responsavel</strong>
                        <span id="loan-teacher" class="editable-field" contenteditable="true"></span>
                    </div>
                    <div>
                        <strong>Data para Devolucao</strong>
                        <span id="loan-return-date" class="editable-field" contenteditable="true"></span>
                    </div>
                </section>

                <section class="loan-equipment">
                    <h3>Especificacao do Equipamento</h3>
                    <div class="loan-term-grid equipment-grid">
                        <div>
                            <strong>Marca/Modelo</strong>
                            <input id="loan-brand-model" class="document-autocomplete-field" list="loan-term-items" type="text" aria-label="Marca/Modelo">
                        </div>
                        <div>
                            <strong>Patrimonio</strong>
                            <span id="loan-patrimony" class="editable-field" contenteditable="true"></span>
                        </div>
                        <div>
                            <strong>N. de Serie</strong>
                            <span id="loan-serial" class="editable-field" contenteditable="true"></span>
                        </div>
                    </div>
                </section>
                <datalist id="loan-term-items">
                    <?php foreach ($items as $item): ?>
                        <option value="<?= e($item['name']) ?>"></option>
                    <?php endforeach; ?>
                </datalist>

                <section class="loan-other-materials">
                    <strong>Outros Materiais:</strong>
                    <div class="loan-checkboxes">
                        <span>Sim</span><span class="checkbox-field"></span>
                        <span>Nao</span><span class="checkbox-field"></span>
                    </div>
                    <span id="loan-other-materials" class="editable-field" contenteditable="true"></span>
                </section>

                <section class="loan-text-block">
                    <h3>Termo de Responsabilidade</h3>
                    <p>Pelo presente Termo de Entrega e Responsabilidade, o Requerente acima qualificado declara que recebeu o equipamento e/ou material acima especificados, de propriedade do Centro de Estudos Superiores de Itacoatiara - UEA, assumindo o compromisso de manter a guarda pessoal sobre os mesmos, ficando a seu cargo:</p>
                    <ul>
                        <li>Adequada utilizacao, de acordo com as recomendacoes;</li>
                        <li>Comprometer-se a nao conceder emprestimos ou confiar a outrem;</li>
                        <li>Comunicar imediatamente qualquer incidente ou ocorrencia com o equipamento sob sua guarda e/ou responsabilidade;</li>
                        <li>Indenizar os danos causados por negligencia, ma utilizacao, guarda inadequada, desleixo ou outro dano que possa decorrer, direta ou indiretamente de sua acao ou omissao;</li>
                        <li>Devolver o material ou equipamento higienizado/limpo e nas mesmas condicoes que foram entregues;</li>
                    </ul>
                </section>

                <section class="loan-signature-grid">
                    <div><strong>Data de Retirada</strong><span class="signature-line editable-field" contenteditable="true"></span></div>
                    <div><strong>Assinatura/Carimbo Requisitante</strong><span class="signature-line editable-field" contenteditable="true"></span></div>
                    <div><strong>Assinatura/Carimbo<br>Almoxarifado da UEA</strong><span class="signature-line editable-field" contenteditable="true"></span></div>
                </section>

                <section class="loan-text-block">
                    <h3>Termo de Devolucao</h3>
                    <p>Pelo presente Termo de Devolucao, o requerente acima qualificado declara que devolveu o equipamento e acessorios acima especificados, nas mesmas condicoes que os recebeu. O servidor do CESIT-UEA abaixo assinado, declara que recebeu os equipamentos em devolucao, nas mesmas condicoes de emprestimo.</p>
                </section>

                <section class="loan-observations">
                    <strong>Observacoes:</strong>
                    <span class="editable-field" contenteditable="true"></span>
                    <span class="editable-field" contenteditable="true"></span>
                </section>

                <section class="loan-signature-grid return-grid">
                    <div><strong>Data de Devolucao</strong><span class="signature-line editable-field" contenteditable="true"></span></div>
                    <div><strong>Assinatura Requisitante</strong><span class="signature-line editable-field" contenteditable="true"></span></div>
                    <div><strong>Assinatura/Carimbo<br>Almoxarifado da UEA</strong><span class="signature-line editable-field" contenteditable="true"></span></div>
                </section>
            </article>

            <script>
                (() => {
                    const items = <?= json_encode($loanItemOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
                    const itemInput = document.getElementById('loan-brand-model');
                    const setText = (id, value) => {
                        const target = document.getElementById(id);

                        if (target) {
                            target.textContent = value || '';
                        }
                    };
                    if (itemInput) {
                        itemInput.addEventListener('input', () => {
                            const item = items.find((entry) => entry.name === itemInput.value);

                            setText('loan-patrimony', item ? item.patrimonyNumber : '');
                            setText('loan-serial', item ? item.serialNumber : '');
                            setText('loan-other-materials', item ? item.otherMaterials : '');
                        });
                    }
                })();
            </script>
        <?php endif; ?>
    </main>
</body>
</html>