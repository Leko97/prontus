<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$pdo    = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query('SELECT chave, valor FROM configuracoes');
    $config = [];
    foreach ($stmt->fetchAll() as $row) {
        $config[$row['chave']] = $row['valor'];
    }
    json_response($config);
}

if ($method === 'PUT') {
    require_admin();
    $data = input_json();

    if (isset($data['cor_primaria']) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $data['cor_primaria'])) {
        error_response('cor_primaria inválida: use formato #RRGGBB');
    }
    if (isset($data['cor_secundaria']) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $data['cor_secundaria'])) {
        error_response('cor_secundaria inválida: use formato #RRGGBB');
    }
    if (isset($data['tempo_alerta_kds']) && ((int)$data['tempo_alerta_kds'] < 1)) {
        error_response('tempo_alerta_kds deve ser inteiro positivo');
    }
    if (isset($data['totem_idle_segundos']) && ((int)$data['totem_idle_segundos'] < 5)) {
        error_response('totem_idle_segundos deve ser >= 5');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO configuracoes (chave, valor) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
    );
    foreach ($data as $chave => $valor) {
        $stmt->execute([$chave, (string)$valor]);
    }

    $all = $pdo->query('SELECT chave, valor FROM configuracoes')->fetchAll();
    $config = [];
    foreach ($all as $row) {
        $config[$row['chave']] = $row['valor'];
    }
    json_response($config);
}

error_response('Método não permitido', 405);
