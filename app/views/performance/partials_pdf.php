<?php
$usd = function($v){ return '$ ' . number_format((float)$v, 2, ',', '.'); };
$int = function($v){ return number_format((int)$v, 0, ',', '.'); };
$top = $d['top_clients'] ?? [];
?><!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Desempenho - <?= htmlspecialchars($d['user']['name'] ?? $d['user']['email'] ?? 'Usuário') ?></title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 12px; }
    h1 { font-size: 18px; margin: 0 0 8px; }
    h2 { font-size: 14px; margin: 16px 0 8px; }
    .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .card { border: 1px solid #ccc; padding: 8px; border-radius: 6px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border-bottom: 1px solid #eee; padding: 6px; text-align: left; }
    th { background: #f6f6f6; }
    .text-right { text-align: right; }
  </style>
</head>
<body>
  <h1>Desempenho Individual</h1>
  <div><strong>Usuário:</strong> <?= htmlspecialchars($d['user']['name'] ?? $d['user']['email'] ?? 'Usuário') ?></div>
  <div><strong>Período:</strong> <?= htmlspecialchars($from_ ?? '') ?> a <?= htmlspecialchars($to_ ?? '') ?></div>

  <h2>Métricas</h2>
  <div class="grid">
    <div class="card"><div>Bruto do vendedor</div><div><strong><?= $usd($d['bruto_total_usd'] ?? 0) ?></strong></div></div>
    <div class="card"><div>Líquido do vendedor</div><div><strong><?= $usd($d['liquido_total_usd'] ?? 0) ?></strong></div></div>
    <div class="card"><div>Líquido apurado</div><div><strong><?= $usd($d['liquido_apurado_usd'] ?? 0) ?></strong></div></div>
    <div class="card"><div>Comissão</div><div><strong><?= $usd($d['comissao_usd'] ?? 0) ?></strong></div></div>
    <div class="card"><div>Vendas no período</div><div><strong><?= $int($d['sales_count'] ?? 0) ?></strong></div></div>
    <div class="card"><div>Atendimentos</div><div>Total: <strong><?= $int($d['att_total'] ?? 0) ?></strong> · Concluídos: <strong><?= $int($d['att_done'] ?? 0) ?></strong></div></div>
  </div>

  <h2>Top 5 clientes</h2>
  <?php if (empty($top)): ?>
    <div>Sem clientes no período.</div>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Cliente</th>
          <th class="text-right">Vendas</th>
          <th class="text-right">Bruto (USD)</th>
          <th class="text-right">Líquido (USD)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($top as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['cliente_nome'] ?? ('#'.$row['cliente_id'])) ?></td>
            <td class="text-right"><?= $int($row['total_vendas'] ?? 0) ?></td>
            <td class="text-right"><?= $usd($row['total_bruto_usd'] ?? 0) ?></td>
            <td class="text-right"><?= $usd($row['total_liquido_usd'] ?? 0) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</body>
</html>
