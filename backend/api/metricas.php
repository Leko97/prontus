<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') error_response('Método não permitido', 405);

require_admin();

$pdo = get_pdo();

$totalPedidos = (int)$pdo->query(
    "SELECT COUNT(*) FROM pedidos WHERE DATE(horario) = CURDATE()"
)->fetchColumn();

$volumeVendas = (float)$pdo->query(
    "SELECT COALESCE(SUM(total), 0) FROM pedidos WHERE DATE(horario) = CURDATE()"
)->fetchColumn();

$tempoMedio = $pdo->query(
    "SELECT AVG(TIMESTAMPDIFF(MINUTE, horario, preparado_em)) as media
     FROM pedidos
     WHERE preparado_em IS NOT NULL AND DATE(horario) = CURDATE()"
)->fetchColumn();
$tempoMedio = $tempoMedio !== null ? round((float)$tempoMedio, 1) : null;

$horasRows = $pdo->query(
    "SELECT HOUR(horario) as hora, COUNT(*) as total
     FROM pedidos WHERE DATE(horario) = CURDATE()
     GROUP BY HOUR(horario) ORDER BY hora"
)->fetchAll();

$pedidosPorHora = array_fill(0, 24, 0);
foreach ($horasRows as $row) {
    $pedidosPorHora[(int)$row['hora']] = (int)$row['total'];
}

json_response([
    'total_pedidos'        => $totalPedidos,
    'volume_vendas'        => $volumeVendas,
    'tempo_medio_minutos'  => $tempoMedio,
    'pedidos_por_hora'     => $pedidosPorHora,
]);
