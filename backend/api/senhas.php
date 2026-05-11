<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') error_response('Método não permitido', 405);

$pdo = get_pdo();

$stmtPrep = $pdo->query(
    "SELECT senha FROM pedidos
     WHERE status IN ('recebido', 'em-preparo') AND DATE(horario) = CURDATE()
     ORDER BY horario ASC"
);
$emPreparo = array_column($stmtPrep->fetchAll(), 'senha');

$stmtPront = $pdo->query(
    "SELECT senha FROM pedidos
     WHERE status = 'pronto' AND DATE(horario) = CURDATE()
     ORDER BY horario ASC"
);
$prontas = array_column($stmtPront->fetchAll(), 'senha');

json_response(['em_preparo' => $emPreparo, 'prontas' => $prontas]);
