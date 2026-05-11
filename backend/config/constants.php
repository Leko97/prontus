<?php
date_default_timezone_set('America/Sao_Paulo');

$_envFile = __DIR__ . '/env.php';
if (file_exists($_envFile)) require_once $_envFile;

define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'prontus');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

const STATUS_VALIDOS = ['recebido', 'em-preparo', 'pronto', 'finalizado'];
const STATUS_SEQUENCIA = [
    'recebido'   => 'em-preparo',
    'em-preparo' => 'pronto',
    'pronto'     => 'finalizado',
];
const PERFIS_VALIDOS = ['admin', 'cozinha'];
const RESTRICOES_PADRAO = [
    ['slug' => 'sem-gluten',   'nome' => 'Sem Glúten',   'cor' => '#E74C3C'],
    ['slug' => 'vegetariano',  'nome' => 'Vegetariano',  'cor' => '#27AE60'],
    ['slug' => 'vegano',       'nome' => 'Vegano',       'cor' => '#2ECC71'],
    ['slug' => 'sem-lactose',  'nome' => 'Sem Lactose',  'cor' => '#3498DB'],
    ['slug' => 'sem-amendoim', 'nome' => 'Sem Amendoim', 'cor' => '#F39C12'],
];
