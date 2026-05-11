<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/produtos.php';

$pdo    = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)$routeParams['id'];

if ($method === 'GET') {
    $produto = buscar_produto_completo($pdo, $id);
    if (empty($produto)) error_response('Produto não encontrado', 404);
    json_response($produto);
}

if ($method === 'PUT') {
    require_admin();
    $data = input_json();

    $check = $pdo->prepare('SELECT id FROM produtos WHERE id = ? AND ativo = 1');
    $check->execute([$id]);
    if (!$check->fetch()) error_response('Produto não encontrado', 404);

    $stmt = $pdo->prepare(
        'UPDATE produtos SET categoria_id = ?, nome = ?, descricao = ?, preco = ?, imagem = ?
         WHERE id = ?'
    );
    $stmt->execute([
        (int)($data['categoriaId'] ?? $data['categoria_id']),
        $data['nome'],
        $data['descricao'] ?? '',
        (float)$data['preco'],
        $data['imagem'] ?? null,
        $id,
    ]);

    $pdo->prepare('DELETE FROM produto_restricoes WHERE produto_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM adicionais WHERE produto_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM remocoes WHERE produto_id = ?')->execute([$id]);

    foreach ($data['restricoes'] ?? [] as $slug) {
        $pdo->prepare('INSERT INTO produto_restricoes VALUES (?, ?)')->execute([$id, $slug]);
    }
    foreach ($data['extras'] ?? [] as $extra) {
        $pdo->prepare('INSERT INTO adicionais (produto_id, nome, preco) VALUES (?, ?, ?)')
            ->execute([$id, $extra['nome'], (float)$extra['preco']]);
    }
    foreach ($data['remocoes'] ?? [] as $nome) {
        $pdo->prepare('INSERT INTO remocoes (produto_id, nome) VALUES (?, ?)')->execute([$id, $nome]);
    }

    json_response(buscar_produto_completo($pdo, $id));
}

if ($method === 'DELETE') {
    require_admin();

    $stmt = $pdo->prepare('UPDATE produtos SET ativo = 0 WHERE id = ?');
    $stmt->execute([$id]);
    json_response(['ok' => true]);
}

error_response('Método não permitido', 405);
