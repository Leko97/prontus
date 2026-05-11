<?php
function json_response(mixed $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function error_response(string $msg, int $status = 400): never {
    json_response(['erro' => $msg], $status);
}

function input_json(): array {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    if (!is_array($data)) error_response('Body JSON inválido', 400);
    return $data;
}

function require_auth(): array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['usuario'])) error_response('Não autenticado', 401);
    return $_SESSION['usuario'];
}

function require_admin(): array {
    $usuario = require_auth();
    if ($usuario['perfil'] !== 'admin') error_response('Acesso negado', 403);
    return $usuario;
}

function cors_headers(): void {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}
