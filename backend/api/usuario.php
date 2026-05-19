<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$pdo    = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($routeParams['id'] ?? 0);

if (!$id) error_response('ID inválido', 400);

if ($method === 'GET') {
    require_admin();
    $stmt = $pdo->prepare('SELECT id, nome, email, perfil, ativo FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if (!$u) error_response('Usuário não encontrado', 404);
    json_response($u);
}

if ($method === 'PUT') {
    $logado = require_admin();
    $data   = input_json();

    $nome   = trim($data['nome']  ?? '');
    $email  = trim($data['email'] ?? '');
    $perfil = $data['perfil']     ?? '';
    $senha  = $data['senha']      ?? '';

    if (!$nome)  error_response('Campo "nome" obrigatório');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) error_response('E-mail inválido');
    if (!in_array($perfil, PERFIS_VALIDOS, true)) error_response('Perfil inválido');

    $check = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? AND id != ?');
    $check->execute([$email, $id]);
    if ($check->fetch()) error_response('E-mail já usado por outro usuário', 409);

    if ($senha) {
        if (strlen($senha) < 6) error_response('Senha deve ter pelo menos 6 caracteres');
        $pdo->prepare('UPDATE usuarios SET nome=?, email=?, perfil=?, senha_hash=? WHERE id=?')
            ->execute([$nome, $email, $perfil, password_hash($senha, PASSWORD_BCRYPT), $id]);
    } else {
        $pdo->prepare('UPDATE usuarios SET nome=?, email=?, perfil=? WHERE id=?')
            ->execute([$nome, $email, $perfil, $id]);
    }
    json_response(['id' => $id, 'nome' => $nome, 'email' => $email, 'perfil' => $perfil]);
}

if ($method === 'DELETE') {
    $logado = require_admin();
    if ((int)$logado['id'] === $id) error_response('Você não pode excluir sua própria conta', 403);
    $pdo->prepare('UPDATE usuarios SET ativo = 0 WHERE id = ?')->execute([$id]);
    json_response(['ok' => true]);
}

error_response('Método não permitido', 405);
