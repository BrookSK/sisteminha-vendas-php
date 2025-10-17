<?php /** @var array|null $mine */ /** @var array $team */ /** @var array $costs */ /** @var string $period */ /** @var string $from */ /** @var string $to */ ?>
<div class="container py-3">
  <h3 class="mb-3">Debug de Comissões</h3>
  <form class="row g-2 mb-3" method="get" action="/admin/commissions/debug">
    <div class="col-auto">
      <label class="form-label">Período</label>
      <input type="month" name="period" value="<?= htmlspecialchars($period) ?>" class="form-control">
    </div>
    <div class="col-auto align-self-end">
      <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
    </div>
  </form>

  <div class="row g-3">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">Variáveis (Meu Cálculo)</div>
        <div class="card-body">
          <?php if ($mine): ?>
          <ul class="list-group list-group-flush">
            <li class="list-group-item"><strong>Bruto USD:</strong> <?= number_format((float)($mine['bruto_total'] ?? 0), 2) ?></li>
            <li class="list-group-item"><strong>Bruto BRL:</strong> <?= number_format((float)($mine['bruto_total_brl'] ?? 0), 2) ?></li>
            <li class="list-group-item"><strong>Líquido USD:</strong> <?= number_format((float)($mine['liquido_total'] ?? 0), 2) ?></li>
            <li class="list-group-item"><strong>Rateio Custo USD:</strong> <?= number_format((float)($mine['allocated_cost'] ?? 0), 2) ?></li>
            <li class="list-group-item"><strong>Líquido Apurado USD:</strong> <?= number_format((float)($mine['liquido_apurado'] ?? 0), 2) ?></li>
            <li class="list-group-item"><strong>Líquido Apurado BRL:</strong> <?= number_format((float)($mine['liquido_apurado_brl'] ?? 0), 2) ?></li>
            <li class="list-group-item"><strong>Comissão Individual USD:</strong> <?= number_format((float)($mine['comissao_individual'] ?? 0), 2) ?></li>
            <li class="list-group-item"><strong>Bônus USD:</strong> <?= number_format((float)($mine['bonus'] ?? 0), 2) ?></li>
            <li class="list-group-item"><strong>Comissão Final USD:</strong> <?= number_format((float)($mine['comissao_final'] ?? 0), 2) ?></li>
          </ul>
          <?php else: ?>
          <div class="alert alert-info">Sem dados para o período selecionado.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card">
        <div class="card-header">Equipe e Configurações</div>
        <div class="card-body">
          <ul class="list-group list-group-flush">
            <li class="list-group-item"><strong>Bruto Equipe USD:</strong> <?= number_format((float)($team['team_bruto_total'] ?? 0), 2) ?></li>
            <li class="list-group-item"><strong>Taxa Custo Global (Admin):</strong> <?= number_format(((float)($team['team_cost_settings_rate'] ?? 0))*100, 2) ?>%</li>
            <li class="list-group-item"><strong>Custo Global (Estimado) USD:</strong> <?= number_format(((float)($team['team_cost_settings_rate'] ?? 0)) * (float)($team['team_bruto_total'] ?? 0), 2) ?></li>
            <li class="list-group-item"><strong>Custos Fixos USD:</strong> <?= number_format((float)($team['team_cost_fixed_usd'] ?? 0), 2) ?></li>
            <li class="list-group-item"><strong>Custos Percentuais (%):</strong> <?= number_format(((float)($team['team_cost_percent_rate'] ?? 0))*100, 2) ?>%</li>
            <li class="list-group-item"><strong>Custos Percentuais USD:</strong> <?= number_format((float)($team['team_cost_percent_total'] ?? 0), 2) ?></li>
            <li class="list-group-item"><strong>Custo Total Equipe USD:</strong> <?= number_format((float)($team['team_cost_total'] ?? 0), 2) ?></li>
            <li class="list-group-item"><strong>Elegíveis p/ Bônus:</strong> <?= (int)($team['active_count'] ?? 0) ?></li>
            <li class="list-group-item"><strong>Taxa Bônus (se meta):</strong> <?= number_format(((float)($team['bonus_rate'] ?? 0))*100, 2) ?>%</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-3">
    <div class="card-header">Custos do Período (Detalhe)</div>
    <div class="card-body">
      <div class="mb-2"><strong>Janela:</strong> <?= htmlspecialchars($from) ?> a <?= htmlspecialchars($to) ?></div>
      <div class="mb-2"><strong>Taxa Custo Global (Admin):</strong> <?= number_format(((float)($costs['settings_cost_rate'] ?? 0))*100, 2) ?>%</div>
      <div class="mb-2"><strong>Total Fixos USD:</strong> <?= number_format((float)($costs['explicit_fixed_usd'] ?? 0), 2) ?> | <strong>Total Percentuais:</strong> <?= number_format((float)($costs['explicit_percent_sum'] ?? 0), 2) ?>%</div>
      <div class="table-responsive">
        <table class="table table-sm table-striped">
          <thead><tr><th>Data</th><th>Descrição</th><th>Tipo</th><th>USD</th><th>%</th></tr></thead>
          <tbody>
          <?php foreach (($costs['explicit_costs'] ?? []) as $c): ?>
            <tr>
              <td><?= htmlspecialchars($c['data'] ?? '') ?></td>
              <td><?= htmlspecialchars($c['descricao'] ?? '') ?></td>
              <td><?= htmlspecialchars($c['valor_tipo'] ?? 'usd') ?></td>
              <td><?= number_format((float)($c['valor_usd'] ?? 0), 2) ?></td>
              <td><?= number_format((float)($c['valor_percent'] ?? 0), 2) ?>%</td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
