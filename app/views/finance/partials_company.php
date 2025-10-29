<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($title ?? 'Financeiro - Empresa') ?></title>
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
  <?php $team = $comm_['team'] ?? []; ?>
  <h2>Relatório Financeiro (Empresa)</h2>
  <div class="muted">Período: <?= htmlspecialchars($from_ ?? '') ?> a <?= htmlspecialchars($to_ ?? '') ?> | Câmbio: 1 USD = R$ <?= number_format((float)($rate_ ?? 0), 2) ?> | Custo Global: <?= number_format((float)($cost_rate_ ?? 0)*100,2) ?>%</div>

  <table>
    <tbody>
      <tr><th>Company Cash (USD)</th><td class="right">$ <?= number_format((float)($team['company_cash_usd'] ?? 0), 2) ?></td></tr>
      <tr><th>Bruto Equipe (USD)</th><td class="right">$ <?= number_format((float)($team['team_bruto_total'] ?? 0), 2) ?></td></tr>
      <tr><th>Líquido Rateado (USD)</th><td class="right">$ <?= number_format((float)($team['sum_rateado_usd'] ?? 0), 2) ?></td></tr>
      <tr><th>Comissões (USD)</th><td class="right">$ <?= number_format((float)($team['sum_commissions_usd'] ?? 0), 2) ?></td></tr>
      <tr><th>Custos Totais (USD)</th><td class="right">$ <?= number_format((float)($team['team_cost_total'] ?? 0), 2) ?></td></tr>
    </tbody>
  </table>

  <h3>Cobertura de Custos</h3>
  <?php 
    $bruto = (float)($team['team_bruto_total'] ?? 0);
    $settingsRate = (float)($team['team_cost_settings_rate'] ?? 0);
    $settingsVal = $bruto * $settingsRate;
    $percRate = (float)($team['team_cost_percent_rate'] ?? 0);
    $percVal = $bruto * $percRate;
  ?>
  <table>
    <thead><tr><th>Tipo</th><th>Descrição</th><th class="right">Valor</th></tr></thead>
    <tbody>
      <tr><td>Settings (global)</td><td><?= number_format($settingsRate*100,2) ?>% × US$ <?= number_format($bruto,2) ?></td><td class="right">$ <?= number_format($settingsVal, 2) ?></td></tr>
      <tr><td>Percentuais explícitos</td><td><?= number_format($percRate*100,2) ?>% × US$ <?= number_format($bruto,2) ?></td><td class="right">$ <?= number_format($percVal, 2) ?></td></tr>
      <tr><td>Fixos (USD)</td><td>Custos fixos cadastrados</td><td class="right">$ <?= number_format((float)($team['team_cost_fixed_usd'] ?? 0), 2) ?></td></tr>
    </tbody>
  </table>

  <h3>Vendedores (Período)</h3>
  <table>
    <thead>
      <tr>
        <th>Vendedor</th>
        <th>Função</th>
        <th class="right">Bruto (USD)</th>
        <th class="right">Líquido (USD)</th>
        <th class="right">Custo Alocado (USD)</th>
        <th class="right">Líquido Apurado (USD)</th>
        <th class="right">Comissão Final (USD)</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($comm_['items'] ?? []) as $it): ?>
        <tr>
          <td><?= htmlspecialchars($it['user']['name'] ?? '') ?></td>
          <td><?= htmlspecialchars($it['user']['role'] ?? '') ?></td>
          <td class="right">$ <?= number_format((float)($it['bruto_total'] ?? 0), 2) ?></td>
          <td class="right">$ <?= number_format((float)($it['liquido_total'] ?? 0), 2) ?></td>
          <td class="right">$ <?= number_format((float)($it['allocated_cost'] ?? 0), 2) ?></td>
          <td class="right">$ <?= number_format((float)($it['liquido_apurado'] ?? 0), 2) ?></td>
          <td class="right">$ <?= number_format((float)($it['comissao_final'] ?? 0), 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
