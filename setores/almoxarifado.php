<?php

require_once __DIR__ . '/../includes/auth.php';

// Home do Almoxarifado: exige usuario logado e pertencente ao setor.
$user = require_sector('almoxarifado');
$sectorName = SECTORS['almoxarifado'];
?>
<?php require __DIR__ . '/../templates/sector-page.php'; ?>
