<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/_produto_helpers.php';

$pdo    = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)$routeParams['id'];

if ($method === 'GET') {
    $produto = buscar_produto_completo($pdo, $id);
    if (empty($produto)) error_response('Produto não encontrado', 404);
    json_response($produto);
}

if ($method === 'PUT') {
    require_admin();
    $data = input_json();

    if (empty($data['nome']) || strlen($data['nome']) > 200)
        error_response('Campo "nome" inválido ou ausente');
    if (!isset($data['preco']) || !is_numeric($data['preco']) || (float)$data['preco'] < 0)
        error_response('Campo "preco" inválido ou ausente');

    $check = $pdo->prepare('SELECT id FROM produtos WHERE id = ? AND ativo = 1');
    $check->execute([$id]);
    if (!$check->fetch()) error_response('Produto não encontrado', 404);

    $catId = (int)($data['categoriaId'] ?? $data['categoria_id'] ?? 0);
    if ($catId <= 0) error_response('Campo "categoriaId" inválido ou ausente');

    $catCheck = $pdo->prepare('SELECT id FROM categorias WHERE id = ? AND ativo = 1');
    $catCheck->execute([$catId]);
    if (!$catCheck->fetch()) error_response('Categoria não encontrada', 404);

    $stmt = $pdo->prepare(
        'UPDATE produtos SET categoria_id = ?, nome = ?, descricao = ?, preco = ?, imagem = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $catId,
        $data['nome'],
        $data['descricao'] ?? '',
        (float)$data['preco'],
        $data['imagem'] ?? null,
        $id,
    ]);

    $pdo->prepare('DELETE FROM produto_restricoes WHERE produto_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM adicionais WHERE produto_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM remocoes WHERE produto_id = ?')->execute([$id]);

    foreach ($data['restricoes'] ?? [] as $slug) {
        $pdo->prepare('INSERT INTO produto_restricoes VALUES (?, ?)')->execute([$id, $slug]);
    }
    foreach ($data['extras'] ?? [] as $extra) {
        $pdo->prepare('INSERT INTO adicionais (produto_id, nome, preco) VALUES (?, ?, ?)')
            ->execute([$id, $extra['nome'], (float)$extra['preco']]);
    }
    foreach ($data['remocoes'] ?? [] as $nome) {
        $pdo->prepare('INSERT INTO remocoes (produto_id, nome) VALUES (?, ?)')->execute([$id, $nome]);
    }

    json_response(buscar_produto_completo($pdo, $id));
}

if ($method === 'DELETE') {
    require_admin();

    $stmt = $pdo->prepare('UPDATE produtos SET ativo = 0 WHERE id = ?');
    $stmt->execute([$id]);
    json_response(['ok' => true]);
}

if ($method === 'POST') {
    require_admin();

    $check = $pdo->prepare('SELECT id FROM produtos WHERE id = ? AND ativo = 1');
    $check->execute([$id]);
    if (!$check->fetch()) error_response('Produto não encontrado', 404);

    if (empty($_FILES['imagem'])) {
        error_response('Nenhum arquivo enviado');
    }

    $file         = $_FILES['imagem'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($file['type'], $allowedTypes, true)) {
        error_response('Tipo não permitido. Use JPG, PNG ou WebP');
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        error_response('Arquivo muito grande. Máximo 2MB');
    }

    $uploadsDir = dirname(__DIR__, 2) . '/uploads/produtos';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }

    $src = null;
    switch ($file['type']) {
        case 'image/jpeg': $src = @imagecreatefromjpeg($file['tmp_name']); break;
        case 'image/png':  $src = @imagecreatefrompng($file['tmp_name']); break;
        case 'image/webp': $src = @imagecreatefromwebp($file['tmp_name']); break;
    }
    if (!$src) error_response('Erro ao processar imagem', 500);

    $origW  = imagesx($src);
    $origH  = imagesy($src);
    $maxDim = 800;

    if ($origW > $maxDim || $origH > $maxDim) {
        $ratio = min($maxDim / $origW, $maxDim / $origH);
        $newW  = (int)round($origW * $ratio);
        $newH  = (int)round($origH * $ratio);
        $dst   = imagecreatetruecolor($newW, $newH);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);
        $src = $dst;
    }

    $destPath = $uploadsDir . '/' . $id . '.webp';
    if (!imagewebp($src, $destPath, 85)) {
        imagedestroy($src);
        error_response('Erro ao salvar imagem', 500);
    }
    imagedestroy($src);

    $url = '/uploads/produtos/' . $id . '.webp';
    $pdo->prepare('UPDATE produtos SET imagem = ? WHERE id = ?')->execute([$url, $id]);

    json_response(['url' => $url]);
}

error_response('Método não permitido', 405);
