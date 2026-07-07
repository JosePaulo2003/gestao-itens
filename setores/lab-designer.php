<?php

require_once __DIR__ . '/../includes/auth.php';

// Home do Lab de Designer: exige usuario logado e pertencente ao setor.
$user = require_sector('lab-designer');
$sectorName = SECTORS['lab-designer'];
?>
<?php require __DIR__ . '/../templates/sector-page.php'; ?>
