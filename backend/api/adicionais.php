<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$pdo    = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $rows = $pdo->query(
        'SELECT a.id, a.nome, a.preco, a.produto_id as produtoId, p.nome as produtoNome
         FROM adicionais a
         JOIN produtos p ON a.produto_id = p.id
         WHERE p.ativo = 1
         ORDER BY a.produto_id, a.id'
    )->fetchAll();

    $adicionais = array_map(fn($r) => [
        'id'          => (int)$r['id'],
        'nome'        => $r['nome'],
        'preco'       => (float)$r['preco'],
        'produtoId'   => (int)$r['produtoId'],
        'produtoNome' => $r['produtoNome'],
    ], $rows);

    json_response($adicionais);
}

if ($method === 'POST') {
    require_admin();
    $data = input_json();

    if (empty($data['nome'])) error_response('Campo "nome" obrigatório');
    if (empty($data['produtoId'])) error_response('Campo "produtoId" obrigatório');

    $stmt = $pdo->prepare('INSERT INTO adicionais (produto_id, nome, preco) VALUES (?, ?, ?)');
    $stmt->execute([(int)$data['produtoId'], $data['nome'], (float)($data['preco'] ?? 0)]);
    $adicionalId = (int)$pdo->lastInsertId();

    $row = $pdo->prepare(
        'SELECT a.id, a.nome, a.preco, a.produto_id as produtoId, p.nome as produtoNome
         FROM adicionais a JOIN produtos p ON a.produto_id = p.id WHERE a.id = ?'
    );
    $row->execute([$adicionalId]);
    $r = $row->fetch();
    json_response([
        'id'          => (int)$r['id'],
        'nome'        => $r['nome'],
        'preco'       => (float)$r['preco'],
        'produtoId'   => (int)$r['produtoId'],
        'produtoNome' => $r['produtoNome'],
    ], 201);
}

if ($method === 'PUT') {
    require_admin();
    $id   = (int)$routeParams['id'];
    $data = input_json();

    $stmt = $pdo->prepare('UPDATE adicionais SET nome = ?, preco = ? WHERE id = ?');
    $stmt->execute([$data['nome'], (float)($data['preco'] ?? 0), $id]);

    $row = $pdo->prepare(
        'SELECT a.id, a.nome, a.preco, a.produto_id as produtoId, p.nome as produtoNome
         FROM adicionais a JOIN produtos p ON a.produto_id = p.id WHERE a.id = ?'
    );
    $row->execute([$id]);
    $r = $row->fetch();
    if (!$r) error_response('Adicional não encontrado', 404);
    json_response([
        'id'          => (int)$r['id'],
        'nome'        => $r['nome'],
        'preco'       => (float)$r['preco'],
        'produtoId'   => (int)$r['produtoId'],
        'produtoNome' => $r['produtoNome'],
    ]);
}

if ($method === 'DELETE') {
    require_admin();
    $id = (int)$routeParams['id'];
    $pdo->prepare('DELETE FROM adicionais WHERE id = ?')->execute([$id]);
    json_response(['ok' => true]);
}

error_response('Método não permitido', 405);
