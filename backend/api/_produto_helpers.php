<?php
function buscar_produto_completo(PDO $pdo, int $id): array {
    $stmt = $pdo->prepare(
        'SELECT p.id, p.categoria_id, p.nome, p.descricao, p.preco, p.imagem, c.icone AS categoria_icone
         FROM produtos p
         JOIN categorias c ON c.id = p.categoria_id
         WHERE p.id = ? AND p.ativo = 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) return [];

    $stmtRest = $pdo->prepare('SELECT restricao_slug FROM produto_restricoes WHERE produto_id = ?');
    $stmtRest->execute([$id]);
    $restricoes = array_column($stmtRest->fetchAll(), 'restricao_slug');

    $stmtExtra = $pdo->prepare('SELECT id, nome, preco FROM adicionais WHERE produto_id = ? AND ativo = 1 ORDER BY id');
    $stmtExtra->execute([$id]);
    $extras = array_map(fn($e) => [
        'id'    => (int)$e['id'],
        'nome'  => $e['nome'],
        'preco' => (float)$e['preco'],
    ], $stmtExtra->fetchAll());

    $stmtRem = $pdo->prepare('SELECT nome FROM remocoes WHERE produto_id = ? ORDER BY id');
    $stmtRem->execute([$id]);
    $remocoes = array_column($stmtRem->fetchAll(), 'nome');

    return [
        'id'          => (int)$row['id'],
        'categoriaId' => (int)$row['categoria_id'],
        'nome'        => $row['nome'],
        'descricao'   => $row['descricao'],
        'preco'       => (float)$row['preco'],
        'imagem'      => $row['imagem'],
        'categoriaIcone' => $row['categoria_icone'] ?? '',
        'restricoes'  => $restricoes,
        'extras'      => $extras,
        'remocoes'    => $remocoes,
    ];
}
