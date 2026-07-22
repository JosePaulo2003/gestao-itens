<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/inventory.php';

/*
 * Testa regras de edicao de usuarios:
 * - gestor/admin do setor edita usuarios do proprio setor;
 * - gestor/admin nao edita usuarios de outro setor;
 * - gestor/admin nao edita Admin Maximo;
 * - Admin Maximo edita qualquer usuario.
 */

$cticAdmin = find_user_by_email('ctic@sas.local');
$almoxAdmin = find_user_by_email('almoxarifado@sas.local');
$superAdmin = find_user_by_email('admin.maximo@sas.local');

// Cada booleano representa uma regra esperada do controle de edição.
$canCticEditCtic = $cticAdmin ? find_user_for_edit((int) $cticAdmin['id'], $cticAdmin) !== null : false;
$canAlmoxEditCtic = ($almoxAdmin && $cticAdmin) ? find_user_for_edit((int) $cticAdmin['id'], $almoxAdmin) !== null : false;
$canCticEditSuper = ($cticAdmin && $superAdmin) ? find_user_for_edit((int) $superAdmin['id'], $cticAdmin) !== null : false;
$canSuperEditAlmox = ($superAdmin && $almoxAdmin) ? find_user_for_edit((int) $almoxAdmin['id'], $superAdmin) !== null : false;

echo 'ctic_edits_ctic=' . ($canCticEditCtic ? 'yes' : 'no') . PHP_EOL;
echo 'almox_edits_ctic=' . ($canAlmoxEditCtic ? 'yes' : 'no') . PHP_EOL;
echo 'ctic_edits_super=' . ($canCticEditSuper ? 'yes' : 'no') . PHP_EOL;
echo 'super_edits_almox=' . ($canSuperEditAlmox ? 'yes' : 'no') . PHP_EOL;
