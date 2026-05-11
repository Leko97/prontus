<?php
require_once __DIR__ . '/../../config/helpers.php';

$usuario = require_auth();

json_response(['logado' => true, 'usuario' => $usuario]);
