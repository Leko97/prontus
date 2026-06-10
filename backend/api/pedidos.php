<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/_pedidos_helpers.php';

$pdo    = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    /* GET ?senha=XXX — consulta pública de status por senha (sem auth) */
    if (isset($_GET['senha'])) {
        $senhaRaw = trim($_GET['senha']);
        /* Aceita "042" ou "#042" */
        $senha = '#' . ltrim($senhaRaw, '#');

        $stmt = $pdo->prepare(
            "SELECT id, senha, status, DATE_FORMAT(horario, '%Y-%m-%dT%H:%i:%s') as horario
             FROM pedidos
             WHERE senha = ? AND DATE(horario) = CURDATE()
             ORDER BY horario DESC
             LIMIT 1"
        );
        $stmt->execute([$senha]);
        $row = $stmt->fetch();

        if (!$row) {
            http_response_code(404);
            echo json_encode(['erro' => 'Pedido não encontrado']);
            exit;
        }

        json_response([
            'id'      => (int)$row['id'],
            'senha'   => $row['senha'],
            'status'  => $row['status'],
            'horario' => $row['horario'],
        ]);
    }

    $stmt = $pdo->query(
        "SELECT id, senha, status, DATE_FORMAT(horario, '%Y-%m-%dT%H:%i:%s') as horario
         FROM pedidos WHERE DATE(horario) = CURDATE() ORDER BY horario ASC"
    );
    $pedidosRows = $stmt->fetchAll();

    $pedidos = array_map(fn($p) => montar_pedido($pdo, $p), $pedidosRows);
    json_response($pedidos);
}

if ($method === 'POST') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    rate_limit_check($ip, 'pedidos');
    rate_limit_add_attempt($ip, 'pedidos', 60, 20, 60);

    $data = input_json();

    if (empty($data['itens']) || !is_array($data['itens'])) {
        error_response('Campo "itens" obrigatório e deve ser um array');
    }

    $total = 0.0;
    $itensPreparados = [];

    foreach ($data['itens'] as $item) {
        $prodStmt = $pdo->prepare(
            'SELECT id, nome, preco FROM produtos WHERE id = ? AND ativo = 1'
        );
        $prodStmt->execute([(int)$item['produtoId']]);
        $produto = $prodStmt->fetch();
        if (!$produto) error_response("Produto {$item['produtoId']} não encontrado", 404);

        $quantidade = max(1, (int)($item['quantidade'] ?? 1));
        $remocoes   = $item['remocoes'] ?? [];
        $restricoes = $item['restricoes'] ?? [];

        // Extras: preço SEMPRE resolvido no servidor a partir de `adicionais`
        $extrasPreparados = [];
        $somaExtras = 0.0;
        foreach (($item['extras'] ?? []) as $ex) {
            $exId  = (int)($ex['id'] ?? 0);
            $exQtd = max(1, (int)($ex['quantidade'] ?? 1));
            if ($exId <= 0) continue;

            $adStmt = $pdo->prepare(
                'SELECT nome, preco FROM adicionais WHERE id = ? AND produto_id = ? AND ativo = 1'
            );
            $adStmt->execute([$exId, (int)$produto['id']]);
            $ad = $adStmt->fetch();
            if (!$ad) continue; // extra inexistente/desativado é ignorado

            $somaExtras += (float)$ad['preco'] * $exQtd;
            $extrasPreparados[] = [
                'nome'           => $ad['nome'],
                'preco_unitario' => (float)$ad['preco'],
                'quantidade'     => $exQtd,
            ];
        }

        $total += ((float)$produto['preco'] + $somaExtras) * $quantidade;

        $itensPreparados[] = [
            'produto_id'     => (int)$produto['id'],
            'produto_nome'   => $produto['nome'],
            'preco_unitario' => (float)$produto['preco'],
            'quantidade'     => $quantidade,
            'extras'         => $extrasPreparados,
            'remocoes'       => $remocoes,
            'restricoes'     => $restricoes,
        ];
    }

    $pdo->beginTransaction();
    try {
        // Gera senha única do dia com retry em caso de colisão na UNIQUE (pedido_dia, senha)
        $pedidoId   = null;
        $senha      = null;
        $maxRetries = 5;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $countStmt = $pdo->query(
                "SELECT COUNT(*) AS total FROM pedidos WHERE DATE(horario) = CURDATE()"
            );
            $count = (int)$countStmt->fetch()['total'];
            $senha = '#' . str_pad($count + 1 + $attempt, 3, '0', STR_PAD_LEFT);

            try {
                $stmtPedido = $pdo->prepare(
                    'INSERT INTO pedidos (senha, status, pagamento, total) VALUES (?, ?, ?, ?)'
                );
                $stmtPedido->execute([$senha, 'recebido', $data['pagamento'] ?? null, $total]);
                $pedidoId = (int)$pdo->lastInsertId();
                break;
            } catch (PDOException $e) {
                // 23000 = violação de UNIQUE: outra requisição usou esta senha; tenta a próxima
                if ($e->getCode() !== '23000') throw $e;
            }
        }

        if ($pedidoId === null) {
            throw new RuntimeException('Não foi possível gerar senha única após várias tentativas');
        }

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

            foreach ($item['extras'] as $ex) {
                $pdo->prepare(
                    'INSERT INTO pedido_item_extras (pedido_item_id, nome, preco_unitario, quantidade)
                     VALUES (?, ?, ?, ?)'
                )->execute([$itemId, $ex['nome'], $ex['preco_unitario'], $ex['quantidade']]);
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
