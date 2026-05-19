<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$pdo    = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $produtoId = (int)($_GET['produto_id'] ?? 0);
    if (!$produtoId) error_response('Parâmetro produto_id obrigatório');

    $stmt = $pdo->prepare('SELECT id, nome FROM remocoes WHERE produto_id = ? ORDER BY id ASC');
    $stmt->execute([$produtoId]);
    json_response($stmt->fetchAll());
}

if ($method === 'POST') {
    require_admin();
    $data = input_json();

    if (empty($data['produto_id']) || !is_numeric($data['produto_id'])) {
        error_response('produto_id obrigatório');
    }
    if (empty($data['nome']) || strlen(trim($data['nome'])) < 1) {
        error_response('nome obrigatório');
    }

    $produtoId = (int)$data['produto_id'];
    $nome      = trim($data['nome']);

    $check = $pdo->prepare('SELECT id FROM produtos WHERE id = ? AND ativo = 1');
    $check->execute([$produtoId]);
    if (!$check->fetch()) error_response('Produto não encontrado', 404);

    $stmt = $pdo->prepare('INSERT INTO remocoes (produto_id, nome) VALUES (?, ?)');
    $stmt->execute([$produtoId, $nome]);

    json_response(['id' => (int)$pdo->lastInsertId(), 'produto_id' => $produtoId, 'nome' => $nome], 201);
}

if ($method === 'DELETE') {
    require_admin();
    $id = (int)($routeParams['id'] ?? 0);
    if (!$id) error_response('ID inválido');

    $stmt = $pdo->prepare('DELETE FROM remocoes WHERE id = ?');
    $stmt->execute([$id]);

    if (!$stmt->rowCount()) error_response('Remoção não encontrada', 404);
    json_response(['ok' => true]);
}

error_response('Método não permitido', 405);
