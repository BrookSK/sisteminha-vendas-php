<?php /** @var array|null $mine */ /** @var array $team */ /** @var float $goal */ /** @var array $historyLabels */ /** @var array $historyValues */ ?>
<div class="container py-3">
  <h3>Minhas Comissões</h3>

  <form class="row g-2 mb-3" method="get" action="/admin/commissions/me">
    <div class="col-auto">
      <label class="form-label">Período</label>
      <input type="month" name="period" value="<?= htmlspecialchars($period ?? ($_GET['period'] ?? date('Y-m'))) ?>" class="form-control">
    </div>
    <div class="col-auto align-self-end">
      <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
    </div>
  </form>

  <?php if ($mine): ?>
  <div class="row mb-3">
    <div class="col-md-4">
      <div class="p-3 bg-light border rounded">
        <div class="fw-bold">Bruto (USD)</div>
        <div>US$ <?= number_format($mine['bruto_total'] ?? 0, 2) ?></div>
        <?php if (isset($mine['bruto_total_brl'])): ?>
          <div class="small text-muted">BRL R$ <?= number_format((float)$mine['bruto_total_brl'], 2) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-md-4">
      <div class="p-3 bg-light border rounded">
        <div class="fw-bold">Líquido (após custos)</div>
        <?php $liqAp = (float)($mine['liquido_apurado'] ?? ($mine['liquido_total'] ?? 0)); $neg = ($liqAp < 0); ?>
        <div class="<?= $neg ? 'text-danger' : '' ?>">US$ <?= number_format($liqAp, 2) ?></div>
        <?php if (isset($mine['liquido_apurado_brl'])): ?>
          <div class="small <?= $neg ? 'text-danger' : 'text-muted' ?>">BRL R$ <?= number_format((float)$mine['liquido_apurado_brl'], 2) ?></div>
        <?php endif; ?>
        <?php if ($neg): ?>
          <div class="small text-danger mt-1">Você iniciou o mês no negativo após o rateio dos custos.</div>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-md-4">
      <div class="p-3 bg-light border rounded">
        <div class="fw-bold">Comissão Final (USD)</div>
        <div>US$ <?= number_format($mine['comissao_final'] ?? 0, 2) ?></div>
        <?php if (isset($mine['comissao_final_brl'])): ?>
          <div class="small text-muted">BRL R$ <?= number_format((float)$mine['comissao_final_brl'], 2) ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="row mb-4">
    <div class="col-md-6">
      <div class="p-3 border rounded">
        <div><strong>Comissão Individual:</strong> US$ <?= number_format($mine['comissao_individual'] ?? 0, 2) ?></div>
        <div><strong>Bônus de Equipe:</strong> US$ <?= number_format($mine['bonus'] ?? 0, 2) ?></div>
        <?php if (isset($mine['percent_individual'])): ?>
          <div class="text-muted small">Percentual do vendedor: <?= number_format(((float)$mine['percent_individual'])*100, 0) ?>%<?php if (isset($team['bonus_rate'])): ?>  •  Bônus: <?= number_format(((float)$team['bonus_rate'])*100, 2) ?>%<?php endif; ?></div>
        <?php endif; ?>
        <?php if (isset($mine['comissao_individual_brl'])): ?>
          <div class="mt-2 text-muted small">BRL: Comissão Individual R$ <?= number_format((float)$mine['comissao_individual_brl'], 2) ?>, Bônus R$ <?= number_format((float)($mine['bonus_brl'] ?? 0), 2) ?></div>
        <?php endif; ?>
        <?php if (isset($mine['allocated_cost_brl'])): ?>
          <div class="mt-2 text-muted small">Custo rateado: US$ <?= number_format((float)($mine['allocated_cost'] ?? 0), 2) ?> (R$ <?= number_format((float)$mine['allocated_cost_brl'], 2) ?>)</div>
        <?php endif; ?>
        <?php $remVend = max(0.0, (float)($mine['allocated_cost'] ?? 0) - (float)($mine['liquido_total'] ?? 0)); ?>
        <div class="mt-2 small <?= $remVend>0 ? 'text-danger' : 'text-muted' ?>">Falta cobrir custos (vendedor): US$ <?= number_format($remVend, 2) ?></div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="p-3 border rounded">
        <div class="fw-bold">Progresso da meta da equipe</div>
        <?php $pct = min(100, max(0, ($team['team_bruto_total'] ?? 0) / $goal * 100)); ?>
        <div class="progress" style="height:12px"><div class="progress-bar <?= ($pct>=100?'bg-success':'') ?>" style="width: <?= $pct ?>%"></div></div>
        <small class="text-muted d-block">Equipe: US$ <?= number_format($team['team_bruto_total'] ?? 0,2) ?> de US$ <?= number_format($goal,2) ?> (<?= round($pct,1) ?>%)</small>
        <?php $falta = max(0, ($goal - (float)($team['team_bruto_total'] ?? 0))); ?>
        <small class="text-muted d-block">Falta: US$ <?= number_format($falta, 2) ?></small>
        <?php if (isset($team['team_bruto_total_brl'])): ?>
          <small class="text-muted d-block">BRL R$ <?= number_format((float)$team['team_bruto_total_brl'], 2) ?> de R$ <?= number_format((float)($team['meta_equipe_brl'] ?? 0), 2) ?></small>
        <?php endif; ?>
        <?php if (isset($team['team_cost_settings_rate'])): ?>
          <span class="badge text-bg-warning mt-2">Custo Global: <?= number_format((float)$team['team_cost_settings_rate']*100, 2) ?>%</span>
        <?php endif; ?>
        <?php if (isset($team['team_remaining_cost_to_cover'])): ?>
          <div class="mt-2 small <?= ((float)$team['team_remaining_cost_to_cover']>0?'text-danger':'text-muted') ?>">Falta cobrir custos da empresa: US$ <?= number_format((float)$team['team_remaining_cost_to_cover'], 2) ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php else: ?>
  <div class="alert alert-info">Sem dados para o período selecionado.</div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header">Histórico Mensal</div>
    <div class="card-body">
      <canvas id="histChart"></canvas>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const hctx = document.getElementById('histChart');
new Chart(hctx, { type: 'line', data: { labels: <?= json_encode($historyLabels) ?>, datasets: [{ label: 'Comissão Final (USD)', data: <?= json_encode($historyValues) ?>, borderColor: '#0d6efd' }] }, options: { responsive: true, scales: { y: { beginAtZero: true }}}});
</script>
