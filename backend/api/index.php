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
    ['GET',    '#^/pedidos/historico$#',        'pedidos_historico.php'],
    ['GET',    '#^/pedidos$#',                'pedidos.php'],
    ['POST',   '#^/pedidos$#',                'pedidos.php'],
    ['PATCH',  '#^/pedidos/(\d+)/status$#',   'pedidos_status.php', ['id']],
    ['GET',    '#^/usuarios$#',               'usuarios.php'],
    ['POST',   '#^/usuarios$#',               'usuarios.php'],
    ['GET',    '#^/usuarios/(\d+)$#',         'usuario.php',  ['id']],
    ['PUT',    '#^/usuarios/(\d+)$#',         'usuario.php',  ['id']],
    ['DELETE', '#^/usuarios/(\d+)$#',         'usuario.php',  ['id']],
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
    ['GET',    '#^/configuracoes$#',          'configuracoes.php'],
    ['PUT',    '#^/configuracoes$#',          'configuracoes.php'],
    ['GET',    '#^/restricoes$#',             'restricoes.php'],
    ['POST',   '#^/restricoes$#',             'restricoes.php'],
    ['PUT',    '#^/restricoes/([a-z0-9-]+)$#', 'restricoes.php', ['slug']],
    ['GET',    '#^/combos$#',                 'combos.php'],
    ['POST',   '#^/combos$#',                 'combos.php'],
    ['PUT',    '#^/combos/(\d+)$#',           'combos.php',   ['id']],
    ['DELETE', '#^/combos/(\d+)$#',           'combos.php',   ['id']],
    ['POST',   '#^/avaliacoes$#',             'avaliacoes.php'],
    ['GET',    '#^/remocoes$#',               'remocoes.php'],
    ['POST',   '#^/remocoes$#',               'remocoes.php'],
    ['DELETE', '#^/remocoes/(\d+)$#',         'remocoes.php',  ['id']],
    ['POST',   '#^/produtos/(\d+)/imagem$#',  'produto.php',   ['id']],
    ['GET',    '#^/relatorios$#',             'relatorios.php'],
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
