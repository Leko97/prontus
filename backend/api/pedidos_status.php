<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') error_response('Método não permitido', 405);

require_auth();

$pdo  = get_pdo();
$id   = (int)$routeParams['id'];
$data = input_json();

$status = $data['status'] ?? '';
if (!in_array($status, STATUS_VALIDOS, true)) {
    error_response('Status inválido. Valores aceitos: ' . implode(', ', STATUS_VALIDOS));
}

if ($status === 'pronto') {
    $stmt = $pdo->prepare('UPDATE pedidos SET status = ?, preparado_em = NOW() WHERE id = ?');
} else {
    $stmt = $pdo->prepare('UPDATE pedidos SET status = ? WHERE id = ?');
}
$stmt->execute([$status, $id]);

json_response(['id' => $id, 'status' => $status]);
