<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') error_response('Método não permitido', 405);

$pdo = get_pdo();
$rows = $pdo->query(
    'SELECT slug, nome, cor FROM restricoes WHERE ativo = 1 ORDER BY id'
)->fetchAll();

json_response($rows);
