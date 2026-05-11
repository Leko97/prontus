<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/helpers.php';

cors_headers();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path   = preg_replace('#^/?api#', '', $uri);
$path   = rtrim($path, '/') ?: '/';

$routeParams = [];

$routes = [
    ['GET',    '#^/cardapio$#',               'cardapio.php'],
    ['GET',    '#^/pedidos$#',                'pedidos.php'],
    ['POST',   '#^/pedidos$#',                'pedidos.php'],
    ['PATCH',  '#^/pedidos/(\d+)/status$#',   'pedidos_status.php', ['id']],
    ['GET',    '#^/senhas$#',                 'senhas.php'],
    ['GET',    '#^/metricas$#',               'metricas.php'],
    ['GET',    '#^/produtos$#',               'produtos.php'],
    ['POST',   '#^/produtos$#',               'produtos.php'],
    ['GET',    '#^/produtos/(\d+)$#',         'produto.php',  ['id']],
    ['PUT',    '#^/produtos/(\d+)$#',         'produto.php',  ['id']],
    ['DELETE', '#^/produtos/(\d+)$#',         'produto.php',  ['id']],
    ['GET',    '#^/categorias$#',             'categorias.php'],
    ['POST',   '#^/categorias$#',             'categorias.php'],
    ['PUT',    '#^/categorias/(\d+)$#',       'categorias.php', ['id']],
    ['DELETE', '#^/categorias/(\d+)$#',       'categorias.php', ['id']],
    ['GET',    '#^/adicionais$#',             'adicionais.php'],
    ['POST',   '#^/adicionais$#',             'adicionais.php'],
    ['PUT',    '#^/adicionais/(\d+)$#',       'adicionais.php', ['id']],
    ['DELETE', '#^/adicionais/(\d+)$#',       'adicionais.php', ['id']],
    ['POST',   '#^/auth/login$#',             'auth/login.php'],
    ['POST',   '#^/auth/logout$#',            'auth/logout.php'],
    ['GET',    '#^/auth/me$#',                'auth/me.php'],
];

foreach ($routes as $route) {
    [$rMethod, $rPattern, $rFile] = $route;
    $rParams = $route[3] ?? [];
    if ($method !== $rMethod) continue;
    if (!preg_match($rPattern, $path, $matches)) continue;
    foreach ($rParams as $i => $name) {
        $routeParams[$name] = $matches[$i + 1];
    }
    require __DIR__ . '/' . $rFile;
    exit;
}

error_response('Rota não encontrada', 404);
