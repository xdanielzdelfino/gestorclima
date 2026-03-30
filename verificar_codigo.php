<?php
/**
 * Verificar se o código atualizado está no servidor
 * Acesse: http://seu-dominio.com/verificar_codigo.php
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Verificação de Código</title>";
echo "<style>body{font-family:Arial;padding:30px;} .ok{color:green;} .erro{color:red;font-weight:bold;} pre{background:#f5f5f5;padding:15px;overflow:auto;}</style></head><body>";
echo "<h1>Verificação de Código Atualizado</h1>";

// 1. Verificar se controller tem busca fuzzy
echo "<h2>1. Controller LocacaoControllerOrcamento</h2>";
$controllerPath = __DIR__ . '/controllers/LocacaoControllerOrcamento.php';
if (file_exists($controllerPath)) {
    $code = file_get_contents($controllerPath);
    
    // Verificar busca fuzzy
    if (strpos($code, 'tentativa por substring') !== false && strpos($code, 'array_filter(explode') !== false) {
        echo "<p class='ok'>✓ Busca fuzzy implementada</p>";
    } else {
        echo "<p class='erro'>✗ Busca fuzzy NÃO encontrada - CÓDIGO DESATUALIZADO!</p>";
    }
    
    // Verificar normalização de palavras
    if (strpos($code, 'Normalizar: remover palavras duplicadas') !== false) {
        echo "<p class='ok'>✓ Normalização de descrição implementada</p>";
    } else {
        echo "<p class='erro'>✗ Normalização NÃO encontrada - CÓDIGO DESATUALIZADO!</p>";
    }
    
    // Mostrar trecho relevante
    $start = strpos($code, '// Iterar todos os itens');
    if ($start !== false) {
        echo "<h3>Trecho do código (primeiras 40 linhas após 'Iterar todos os itens'):</h3>";
        $lines = explode("\n", substr($code, $start, 3000));
        echo "<pre>" . htmlspecialchars(implode("\n", array_slice($lines, 0, 40))) . "</pre>";
    }
} else {
    echo "<p class='erro'>✗ Arquivo não encontrado: {$controllerPath}</p>";
}

// 2. Verificar DocumentoPdfService
echo "<h2>2. DocumentoPdfService (OPcache clear)</h2>";
$servicePath = __DIR__ . '/app/Services/DocumentoPdfService.php';
if (file_exists($servicePath)) {
    $code = file_get_contents($servicePath);
    if (strpos($code, 'opcache_invalidate') !== false) {
        echo "<p class='ok'>✓ opcache_invalidate implementado</p>";
    } else {
        echo "<p class='erro'>✗ opcache_invalidate NÃO encontrado - CÓDIGO DESATUALIZADO!</p>";
    }
} else {
    echo "<p class='erro'>✗ Arquivo não encontrado: {$servicePath}</p>";
}

// 3. Verificar modelo Locacao (fallback)
echo "<h2>3. Modelo Locacao (fallback para registros antigos)</h2>";
$modelPath = __DIR__ . '/models/Locacao.php';
if (file_exists($modelPath)) {
    $code = file_get_contents($modelPath);
    if (strpos($code, 'Fallback para registros legados') !== false || strpos($code, 'fallback') !== false) {
        echo "<p class='ok'>✓ Fallback para registros legados implementado</p>";
    } else {
        echo "<p class='erro'>✗ Fallback NÃO encontrado - CÓDIGO DESATUALIZADO!</p>";
    }
} else {
    echo "<p class='erro'>✗ Arquivo não encontrado: {$modelPath}</p>";
}

// 4. Testar locação 43
echo "<h2>4. Teste Rápido - Locação ID 43</h2>";
require_once __DIR__ . '/models/Locacao.php';
$locacaoModel = new Locacao();
$locacao = $locacaoModel->buscarPorId(43);
if ($locacao) {
    echo "<p class='ok'>✓ Locação 43 encontrada</p>";
    echo "<p>Cliente: " . htmlspecialchars($locacao['cliente_nome']) . "</p>";
    
    if (isset($locacao['climatizadores']) && is_array($locacao['climatizadores'])) {
        echo "<p class='ok'>✓ Campo climatizadores existe (array com " . count($locacao['climatizadores']) . " itens)</p>";
        foreach ($locacao['climatizadores'] as $idx => $it) {
            echo "<p>Item {$idx}: descricao=\"" . htmlspecialchars($it['descricao'] ?? '') . "\" qtd=" . ($it['quantidade'] ?? 0) . "</p>";
        }
    } else {
        echo "<p class='erro'>✗ Campo climatizadores NÃO existe ou não é array!</p>";
    }
} else {
    echo "<p class='erro'>✗ Locação 43 NÃO encontrada</p>";
}

// 5. Info do servidor
echo "<h2>5. Informações do Servidor</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>OPcache habilitado: " . (function_exists('opcache_reset') ? 'SIM' : 'NÃO') . "</p>";
echo "<p>Caminho deste arquivo: " . __FILE__ . "</p>";
echo "<p>Data/Hora: " . date('Y-m-d H:i:s') . "</p>";

echo "<hr><p style='color:#666;font-size:12px;'><strong>IMPORTANTE:</strong> Remova este arquivo após uso por segurança.</p>";
echo "</body></html>";
