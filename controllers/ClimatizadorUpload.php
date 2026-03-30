<?php
/**
 * Endpoint mínimo para upload de foto de climatizador.
 * Recebe multipart/form-data com 'foto' e 'id' ou 'codigo'.
 * Salva em assets/images/climatizadores/ com nome seguro.
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        exit;
    }

    if (!isset($_FILES['foto']) || !is_uploaded_file($_FILES['foto']['tmp_name'])) {
        throw new Exception('Arquivo não enviado');
    }

    $file = $_FILES['foto'];
    // Validações básicas
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) throw new Exception('Arquivo muito grande (máx 5MB)');

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowed = [ 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif' ];
    if (!isset($allowed[$mime])) throw new Exception('Tipo de arquivo não permitido');

    // identificar nome base (priorizar id, depois codigo, depois nome original sem extensão)
    $base = '';
    if (!empty($_POST['id'])) $base = preg_replace('/[^0-9]/', '', $_POST['id']);
    if ($base === '' && !empty($_POST['codigo'])) $base = preg_replace('/[^A-Za-z0-9_\-]/', '_', $_POST['codigo']);
    if ($base === '') {
        $base = pathinfo($file['name'], PATHINFO_FILENAME);
        $base = preg_replace('/[^A-Za-z0-9_\-]/', '_', $base);
        if ($base === '') $base = uniqid('img_');
    }

    $ext = $allowed[$mime];
    $targetDir = __DIR__ . '/../assets/images/climatizadores';
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

    // Construir nome seguro: base + extensão
    $targetName = $base . '.' . $ext;
    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $targetName;

    // Se já existir, gerar sufixo
    $i = 1;
    while (file_exists($targetPath)) {
        $targetName = $base . '_' . $i . '.' . $ext;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $targetName;
        $i++;
        if ($i > 20) break;
    }

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Falha ao salvar arquivo');
    }

    // Retornar caminho relativo para uso no front/PDF (usar assets/images/climatizadores/...)
    $relative = 'assets/images/climatizadores/' . $targetName;
    echo json_encode(['success' => true, 'file' => $relative, 'fileName' => $targetName]);
    exit;
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
