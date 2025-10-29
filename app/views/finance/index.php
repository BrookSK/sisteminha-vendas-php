<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Financeiro</h5>
  <div class="d-flex gap-2 align-items-center">
    <span class="badge text-bg-secondary">Período: <?= htmlspecialchars($from ?? '') ?> a <?= htmlspecialchars($to ?? '') ?></span>
    <span class="badge text-bg-secondary">Câmbio: 1 USD = R$ <?= number_format((float)($rate ?? 0), 2) ?></span>
    <span class="badge text-bg-warning">Custo Global: <?= number_format((float)($cost_rate ?? 0)*100, 2) ?>%</span>
  </div>
</div>

<form class="row g-2 mb-3" method="get" action="/admin/finance">
  <div class="col-auto">
    <label class="form-label">De</label>
    <input type="date" name="from" value="<?= htmlspecialchars($from ?? date('Y-m-01')) ?>" class="form-control">
  </div>
  <div class="col-auto">
    <label class="form-label">Até</label>
    <input type="date" name="to" value="<?= htmlspecialchars($to ?? date('Y-m-t')) ?>" class="form-control">
  </div>
  <div class="col-auto align-self-end">
    <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
  </div>
  <div class="col-auto align-self-end">
    <a class="btn btn-outline-primary" href="/admin/finance/export-company.pdf?from=<?= urlencode($from ?? '') ?>&to=<?= urlencode($to ?? '') ?>">Exportar Empresa PDF</a>
    <a class="btn btn-outline-success" href="/admin/finance/export-company.xlsx?from=<?= urlencode($from ?? '') ?>&to=<?= urlencode($to ?? '') ?>">Exportar Empresa XLSX</a>
  </div>
</form>

<?php $team = $comm['team'] ?? []; ?>
<div class="row g-3 mb-2">
  <div class="col-md-3">
    <div class="p-2 border rounded h-100">
      <div class="text-muted small">Caixa da Empresa</div>
      <div class="fw-bold <?= (($team['company_cash_usd'] ?? 0) < 0)?'text-danger':'' ?>">USD <?= number_format((float)($team['company_cash_usd'] ?? 0), 2) ?></div>
      <div class="small <?= (($team['company_cash_brl'] ?? 0) < 0)?'text-danger':'text-muted' ?>">BRL R$ <?= number_format((float)($team['company_cash_brl'] ?? 0), 2) ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="p-2 border rounded h-100">
      <div class="text-muted small">Bruto (Equipe)</div>
      <div class="fw-bold">$ <?= number_format((float)($team['team_bruto_total'] ?? 0), 2) ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="p-2 border rounded h-100">
      <div class="text-muted small">Líquido Rateado (USD)</div>
      <div class="fw-bold">$ <?= number_format((float)($team['sum_rateado_usd'] ?? 0), 2) ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="p-2 border rounded h-100">
      <div class="text-muted small">Comissões (USD)</div>
      <div class="fw-bold">$ <?= number_format((float)($team['sum_commissions_usd'] ?? 0), 2) ?></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-2">
  <div class="col-md-4">
    <div class="p-2 border rounded h-100">
      <div class="text-muted small">Custos Totais (USD)</div>
      <div class="fw-bold">$ <?= number_format((float)($team['team_cost_total'] ?? 0), 2) ?></div>
      <div class="small text-muted">Settings: <?= number_format((float)($team['team_cost_settings_rate'] ?? 0)*100,2) ?>% | Percent: <?= number_format((float)($team['team_cost_percent_rate'] ?? 0)*100,2) ?>% | Fixos: $ <?= number_format((float)($team['team_cost_fixed_usd'] ?? 0), 2) ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="p-2 border rounded h-100">
      <div class="text-muted small">Cota Igualitária por Elegível</div>
      <div class="fw-bold">$ <?= number_format((float)($team['equal_cost_share_per_active_seller'] ?? 0), 2) ?></div>
      <div class="small text-muted">Elegíveis p/ rateio: <?= (int)($team['active_cost_split_count'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="p-2 border rounded h-100">
      <div class="text-muted small">Meta Equipe (BRL) e Bônus</div>
      <div class="small">Meta: R$ <?= number_format((float)($team['meta_equipe_brl'] ?? 0), 2) ?></div>
      <div class="small">Aplica bônus? <?= !empty($team['apply_bonus']) ? 'Sim' : 'Não' ?></div>
      <div class="small">Ativos: <?= (int)($team['active_count'] ?? 0) ?> | Rate: <?= number_format((float)($team['bonus_rate'] ?? 0)*100, 2) ?>%</div>
    </div>
  </div>
</div>

<div class="card mt-3">
  <div class="card-header">Comissões por Função</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th>Função</th>
            <th class="text-end">Comissão (USD)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($byRole ?? []) as $role => $sum): ?>
            <tr>
              <td><?= htmlspecialchars($role) ?></td>
              <td class="text-end">$ <?= number_format((float)$sum, 2) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php 
  $bruto = (float)($team['team_bruto_total'] ?? 0);
  $settingsRate = (float)($team['team_cost_settings_rate'] ?? 0);
  $settingsVal = $bruto * $settingsRate;
  $percRate = (float)($team['team_cost_percent_rate'] ?? 0);
  $percVal = $bruto * $percRate;
?>
<div class="card mt-3">
  <div class="card-header">Custos da Empresa (detalhamento)</div>
  <div class="card-body">
    <div class="mb-2 small text-muted">Exemplo: <?= number_format($settingsRate*100,2) ?>% × US$ <?= number_format($bruto, 2) ?> = US$ <?= number_format($settingsVal, 2) ?></div>
    <div class="mb-2 small text-muted">Percentuais explícitos: <?= number_format($percRate*100,2) ?>% × US$ <?= number_format($bruto, 2) ?> = US$ <?= number_format($percVal, 2) ?></div>
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th>Tipo</th>
            <th>Descrição</th>
            <th class="text-end">Valor</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Settings (global)</td>
            <td>Custo global configurado</td>
            <td class="text-end">$ <?= number_format($settingsVal, 2) ?></td>
          </tr>
          <tr>
            <td>Percentuais explícitos</td>
            <td>Soma dos % cadastrados em Custos</td>
            <td class="text-end">$ <?= number_format($percVal, 2) ?></td>
          </tr>
          <tr>
            <td>Fixos (USD)</td>
            <td>Soma dos custos fixos (Custos)</td>
            <td class="text-end">$ <?= number_format((float)($team['team_cost_fixed_usd'] ?? 0), 2) ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card mt-3">
  <div class="card-header">Vendedores/Equipe (período)</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped mb-0 align-middle">
        <thead>
          <tr>
            <th>Vendedor</th>
            <th>Função</th>
            <th class="text-end">Bruto (USD)</th>
            <th class="text-end">Líquido (USD)</th>
            <th class="text-end">Custo Alocado (USD)</th>
            <th class="text-end">Custo Alocado (BRL)</th>
            <th class="text-end">Líquido Apurado (USD)</th>
            <th class="text-end">Comissão Final (USD)</th>
            <th class="text-end">Export</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($comm['items'] ?? []) as $it): ?>
            <?php $la = (float)($it['liquido_apurado'] ?? 0); ?>
            <tr>
              <td><?= htmlspecialchars($it['user']['name'] ?? '') ?></td>
              <td><?= htmlspecialchars($it['user']['role'] ?? '') ?></td>
              <td class="text-end">$ <?= number_format((float)($it['bruto_total'] ?? 0), 2) ?></td>
              <td class="text-end">$ <?= number_format((float)($it['liquido_total'] ?? 0), 2) ?></td>
              <td class="text-end">$ <?= number_format((float)($it['allocated_cost'] ?? 0), 2) ?></td>
              <td class="text-end">R$ <?= number_format((float)($it['allocated_cost_brl'] ?? 0), 2) ?></td>
              <td class="text-end <?= $la<0?'text-danger':'' ?>">$ <?= number_format($la, 2) ?></td>
              <td class="text-end">$ <?= number_format((float)($it['comissao_final'] ?? 0), 2) ?></td>
              <td class="text-end">
                <?php $sid = (int)($it['vendedor_id'] ?? 0); $uFrom = urlencode($from ?? ''); $uTo = urlencode($to ?? ''); $sellerPdfUrl = "/admin/finance/export-seller.pdf?seller_id=".$sid."&from=".$uFrom."&to=".$uTo; ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($sellerPdfUrl) ?>">PDF</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
