<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

function buscar_produto_completo(PDO $pdo, int $id): array {
    $stmt = $pdo->prepare(
        'SELECT id, categoria_id, nome, descricao, preco, imagem
         FROM produtos WHERE id = ? AND ativo = 1'
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
        'restricoes'  => $restricoes,
        'extras'      => $extras,
        'remocoes'    => $remocoes,
    ];
}

$pdo    = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $rows = $pdo->query(
        'SELECT id FROM produtos WHERE ativo = 1 ORDER BY categoria_id, id'
    )->fetchAll();

    $prods = array_map(fn($r) => buscar_produto_completo($pdo, (int)$r['id']), $rows);
    json_response($prods);
}

if ($method === 'POST') {
    require_admin();
    $data = input_json();

    if (empty($data['nome']) || strlen($data['nome']) > 200)
        error_response('Campo "nome" inválido');
    if (!isset($data['preco']) || (float)$data['preco'] <= 0)
        error_response('Campo "preco" inválido');
    if (empty($data['categoriaId']))
        error_response('Campo "categoriaId" obrigatório');

    $catStmt = $pdo->prepare('SELECT id FROM categorias WHERE id = ? AND ativo = 1');
    $catStmt->execute([$data['categoriaId']]);
    if (!$catStmt->fetch()) error_response('Categoria não encontrada', 404);

    $stmt = $pdo->prepare(
        'INSERT INTO produtos (categoria_id, nome, descricao, preco, imagem)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        (int)$data['categoriaId'],
        $data['nome'],
        $data['descricao'] ?? '',
        (float)$data['preco'],
        $data['imagem'] ?? null,
    ]);
    $produtoId = (int)$pdo->lastInsertId();

    foreach ($data['restricoes'] ?? [] as $slug) {
        $pdo->prepare('INSERT INTO produto_restricoes VALUES (?, ?)')->execute([$produtoId, $slug]);
    }
    foreach ($data['extras'] ?? [] as $extra) {
        $pdo->prepare('INSERT INTO adicionais (produto_id, nome, preco) VALUES (?, ?, ?)')
            ->execute([$produtoId, $extra['nome'], (float)$extra['preco']]);
    }
    foreach ($data['remocoes'] ?? [] as $nome) {
        $pdo->prepare('INSERT INTO remocoes (produto_id, nome) VALUES (?, ?)')->execute([$produtoId, $nome]);
    }

    json_response(buscar_produto_completo($pdo, $produtoId), 201);
}

error_response('Método não permitido', 405);
