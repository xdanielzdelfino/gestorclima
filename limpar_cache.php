<?php
// Script para limpar todos os caches PHP (OPcache, APCu, etc.)
// Acesse via navegador: http://seu-dominio.com/limpar_cache.php

$cleared = [];

if (function_exists('opcache_reset')) {
    opcache_reset();
    $cleared[] = 'OPcache';
}

if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    $cleared[] = 'APCu';
}

if (function_exists('apc_clear_cache')) {
    apc_clear_cache();
    $cleared[] = 'APC';
}

// Limpar cache de session se houver
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
    $cleared[] = 'Sessões PHP';
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cache Limpo</title>
    <style>
        body { font-family: Arial; padding: 40px; text-align: center; }
        .success { color: green; font-size: 24px; margin: 20px 0; }
        .list { background: #f5f5f5; padding: 20px; display: inline-block; text-align: left; }
    </style>
</head>
<body>
    <h1>✓ Cache Limpo com Sucesso</h1>
    <div class="success">Todos os caches PHP foram limpos!</div>
    
    <?php if (!empty($cleared)): ?>
    <div class="list">
        <strong>Caches limpos:</strong>
        <ul>
            <?php foreach ($cleared as $c): ?>
                <li><?= htmlspecialchars($c) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php else: ?>
    <p>Nenhum cache ativo encontrado.</p>
    <?php endif; ?>
    
    <p style="margin-top:40px;">
        <a href="javascript:history.back()">← Voltar</a>
    </p>
    
    <p style="color:#666; font-size:12px; margin-top:60px;">
        <strong>IMPORTANTE:</strong> Remova este arquivo (limpar_cache.php) após uso por segurança.
    </p>
</body>
</html>
