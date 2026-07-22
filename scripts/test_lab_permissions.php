<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

/*
 * Confere os rotulos e permissoes dos laboratorios.
 * Admin pode gerenciar; bolsista deve ficar apenas com consulta.
 */

$emails = [
    'designer@sas.local',
    'bolsista.designer@sas.local',
    'maker@sas.local',
    'bolsista.maker@sas.local',
];

foreach ($emails as $email) {
    // Cada saída mostra o rótulo de tela e se o perfil pode gerenciar itens.
    $user = find_user_by_email($email);

    if (!$user) {
        echo $email . ' NOT_FOUND' . PHP_EOL;
        continue;
    }

    echo $email
        . ' sector=' . $user['sector']
        . ' label=' . role_label($user['role'], $user['sector'])
        . ' can_manage=' . (can_manage_items($user) ? 'yes' : 'no')
        . PHP_EOL;
}
