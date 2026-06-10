<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$pdo    = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];

/* Extrair :id da URL, ex: /api/combos/5 */
$uriParts = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
$idParam  = null;
foreach ($uriParts as $i => $part) {
    if ($part === 'combos' && isset($uriParts[$i + 1]) && is_numeric($uriParts[$i + 1])) {
        $idParam = (int)$uriParts[$i + 1];
        break;
    }
}

/* GET /api/combos — público */
if ($method === 'GET') {
    $stmt = $pdo->query(
        "SELECT c.id, c.nome, c.descricao, c.preco, c.imagem, c.ativo
         FROM combos c
         WHERE c.ativo = 1
         ORDER BY c.id ASC"
    );
    $combos = $stmt->fetchAll();

    foreach ($combos as &$combo) {
        $combo['id']    = (int)$combo['id'];
        $combo['preco'] = (float)$combo['preco'];
        $combo['ativo'] = (bool)$combo['ativo'];

        $itensStmt = $pdo->prepare(
            "SELECT ci.id, ci.produto_id, ci.quantidade, p.nome AS produto_nome, p.preco AS produto_preco
             FROM combo_itens ci
             JOIN produtos p ON p.id = ci.produto_id
             WHERE ci.combo_id = ?"
        );
        $itensStmt->execute([$combo['id']]);
        $combo['itens'] = array_map(fn($i) => [
            'id'            => (int)$i['id'],
            'produto_id'    => (int)$i['produto_id'],
            'produto_nome'  => $i['produto_nome'],
            'produto_preco' => (float)$i['produto_preco'],
            'quantidade'    => (int)$i['quantidade'],
        ], $itensStmt->fetchAll());
    }
    unset($combo);

    json_response($combos);
}

/* POST /api/combos — requer admin */
if ($method === 'POST') {
    require_admin();
    $data = input_json();

    if (empty($data['nome'])) error_response('Campo "nome" obrigatório');
    if (!isset($data['preco']) || !is_numeric($data['preco'])) error_response('Campo "preco" obrigatório');

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO combos (nome, descricao, preco, imagem) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            trim($data['nome']),
            $data['descricao'] ?? null,
            (float)$data['preco'],
            $data['imagem'] ?? null,
        ]);
        $comboId = (int)$pdo->lastInsertId();

        if (!empty($data['itens']) && is_array($data['itens'])) {
            $stmtItem = $pdo->prepare(
                'INSERT INTO combo_itens (combo_id, produto_id, quantidade) VALUES (?, ?, ?)'
            );
            foreach ($data['itens'] as $item) {
                $stmtItem->execute([$comboId, (int)$item['produto_id'], (int)($item['quantidade'] ?? 1)]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_response('Erro ao criar combo', 500);
    }

    $row = $pdo->prepare('SELECT id, nome, descricao, preco, imagem, ativo FROM combos WHERE id = ?');
    $row->execute([$comboId]);
    json_response($row->fetch(), 201);
}

/* PUT /api/combos/:id — requer admin */
if ($method === 'PUT' && $idParam) {
    require_admin();
    $data = input_json();

    $campos  = [];
    $valores = [];

    if (isset($data['nome']))      { $campos[] = 'nome = ?';      $valores[] = trim($data['nome']); }
    if (isset($data['descricao'])) { $campos[] = 'descricao = ?'; $valores[] = $data['descricao']; }
    if (isset($data['preco']))     { $campos[] = 'preco = ?';     $valores[] = (float)$data['preco']; }
    if (isset($data['imagem']))    { $campos[] = 'imagem = ?';    $valores[] = $data['imagem']; }
    if (isset($data['ativo']))     { $campos[] = 'ativo = ?';     $valores[] = (int)(bool)$data['ativo']; }

    if (empty($campos)) error_response('Nenhum campo para atualizar');

    $valores[] = $idParam;
    $pdo->prepare('UPDATE combos SET ' . implode(', ', $campos) . ' WHERE id = ?')->execute($valores);

    /* Atualizar itens se fornecidos */
    if (isset($data['itens']) && is_array($data['itens'])) {
        $pdo->prepare('DELETE FROM combo_itens WHERE combo_id = ?')->execute([$idParam]);
        $stmtItem = $pdo->prepare(
            'INSERT INTO combo_itens (combo_id, produto_id, quantidade) VALUES (?, ?, ?)'
        );
        foreach ($data['itens'] as $item) {
            $stmtItem->execute([$idParam, (int)$item['produto_id'], (int)($item['quantidade'] ?? 1)]);
        }
    }

    $row = $pdo->prepare('SELECT id, nome, descricao, preco, imagem, ativo FROM combos WHERE id = ?');
    $row->execute([$idParam]);
    json_response($row->fetch());
}

/* DELETE /api/combos/:id — soft-delete — requer admin */
if ($method === 'DELETE' && $idParam) {
    require_admin();
    $pdo->prepare('UPDATE combos SET ativo = 0 WHERE id = ?')->execute([$idParam]);
    json_response(['ok' => true]);
}

error_response('Método não permitido', 405);
