<?php

require_once __DIR__ . '/../includes/inventory.php';

$user = require_login();

if (!is_requester($user) && !can_manage_loan_requests($user)) {
    redirect_to_user_sector($user);
}

$sectorName = is_requester($user) ? 'SOLICITANTE' : SECTORS[$user['sector']];
$canManageLoanRequests = can_manage_loan_requests($user);
$activePage = 'solicitacoes';
$message = '';
$error = '';
$items = list_items($user['sector']);
$blockState = is_requester($user) ? requester_block_state((int) $user['id']) : null;

// POST trata tanto criação pelo solicitante quanto ações de gestão do empréstimo.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf_token();

        $action = (string) ($_POST['action'] ?? 'create');

        if ($action === 'create') {
            // Solicitante abre pedido para item disponível no setor dele.
            create_material_loan_request(
                $user,
                (int) ($_POST['item_id'] ?? 0),
                trim((string) ($_POST['borrower_name'] ?? '')),
                trim((string) ($_POST['registration_number'] ?? '')),
                trim((string) ($_POST['responsible_teacher'] ?? '')),
                (string) ($_POST['return_due_date'] ?? ''),
                (int) ($_POST['requested_quantity'] ?? 1),
                trim((string) ($_POST['other_materials'] ?? '')),
                isset($_POST['rules_accepted'])
            );
            $message = 'Solicitação enviada ao setor.';
        } elseif ($action === 'withdraw' || $action === 'return') {
            // Gestor confirma saída ou devolução e o estoque é atualizado junto.
            update_material_loan_status((int) ($_POST['loan_id'] ?? 0), $user, $action);
            $message = $action === 'withdraw'
                ? 'Retirada registrada e estoque atualizado.'
                : 'Devolução registrada e estoque atualizado.';
        } elseif ($action === 'renew') {
            // Renovação muda apenas a data limite de devolução.
            renew_material_loan_due_date((int) ($_POST['loan_id'] ?? 0), $user, (string) ($_POST['new_return_due_date'] ?? ''));
            $message = 'Prazo de devolução renovado.';
        } elseif ($action === 'infraction') {
            // Infração bloqueia o solicitante conforme a recorrência dele.
            $blockDays = register_material_loan_infraction(
                (int) ($_POST['loan_id'] ?? 0),
                $user,
                trim((string) ($_POST['infraction_reason'] ?? ''))
            );
            $message = 'Infração registrada. Usuário bloqueado por ' . $blockDays . ' dias.';
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$blockState = is_requester($user) ? requester_block_state((int) $user['id']) : null;
$loans = list_material_loans_for_user($user);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Solicitações - Gestão de Recurso Setorial</title>
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

        <?php if (is_requester($user)): ?>
            <section class="panel">
                <h2>Solicitar retirada</h2>
                <!-- Usuário bloqueado continua vendo o histórico, mas não abre pedido novo. -->
                <?php if ($blockState && $blockState['blocked']): ?>
                    <div class="notice error">Usuário bloqueado para novos empréstimos até <?= e($blockState['blocked_until_label']) ?>. Infrações registradas: <?= (int) $blockState['infraction_count'] ?>.</div>
                <?php else: ?>
                    <form method="post" class="form-grid" autocomplete="off">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="create">

                        <label>
                            Item
                            <select name="item_id" required>
                                <option value="">Selecione</option>
                                <!-- A lista vem do estoque do setor do próprio solicitante. -->
                                <?php foreach ($items as $item): ?>
                                    <option value="<?= (int) $item['id'] ?>"><?= e($item['name']) ?> - estoque <?= (int) $item['quantity'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label>
                            Quantidade
                            <input name="requested_quantity" type="number" min="1" value="1" required>
                        </label>

                        <label>
                            Nome do requisitante
                            <input name="borrower_name" type="text" value="<?= e($user['name']) ?>" required>
                        </label>

                        <label>
                            Matrícula
                            <input name="registration_number" type="text">
                        </label>

                        <label>
                            Prof. responsável
                            <input name="responsible_teacher" type="text">
                        </label>

                        <label>
                            Data para devolução
                            <input name="return_due_date" type="date" min="<?= e(date('Y-m-d')) ?>" required>
                        </label>

                        <label class="full">
                            Outros materiais
                            <textarea name="other_materials" rows="3"></textarea>
                        </label>

                        <label class="full checkbox-label">
                            <input name="rules_accepted" type="checkbox" value="1" required>
                            <span>Declaro que o empréstimo tem prazo de validade e que devo cumprir as regras: uso adequado, não repassar a terceiros, comunicar incidentes, indenizar danos por mau uso e devolver higienizado/limpo nas mesmas condições.</span>
                        </label>

                        <button type="submit">Enviar solicitação</button>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="panel">
            <h2><?= is_requester($user) ? 'Minhas solicitações' : 'Solicitações dos setores' ?></h2>

            <?php if (!$loans): ?>
                <p class="empty">Nenhuma solicitação registrada.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Setor</th>
                                <th>Requisitante</th>
                                <th>Item</th>
                                <th>Qtd.</th>
                                <th>Devolução</th>
                                <th>Status</th>
                                <th>Datas</th>
                                <?php if ($canManageLoanRequests): ?>
                                    <th class="action-cell">Ação</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($loans as $loan): ?>
                                <tr>
                                    <td><?= e($loan['requester_sector_name']) ?></td>
                                    <td>
                                        <?= e($loan['borrower_name']) ?>
                                        <?php if (!empty($loan['registration_number'])): ?>
                                            <small class="muted">Mat. <?= e((string) $loan['registration_number']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= e($loan['item_name']) ?>
                                        <?php if (!empty($loan['brand_model']) || !empty($loan['patrimony_number']) || !empty($loan['serial_number'])): ?>
                                            <small class="muted"><?= e(trim((string) ($loan['brand_model'] ?? '') . ' ' . (string) ($loan['patrimony_number'] ?? '') . ' ' . (string) ($loan['serial_number'] ?? ''))) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= (int) $loan['requested_quantity'] ?></td>
                                    <td>
                                        <?= e(date('d/m/Y', strtotime((string) $loan['return_due_date']))) ?>
                                        <?php if ($loan['status'] === 'retirada' && loan_is_overdue($loan)): ?>
                                            <small class="muted danger-text">Prazo vencido</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="stock-badge <?= e((string) $loan['status']) ?>"><?= e(material_loan_status_label((string) $loan['status'])) ?></span></td>
                                    <td>
                                        <small class="muted">Solicitada: <?= e(date('d/m/Y H:i', strtotime((string) $loan['requested_at']))) ?></small>
                                        <?php if ($loan['withdrawn_at']): ?>
                                            <small class="muted">Retirada: <?= e(date('d/m/Y H:i', strtotime((string) $loan['withdrawn_at']))) ?></small>
                                        <?php endif; ?>
                                        <?php if ($loan['returned_at']): ?>
                                            <small class="muted">Devolvida: <?= e(date('d/m/Y H:i', strtotime((string) $loan['returned_at']))) ?></small>
                                        <?php endif; ?>
                                        <?php if ($loan['infraction_at']): ?>
                                            <small class="muted danger-text">Infracao: <?= e((string) $loan['infraction_reason']) ?> (<?= (int) $loan['block_days'] ?> dias)</small>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($canManageLoanRequests): ?>
                                        <td class="action-cell loan-actions">
                                            <!-- Botões mudam conforme o estágio do empréstimo. -->
                                            <?php if ($loan['status'] === 'solicitada'): ?>
                                                <form method="post" class="inline-form">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="withdraw">
                                                    <input type="hidden" name="loan_id" value="<?= (int) $loan['id'] ?>">
                                                    <button class="table-action" type="submit">Registrar retirada</button>
                                                </form>
                                            <?php elseif ($loan['status'] === 'retirada'): ?>
                                                <form method="post" class="inline-form">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="return">
                                                    <input type="hidden" name="loan_id" value="<?= (int) $loan['id'] ?>">
                                                    <button class="table-action" type="submit">Registrar devolução</button>
                                                </form>
                                                <form method="post" class="inline-form loan-renew-form">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="renew">
                                                    <input type="hidden" name="loan_id" value="<?= (int) $loan['id'] ?>">
                                                    <input name="new_return_due_date" type="date" min="<?= e(date('Y-m-d')) ?>" required>
                                                    <button class="table-action" type="submit">Renovar prazo</button>
                                                </form>
                                                <?php if (empty($loan['infraction_at'])): ?>
                                                    <form method="post" class="inline-form loan-infraction-form">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="infraction">
                                                        <input type="hidden" name="loan_id" value="<?= (int) $loan['id'] ?>">
                                                        <input name="infraction_reason" type="text" placeholder="Motivo da infração" required>
                                                        <button class="table-action danger-action" type="submit">Registrar infração</button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="muted">Concluída</span>
                                            <?php endif; ?>
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
