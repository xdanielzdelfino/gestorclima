<?php
/**
 * Partial: página com foto do climatizador
 * Uso:
 * - Coloque as imagens no diretório: assets/images/climatizadores/
 * - Nome sugerido: <codigo>.png, <codigo>.jpg ou <id> (ex: ROTO_PLAST_52.png ou 123.jpg)
 * - Ao gerar o documento, passe a variável $climatizador_foto_path contendo o caminho absoluto ou relativo para a imagem.
 *   Exemplo no controller: $dados['climatizador_foto_path'] = __DIR__ . '/../../assets/images/climatizadores/' . $codigo . '.png';
 * - Se a variável não estiver definida ou o arquivo não existir, será exibida uma caixa reservada.
 */
?>

<?php
    // Permitir controlar a quebra de página antes da imagem via variável opcional
    // Por padrão mantém a quebra; defina $image_page_break = false antes do include para não quebrar.
    $__break = !isset($image_page_break) || $image_page_break !== false;
    $__breakStyle = $__break ? 'page-break-before: always;' : '';
    // Evitar que o rótulo, a imagem e as especificações sejam separados entre páginas
    $__groupStyle = $__breakStyle . ' page-break-inside: avoid; break-inside: avoid; page-break-after: avoid; text-align: center; margin-top: 20px;';

    // Priorizar $it['foto_path'] se veio do controller (já resolvido)
    if (empty($climatizador_foto_path) && isset($it) && is_array($it) && !empty($it['foto_path'])) {
        $climatizador_foto_path = $it['foto_path'];
    }
?>
<div style="<?= $__groupStyle ?>">
    <?php if (!empty($climatizador_foto_path) && file_exists($climatizador_foto_path)): ?>
        <?php
        // Limites padrão pensados para A4
        $maxWidthMm = isset($image_max_width_mm) ? (int)$image_max_width_mm : 170; // mm
        $maxHeightMm = isset($image_max_height_mm) ? (int)$image_max_height_mm : 220; // mm
        // Se não houver quebra de página, reduza automaticamente para caber com especificações na mesma página
        if (!$__break && !isset($image_max_width_mm) && !isset($image_max_height_mm)) {
            $maxWidthMm = 140;  // mais estreito
            $maxHeightMm = 120; // mais baixo
        }
        ?>
        <!-- Rótulo/identificação do anexo junto da imagem (mantém agrupamento) -->
        <div style="display:block; width:100%; text-align:center;">
            <p style="font-size:14px; font-weight:600; margin:0 0 8px 0;">Anexo 1</p>
            <div style="width:100%; display:flex; align-items:center; justify-content:center;">
                <img src="<?= htmlspecialchars($climatizador_foto_path) ?>" alt="Anexo 1" 
                     style="max-width: <?= $maxWidthMm ?>mm; max-height: <?= $maxHeightMm ?>mm; width: auto; height: auto; object-fit: contain;"/>
            </div>
        </div>
    <?php else: ?>
        <!-- Rótulo/identificação do anexo junto do placeholder -->
        <div style="display:block; width:100%; text-align:center;">
            <p style="font-size:14px; font-weight:600; margin:0 0 8px 0;">Anexo 1</p>
            <!-- Caixa reservada quando não há imagem disponível -->
            <div style="width:100%; height:200px; border:1px dashed #999; display:flex; align-items:center; justify-content:center;">
                <span style="color:#666; font-size:14px;">Coloque a foto do climatizador em: assets/images/climatizadores/&lt;codigo|id&gt;.png (ou .jpg)</span>
            </div>
            <p style="font-size: 12px; margin-top: 8px; color:#666;">Observação: defina $climatizador_foto_path ao chamar o gerador de PDF para incluir a imagem aqui.</p>
        </div>
    <?php endif; ?>
</div>
