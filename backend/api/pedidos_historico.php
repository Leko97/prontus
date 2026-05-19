<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/_pedidos_helpers.php';

require_auth();

$pdo = get_pdo();

$dataInicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$dataFim    = $_GET['data_fim']    ?? date('Y-m-d');
$status     = $_GET['status']      ?? '';
$senha      = $_GET['senha']       ?? '';
$limite     = min((int)($_GET['limite'] ?? 300), 500);

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataInicio)) $dataInicio = date('Y-m-d', strtotime('-30 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFim))    $dataFim    = date('Y-m-d');
if ($status && !in_array($status, STATUS_VALIDOS, true)) $status = '';

$where  = ["DATE(horario) BETWEEN ? AND ?"];
$params = [$dataInicio, $dataFim];

if ($status) { $where[] = 'status = ?'; $params[] = $status; }
if ($senha)  { $where[] = 'senha LIKE ?'; $params[] = '%' . $senha . '%'; }

$sql = "SELECT id, senha, status, pagamento, total,
               DATE_FORMAT(horario, '%Y-%m-%dT%H:%i:%s') as horario,
               DATE_FORMAT(preparado_em, '%Y-%m-%dT%H:%i:%s') as preparado_em
        FROM pedidos
        WHERE " . implode(' AND ', $where) . "
        ORDER BY horario DESC
        LIMIT ?";
$params[] = $limite;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

json_response(array_map(fn($p) => montar_pedido($pdo, $p), $rows));
