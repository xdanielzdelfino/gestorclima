<?php
/**
 * Modelo de contrato específico para "CONTRATO DE LOCAÇÃO DE CLIMATIZADORES"
 * Este template produz um contrato no formato solicitado pelo usuário.
 * Ele usa variáveis passadas pelo controller/service e provê valores padrão
 * caso alguma variável não esteja definida (para evitar que o PDF saia vazio).
 */

// --- Helpers ---
function _esc($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function _float($v, $decimals = 2) { return number_format(floatval($v ?? 0), $decimals, ',', '.'); }
function _formatDatePt($raw)
{
	if (empty($raw)) return '';
	// tentar vários formatos comuns
	$dt = DateTime::createFromFormat('Y-m-d H:i:s', $raw) ?: DateTime::createFromFormat('Y-m-d\TH:i', $raw) ?: DateTime::createFromFormat('Y-m-d', $raw);
	if (!$dt) return _esc($raw);
	$months = [1=> 'janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
	$d = intval($dt->format('d'));
	$m = intval($dt->format('n'));
	$y = $dt->format('Y');
	$hour = $dt->format('H');
	$minute = $dt->format('i');
	// formato: 05 de dezembro de 2025, às 13h (quando minuto == 00 mostramos apenas hora)
	$hora = ($minute === '00') ? sprintf('%dh', intval($hour)) : sprintf('%s:%sh', $hour, $minute);
	return sprintf('%02d de %s de %s, às %s', $d, $months[$m], $y, $hora);
}

/**
 * Formata e rotula CPF/CNPJ conforme o número informado.
 * - Se 11 dígitos -> CPF (000.000.000-00)
 * - Se 14 dígitos -> CNPJ (00.000.000/0000-00)
 * - Caso contrário retorna o texto original e rótulo genérico
 */
function _onlyDigits($v) { return preg_replace('/\D+/', '', (string)$v); }
function _cpfCnpjLabel($raw) {
	$d = _onlyDigits($raw);
	if (strlen($d) === 11) return 'CPF';
	if (strlen($d) === 14) return 'CNPJ';
	return 'CPF/CNPJ';
}
function _formatCpfCnpj($raw) {
	$d = _onlyDigits($raw);
	if (strlen($d) === 11) {
		return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '\\1.\\2.\\3-\\4', $d);
	}
	if (strlen($d) === 14) {
		return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '\\1.\\2.\\3/\\4-\\5', $d);
	}
	return $raw;
}

// --- Valores e defaults ---
// Locador (mantém defaults conforme exemplo)
$locador_nome = 'Imperial Clima';
$locador_razao = 'DFD Treinamentos LTDA';
$locador_cnpj = '14.991.307/0001-06';
$locador_endereco = 'Av. General Osorio de Paiva, 7665, Siqueira 2, Fortaleza-CE, CEP 60731-335';
$locador_telefone = '85-98661-3750';
$locador_email = '';

// Normalização das variáveis do locatário/cliente — aceitar chaves vindas do controller/script
$locatario_nome = $locatario ?? $cliente ?? $cliente_nome ?? ($cliente_nome ?? null);
$locatario_cnpj = $locatario_cnpj ?? $cliente_cpf_cnpj ?? $cpf_cnpj ?? '';
$locatario_endereco = $locatario_endereco ?? $cliente_endereco ?? $endereco ?? '';
$responsavel = $responsavel ?? $contato ?? $cliente_responsavel ?? '';
$locatario_telefone = $locatario_telefone ?? $cliente_telefone ?? $telefone ?? '';

$obj_quantidade = isset($quantidade) ? intval($quantidade) : (isset($itens) && is_array($itens) ? array_sum(array_column($itens,'quantidade')) : 10);
$obj_descricao = $obj_descricao ?? ($itens[0]['descricao'] ?? 'Climatizadores Umidificadores de Ar Industrial Ultra ar 80 Double');

$data_inicio = $data_inicio ?? $inicio ?? ($itens[0]['data_inicio'] ?? '');
$data_fim = $data_fim ?? $fim ?? ($itens[0]['data_fim'] ?? '');
$local_evento = $local_evento ?? ($evento_local ?? 'Colosso');

$valor_unitario = $valor_unitario ?? ($itens[0]['valor_unitario'] ?? 260.00);
// Quantidade de dias (padrão 1) — importante para calcular corretamente o subtotal
$quantidade_dias = isset($quantidade_dias) ? intval($quantidade_dias) : (isset($itens[0]['quantidade_dias']) ? intval($itens[0]['quantidade_dias']) : 1);

// Subtotal deve considerar: valor unitário * quantidade de unidades * quantidade de dias
$subtotal = isset($subtotal) ? floatval($subtotal) : ($valor_unitario * $obj_quantidade * max(1, $quantidade_dias));
$desconto = isset($desconto) ? floatval($desconto) : 0.0;
$despesas_acessorias = isset($despesas_acessorias) ? floatval($despesas_acessorias) : 450.00;
$total = isset($total) ? floatval($total) : ($subtotal - $desconto + $despesas_acessorias);

// Rótulo customizável para despesas acessórias (padrão se não informado)
$despesasLabel = isset($despesas_acessorias_tipo) && trim($despesas_acessorias_tipo) !== '' ? $despesas_acessorias_tipo : 'Despesas acessórias (transporte, instalação e suporte):';

?>
<!doctype html>
<html>
<head>
	<meta charset="utf-8" />
	<title>CONTRATO DE LOCAÇÃO DE CLIMATIZADORES</title>
	<style>
		body { font-family: Arial, Helvetica, sans-serif; font-size: 12pt; color: #111; margin: 30px; }
		h1 { text-align: center; font-size: 16pt; margin-bottom: 18px; }
		p, li { line-height: 1.35; }
		.section { margin-top: 12px; }
		.two-cols { display:flex; gap:20px; }
		.col { flex:1 }
		table.values { width: 100%; border-collapse: collapse; margin-top:8px; }
		table.values td { padding:6px 4px; }
		.right { text-align: right; }
		.sign { margin-top: 60px; }
	</style>
</head>
<body>

<!-- Cabeçalho com logomarca (preservar logomarca Imperial Clima se passada pelo controller) -->
<div style="text-align:center; margin-bottom:10px;">
	<?php if (!empty($logoPath) && file_exists($logoPath)): ?>
		<img src="<?= htmlspecialchars($logoPath) ?>" alt="Imperial Clima" style="max-width:200px; display:block; margin: 0 auto 8px;" />
	<?php endif; ?>
	<h1>CONTRATO DE LOCAÇÃO DE CLIMATIZADORES</h1>
</div>

<div class="section">
	<strong>Locador:</strong> <?= _esc($locador_nome) ?><br>
	<strong>Razão Social:</strong> <?= _esc($locador_razao) ?><br>
	<strong>CNPJ:</strong> <?= _esc($locador_cnpj) ?><br>
	<strong>Endereço:</strong> <?= _esc($locador_endereco) ?><br>
	<strong>Telefone/WhatsApp:</strong> <?= _esc($locador_telefone) ?><br>
</div>

<div class="section">
	<strong>Locatário:</strong> <?= _esc($locatario_nome) ?><br>
	<?php $rawId = $locatario_cnpj ?? ''; $idLabel = _cpfCnpjLabel($rawId); $idFormatted = _formatCpfCnpj($rawId); ?>
	<strong><?= $idLabel ?>:</strong> <?= _esc($idFormatted) ?><br>
	<strong>Endereço:</strong> <?= _esc($locatario_endereco) ?><br>
	<strong>Responsável:</strong> <?= _esc($responsavel) ?><br>
	<strong>Telefone/WhatsApp:</strong> <?= _esc($locatario_telefone) ?><br>
</div>

<div class="section">
	<strong>Objeto da Locação:</strong>
	<?php
	// Se houver múltiplos itens, compor a descrição juntando cada modelo/quantidade
	$totalUnidades = 0;
	$modelosParts = [];
	if (!empty($itens) && is_array($itens)) {
		foreach ($itens as $it) {
			$q = isset($it['quantidade']) ? intval($it['quantidade']) : 0;
			$descricaoItem = isset($it['descricao']) ? trim($it['descricao']) : '';
			if ($q > 0 && $descricaoItem !== '') {
				// Formato: '5 x ROTO PLAST 52' para maior clareza
				$modelosParts[] = sprintf('%d x %s', $q, htmlspecialchars($descricaoItem));
				$totalUnidades += $q;
			}
		}
	}
	// fallback para compatibilidade com versões antigas
	if (empty($modelosParts)) {
		$totalUnidades = isset($obj_quantidade) ? intval($obj_quantidade) : $totalUnidades;
		$modelosParts[] = sprintf('%d x %s', $totalUnidades, htmlspecialchars($obj_descricao ?? 'Climatizador'));
	}
	// juntar com ponto e vírgula para legibilidade
	$modelosJoined = implode('; ', $modelosParts);
	// frase mais clara: indicar total de unidades seguido da lista detalhada
	$unidadeLabel = ($totalUnidades === 1) ? 'unidade' : 'unidades';
	?>
	<p>Locação: <strong><?= htmlspecialchars((string)$totalUnidades) ?> <?= $unidadeLabel ?></strong> — <?= $modelosJoined ?>. Destinados à climatização do evento aberto.</p>
</div>

<?php if (!empty($data_inicio) || !empty($data_fim)): ?>
	<div class="section">
		<strong>Data e Horário do Evento:</strong>
		<ul>
			<?php if (!empty($data_inicio)): ?><li>Início: <?= _formatDatePt($data_inicio) ?></li><?php endif; ?>
			<?php if (!empty($data_fim)): ?><li>Término: <?= _formatDatePt($data_fim) ?></li><?php endif; ?>
		</ul>
	</div>
<?php endif; ?>

<div class="section">
	<strong>Local do Evento:</strong>
	<p><?= _esc($local_evento) ?></p>
</div>

<div class="section">
	<strong>Serviços Inclusos na Locação:</strong>
	<ul>
		<li>Entrega e retirada dos equipamentos no local do evento;</li>
		<li>Instalação e posicionamento técnico dos climatizadores;</li>
		<li>Orientação quanto ao uso adequado dos equipamentos;</li>
	</ul>
</div>

<div class="avoid-break" style="page-break-inside: avoid; break-inside: avoid;">
<div class="section">
	<strong>Valores e Condições Comerciais:</strong>
	<?php if (isset($itens) && is_array($itens) && count($itens) > 1): ?>
		<table class="values" style="width:100%; border-collapse: collapse; margin-top:8px;">
			<thead>
				<tr style="background:#f0f0f0;">
					<th style="text-align:left; padding:6px 4px;">Descrição</th>
					<th style="text-align:right; padding:6px 4px;">Qtd</th>
					<th style="text-align:right; padding:6px 4px;">Valor Diária</th>
					<th style="text-align:right; padding:6px 4px;">Total (por dia)</th>
				</tr>
			</thead>
			<tbody>
				<?php
					$calcSubtotal = 0.0;
					foreach ($itens as $it):
						$q = isset($it['quantidade']) ? intval($it['quantidade']) : 0;
						$vu = isset($it['valor_unitario']) ? floatval($it['valor_unitario']) : 0.0;
						$itTotal = $q * $vu;
						$calcSubtotal += $itTotal;
				?>
				<tr>
					<td style="padding:6px 4px;"><?= htmlspecialchars($it['descricao'] ?? '') ?></td>
					<td style="padding:6px 4px; text-align:right;"><?= htmlspecialchars($it['quantidade'] ?? 0) ?></td>
					<td style="padding:6px 4px; text-align:right;">R$ <?= number_format($vu,2,',','.') ?></td>
					<td style="padding:6px 4px; text-align:right;">R$ <?= number_format($itTotal,2,',','.') ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<br>
		<?php
			$dias = isset($quantidade_dias) ? intval($quantidade_dias) : 1;
			$descontoVal = isset($desconto) ? floatval($desconto) : 0.0;
			$despesasVal = isset($despesas_acessorias) ? floatval($despesas_acessorias) : 0.0;
			$computedTotal = ($calcSubtotal * max(1, $dias)) - $descontoVal + $despesasVal;
		?>
		<table class="values">
			<tr><td>Subtotal (por dia):</td><td class="right">R$ <?= number_format($calcSubtotal,2,',','.') ?></td></tr>
			<tr><td>Quantidade de Dias:</td><td class="right"><?= htmlspecialchars($dias) ?> dia(s)</td></tr>
			<?php if ($descontoVal > 0): ?><tr><td>Desconto:</td><td class="right">R$ <?= number_format($descontoVal,2,',','.') ?></td></tr><?php endif; ?>
			<tr><td><?= htmlspecialchars($despesasLabel) ?></td><td class="right">R$ <?= number_format($despesasVal,2,',','.') ?></td></tr>
			<tr><td><strong>Valor total da locação:</strong></td><td class="right"><strong>R$ <?= number_format($computedTotal,2,',','.') ?></strong></td></tr>
		</table>
	<?php else: ?>
		<table class="values">
			<tr>
				<td>Valor unitário de locação (por equipamento, por dia):</td>
				<td class="right">R$ <?= _float($valor_unitario) ?></td>
			</tr>
			<tr>
				<td>Quantidade de dias:</td>
				<td class="right"><?= _esc($quantidade_dias) ?> dia(s)</td>
			</tr>
			<tr>
				<td>Quantidade de unidades:</td>
				<td class="right"><?= _esc($obj_quantidade) ?> unidades</td>
			</tr>
			<tr>
				<td>Subtotal:</td>
				<td class="right">R$ <?= _float($subtotal) ?></td>
			</tr>
			<?php if (!empty($desconto) && floatval($desconto) > 0): ?>
			<tr>
				<td>Desconto:</td>
				<td class="right">R$ <?= _float($desconto) ?></td>
			</tr>
			<?php endif; ?>
			<tr>
				<td><?= htmlspecialchars($despesasLabel) ?></td>
				<td class="right">R$ <?= _float($despesas_acessorias) ?></td>
			</tr>
			<tr>
				<td><strong>Valor total da locação:</strong></td>
				<td class="right"><strong>R$ <?= _float($total) ?></strong></td>
			</tr>
		</table>
	<?php endif; ?>
	</div>

	<div class="section">
		<strong>Condições de pagamento:</strong>
		<ul>
			<li>50% no ato da contratação;</li>
			<li>50% restantes até o dia do evento.</li>
		</ul>
	</div>

	<div class="section">
		<strong>Observações:</strong>
		<?php if (!empty($observacoes)): ?>
			<p><?= nl2br(_esc($observacoes)) ?></p>
		<?php else: ?>
			<ol>
				<li>O contratante deverá garantir infraestrutura elétrica adequada para operação simultânea dos climatizadores.</li>
				<li>Caso haja necessidade de permanência dos equipamentos além do período contratado, será cobrada diária adicional.</li>
				<li>A quantidade de climatizadores e distribuição (localização) dos mesmos durante o evento, podem ser alterados conforme vontade da contratante. Cabendo apenas ajustes dos valores monetários conforme alterações realizadas.</li>
				<li>Em caso de cancelamento do evento, aplica-se multa de 20% (vinte por cento) sobre o valor total do contrato.</li>
			</ol>
		<?php endif; ?>
	</div>

	<div class="section" style="margin-top:30px;">
		<p>Fortaleza/CE, <?= _formatDatePt((new DateTime())->format('Y-m-d H:i:s')) ?>
		</p>
	</div>

	<div class="sign">
		<table width="100%">
			<tr>
				<td style="width:50%; text-align:center;">________________________________________________________<br>DFD Treinamentos LTDA – Locador</td>
				<td style="width:50%; text-align:center;">________________________________________________________<br><?= _esc($responsavel) ?> – Locatário</td>
			</tr>
		</table>
	</div>

</div>

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
			// Características técnicas completas (vindo do cadastro). Se não existir coluna específica,
			// usamos `descricao` como fallback para compatibilidade.
			$caracteristicas = isset($it['caracteristicas']) && trim((string)$it['caracteristicas']) !== ''
				? trim($it['caracteristicas'])
				: (isset($it['descricao']) ? trim($it['descricao']) : '');
			include __DIR__ . '/_document_image_page.php';
			// mostrar características conforme o cadastro (sem fallback)
			$m = strtoupper($caracteristicas);
	?>
			<div style="margin-top:8px; font-size:12px; line-height:1.25; page-break-inside: avoid;">
				<h3 style="margin-bottom:8px;">Características Técnicas básicas:</h3>
				<ul style="margin:0 0 0 18px; padding:0;">
					<?php
					// Usar exclusivamente as características do cadastro quando fornecida, preservando linhas
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
