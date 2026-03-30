<?php
// Partial com o corpo compartilhado entre orçamento e contrato.
// Recebe as mesmas variáveis que os templates (itens, subtotal, desconto, etc.).
?>
    <!-- Cabeçalho -->
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="<?= $logoPath ?>" alt="ImperialClima" style="max-width: 200px;">
        <?php if (!empty($isContrato) && $isContrato): ?>
            <h2 style="font-size: 24px; margin: 10px 0;">CONTRATO DE LOCAÇÃO DE CLIMATIZADORES</h2>
        <?php else: ?>
            <h2 style="font-size: 24px; margin: 10px 0;">ORÇAMENTO DE LOCAÇÃO DE CLIMATIZADORES</h2>
        <?php endif; ?>
    </div>
    <hr>
    <!-- Dados do cliente (duas colunas, top-alinhadas para melhor encaixe em PDF) -->
    <table width="100%" style="margin-bottom: 20px; font-size: 14px;">
        <tr>
            <td style="vertical-align: top; width: 65%;">
                <div><strong>Cliente:</strong> <?= nl2br(htmlspecialchars(html_entity_decode($cliente ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?></div>
                <div style="margin-top:6px;"><strong>Telefone:</strong> <?= htmlspecialchars($telefone) ?></div>
                <div style="margin-top:6px;"><strong>Endereço:</strong> <?= htmlspecialchars($endereco) ?></div>
            </td>
            <td style="vertical-align: top; width: 35%;">
                <?php if (!empty($data_inicio)): ?>
                    <div><strong>Data Início:</strong> <?= htmlspecialchars($data_inicio) ?></div>
                <?php endif; ?>
                <?php if (!empty($data_fim)): ?>
                    <div style="margin-top:6px;"><strong>Data Fim:</strong> <?= htmlspecialchars($data_fim) ?></div>
                <?php endif; ?>
                <div style="margin-top:6px;"><strong>Local do Evento:</strong> <?= htmlspecialchars($local_evento ?? '-') ?></div>
            </td>
        </tr>
    </table>
    <!-- Tabela de itens -->
    <table width="100%" border="1" cellspacing="0" cellpadding="5" style="font-size: 14px; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f0f0f0;">
                <th>Descrição</th>
                <th>Qtd</th>
                <th>Valor Diária</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
        <?php
            $calculatedSubtotal = 0.0;
            foreach ($itens as $item):
                $quant = isset($item['quantidade']) ? floatval($item['quantidade']) : 0;
                $valorDiaria = isset($item['valor_diaria']) ? floatval($item['valor_diaria']) : (isset($item['valor_unitario']) ? floatval($item['valor_unitario']) : 0);
                $itemTotal = $quant * $valorDiaria;
                $calculatedSubtotal += $itemTotal;
        ?>
            <tr>
                <td><?= htmlspecialchars($item['descricao']) ?></td>
                <td><?= htmlspecialchars($item['quantidade']) ?></td>
                <td>R$ <?= number_format($valorDiaria, 2, ',', '.') ?></td>
                <td>R$ <?= number_format($itemTotal, 2, ',', '.') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <!-- Resumo -->
    <table width="100%" style="font-size: 14px; margin-top: 20px;">
        <?php
            // Exibir subtotal por dia (calculado a partir dos itens)
            $displaySubtotal = isset($calculatedSubtotal) ? $calculatedSubtotal : (isset($subtotal) ? floatval($subtotal) : 0.0);
            $dias = isset($quantidade_dias) ? intval($quantidade_dias) : 1;
            $descontoVal = isset($desconto) ? floatval($desconto) : 0.0;
            $despesasVal = isset($despesas_acessorias) ? floatval($despesas_acessorias) : 0.0;
            // Rótulo para as despesas acessórias (permite personalizar entre os templates/DB)
            // Normalização: registros antigos ainda podem trazer "... e suporte".
            if (isset($despesas_acessorias_tipo)) {
                $despesas_acessorias_tipo = str_replace(
                    ['Despesas acessórias (transporte, instalação e suporte):', 'Despesas acessórias (transporte, instalação e suporte)'],
                    ['Despesas acessórias (transporte e instalação):', 'Despesas acessórias (transporte e instalação)'],
                    $despesas_acessorias_tipo
                );
            }
            $despesasLabel = isset($despesas_acessorias_tipo) && trim($despesas_acessorias_tipo) !== '' ? $despesas_acessorias_tipo : 'Despesas acessórias (transporte e instalação):';
            // Total final: subtotal por dia * dias - desconto + despesas
            $computedTotal = ($displaySubtotal * max(1, $dias)) - $descontoVal + $despesasVal;
            // Se controller passou 'total' explicitamente, respeitar; caso contrário usar calculado
            $totalToShow = isset($total) ? floatval($total) : $computedTotal;
        ?>
        <tr>
            <td><strong>Subtotal (por dia):</strong></td>
            <td>R$ <?= number_format($displaySubtotal, 2, ',', '.') ?></td>
        </tr>
        <tr>
            <td><strong>Quantidade de Dias:</strong></td>
            <td><?= htmlspecialchars($quantidade_dias ?? 1) ?></td>
        </tr>
        <?php if ($descontoVal > 0): ?>
        <tr>
            <td><strong>Desconto:</strong></td>
            <td>R$ <?= number_format($descontoVal, 2, ',', '.') ?> <?php if (isset($desconto_percent) && floatval($desconto_percent) > 0): ?>(<?= number_format($desconto_percent, 2, ',', '.') ?>%)<?php endif; ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td><strong><?= htmlspecialchars($despesasLabel) ?></strong></td>
            <td>R$ <?= number_format($despesasVal, 2, ',', '.') ?></td>
        </tr>
        <tr>
            <td><strong>Total:</strong></td>
            <td>R$ <?= number_format($totalToShow, 2, ',', '.') ?></td>
        </tr>
    </table>
    <br>
    <!-- Observações e serviços (mantém juntas para evitar quebra de página) -->
    <div style="margin-top: 2px;">
        <p style="font-size: 12px; margin: 0 0 6px 0;"><strong>Observações:</strong></p>
        <ul style="font-size: 12px; margin-top: 6px;">
            <li>O contratante deverá garantir infraestrutura elétrica adequada para operação simultânea dos climatizadores.</li>
            <li>Caso haja necessidade de permanência dos equipamentos além do período contratado, será cobrada diária adicional.</li>
            <li>A quantidade de climatizadores e distribuição (localização) dos mesmos durante o evento, podem ser alterados conforme vontade da contratante. Cabendo apenas ajustes dos valores monetários conforme alterações realizadas.</li>
            <li>Em caso de cancelamento do evento, aplica-se multa de 20% (vinte por cento) sobre o valor total do contrato.</li>
        </ul>

        <p style="font-size: 12px; margin-top: 10px;"><strong>Serviços Inclusos na Locação:</strong></p>
        <ul style="font-size: 12px; margin-top: 10px;">
            <li>Entrega e retirada dos equipamentos no local do evento;</li>
            <li>Instalação e posicionamento técnico dos climatizadores;</li>
            <li>Orientação quanto ao uso adequado dos equipamentos;</li>
        </ul>

        <p style="font-size: 12px; margin-top: 10px;"><strong>Condições de pagamento:</strong></p>
        <ul style="font-size: 12px; margin-top: 10px;">
            <li>50% no ato da contratação;</li>
            <li>50% restantes até o dia do evento.</li>
        </ul>
    </div>
    
    <!-- Seção de Faturamento -->
    <div class="avoid-break" style="margin-top: 20px; background-color: white !important;">
        <p style="font-size: 12px; margin: 0 0 10px 0;"><strong>Informações para Faturamento:</strong></p>
        <table width="100%" style="font-size: 12px; border: 1px solid #ccc;">
            <tr style="background-color: #fff;">
                <td style="padding: 8px;"><strong>Empresa:</strong></td>
                <td style="padding: 8px;">DFD TREINAMENTOS LTDA</td>
            </tr>
            <tr>
                <td style="padding: 8px;"><strong>CNPJ:</strong></td>
                <td style="padding: 8px;">14.991.307/0001-06</td>
            </tr>
            <tr style="background-color: #fff;">
                <td style="padding: 8px;"><strong>E-mail:</strong></td>
                <td style="padding: 8px;">contato@imperialclima.com.br</td>
            </tr>
            <tr>
                <td style="padding: 8px;"><strong>Telefone:</strong></td>
                <td style="padding: 8px;">(85) 99111-0955</td>
            </tr>
        </table>
    </div>

    <!-- Seção de Forma de Pagamento -->
    <div class="avoid-break" style="margin-top: 20px; background-color: white !important;">
        <p style="font-size: 12px; margin: 0 0 10px 0;"><strong>Forma de Pagamento:</strong></p>
        <table width="100%" style="font-size: 12px; border: 1px solid #ccc;">
            <tr style="background-color: #fff;">
                <td style="padding: 8px;"><strong>Método:</strong></td>
                <td style="padding: 8px;">PIX</td>
            </tr>
            <tr>
                <td style="padding: 8px;"><strong>Chave PIX (CNPJ):</strong></td>
                <td style="padding: 8px;">14.991.307/0001-06</td>
            </tr>
            <tr style="background-color: #fff;">
                <td style="padding: 8px;"><strong>Recebedor:</strong></td>
                <td style="padding: 8px;">DFD TREINAMENTOS LTDA</td>
            </tr>
            <tr>
                <td style="padding: 8px;"><strong>Banco:</strong></td>
                <td style="padding: 8px;">Inter</td>
            </tr>
        </table>
    </div>

    <!-- Rodapé compartilhado (omitível para contrato) -->
    <?php if (!isset($isContrato) || !$isContrato): ?>
    <!-- Data/Hora do orçamento: exibida acima do aviso de validade -->
    <p style="font-size: 12px; text-align: center; margin-top: 12px;">
        <strong>Data:</strong> <?= htmlspecialchars($dataOrcamento) ?>
    </p>
    <p style="font-size: 12px; text-align: center; margin-top: 6px;">
        Este orçamento é válido por 10 dias. Sujeito à disponibilidade de equipamentos.<br>
        Imperial Clima
    </p>
    <?php endif; ?>