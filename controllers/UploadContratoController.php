<?php
/**
 * Controller simples para receber upload de contrato assinado e associar à locação
 * POST: campos esperados: locacao_id (int), signed_contract (arquivo)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Locacao.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        exit;
    }

    $locacaoId = isset($_POST['locacao_id']) ? intval($_POST['locacao_id']) : 0;
    if (!$locacaoId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID da locação não informado']);
        exit;
    }

    // Verificar se a locação existe (opcional)
    $locModel = new Locacao();
    $loc = $locModel->buscarPorId($locacaoId);
    if (!$loc) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Locação não encontrada']);
        exit;
    }

    if (!isset($_FILES['signed_contract']) || $_FILES['signed_contract']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Arquivo não enviado ou com erro']);
        exit;
    }

    $file = $_FILES['signed_contract'];
    $allowed = ['application/pdf', 'image/png', 'image/jpeg'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tipo de arquivo não permitido']);
        exit;
    }

    // Tamanho máximo 10MB
    if ($file['size'] > 10 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Arquivo excede o tamanho máximo de 10MB']);
        exit;
    }

    // Preparar pasta
    $uploadsDir = __DIR__ . '/../assets/uploads/contratos';
    if (!is_dir($uploadsDir)) @mkdir($uploadsDir, 0755, true);

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
    $generated = sprintf('contrato_assinado_loc_%d_%s.%s', $locacaoId, uniqid(), $ext);
    $dest = $uploadsDir . '/' . $generated;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Falha ao salvar o arquivo no servidor']);
        exit;
    }

    // Atualizar índice simples (JSON) para permitir listagem posterior
    $indexFile = $uploadsDir . '/index.json';
    $index = [];
    if (file_exists($indexFile)) {
        $raw = file_get_contents($indexFile);
        $index = json_decode($raw, true) ?: [];
    }

    $entry = [
        'file' => $generated,
        'original_name' => $file['name'],
        'locacao_id' => $locacaoId,
        'uploaded_at' => time(),
        'mime' => $mime,
        'size' => $file['size']
    ];

    // permitir múltiplos anexos por locação: armazenar em array
    if (!isset($index[$locacaoId]) || !is_array($index[$locacaoId])) $index[$locacaoId] = [];
    $index[$locacaoId][] = $entry;

    file_put_contents($indexFile, json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

    // Gerar URL pública simples
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $basePath = dirname($_SERVER['SCRIPT_NAME']);
    $basePath = rtrim(str_replace('controllers', '', $basePath), '/');
    $fileUrl = $scheme . '://' . $host . $basePath . '/assets/uploads/contratos/' . $generated;

    echo json_encode(['success' => true, 'message' => 'Arquivo enviado com sucesso', 'file' => $generated, 'url' => $fileUrl]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
    exit;
}
