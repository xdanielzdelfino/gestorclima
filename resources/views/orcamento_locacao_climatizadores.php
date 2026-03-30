<?php
/**
 * Template de orçamento de locação de climatizadores
 * Este arquivo deve ser renderizado com os dados dinâmicos da locação
 * Utilize variáveis PHP para preencher os campos necessários
 * Siga o modelo visual do HTML fornecido
 */
?>
<?php
// Valores defensivos para evitar warnings caso controller não passe alguma chave
$itens = isset($itens) && is_array($itens) ? $itens : [];
$quantidade_dias = isset($quantidade_dias) ? intval($quantidade_dias) : (isset($itens[0]['quantidade_dias']) ? intval($itens[0]['quantidade_dias']) : 1);

// Se o subtotal foi passado pelo controller respeitamos, caso contrário calculamos a partir dos itens
if (isset($subtotal)) {
    $subtotal = floatval($subtotal);
} else {
    $subtotal = 0.0;
    foreach ($itens as $it) {
        $qtd = isset($it['quantidade']) ? intval($it['quantidade']) : 1;
        $valorUnit = isset($it['valor_unitario']) ? floatval($it['valor_unitario']) : 0.0;
        // se o item especificar quantidade de dias, usa; senão usa o global
        $itemDias = isset($it['quantidade_dias']) ? intval($it['quantidade_dias']) : $quantidade_dias;
        $subtotal += $valorUnit * $qtd * max(1, $itemDias);
    }
}

$despesas_acessorias = isset($despesas_acessorias) ? floatval($despesas_acessorias) : 0.0;

// desconto pode ser passado como percent (desconto_percent) ou como valor (desconto)
$desconto = 0.0;
if (isset($desconto)) {
    $desconto = floatval($desconto);
} elseif (isset($desconto_percent) && floatval($desconto_percent) > 0) {
    $desconto = ($subtotal * floatval($desconto_percent) / 100.0);
}

$total = isset($total) ? floatval($total) : ($subtotal - $desconto + $despesas_acessorias);
?>
<!-- Template HTML do orçamento -->
<!-- Substitua os campos dinâmicos por variáveis PHP -->
<html>
<head>
    <meta charset="utf-8">
    <title>Orçamento de Locação de Climatizadores</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; }
        /* estilos do template original... */
        /* evita que blocos importantes sejam divididos entre páginas no PDF */
        .avoid-break {
            page-break-inside: avoid;
            break-inside: avoid;
            -webkit-column-break-inside: avoid;
            -moz-column-break-inside: avoid;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/_document_body.php'; ?>

    <!-- Imagens e especificações dos climatizadores - NOVA PÁGINA -->
    <div style="page-break-before: always;"></div>
    <?php
    // Iterar todos os itens e gerar um anexo por item (Anexo 1, Anexo 2, ...)
    $ann = 0;
    if (!empty($itens) && is_array($itens)):
        foreach ($itens as $it):
            $ann++;
            $descricao = isset($it['descricao']) ? trim($it['descricao']) : '';
            // Controller já resolveu foto_path
            $climatizador_foto_path = isset($it['foto_path']) ? $it['foto_path'] : null;
            $image_page_break = false;
            // Características técnicas completas (vindo do cadastro). Se não existir campo específico,
            // usamos `descricao` como fallback para manter compatibilidade com o cadastro antigo.
            $caracteristicas = isset($it['caracteristicas']) && trim((string)$it['caracteristicas']) !== ''
                ? trim($it['caracteristicas'])
                : (isset($it['descricao']) ? trim($it['descricao']) : '');
            // incluir partial que mostra Anexo N + imagem/placeholder (partial usa $image_page_break)
            include __DIR__ . '/_document_image_page.php';
            // mostrar características conforme o cadastro (sem fallback)
            $m = strtoupper($caracteristicas);
    ?>
            <div style="margin-top:8px; font-size:12px; line-height:1.25; page-break-inside: avoid;">
                <h3 style="margin-bottom:8px;">Características Técnicas básicas:</h3>
                <ul style="margin:0 0 0 18px; padding:0;">
                    <?php
                    // Sempre renderizar as características do cadastro (quando presente) preservando quebras de linha
                    $lines = preg_split('/\r\n|\r|\n/', $caracteristicas);
                    $rendered = false;
                    if (is_array($lines)) {
                        foreach ($lines as $ln) {
                            $ln = trim($ln);
                            if ($ln === '') continue;
                            echo '<li>' . htmlspecialchars($ln) . '</li>';
                            $rendered = true;
                        }
                    }
                    if (!$rendered) {
                        // se não houver descrição, não exibir fallback automático
                        if (trim($caracteristicas) !== '') {
                            echo '<li>' . htmlspecialchars($caracteristicas) . '</li>';
                        }
                    }
                    ?>
                </ul>
            </div>
    <?php
        endforeach;
    endif;
    ?>
</body>
</html>
