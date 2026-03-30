<?php
/**
 * Template: Relatório de Locações
 * Variáveis esperadas:
 * - $periodo_inicio, $periodo_fim (YYYY-MM-DD)
 * - $status (opcional), $cliente_id (opcional)
 * - $registros (array de locações)
 * - $total_locacoes, $soma_total, $por_status (array)
 * - $gerado_em (string d/m/Y H:i)
 */

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function dt($iso){ if(!$iso) return '—'; try { $d = new DateTime($iso); return $d->format('d/m/Y H:i'); } catch(Exception $e){ return $iso; } }
function moneyBR($v){ $n = (float)$v; return 'R$ ' . number_format($n, 2, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Relatório de Locações</title>
<style>
  body { font-family: Arial, sans-serif; font-size: 12px; color: #222; }
  .header { text-align:center; margin-bottom: 12px; }
  .header h1 { font-size: 18px; margin: 0; }
  .meta { text-align:center; color:#555; margin-bottom: 12px; }
  .filters { font-size: 11px; color:#444; text-align:center; margin-bottom: 8px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #e3e3e3; padding: 6px 8px; }
  th { background: #f7f9fc; text-align: left; }
  tfoot td { font-weight: bold; }
  .right { text-align: right; }
  .muted { color:#666; }
</style>
</head>
<body>
  <div class="header">
    <h1>Relatório de Locações</h1>
    <div class="meta">Gerado em: <?php echo e($gerado_em); ?></div>
  </div>
  <div class="filters">
    Período: <strong><?php echo e(date('d/m/Y', strtotime($periodo_inicio))); ?></strong>
    a <strong><?php echo e(date('d/m/Y', strtotime($periodo_fim))); ?></strong>
    <?php if (!empty($status)): ?> | Status: <strong><?php echo e($status); ?></strong><?php endif; ?>
    <?php if (!empty($cliente_id)): ?> | Cliente ID: <strong><?php echo e($cliente_id); ?></strong><?php endif; ?>
  </div>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Cliente</th>
        <th>Contato</th>
        <th>Climatizador</th>
        <th>Início</th>
        <th>Fim</th>
        <th>Dias</th>
        <th>Qtd</th>
        <th class="right">Diária</th>
        <th class="right">Total</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($registros)): ?>
        <tr><td colspan="11" class="muted">Nenhuma locação encontrada no período.</td></tr>
      <?php else: ?>
        <?php foreach ($registros as $i => $r): ?>
          <tr>
            <td><?php echo $i+1; ?></td>
            <td><?php echo e($r['cliente_nome'] ?? ''); ?></td>
            <td><?php echo e($r['cliente_telefone'] ?? ''); ?></td>
            <td><?php echo e(trim(($r['climatizador_modelo'] ?? '') . ' ' . ($r['climatizador_capacidade'] ?? ''))); ?></td>
            <td><?php echo dt($r['data_inicio'] ?? ''); ?></td>
            <td><?php echo dt($r['data_fim'] ?? ''); ?></td>
            <td><?php echo e($r['quantidade_dias'] ?? ''); ?></td>
            <td><?php echo e($r['quantidade_climatizadores'] ?? ''); ?></td>
            <td class="right"><?php echo moneyBR($r['valor_diaria'] ?? 0); ?></td>
            <td class="right"><?php echo moneyBR($r['valor_total'] ?? 0); ?></td>
            <td><?php echo e($r['status'] ?? ''); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="9">Total de Locações</td>
        <td class="right" colspan="1"><?php echo moneyBR($soma_total); ?></td>
        <td><?php echo e($total_locacoes); ?></td>
      </tr>
    </tfoot>
  </table>

  <div class="muted" style="margin-top:10px;">
    Por status:
    <?php foreach(($por_status ?? []) as $k=>$v): ?>
      <span style="margin-right:8px;"><?php echo e($k); ?>: <strong><?php echo e($v); ?></strong></span>
    <?php endforeach; ?>
  </div>
</body>
</html>
