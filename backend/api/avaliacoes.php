<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$pdo    = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];

/* POST /api/avaliacoes — público (cliente avalia sem auth) */
if ($method === 'POST') {
    $data = input_json();

    $pedidoId = (int)($data['pedido_id'] ?? 0);
    $nota     = (int)($data['nota'] ?? 0);

    if ($pedidoId <= 0) error_response('Campo "pedido_id" obrigatório');
    if ($nota < 1 || $nota > 5) error_response('Campo "nota" deve ser entre 1 e 5');

    /* Verificar que o pedido existe */
    $stmtCheck = $pdo->prepare('SELECT id FROM pedidos WHERE id = ?');
    $stmtCheck->execute([$pedidoId]);
    if (!$stmtCheck->fetch()) error_response('Pedido não encontrado', 404);

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO avaliacoes (pedido_id, nota) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE nota = VALUES(nota)'
        );
        $stmt->execute([$pedidoId, $nota]);
    } catch (Throwable $e) {
        error_response('Erro ao registrar avaliação', 500);
    }

    json_response(['ok' => true], 201);
}

error_response('Método não permitido', 405);
