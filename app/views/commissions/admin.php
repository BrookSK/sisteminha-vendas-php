<?php /** @var array $items */ /** @var array|null $team */ /** @var float $goal */ /** @var array $chartLabels */ /** @var array $chartData */ /** @var array $historyLabels */ /** @var array $historyTotals */ ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Comissões (Admin)</h3>
    <form method="post" action="/admin/commissions/recalc" class="d-inline">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Core\Auth::csrf()) ?>">
      <input type="month" name="period" value="<?= htmlspecialchars($period ?? ($_GET['period'] ?? date('Y-m'))) ?>" class="form-control d-inline" style="width:auto; display:inline-block">
      <button class="btn btn-primary ms-2" type="submit">Recalcular Comissões</button>
    </form>
    <a class="btn btn-outline-secondary" href="/admin/commissions/export?period=<?= urlencode($period ?? ($_GET['period'] ?? date('Y-m'))) ?>">Exportar CSV</a>
  </div>

  <form class="row g-2 mb-3" method="get" action="/admin/commissions">
    <div class="col-auto">
      <label class="form-label">Mensal</label>
      <input type="month" name="period" value="<?= htmlspecialchars($period ?? ($_GET['period'] ?? date('Y-m'))) ?>" class="form-control">
    </div>
    <div class="col-auto align-self-end">
      <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
    </div>
  </form>

  <?php if ($team): ?>
  <div class="row mb-3">
    <div class="col">
      <div class="p-3 bg-light border rounded">
        <div class="d-flex justify-content-between">
          <div>
            <div class="fw-bold">Meta de equipe</div>
            <div>US$ <?= number_format($goal, 2) ?></div>
          </div>
          <div class="text-end">
            <div class="fw-bold">Bruto equipe</div>
            <div>US$ <?= number_format($team['team_bruto_total'] ?? 0, 2) ?></div>
            <?php if (isset($team['team_bruto_total_brl'])): ?>
              <div class="small text-muted">BRL R$ <?= number_format((float)$team['team_bruto_total_brl'], 2) ?></div>
            <?php endif; ?>
          </div>
        </div>
        <div class="mt-2">
          <?php $pct = min(100, max(0, ($team['team_bruto_total'] ?? 0) / $goal * 100)); ?>
          <div class="progress" style="height: 12px;">
            <div class="progress-bar <?= ($pct>=100?'bg-success':'') ?>" role="progressbar" style="width: <?= $pct ?>%;"></div>
          </div>
          <small class="text-muted">Progresso: <?= round($pct,1) ?>%</small>
          <?php $falta = max(0, ($goal - (float)($team['team_bruto_total'] ?? 0))); ?>
          <small class="text-muted ms-2">Falta: US$ <?= number_format($falta, 2) ?></small>
          <?php if (isset($team['equal_cost_share_per_active_seller'])): ?>
            <span class="badge text-bg-info ms-2">Imposto (15%) por não-trainee ativo: US$ <?= number_format((float)$team['equal_cost_share_per_active_seller'], 2) ?></span>
          <?php endif; ?>
          <?php if (isset($team['explicit_cost_share_per_non_trainee'])): ?>
            <span class="badge text-bg-warning ms-2">Custo explícito por não-trainee: US$ <?= number_format((float)$team['explicit_cost_share_per_non_trainee'], 2) ?></span>
          <?php endif; ?>
          <?php if (isset($team['company_cash_usd'])): ?>
            <span class="badge text-bg-success ms-2">Caixa Empresa: US$ <?= number_format((float)$team['company_cash_usd'], 2) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="table-responsive mb-4">
    <table class="table table-striped align-middle">
        <tr>
          <th>Vendedor</th>
          <th>Status</th>
          <th class="text-end">Bruto (USD)</th>
          <th class="text-end">Líquido (USD)</th>
          <?php if (isset($items[0]['bruto_total_brl'])): ?>
            <th class="text-end">Bruto (BRL)</th>
            <th class="text-end">Líquido (BRL)</th>
          <?php endif; ?>
          <?php if (isset($items[0]['allocated_cost']) || isset($items[0]['allocated_cost_brl'])): ?>
            <th class="text-end">Custo Rateado (USD)</th>
            <?php if (isset($items[0]['allocated_cost_brl'])): ?><th class="text-end">Custo Rateado (BRL)</th><?php endif; ?>
          <?php endif; ?>
          <th class="text-end">Comissão Individual (USD)</th>
          <th class="text-end">Bônus (USD)</th>
          <th class="text-end">Comissão Final (USD)</th>
          <th class="text-end">Comissão Final (BRL)</th>
          <?php if (isset($items[0]['comissao_individual_brl'])): ?>
            <th class="text-end">Comissão Individual (BRL)</th>
            <th class="text-end">Bônus (BRL)</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= htmlspecialchars($it['name'] ?? ($it['user']['name'] ?? '')) ?></td>
          <td>
            <?php $active = (int)($it['ativo'] ?? ($it['user']['ativo'] ?? 0)) === 1; ?>
            <span class="badge <?= $active?'bg-success':'bg-secondary' ?>">&nbsp;<?= $active?'Ativo':'Inativo' ?></span>
          </td>
          <td class="text-end"><?= number_format((float)($it['bruto_total'] ?? 0), 2) ?></td>
          <td class="text-end"><?= number_format((float)($it['liquido_total'] ?? 0), 2) ?></td>
          <?php if (isset($it['bruto_total_brl'])): ?>
            <td class="text-end"><?= number_format((float)$it['bruto_total_brl'], 2) ?></td>
            <td class="text-end"><?= number_format((float)($it['liquido_total_brl'] ?? 0), 2) ?></td>
          <?php endif; ?>
          <?php if (isset($it['allocated_cost']) || isset($it['allocated_cost_brl'])): ?>
            <td class="text-end"><?= number_format((float)($it['allocated_cost'] ?? 0), 2) ?></td>
            <?php if (isset($it['allocated_cost_brl'])): ?><td class="text-end"><?= number_format((float)$it['allocated_cost_brl'], 2) ?></td><?php endif; ?>
          <?php endif; ?>
          <td class="text-end"><?= number_format((float)($it['comissao_individual'] ?? 0), 2) ?></td>
          <td class="text-end"><?= number_format((float)($it['bonus'] ?? 0), 2) ?></td>
          <td class="text-end fw-bold"><?= number_format((float)($it['comissao_final'] ?? 0), 2) ?></td>
          <?php 
            $finalBrl = isset($it['comissao_final_brl'])
              ? (float)$it['comissao_final_brl']
              : ((float)($it['comissao_final'] ?? 0) * (float)($usdRate ?? 0));
          ?>
          <td class="text-end fw-bold"><?= number_format((float)$finalBrl, 2) ?></td>
          <?php if (isset($it['comissao_individual_brl'])): ?>
            <td class="text-end"><?= number_format((float)$it['comissao_individual_brl'], 2) ?></td>
            <td class="text-end"><?= number_format((float)$it['bonus_brl'], 2) ?></td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="row">
    <div class="col-md-6 mb-4">
      <div class="card">
        <div class="card-header">Comissões Individuais</div>
        <div class="card-body">
          <canvas id="chartBar"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-6 mb-4">
      <div class="card">
        <div class="card-header">Evolução Mensal (Total)</div>
        <div class="card-body">
          <canvas id="chartLine"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const barCtx = document.getElementById('chartBar');
new Chart(barCtx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($chartLabels) ?>,
    datasets: [{ label: 'Comissão Final (USD)', data: <?= json_encode($chartData) ?>, backgroundColor: '#0d6efd' }]
  },
  options: { responsive: true, scales: { y: { beginAtZero: true } } }
});
const lineCtx = document.getElementById('chartLine');
new Chart(lineCtx, {
  type: 'line',
  data: {
    labels: <?= json_encode($historyLabels) ?>,
    datasets: [{ label: 'Total Comissões (USD)', data: <?= json_encode($historyTotals) ?>, fill: false, borderColor: '#198754' }]
  },
  options: { responsive: true, scales: { y: { beginAtZero: true } } }
});
</script>
