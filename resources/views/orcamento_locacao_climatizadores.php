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
            // incluir partial que mostra Anexo N + imagem/placeholder (partial usa $image_page_break)
            include __DIR__ . '/_document_image_page.php';
            // mostrar características conforme o modelo detectado na descrição
            $m = strtoupper($descricao);
    ?>
            <div style="margin-top:8px; font-size:12px; line-height:1.25; page-break-inside: avoid;">
                <h3 style="margin-bottom:8px;">Características Técnicas básicas:</h3>
                <ul style="margin:0 0 0 18px; padding:0;">
                    <?php if (strpos($m, '52') !== false): ?>
                        <li>Vazão de ar: 16.000 m³/h</li>
                        <li>Motor: M/F 127v: 180w, 2.1A / M/F 220V: 180w, 1.2A</li>
                        <li>Motores individuais (2) em Alumínio</li>
                        <li>Alimentação elétrica: Tensão M/F 127v ou 220v</li>
                        <li>Frequência: 60 Hz</li>
                        <li>Ruído: entre 68 e 72 dB(A).</li>
                        <li>Reservatório: 80 Litros.</li>
                        <li>Área resfriada: o resfriamento cobre cerca de 150 a 200 m², dependendo da ventilação e do clima.</li>
                        <li>Potência em Watts: 510 Watts</li>
                        <li>Altura: 1,96 metros</li>
                        <li>Evaporativo: sim</li>
                        <li>Velocidade: São 3 velocidades, Baixa/Média/Alta</li>
                    <?php elseif (strpos($m, '55') !== false): ?>
                        <li>Vazão de ar: 30.000 m³/h</li>
                        <li>Motor: (1500w) 2.0cv, 220V, 7,5A</li>
                        <li>Motor em Alumínio</li>
                        <li>Alimentação elétrica: Tensão M/F 220v</li>
                        <li>Frequência: 60 Hz</li>
                        <li>Ruído: 72 dB (próximo ao climatizador)</li>
                        <li>Reservatório: 204 litros.</li>
                        <li>Área resfriada: Até 200 m².</li>
                        <li>Potência em Watts: 1500W (1,5 kW)</li>
                        <li>Altura: 2200 mm</li>
                        <li>Evaporativo: sim</li>
                        <li>Velocidade: São 3 velocidades, Baixa/Média/Alta</li>
                    <?php else: ?>
                        <li>Descrição: <?= htmlspecialchars($descricao) ?></li>
                    <?php endif; ?>
                </ul>
            </div>
    <?php
        endforeach;
    endif;
    ?>
</body>
</html>
