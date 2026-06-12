<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$pdo    = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    /* GET ?todas=1 — listagem administrativa, inclui inativas */
    if (isset($_GET['todas'])) {
        require_admin();
        $rows = $pdo->query(
            'SELECT slug, nome, cor, ativo FROM restricoes ORDER BY id'
        )->fetchAll();
        foreach ($rows as &$r) {
            $r['ativo'] = (bool)$r['ativo'];
        }
        json_response($rows);
    }

    $rows = $pdo->query(
        'SELECT slug, nome, cor FROM restricoes WHERE ativo = 1 ORDER BY id'
    )->fetchAll();
    json_response($rows);
}

if ($method === 'POST') {
    require_admin();
    $data = input_json();

    $nome = trim($data['nome'] ?? '');
    $cor  = trim($data['cor'] ?? '');
    if ($nome === '') error_response('Campo "nome" obrigatório');
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $cor)) $cor = '#F39C12';

    $slug = mb_strtolower($nome, 'UTF-8');
    $translit = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
    if ($translit !== false && $translit !== '') $slug = $translit;
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    if ($slug === '') error_response('Nome inválido');

    $stmt = $pdo->prepare('SELECT id FROM restricoes WHERE slug = ?');
    $stmt->execute([$slug]);
    if ($stmt->fetch()) error_response('Já existe uma restrição com esse nome', 409);

    $pdo->prepare('INSERT INTO restricoes (slug, nome, cor) VALUES (?, ?, ?)')
        ->execute([$slug, $nome, $cor]);

    json_response(['slug' => $slug, 'nome' => $nome, 'cor' => $cor, 'ativo' => true], 201);
}

if ($method === 'PUT') {
    require_admin();
    $slug = $routeParams['slug'] ?? '';
    $data = input_json();

    $stmt = $pdo->prepare('SELECT slug, nome, cor, ativo FROM restricoes WHERE slug = ?');
    $stmt->execute([$slug]);
    $atual = $stmt->fetch();
    if (!$atual) error_response('Restrição não encontrada', 404);

    $campos = [];
    $params = [];
    if (array_key_exists('ativo', $data)) {
        $campos[] = 'ativo = ?';
        $params[] = $data['ativo'] ? 1 : 0;
    }
    if (isset($data['nome']) && trim($data['nome']) !== '') {
        $campos[] = 'nome = ?';
        $params[] = trim($data['nome']);
    }
    if (isset($data['cor']) && preg_match('/^#[0-9A-Fa-f]{6}$/', trim($data['cor']))) {
        $campos[] = 'cor = ?';
        $params[] = trim($data['cor']);
    }
    if (!$campos) error_response('Nenhum campo para atualizar');

    $params[] = $slug;
    $pdo->prepare('UPDATE restricoes SET ' . implode(', ', $campos) . ' WHERE slug = ?')
        ->execute($params);

    $stmt = $pdo->prepare('SELECT slug, nome, cor, ativo FROM restricoes WHERE slug = ?');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    $row['ativo'] = (bool)$row['ativo'];
    json_response($row);
}

error_response('Método não permitido', 405);
