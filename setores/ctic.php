<?php

require_once __DIR__ . '/../includes/auth.php';

// Home do CTIC: exige usuario logado e pertencente ao setor CTIC.
$user = require_sector('ctic');
$sectorName = SECTORS['ctic'];
?>
<?php require __DIR__ . '/../templates/sector-page.php'; ?>
