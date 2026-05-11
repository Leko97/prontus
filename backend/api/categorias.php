<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$pdo    = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $cats = $pdo->query(
        'SELECT id, nome, icone FROM categorias WHERE ativo = 1 ORDER BY id'
    )->fetchAll();
    json_response($cats);
}

if ($method === 'POST') {
    require_admin();
    $data = input_json();

    if (empty($data['nome'])) error_response('Campo "nome" obrigatório');

    $stmt = $pdo->prepare('INSERT INTO categorias (nome, icone) VALUES (?, ?)');
    $stmt->execute([$data['nome'], $data['icone'] ?? '']);
    $catId = (int)$pdo->lastInsertId();

    $cat = $pdo->prepare('SELECT id, nome, icone FROM categorias WHERE id = ?');
    $cat->execute([$catId]);
    json_response($cat->fetch(), 201);
}

if ($method === 'PUT') {
    require_admin();
    $id   = (int)$routeParams['id'];
    $data = input_json();

    if (empty($data['nome'])) error_response('Campo "nome" obrigatório');

    $stmt = $pdo->prepare('UPDATE categorias SET nome = ?, icone = ? WHERE id = ? AND ativo = 1');
    $stmt->execute([$data['nome'], $data['icone'] ?? '', $id]);

    $cat = $pdo->prepare('SELECT id, nome, icone FROM categorias WHERE id = ?');
    $cat->execute([$id]);
    $updated = $cat->fetch();
    if (!$updated) error_response('Categoria não encontrada', 404);
    json_response($updated);
}

if ($method === 'DELETE') {
    require_admin();
    $id = (int)$routeParams['id'];

    $check = $pdo->prepare(
        'SELECT COUNT(*) as total FROM produtos WHERE categoria_id = ? AND ativo = 1'
    );
    $check->execute([$id]);
    if ((int)$check->fetch()['total'] > 0) {
        error_response('Existem produtos ativos nesta categoria', 409);
    }

    $pdo->prepare('UPDATE categorias SET ativo = 0 WHERE id = ?')->execute([$id]);
    json_response(['ok' => true]);
}

error_response('Método não permitido', 405);
