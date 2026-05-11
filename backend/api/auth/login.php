<?php
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

session_start();

$email    = trim($_POST['email'] ?? '');
$senha    = $_POST['senha'] ?? '';
$redirect = $_POST['redirect'] ?? '/admin/pages/login.html';
$fallback = strtok($redirect, '?') . '?erro=1';

$stmt = get_pdo()->prepare(
    'SELECT id, nome, email, senha_hash, perfil FROM usuarios WHERE email = ? AND ativo = 1'
);
$stmt->execute([$email]);
$usuario = $stmt->fetch();

if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
    header('Location: ' . $fallback);
    exit;
}

$_SESSION['usuario'] = [
    'id'     => $usuario['id'],
    'nome'   => $usuario['nome'],
    'email'  => $usuario['email'],
    'perfil' => $usuario['perfil'],
];

header('Location: ' . $redirect);
exit;
