<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$pdo    = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];

function montar_pedido(PDO $pdo, array $pedido): array {
    $stmtItens = $pdo->prepare(
        'SELECT id, produto_id, produto_nome, preco_unitario, quantidade
         FROM pedido_itens WHERE pedido_id = ? ORDER BY id'
    );
    $stmtItens->execute([(int)$pedido['id']]);
    $itensRows = $stmtItens->fetchAll();

    $stmtExtras   = $pdo->prepare('SELECT nome FROM pedido_item_extras WHERE pedido_item_id = ?');
    $stmtRemocoes = $pdo->prepare('SELECT nome FROM pedido_item_remocoes WHERE pedido_item_id = ?');
    $stmtRest     = $pdo->prepare('SELECT restricao_slug FROM pedido_item_restricoes WHERE pedido_item_id = ?');

    $itens = [];
    foreach ($itensRows as $item) {
        $itemId = (int)$item['id'];

        $stmtExtras->execute([$itemId]);
        $extras = array_column($stmtExtras->fetchAll(), 'nome');

        $stmtRemocoes->execute([$itemId]);
        $remocoes = array_column($stmtRemocoes->fetchAll(), 'nome');

        $stmtRest->execute([$itemId]);
        $restricoes = array_column($stmtRest->fetchAll(), 'restricao_slug');

        $itens[] = [
            'produto'    => $item['produto_nome'],
            'quantidade' => (int)$item['quantidade'],
            'extras'     => $extras,
            'remocoes'   => $remocoes,
            'restricoes' => $restricoes,
        ];
    }

    return [
        'id'      => (int)$pedido['id'],
        'senha'   => $pedido['senha'],
        'status'  => $pedido['status'],
        'horario' => $pedido['horario'],
        'itens'   => $itens,
    ];
}

if ($method === 'GET') {
    $stmt = $pdo->query(
        "SELECT id, senha, status, DATE_FORMAT(horario, '%Y-%m-%dT%H:%i:%s') as horario
         FROM pedidos WHERE DATE(horario) = CURDATE() ORDER BY horario ASC"
    );
    $pedidosRows = $stmt->fetchAll();

    $pedidos = array_map(fn($p) => montar_pedido($pdo, $p), $pedidosRows);
    json_response($pedidos);
}

if ($method === 'POST') {
    $data = input_json();

    if (empty($data['itens']) || !is_array($data['itens'])) {
        error_response('Campo "itens" obrigatório e deve ser um array');
    }

    // Gera senha sequencial do dia
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM pedidos WHERE DATE(horario) = CURDATE()");
    $count     = (int)$countStmt->fetch()['total'];
    $senha     = '#' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);

    $total = 0.0;
    $itensPreparados = [];

    foreach ($data['itens'] as $item) {
        $prodStmt = $pdo->prepare(
            'SELECT id, nome, preco FROM produtos WHERE id = ? AND ativo = 1'
        );
        $prodStmt->execute([(int)$item['produtoId']]);
        $produto = $prodStmt->fetch();
        if (!$produto) error_response("Produto {$item['produtoId']} não encontrado", 404);

        $extras        = $item['extras'] ?? [];
        $remocoes      = $item['remocoes'] ?? [];
        $restricoes    = $item['restricoes'] ?? [];
        $quantidade    = (int)($item['quantidade'] ?? 1);

        $total += (float)$produto['preco'] * $quantidade;

        $itensPreparados[] = [
            'produto_id'     => (int)$produto['id'],
            'produto_nome'   => $produto['nome'],
            'preco_unitario' => (float)$produto['preco'],
            'quantidade'     => $quantidade,
            'extras'         => $extras,
            'remocoes'       => $remocoes,
            'restricoes'     => $restricoes,
        ];
    }

    $pdo->beginTransaction();
    try {
        $stmtPedido = $pdo->prepare(
            'INSERT INTO pedidos (senha, status, pagamento, total) VALUES (?, ?, ?, ?)'
        );
        $stmtPedido->execute([$senha, 'recebido', $data['pagamento'] ?? null, $total]);
        $pedidoId = (int)$pdo->lastInsertId();

        foreach ($itensPreparados as $item) {
            $stmtItem = $pdo->prepare(
                'INSERT INTO pedido_itens (pedido_id, produto_id, produto_nome, preco_unitario, quantidade)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmtItem->execute([
                $pedidoId,
                $item['produto_id'],
                $item['produto_nome'],
                $item['preco_unitario'],
                $item['quantidade'],
            ]);
            $itemId = (int)$pdo->lastInsertId();

            foreach ($item['extras'] as $nome) {
                $pdo->prepare('INSERT INTO pedido_item_extras (pedido_item_id, nome) VALUES (?, ?)')
                    ->execute([$itemId, $nome]);
            }
            foreach ($item['remocoes'] as $nome) {
                $pdo->prepare('INSERT INTO pedido_item_remocoes (pedido_item_id, nome) VALUES (?, ?)')
                    ->execute([$itemId, $nome]);
            }
            foreach ($item['restricoes'] as $slug) {
                $pdo->prepare('INSERT INTO pedido_item_restricoes (pedido_item_id, restricao_slug) VALUES (?, ?)')
                    ->execute([$itemId, $slug]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_response('Erro ao registrar pedido', 500);
    }

    $pedidoRow = $pdo->prepare(
        "SELECT id, senha, status, DATE_FORMAT(horario, '%Y-%m-%dT%H:%i:%s') as horario
         FROM pedidos WHERE id = ?"
    );
    $pedidoRow->execute([$pedidoId]);
    $pedido = $pedidoRow->fetch();

    json_response(montar_pedido($pdo, $pedido), 201);
}

error_response('Método não permitido', 405);
