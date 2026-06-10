<?php
function montar_pedido(PDO $pdo, array $pedido): array {
    $stmtItens = $pdo->prepare(
        'SELECT id, produto_id, produto_nome, preco_unitario, quantidade
         FROM pedido_itens WHERE pedido_id = ? ORDER BY id'
    );
    $stmtItens->execute([(int)$pedido['id']]);
    $itensRows = $stmtItens->fetchAll();

    $stmtExtras   = $pdo->prepare('SELECT nome, preco_unitario, quantidade FROM pedido_item_extras WHERE pedido_item_id = ?');
    $stmtRemocoes = $pdo->prepare('SELECT nome FROM pedido_item_remocoes WHERE pedido_item_id = ?');
    $stmtRest     = $pdo->prepare('SELECT restricao_slug FROM pedido_item_restricoes WHERE pedido_item_id = ?');

    $itens = [];
    foreach ($itensRows as $item) {
        $itemId = (int)$item['id'];
        $stmtExtras->execute([$itemId]);
        $extras = array_map(fn($e) => [
            'nome'       => $e['nome'],
            'preco'      => (float)$e['preco_unitario'],
            'quantidade' => (int)$e['quantidade'],
        ], $stmtExtras->fetchAll());
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
        'id'           => (int)$pedido['id'],
        'senha'        => $pedido['senha'],
        'status'       => $pedido['status'],
        'pagamento'    => $pedido['pagamento'] ?? null,
        'total'        => isset($pedido['total']) ? (float)$pedido['total'] : null,
        'horario'      => $pedido['horario'],
        'preparado_em' => $pedido['preparado_em'] ?? null,
        'itens'        => $itens,
    ];
}
