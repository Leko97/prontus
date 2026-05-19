<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$pdo    = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    require_admin();
    $stmt = $pdo->query('SELECT id, nome, email, perfil, ativo FROM usuarios ORDER BY nome');
    json_response($stmt->fetchAll());
}

if ($method === 'POST') {
    require_admin();
    $data = input_json();

    $nome   = trim($data['nome']  ?? '');
    $email  = trim($data['email'] ?? '');
    $senha  = $data['senha']      ?? '';
    $perfil = $data['perfil']     ?? 'cozinha';

    if (!$nome)  error_response('Campo "nome" obrigatório');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) error_response('E-mail inválido');
    if (strlen($senha) < 6) error_response('Senha deve ter pelo menos 6 caracteres');
    if (!in_array($perfil, PERFIS_VALIDOS, true)) error_response('Perfil inválido');

    $check = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) error_response('E-mail já cadastrado', 409);

    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$nome, $email, password_hash($senha, PASSWORD_BCRYPT), $perfil]);
    $id = (int)$pdo->lastInsertId();

    json_response(['id' => $id, 'nome' => $nome, 'email' => $email, 'perfil' => $perfil, 'ativo' => 1], 201);
}

error_response('Método não permitido', 405);
