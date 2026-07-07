<?php

require_once __DIR__ . '/../includes/auth.php';

// Home do Lab Maker: exige usuario logado e pertencente ao setor.
$user = require_sector('lab-maker');
$sectorName = SECTORS['lab-maker'];
?>
<?php require __DIR__ . '/../templates/sector-page.php'; ?>
