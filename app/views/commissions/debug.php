<?php /** @var array|null $mine */ /** @var array $team */ /** @var array $costs */ /** @var array $sources */ /** @var string $period */ /** @var string $from */ /** @var string $to */ ?>
<div class="container py-3">
  <h3 class="mb-3">Debug de Comissões</h3>
  <form class="row g-2 mb-3" method="get" action="/admin/commissions/debug">
    <div class="col-auto">
      <label class="form-label">Período</label>
      <input type="month" name="period" value="<?= htmlspecialchars($period) ?>" class="form-control">
    </div>

  <div class="card mt-3">
    <div class="card-header">Caixa da Empresa (Detalhamento)</div>
    <div class="card-body">
      <div class="mb-2 small text-muted">Caixa = Σ(líquido apurado dos vendedores) − Σ(comissões finais). Valores abaixo consideram o rateio igualitário já aplicado.</div>
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle">
          <thead>
            <tr>
              <th>Vendedor</th>
              <th class="text-end">Líquido Apurado (USD)</th>
              <th class="text-end">Líquido Apurado (BRL)</th>
              <th class="text-end">Comissão Final (USD)</th>
              <th class="text-end">Comissão Final (BRL)</th>
            </tr>
          </thead>
          <tbody>
            <?php $sumLiqUsd=0.0; $sumLiqBrl=0.0; $sumComUsd=0.0; $sumComBrl=0.0; foreach (($items ?? []) as $it): ?>
            <?php $sumLiqUsd += (float)($it['liquido_apurado'] ?? 0); $sumLiqBrl += (float)($it['liquido_apurado_brl'] ?? 0); $sumComUsd += (float)($it['comissao_final'] ?? 0); $sumComBrl += (float)($it['comissao_final_brl'] ?? 0); ?>
            <tr>
              <td><?= htmlspecialchars($it['name'] ?? ($it['user']['name'] ?? '')) ?></td>
              <td class="text-end <?= ((float)($it['liquido_apurado'] ?? 0))<0?'text-danger':'' ?>"><?= number_format((float)($it['liquido_apurado'] ?? 0), 2) ?></td>
              <td class="text-end <?= ((float)($it['liquido_apurado_brl'] ?? 0))<0?'text-danger':'' ?>"><?= number_format((float)($it['liquido_apurado_brl'] ?? 0), 2) ?></td>
              <td class="text-end"><?= number_format((float)($it['comissao_final'] ?? 0), 2) ?></td>
              <td class="text-end"><?= number_format((float)($it['comissao_final_brl'] ?? 0), 2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr class="table-secondary">
              <th>Total</th>
              <th class="text-end <?= $sumLiqUsd<0?'text-danger':'' ?>"><?= number_format($sumLiqUsd, 2) ?></th>
              <th class="text-end <?= $sumLiqBrl<0?'text-danger':'' ?>"><?= number_format($sumLiqBrl, 2) ?></th>
              <th class="text-end"><?= number_format($sumComUsd, 2) ?></th>
              <th class="text-end"><?= number_format($sumComBrl, 2) ?></th>
            </tr>
            <tr class="table-secondary">
              <th>Caixa da Empresa</th>
              <th class="text-end <?= ($sumLiqUsd-$sumComUsd)<0?'text-danger':'' ?>">USD <?= number_format($sumLiqUsd - $sumComUsd, 2) ?></th>
              <th class="text-end <?= ($sumLiqBrl-$sumComBrl)<0?'text-danger':'' ?>">BRL R$ <?= number_format($sumLiqBrl - $sumComBrl, 2) ?></th>
              <th></th>
              <th></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
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
            <li class="list-group-item"><strong>Líquido Apurado USD:</strong> <?= number_format((float)($mine['liquido_apurado'] ?? 0), 2) ?></li>
            <li class="list-group-item"><strong>Líquido Apurado BRL:</strong> <?= number_format((float)($mine['liquido_apurado_brl'] ?? 0), 2) ?></li>
            <li class="list-group-item"><strong>Comissão Individual USD:</strong> <?= number_format((float)($mine['comissao_individual'] ?? 0), 2) ?></li>
            <li class="list-group-item"><strong>Bônus USD:</strong> <?= number_format((float)($mine['bonus'] ?? 0), 2) ?></li>
            <li class="list-group-item"><strong>Comissão Final USD:</strong> <?= number_format((float)($mine['comissao_final'] ?? 0), 2) ?></li>
            <?php $remVendDbg = max(0.0, (float)($mine['allocated_cost'] ?? 0) - (float)($mine['liquido_total'] ?? 0)); ?>
            <li class="list-group-item"><strong>Falta cobrir custos (vendedor):</strong> US$ <?= number_format($remVendDbg, 2) ?></li>
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
            <li class="list-group-item"><strong>Líquido Equipe USD:</strong> <?= number_format((float)($team['team_liquido_total'] ?? 0), 2) ?></li>
            <li class="list-group-item"><strong>Taxa Custo Global (Admin):</strong> <?= number_format(((float)($team['team_cost_settings_rate'] ?? 0))*100, 2) ?>%</li>
            <li class="list-group-item"><strong>Custo Global (Estimado) USD:</strong> <?= number_format(((float)($team['team_cost_settings_rate'] ?? 0)) * (float)($team['team_bruto_total'] ?? 0), 2) ?></li>
            <li class="list-group-item"><strong>Custos Fixos USD:</strong> <?= number_format((float)($team['team_cost_fixed_usd'] ?? 0), 2) ?></li>
            <li class="list-group-item"><strong>Custos Percentuais (%):</strong> <?= number_format(((float)($team['team_cost_percent_rate'] ?? 0))*100, 2) ?>%</li>
            <li class="list-group-item"><strong>Custos Percentuais USD:</strong> <?= number_format((float)($team['team_cost_percent_total'] ?? 0), 2) ?></li>
            <li class="list-group-item"><strong>Custo Total Equipe USD:</strong> <?= number_format((float)($team['team_cost_total'] ?? 0), 2) ?></li>
            <?php if (isset($team['equal_cost_share_per_active_seller'])): ?>
            <li class="list-group-item"><strong>Cota igualitária por vendedor ativo:</strong> US$ <?= number_format((float)$team['equal_cost_share_per_active_seller'], 2) ?></li>
            <?php endif; ?>
            <li class="list-group-item"><strong>Elegíveis p/ Bônus:</strong> <?= (int)($team['active_count'] ?? 0) ?></li>
            <li class="list-group-item"><strong>Taxa Bônus (se meta):</strong> <?= number_format(((float)($team['bonus_rate'] ?? 0))*100, 2) ?>%</li>
            <li class="list-group-item"><strong>Falta cobrir custos da empresa:</strong> US$ <?= number_format((float)($team['team_remaining_cost_to_cover'] ?? 0), 2) ?></li>
            <?php if (isset($team['company_cash_usd'])): ?>
            <li class="list-group-item"><strong>Caixa da Empresa (USD):</strong> <?= number_format((float)$team['company_cash_usd'], 2) ?></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-3">
    <div class="card-header">Origem do Líquido Total (por fonte)</div>
    <div class="card-body">
      <?php $src = $sources ?? ['legacy'=>[],'intl'=>[],'nat'=>[],'total'=>[]]; ?>
      <div class="table-responsive">
        <table class="table table-sm table-striped">
          <thead><tr><th>Fonte</th><th>Bruto (USD)</th><th>Líquido (USD)</th></tr></thead>
          <tbody>
            <tr><td>Legado</td><td><?= number_format((float)($src['legacy']['bruto_total'] ?? 0),2) ?></td><td><?= number_format((float)($src['legacy']['liquido_total'] ?? 0),2) ?></td></tr>
            <tr><td>Internacional</td><td><?= number_format((float)($src['intl']['bruto_total'] ?? 0),2) ?></td><td><?= number_format((float)($src['intl']['liquido_total'] ?? 0),2) ?></td></tr>
            <tr><td>Nacional</td><td><?= number_format((float)($src['nat']['bruto_total'] ?? 0),2) ?></td><td><?= number_format((float)($src['nat']['liquido_total'] ?? 0),2) ?></td></tr>
            <tr class="table-secondary"><td><strong>Total</strong></td><td><strong><?= number_format((float)($src['total']['bruto_total'] ?? 0),2) ?></strong></td><td><strong><?= number_format((float)($src['total']['liquido_total'] ?? 0),2) ?></strong></td></tr>
          </tbody>
        </table>
      </div>
      <div class="small text-muted">Observação: o líquido total é a soma do líquido (USD) de todas as fontes no período.</div>
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

  <?php $metrics = $salesMetrics ?? ['total_peso_kg'=>0.0,'total_produtos_compra_usd'=>0.0]; ?>
  <div class="row g-3 mt-3">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">Peso Total das Vendas (10 a 9)</div>
        <div class="card-body">
          <p class="mb-1"><strong>Janela:</strong> <?= htmlspecialchars($from) ?> a <?= htmlspecialchars($to) ?></p>
          <p class="mb-0"><strong>Peso Total:</strong> <?= number_format((float)($metrics['total_peso_kg'] ?? 0), 2) ?> kg</p>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">Produtos Compra (USD) - Total (10 a 9)</div>
        <div class="card-body">
          <p class="mb-1"><strong>Janela:</strong> <?= htmlspecialchars($from) ?> a <?= htmlspecialchars($to) ?></p>
          <p class="mb-0"><strong>Total Produtos Compra (USD):</strong> <?= number_format((float)($metrics['total_produtos_compra_usd'] ?? 0), 2) ?></p>
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-3">
    <div class="card-header">Como é calculado (Fórmulas com valores)</div>
    <div class="card-body">
      <?php 
        $teamBruto = (float)($team['team_bruto_total'] ?? 0);
        $rateAdmin = (float)($team['team_cost_settings_rate'] ?? 0);
        $globalCost = $teamBruto * $rateAdmin;
        $fixos = (float)($team['team_cost_fixed_usd'] ?? 0);
        $percRate = (float)($team['team_cost_percent_rate'] ?? 0);
        $percTotal = (float)($team['team_cost_percent_total'] ?? 0);
        $teamTotalCost = (float)($team['team_cost_total'] ?? 0);
        $sellerBruto = (float)($mine['bruto_total'] ?? 0);
        $sellerLiquido = (float)($mine['liquido_total'] ?? 0);
        $equalShare = (float)($team['equal_cost_share_per_active_seller'] ?? 0);
        $allocated = (float)($mine['allocated_cost'] ?? 0); // valor aplicado pelo cálculo (igualitário se seller/trainee ativo)
        $liquidoApurado = (float)($mine['liquido_apurado'] ?? ($sellerLiquido - $allocated));
        $usdRate = 0.0; try { $r = new \Models\Setting(); $usdRate = (float)$r->get('usd_rate','5.83'); } catch (\Throwable $e) {}
        $brutoBRL = $sellerBruto * ($usdRate>0?$usdRate:1);
        $liqApBRL = $liquidoApurado * ($usdRate>0?$usdRate:1);
        // regra de faixas (em BRL)
        $t30 = 30000.0 * ($usdRate>0?$usdRate:1);
        $t45 = 45000.0 * ($usdRate>0?$usdRate:1);
        $perc = ($brutoBRL <= $t30) ? 0.15 : (($brutoBRL <= $t45) ? 0.25 : 0.25);
        $indBRL = $liqApBRL * $perc;
        $applyBonus = (bool)($team['apply_bonus'] ?? false);
        $bonusRate = (float)($team['bonus_rate'] ?? 0);
        $bonusBRL = $applyBonus ? ($liqApBRL * $bonusRate) : 0.0;
        $finalBRL = $indBRL + $bonusBRL;
        $indUSD = ($usdRate>0) ? ($indBRL/$usdRate) : 0.0;
        $bonusUSD = ($usdRate>0) ? ($bonusBRL/$usdRate) : 0.0;
        $finalUSD = ($usdRate>0) ? ($finalBRL/$usdRate) : 0.0;
      ?>
      <div class="mb-2"><strong>Janela:</strong> <?= htmlspecialchars($from) ?> a <?= htmlspecialchars($to) ?></div>
      <ol class="mb-3">
        <li><strong>Custo Global (empresa):</strong> global = taxa_admin × bruto_equipe = <?= number_format($rateAdmin*100,2) ?>% × US$ <?= number_format($teamBruto,2) ?> = <strong>US$ <?= number_format($globalCost,2) ?></strong></li>
        <li><strong>Custos explícitos fixos:</strong> <strong>US$ <?= number_format($fixos,2) ?></strong></li>
        <li><strong>Custos explícitos percentuais:</strong> percent_total = <?= number_format($percRate*100,2) ?>% × US$ <?= number_format($teamBruto,2) ?> = <strong>US$ <?= number_format($percTotal,2) ?></strong></li>
        <li><strong>Custo Total da Equipe:</strong> total = global + fixos + percentuais = US$ <?= number_format($globalCost,2) ?> + US$ <?= number_format($fixos,2) ?> + US$ <?= number_format($percTotal,2) ?> = <strong>US$ <?= number_format($teamTotalCost,2) ?></strong></li>
        <li><strong>Rateio pro vendedor (igualitário):</strong> parcela = custo_total / vendedores_ativos(seller+trainee) = US$ <?= number_format($teamTotalCost,2) ?> / <?= (int)($team['active_cost_split_count'] ?? 0) ?> = <strong>US$ <?= number_format($equalShare,2) ?></strong></li>
        <li><strong>Líquido Apurado do vendedor:</strong> líquido_apurado = líquido_total − parcela = US$ <?= number_format($sellerLiquido,2) ?> − US$ <?= number_format($allocated,2) ?> = <strong>US$ <?= number_format($liquidoApurado,2) ?></strong></li>
        <li><strong>Base da Comissão (BRL):</strong> líquido_apurado_brl = US$ <?= number_format($liquidoApurado,2) ?> × <?= number_format($usdRate,2) ?> = <strong>R$ <?= number_format($liqApBRL,2) ?></strong>; bruto_brl = US$ <?= number_format($sellerBruto,2) ?> × <?= number_format($usdRate,2) ?> = R$ <?= number_format($brutoBRL,2) ?></li>
        <li><strong>Percentual de Comissão:</strong> se bruto_brl ≤ R$ <?= number_format($t30,2) ?> → 15%; se ≤ R$ <?= number_format($t45,2) ?> → 25%; senão 25%. Aplicado: <strong><?= number_format($perc*100,2) ?>%</strong></li>
        <li><strong>Comissão Individual:</strong> ind_brl = líquido_apurado_brl × perc = R$ <?= number_format($liqApBRL,2) ?> × <?= number_format($perc*100,2) ?>% = R$ <?= number_format($indBRL,2) ?> (≈ US$ <?= number_format($indUSD,2) ?>)</li>
        <li><strong>Bônus de Equipe:</strong> se meta atingida, bonus_brl = líquido_apurado_brl × (5% / elegíveis) = R$ <?= number_format($liqApBRL,2) ?> × <?= number_format($bonusRate*100,2) ?>% = R$ <?= number_format($bonusBRL,2) ?> (≈ US$ <?= number_format($bonusUSD,2) ?>)</li>
        <li><strong>Comissão Final:</strong> final_brl = ind_brl + bonus_brl = R$ <?= number_format($indBRL,2) ?> + R$ <?= number_format($bonusBRL,2) ?> = R$ <?= number_format($finalBRL,2) ?> (≈ US$ <?= number_format($finalUSD,2) ?>)</li>
      </ol>
    </div>
  </div>
</div>
