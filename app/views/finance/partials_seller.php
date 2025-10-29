<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($title ?? 'Relatório do Vendedor') ?></title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 12px; }
    h2 { margin: 0 0 8px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
    th { background: #f5f5f5; }
    .right { text-align: right; }
    .muted { color: #666; font-size: 11px; }
  </style>
</head>
<body>
  <h2>Relatório de Desempenho do Vendedor</h2>
  <div class="muted">Período: <?= htmlspecialchars($from_ ?? '') ?> a <?= htmlspecialchars($to_ ?? '') ?> | Câmbio: 1 USD = R$ <?= number_format((float)($rate_ ?? 0), 2) ?></div>

  <?php if (!$mine_): ?>
    <p>Sem dados para o vendedor no período.</p>
  <?php else: ?>
    <table>
      <tbody>
        <tr><th>Vendedor</th><td><?= htmlspecialchars($mine_['user']['name'] ?? '') ?></td></tr>
        <tr><th>Função</th><td><?= htmlspecialchars($mine_['user']['role'] ?? '') ?></td></tr>
        <tr><th>Bruto (USD)</th><td class="right">$ <?= number_format((float)($mine_['bruto_total'] ?? 0), 2) ?></td></tr>
        <tr><th>Líquido (USD)</th><td class="right">$ <?= number_format((float)($mine_['liquido_total'] ?? 0), 2) ?></td></tr>
        <tr><th>Custo Alocado (USD)</th><td class="right">$ <?= number_format((float)($mine_['allocated_cost'] ?? 0), 2) ?></td></tr>
        <tr><th>Líquido Apurado (USD)</th><td class="right">$ <?= number_format((float)($mine_['liquido_apurado'] ?? 0), 2) ?></td></tr>
        <tr><th>Comissão Individual (USD)</th><td class="right">$ <?= number_format((float)($mine_['comissao_individual'] ?? 0), 2) ?></td></tr>
        <tr><th>Bônus (USD)</th><td class="right">$ <?= number_format((float)($mine_['bonus'] ?? 0), 2) ?></td></tr>
        <tr><th>Comissão Final (USD)</th><td class="right">$ <?= number_format((float)($mine_['comissao_final'] ?? 0), 2) ?></td></tr>
      </tbody>
    </table>

    <h3>Contexto da Equipe</h3>
    <table>
      <tbody>
        <tr><th>Bruto Equipe (USD)</th><td class="right">$ <?= number_format((float)($team_['team_bruto_total'] ?? 0), 2) ?></td></tr>
        <tr><th>Meta (BRL)</th><td class="right">R$ <?= number_format((float)($team_['meta_equipe_brl'] ?? 0), 2) ?></td></tr>
        <tr><th>Ativos p/ Bônus</th><td class="right"><?= (int)($team_['active_count'] ?? 0) ?></td></tr>
        <tr><th>Rate do Bônus</th><td class="right"><?= number_format((float)($team_['bonus_rate'] ?? 0)*100, 2) ?>%</td></tr>
      </tbody>
    </table>
  <?php endif; ?>
</body>
</html>
