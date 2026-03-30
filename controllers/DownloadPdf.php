<?php
/**
 * Endpoint para download seguro de PDFs gerados.
 * Uso: DownloadPdf.php?file=orcamento_123_abc.pdf&ts=TIMESTAMP&sig=HMAC
 */
// Carrega segredo
$secretCfg = @include __DIR__ . '/../config/secret.php';
$secret = $secretCfg['pdf_secret'] ?? null;

if (!$secret) {
    http_response_code(500);
    echo 'Server misconfiguration';
    exit;
}

// Suporte a token persistente: ?token=abc
$token = isset($_GET['token']) ? preg_replace('/[^0-9a-f]/', '', $_GET['token']) : '';
if ($token) {
    $indexFile = __DIR__ . '/../assets/pdfs/index.json';
    if (!file_exists($indexFile)) {
        http_response_code(404);
        echo 'Arquivo não encontrado';
        exit;
    }
    $index = json_decode(file_get_contents($indexFile), true) ?: [];
    if (!isset($index[$token]) || !isset($index[$token]['file'])) {
        http_response_code(404);
        echo 'Token inválido ou revogado';
        exit;
    }
    $file = basename($index[$token]['file']);
    $pdfPath = __DIR__ . '/../assets/pdfs/' . $file;
} else {
    $file = isset($_GET['file']) ? basename($_GET['file']) : '';
    $ts = isset($_GET['ts']) ? (int) $_GET['ts'] : 0;
    $sig = isset($_GET['sig']) ? $_GET['sig'] : '';

    // parâmetros mínimos
    if (!$file || !$ts || !$sig) {
        http_response_code(400);
        echo 'Parâmetros inválidos';
        exit;
    }

    // Verifica validade temporal (ex.: 1 hora)
    $now = time();
    if (abs($now - $ts) > 60 * 60) { // 1 hora
        http_response_code(403);
        echo 'Token expirado';
        exit;
    }

    // Recria assinatura HMAC
    $data = $file . '|' . $ts;
    $expected = hash_hmac('sha256', $data, $secret);
    if (!hash_equals($expected, $sig)) {
        http_response_code(403);
        echo 'Assinatura inválida';
        exit;
    }

    $pdfPath = __DIR__ . '/../assets/pdfs/' . $file;
}
if (!file_exists($pdfPath)) {
    http_response_code(404);
    echo 'Arquivo não encontrado';
    exit;
}

// Servir o arquivo com headers adequados
header('Content-Type: application/pdf');
// Prefer inline to allow preview in browser; fallback to download if browser doesn't support inline
header('Content-Disposition: inline; filename="' . $file . '"');
header('Content-Length: ' . filesize($pdfPath));
readfile($pdfPath);
exit;
