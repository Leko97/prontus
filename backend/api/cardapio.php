<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') error_response('Método não permitido', 405);

$pdo = get_pdo();

$cats = $pdo->query(
    'SELECT id, nome, icone FROM categorias WHERE ativo = 1 ORDER BY id'
)->fetchAll();

$rows = $pdo->query(
    'SELECT id, categoria_id, nome, descricao, preco, imagem
     FROM produtos WHERE ativo = 1 ORDER BY categoria_id, id'
)->fetchAll();

$stmtRest  = $pdo->prepare('SELECT restricao_slug FROM produto_restricoes WHERE produto_id = ?');
$stmtExtra = $pdo->prepare('SELECT id, nome, preco FROM adicionais WHERE produto_id = ? AND ativo = 1 ORDER BY id');
$stmtRem   = $pdo->prepare('SELECT nome FROM remocoes WHERE produto_id = ? ORDER BY id');

$prods = [];
foreach ($rows as $row) {
    $id = (int)$row['id'];

    $stmtRest->execute([$id]);
    $restricoes = array_column($stmtRest->fetchAll(), 'restricao_slug');

    $stmtExtra->execute([$id]);
    $extras = array_map(fn($e) => [
        'id'    => (int)$e['id'],
        'nome'  => $e['nome'],
        'preco' => (float)$e['preco'],
    ], $stmtExtra->fetchAll());

    $stmtRem->execute([$id]);
    $remocoes = array_column($stmtRem->fetchAll(), 'nome');

    $prods[] = [
        'id'          => $id,
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

json_response(['categorias' => $cats, 'produtos' => $prods]);
