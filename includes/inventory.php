<?php

declare(strict_types=1);

/*
 * Arquivo agregador do modulo de recursos.
 *
 * As paginas do sistema podem continuar incluindo somente este arquivo.
 * Ele carrega as partes separadas da regra de negocio:
 * - auth.php: sessao, login, setores e perfis.
 * - uploads.php: validacao e salvamento de imagens.
 * - items.php: cadastro/listagem/estoque dos itens.
 * - users.php: cadastro de novos usuarios.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/uploads.php';
require_once __DIR__ . '/items.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/reports.php';
require_once __DIR__ . '/requests.php';
