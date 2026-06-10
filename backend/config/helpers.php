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

function rate_limit_check(string $ip, string $scope = 'login'): void {
    $file = sys_get_temp_dir() . '/prontus_rl_' . $scope . '_' . md5($ip) . '.json';
    $now  = time();
    if (!file_exists($file)) return;
    $data = @json_decode(file_get_contents($file), true);
    if (!is_array($data)) return;
    if (!empty($data['blocked_until']) && $data['blocked_until'] > $now) {
        $wait = ceil(($data['blocked_until'] - $now) / 60);
        error_response("Muitas requisições. Aguarde {$wait} minuto(s).", 429);
    }
}

function rate_limit_add_attempt(
    string $ip, string $scope = 'login',
    int $window = 600, int $maxTries = 5, int $blockFor = 900
): void {
    $file = sys_get_temp_dir() . '/prontus_rl_' . $scope . '_' . md5($ip) . '.json';
    $now  = time();
    $data = ['blocked_until' => 0, 'attempts' => []];
    if (file_exists($file)) {
        $raw = @json_decode(file_get_contents($file), true);
        if (is_array($raw)) $data = $raw;
    }
    $data['attempts'] = array_values(array_filter(
        $data['attempts'] ?? [], fn($t) => $t > $now - $window
    ));
    $data['attempts'][] = $now;
    if (count($data['attempts']) >= $maxTries) {
        $data['blocked_until'] = $now + $blockFor;
        $data['attempts'] = [];
    }
    file_put_contents($file, json_encode($data), LOCK_EX);
}

function cors_headers(): void {
    $origem = $_SERVER['HTTP_ORIGIN'] ?? '';

    $envOrigins = getenv('PRONTUS_CORS_ORIGINS');
    $permitidas = $envOrigins
        ? array_filter(array_map('trim', explode(',', $envOrigins)))
        : [
            'http://159.223.165.79:8080',
            'http://localhost:8080',
            'https://leko97.com.br',
            'https://www.leko97.com.br',
        ];

    if (in_array($origem, $permitidas, true)) {
        header("Access-Control-Allow-Origin: {$origem}");
    }
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Credentials: true');
}
