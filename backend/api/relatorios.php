<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$pdo    = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') error_response('Método não permitido', 405);

require_admin();

$dataInicio = $_GET['data_inicio'] ?? '';
$dataFim    = $_GET['data_fim']    ?? '';
$formato    = $_GET['formato']     ?? 'json';

if (!$dataInicio || !$dataFim) {
    error_response('Parâmetros data_inicio e data_fim obrigatórios');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataInicio) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFim)) {
    error_response('Formato de data inválido. Use YYYY-MM-DD');
}

/* Adiciona 23:59:59 ao data_fim para incluir o dia inteiro */
$dataFimFull = $dataFim . ' 23:59:59';

/* -------- Resumo -------- */
$resumoStmt = $pdo->prepare(
    "SELECT
       COUNT(*)                                           AS total_pedidos,
       COALESCE(SUM(total), 0)                           AS total_faturado,
       COALESCE(AVG(total), 0)                           AS ticket_medio,
       COALESCE(AVG(TIMESTAMPDIFF(MINUTE, horario, preparado_em)), 0) AS tempo_medio_minutos
     FROM pedidos
     WHERE horario BETWEEN ? AND ?
       AND status = 'finalizado'"
);
$resumoStmt->execute([$dataInicio, $dataFimFull]);
$resumo = $resumoStmt->fetch();

/* -------- Por dia -------- */
$porDiaStmt = $pdo->prepare(
    "SELECT
       DATE(horario)      AS data,
       COUNT(*)           AS pedidos,
       SUM(total)         AS faturado
     FROM pedidos
     WHERE horario BETWEEN ? AND ?
     GROUP BY DATE(horario)
     ORDER BY DATE(horario) ASC"
);
$porDiaStmt->execute([$dataInicio, $dataFimFull]);
$porDia = $porDiaStmt->fetchAll();

/* -------- Por forma de pagamento -------- */
$totalGeralStmt = $pdo->prepare(
    "SELECT COUNT(*) AS total FROM pedidos WHERE horario BETWEEN ? AND ?"
);
$totalGeralStmt->execute([$dataInicio, $dataFimFull]);
$totalGeral = (int)$totalGeralStmt->fetch()['total'];

$pagStmt = $pdo->prepare(
    "SELECT
       pagamento,
       COUNT(*) AS total
     FROM pedidos
     WHERE horario BETWEEN ? AND ?
     GROUP BY pagamento
     ORDER BY total DESC"
);
$pagStmt->execute([$dataInicio, $dataFimFull]);
$porPagamento = array_map(function ($row) use ($totalGeral) {
    return [
        'pagamento'  => $row['pagamento'],
        'total'      => (int)$row['total'],
        'percentual' => $totalGeral > 0 ? round($row['total'] / $totalGeral * 100, 1) : 0,
    ];
}, $pagStmt->fetchAll());

/* -------- Top 10 produtos mais vendidos -------- */
$prodStmt = $pdo->prepare(
    "SELECT
       pi.produto_nome,
       SUM(pi.quantidade)                              AS quantidade,
       SUM(pi.quantidade * pi.preco_unitario)          AS total_faturado
     FROM pedido_itens pi
     JOIN pedidos p ON p.id = pi.pedido_id
     WHERE p.horario BETWEEN ? AND ?
     GROUP BY pi.produto_nome
     ORDER BY quantidade DESC
     LIMIT 10"
);
$prodStmt->execute([$dataInicio, $dataFimFull]);
$maisVendidos = $prodStmt->fetchAll();

/* -------- Pedidos por hora -------- */
$horaStmt = $pdo->prepare(
    "SELECT
       HOUR(horario) AS hora,
       COUNT(*)      AS pedidos
     FROM pedidos
     WHERE horario BETWEEN ? AND ?
     GROUP BY HOUR(horario)
     ORDER BY HOUR(horario) ASC"
);
$horaStmt->execute([$dataInicio, $dataFimFull]);
$porHoraRows = $horaStmt->fetchAll();
$porHora = array_fill(0, 24, 0);
foreach ($porHoraRows as $row) {
    $porHora[(int)$row['hora']] = (int)$row['pedidos'];
}

/* -------- CSV -------- */
if ($formato === 'csv') {
    $pedidosCsvStmt = $pdo->prepare(
        "SELECT
           p.senha,
           DATE_FORMAT(p.horario, '%Y-%m-%d %H:%i:%s') AS horario,
           p.status,
           p.pagamento,
           p.total,
           GROUP_CONCAT(CONCAT(pi.quantidade, 'x ', pi.produto_nome) SEPARATOR '; ') AS itens
         FROM pedidos p
         LEFT JOIN pedido_itens pi ON pi.pedido_id = p.id
         WHERE p.horario BETWEEN ? AND ?
         GROUP BY p.id
         ORDER BY p.horario ASC"
    );
    $pedidosCsvStmt->execute([$dataInicio, $dataFimFull]);
    $pedidosCsv = $pedidosCsvStmt->fetchAll();

    $filename = 'relatorio_' . $dataInicio . '_' . $dataFim . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 para Excel
    fputcsv($out, ['Senha', 'Horário', 'Status', 'Pagamento', 'Total (R$)', 'Itens'], ';');
    foreach ($pedidosCsv as $row) {
        fputcsv($out, [
            $row['senha'],
            $row['horario'],
            $row['status'],
            $row['pagamento'],
            number_format((float)$row['total'], 2, ',', '.'),
            $row['itens'],
        ], ';');
    }
    fclose($out);
    exit;
}

json_response([
    'resumo'               => [
        'total_pedidos'       => (int)$resumo['total_pedidos'],
        'total_faturado'      => (float)$resumo['total_faturado'],
        'ticket_medio'        => round((float)$resumo['ticket_medio'], 2),
        'tempo_medio_minutos' => round((float)$resumo['tempo_medio_minutos'], 1),
    ],
    'por_dia'              => $porDia,
    'por_forma_pagamento'  => $porPagamento,
    'produtos_mais_vendidos' => $maisVendidos,
    'pedidos_por_hora'     => $porHora,
]);
