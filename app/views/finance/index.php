<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Financeiro</h5>
  <div class="d-flex gap-2 align-items-center">
    <span class="badge text-bg-secondary">Período: <?= htmlspecialchars($from ?? '') ?> a <?= htmlspecialchars($to ?? '') ?></span>
    <span class="badge text-bg-secondary">Câmbio: 1 USD = R$ <?= number_format((float)($rate ?? 0), 2) ?></span>
    <span class="badge text-bg-warning">Custo Global: <?= number_format((float)($cost_rate ?? 0)*100, 2) ?>%</span>
  </div>

<div class="modal fade" id="modalByRole" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Comissões por Função</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Somatório de <strong>comissão final (USD)</strong> agrupado por função do usuário (seller, manager, trainee, etc.).</p>
        <p>Fórmula: Para cada função F, ∑ comissao_final dos usuários com role = F.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
 </div>

<script>
  (function() {
    function initTooltips(){
      if (!window.bootstrap || !bootstrap.Tooltip) return;
      var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
      tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
      });
    }
    function initModals(){
      if (!window.bootstrap || !bootstrap.Modal) return;
      document.querySelectorAll('[data-bs-toggle="modal"][data-bs-target]').forEach(function(btn){
        btn.addEventListener('click', function(ev){
          ev.preventDefault();
          var sel = this.getAttribute('data-bs-target');
          if (!sel) return;
          var el = document.querySelector(sel);
          if (!el) return;
          var m = bootstrap.Modal.getOrCreateInstance(el);
          m.show();
        });
      });
    }
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
      setTimeout(function(){ initTooltips(); initModals(); }, 0);
    } else {
      document.addEventListener('DOMContentLoaded', function(){ initTooltips(); initModals(); });
      window.addEventListener('load', function(){ initTooltips(); initModals(); });
    }
  })();
  </script>
</div>

<div class="modal fade" id="modalCompanyCash" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Caixa da Empresa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Representa o saldo estimado da empresa em USD no período, considerando receitas brutas, custos totais e comissões pagas.</p>
        <p>Fórmula base: Receitas líquidas − Custos − Comissões.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
 </div>

<div class="modal fade" id="modalTeamBruto" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Bruto (Equipe)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Soma do valor bruto em USD de todas as vendas do período, independentemente de custos e comissões.</p>
        <p>Fórmula: ∑ Bruto_USD(venda).</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
 </div>

<div class="modal fade" id="modalLiquidoRateado" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Líquido Rateado</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Receita líquida após dedução dos custos globais, fixos e percentuais, base para rateio entre vendedores elegíveis.</p>
        <p>Inclui regras de elegibilidade e rateio igualitário de custos quando aplicável.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
 </div>

<div class="modal fade" id="modalComissoes" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Comissões</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Somatório das comissões finais de cada vendedor: comissão individual + bônus de equipe (quando aplicável).</p>
        <p>Considera meta da equipe e quantidade de ativos para distribuição do bônus.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
 </div>

<div class="modal fade" id="modalCustosTotais" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Custos Totais</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Composição: custo global (settings) + percentuais explícitos sobre o bruto + custos fixos em USD.</p>
        <p>Exemplo: X% × US$ Bruto + Y% × US$ Bruto + Fixos.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
 </div>

<div class="modal fade" id="modalCotaIgualitaria" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Cota Igualitária</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Parcela de custos dividida igualmente entre os vendedores elegíveis para rateio no período.</p>
        <p>Fórmula: (Custos para rateio) ÷ (Elegíveis).</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
 </div>

<div class="modal fade" id="modalMetaBonus" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Meta e Bônus</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Meta da equipe em BRL. Se atingida, distribui-se um bônus proporcional entre os ativos.</p>
        <p>Rate de bônus: percentual dividido entre ativos elegíveis.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
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
  <div class="col-auto">
    <label class="form-label">Atend. De</label>
    <input type="date" name="att_from" value="<?= htmlspecialchars($att_from ?? ($from ?? '')) ?>" class="form-control">
  </div>
  <div class="col-auto">
    <label class="form-label">Atend. Até</label>
    <input type="date" name="att_to" value="<?= htmlspecialchars($att_to ?? ($to ?? '')) ?>" class="form-control">
  </div>
  <div class="col-auto align-self-end">
    <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
  </div>
  <div class="col-auto align-self-end">
    <a class="btn btn-outline-primary" href="/admin/finance/export-company.pdf?from=<?= urlencode($from ?? '') ?>&to=<?= urlencode($to ?? '') ?>">Exportar Empresa PDF</a>
    <a class="btn btn-outline-success" href="/admin/finance/export-company.xlsx?from=<?= urlencode($from ?? '') ?>&to=<?= urlencode($to ?? '') ?>">Exportar Empresa XLSX</a>
    <a class="btn btn-outline-dark" href="/admin/finance/export-costs.csv?from=<?= urlencode($from ?? '') ?>&to=<?= urlencode($to ?? '') ?>">Custos CSV</a>
    <a class="btn btn-outline-dark" href="/admin/finance/export-attendances.csv?att_from=<?= urlencode($att_from ?? ($from ?? '')) ?>&att_to=<?= urlencode($att_to ?? ($to ?? '')) ?>">Atendimentos CSV</a>
    <a class="btn btn-outline-dark" href="/admin/finance/export-attendances.xlsx?att_from=<?= urlencode($att_from ?? ($from ?? '')) ?>&att_to=<?= urlencode($att_to ?? ($to ?? '')) ?>">Atendimentos XLSX</a>
  </div>
</form>

<?php $team = $comm['team'] ?? []; ?>
<div class="row g-3 mb-2">
  <div class="col-md-3">
    <div class="p-2 border rounded h-100">
      <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted small">Caixa da Empresa <span class="badge rounded-pill text-bg-info" data-bs-toggle="tooltip" title="Saldo estimado em USD após receitas, custos e comissões.">?</span></div>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalCompanyCash">Como é calculado</button>
      </div>
      <div class="fw-bold <?= (($team['company_cash_usd'] ?? 0) < 0)?'text-danger':'' ?>">USD <?= number_format((float)($team['company_cash_usd'] ?? 0), 2) ?></div>
      <div class="small <?= (($team['company_cash_brl'] ?? 0) < 0)?'text-danger':'text-muted' ?>">BRL R$ <?= number_format((float)($team['company_cash_brl'] ?? 0), 2) ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="p-2 border rounded h-100">
      <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted small">Bruto (Equipe) <span class="badge rounded-pill text-bg-info" data-bs-toggle="tooltip" title="Soma do total bruto (USD) de todas as vendas do período.">?</span></div>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalTeamBruto">Como é calculado</button>
      </div>
      <div class="fw-bold">$ <?= number_format((float)($team['team_bruto_total'] ?? 0), 2) ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="p-2 border rounded h-100">
      <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted small">Líquido Rateado (USD) <span class="badge rounded-pill text-bg-info" data-bs-toggle="tooltip" title="Receita líquida após custos, usada para rateio entre vendedores elegíveis.">?</span></div>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalLiquidoRateado">Como é calculado</button>
      </div>
      <div class="fw-bold">$ <?= number_format((float)($team['sum_rateado_usd'] ?? 0), 2) ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="p-2 border rounded h-100">
      <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted small">Comissões (USD) <span class="badge rounded-pill text-bg-info" data-bs-toggle="tooltip" title="Somatório das comissões finais dos vendedores no período.">?</span></div>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalComissoes">Como é calculado</button>
      </div>
      <div class="fw-bold">$ <?= number_format((float)($team['sum_commissions_usd'] ?? 0), 2) ?></div>
    </div>
  </div>
</div>

<?php
  $pctSettings = (float)($team['team_cost_settings_rate'] ?? 0);
  $pctExplicit = (float)($team['team_cost_percent_rate'] ?? 0);
  $currGross = (float)($team['team_bruto_total'] ?? 0);
  $sumComms = (float)($team['sum_commissions_usd'] ?? 0);
  $commPct = ($currGross > 0) ? ($sumComms / $currGross) : 0.0; // fração do bruto
  $pctCosts = $pctSettings + $pctExplicit;
  $pctTotalEff = $pctCosts + $commPct; // custos (%) + comissões (%)
  $fixedUsd = (float)($team['team_cost_fixed_usd'] ?? 0);
  $beGross = null; $beBrl = null; $gapUsd = 0.0; $gapBrl = 0.0;
  if ($pctTotalEff < 1.0) {
    $den = (1.0 - $pctTotalEff);
    if ($den <= 0) { $den = 0.000001; }
    $beGross = $fixedUsd / $den; // G - (pctCosts+commPct)*G - fixed = 0 => G = fixed / (1 - (pctCosts+commPct))
    $beBrl = $beGross * (float)($rate ?? 0);
    // Falta baseada no déficit atual de caixa da empresa
    $companyCash = (float)($team['company_cash_usd'] ?? 0);
    if ($companyCash < 0) {
      $deficit = -$companyCash;
      // Vendas adicionais necessárias para cobrir o déficit atual,
      // usando a mesma taxa efetiva de retenção (1 - pctTotalEff)
      $gapUsd = $deficit / $den;
      $gapBrl = $gapUsd * (float)($rate ?? 0);
    } else {
      $gapUsd = 0.0; $gapBrl = 0.0;
    }
  }
?>
<div class="card mt-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Previsão para Cobrir Custos <span class="badge rounded-pill text-bg-info" data-bs-toggle="tooltip" title="Estimativa do bruto necessário para zerar o resultado após custos (fixos + percentuais sobre o bruto).">?</span></span>
  </div>
  <div class="card-body">
    <?php
      $projTotalCommissions = 0.0;
      foreach (($comm['items'] ?? []) as $it) {
        $brutoVend = (float)($it['bruto_total'] ?? 0);
        $liqVend = (float)($it['liquido_apurado'] ?? 0);
        if ($liqVend < 0) { $liqVend = 0.0; }
        $perc = ($brutoVend <= 30000.0) ? 0.15 : 0.25;
        $projTotalCommissions += ($liqVend * $perc);
      }
      $taxaMediaComissao = ($currGross > 0.0) ? ($projTotalCommissions / $currGross) : 0.0;
      $retencaoLiquida = 1.0 - $pctSettings - $pctExplicit - $taxaMediaComissao;
      $caixaAtual = (float)($team['company_cash_usd'] ?? 0);
      $vExtra = 0.0;
      if ($retencaoLiquida > 0.0 && $caixaAtual < 0.0) {
        $vExtra = abs($caixaAtual) / $retencaoLiquida;
      }
      $brutoAlvo = $currGross + $vExtra;
    ?>
    <?php if ($retencaoLiquida <= 0.0): ?>
      <div class="text-danger small">A retenção líquida é ≤ 0%. Ajuste percentuais de custos ou comissões para projetar o ponto de equilíbrio.</div>
    <?php else: ?>
      <div class="fs-6 mb-1">📈 Previsão de ponto de equilíbrio: US$ <?= number_format((float)$brutoAlvo, 2) ?></div>
      <div class="text-muted">💰 É necessário vender aproximadamente US$ <?= number_format((float)$vExtra, 2) ?> a mais para o caixa ficar positivo.</div>
    <?php endif; ?>
  </div>
</div>

<?php $explicit = $costs['explicit_costs'] ?? []; $teamBrutoForCosts = (float)($team['team_bruto_total'] ?? 0); ?>
<?php if (!empty($explicit)): ?>
<div class="card mt-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Custos Cadastrados (itens) <span class="badge rounded-pill text-bg-info" data-bs-toggle="tooltip" title="Lista de custos cadastrados no período e seus valores finais em USD.">?</span></span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped mb-0 align-middle">
        <thead>
          <tr>
            <th>Descrição</th>
            <th>Tipo</th>
            <th class="text-end">Valor Final (USD)</th>
            <th class="text-end">Valor Final (BRL)</th>
            <th>Fórmula</th>
          </tr>
        </thead>
        <tbody>
          <?php
            $rateNow = (float)($rate ?? 0);
            $sRate = (float)($team['team_cost_settings_rate'] ?? 0);
            $sAmt = $teamBrutoForCosts * $sRate;
            $sAmtBrl = $rateNow > 0 ? ($sAmt * $rateNow) : 0.0;
            $totalUsd = $sAmt;
            $totalBrl = $sAmtBrl;
          ?>
          <tr>
            <td>Impostos</td>
            <td>percent</td>
            <td class="text-end">$ <?= number_format($sAmt, 2) ?></td>
            <td class="text-end">R$ <?= number_format($sAmtBrl, 2) ?></td>
            <td class="small text-muted"><?= number_format($sRate*100,2) ?>% × US$ <?= number_format($teamBrutoForCosts,2) ?> = US$ <?= number_format($sAmt,2) ?></td>
          </tr>
          <?php foreach ($explicit as $c): ?>
            <?php
              $tipo = (string)($c['valor_tipo'] ?? 'fixed');
              if ($tipo === '') $tipo = 'fixed';
              if ($tipo === 'percent') {
                $pct = (float)($c['valor_percent'] ?? 0);
                $amt = ($teamBrutoForCosts * ($pct/100.0));
                $formula = number_format($pct,2).'%' . ' × US$ ' . number_format($teamBrutoForCosts,2) . ' = US$ ' . number_format($amt,2);
              } else {
                $amt = (float)($c['valor_usd'] ?? 0);
                $formula = '—';
              }
              $amtBrl = $rateNow > 0 ? ($amt * $rateNow) : 0.0;
              $totalUsd += $amt;
              $totalBrl += $amtBrl;
            ?>
            <tr>
              <td><?= htmlspecialchars($c['descricao'] ?? '') ?></td>
              <td><?= ($tipo === 'percent' ? 'percent' : 'fixed') ?></td>
              <td class="text-end">$ <?= number_format($amt, 2) ?></td>
              <td class="text-end">R$ <?= number_format($amtBrl, 2) ?></td>
              <td class="small text-muted"><?= htmlspecialchars($formula) ?></td>
            </tr>
          <?php endforeach; ?>
          <tr>
            <td><strong>Total</strong></td>
            <td>—</td>
            <td class="text-end"><strong>$ <?= number_format($totalUsd, 2) ?></strong></td>
            <td class="text-end"><strong>R$ <?= number_format($totalBrl, 2) ?></strong></td>
            <td class="small text-muted">—</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="row mb-2 mt-3">
  <div class="col-md-4">
    <div class="p-2 border rounded h-100">
      <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted small">Custos Totais (USD) <span class="badge rounded-pill text-bg-info" data-bs-toggle="tooltip" title="Soma dos custos globais (settings), percentuais explícitos e fixos em USD.">?</span></div>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalCustosTotais">Como é calculado</button>
      </div>
      <div class="fw-bold">$ <?= number_format((float)($team['team_cost_total'] ?? 0), 2) ?></div>
      <div class="small text-muted">Settings: <?= number_format((float)($team['team_cost_settings_rate'] ?? 0)*100,2) ?>% | Percent: <?= number_format((float)($team['team_cost_percent_rate'] ?? 0)*100,2) ?>% | Fixos: $ <?= number_format((float)($team['team_cost_fixed_usd'] ?? 0), 2) ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="p-2 border rounded h-100">
      <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted small">Cota Igualitária por Elegível <span class="badge rounded-pill text-bg-info" data-bs-toggle="tooltip" title="Parcela de custos rateada igualmente entre vendedores elegíveis.">?</span></div>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalCotaIgualitaria">Como é calculado</button>
      </div>
      <div class="fw-bold">$ <?= number_format((float)($team['equal_cost_share_per_active_seller'] ?? 0), 2) ?></div>
      <div class="small text-muted">Elegíveis p/ rateio: <?= (int)($team['active_cost_split_count'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="p-2 border rounded h-100">
      <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted small">Meta Equipe (BRL) e Bônus <span class="badge rounded-pill text-bg-info" data-bs-toggle="tooltip" title="Alvo da equipe em BRL e regra de bônus proporcional por vendedor ativo.">?</span></div>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalMetaBonus">Como é calculado</button>
      </div>
      <div class="small">Meta: R$ <?= number_format((float)($team['meta_equipe_brl'] ?? 0), 2) ?></div>
      <div class="small">Aplica bônus? <?= !empty($team['apply_bonus']) ? 'Sim' : 'Não' ?></div>
      <div class="small">Ativos: <?= (int)($team['active_count'] ?? 0) ?> | Rate: <?= number_format((float)($team['bonus_rate'] ?? 0)*100, 2) ?>%</div>
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
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Custos da Empresa (detalhamento) <span class="badge rounded-pill text-bg-info" data-bs-toggle="tooltip" title="Detalhe das parcelas de custos: settings (global), percentuais explícitos e fixos.">?</span></span>
    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalCustosDetalhamento">Como é calculado</button>
  </div>
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
</div>

<div class="card mt-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Comissões por Função <span class="badge rounded-pill text-bg-info" data-bs-toggle="tooltip" title="Somatório das comissões finais agrupado por função (seller, manager, trainee, etc).">?</span></span>
    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalByRole">Como é calculado</button>
  </div>
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

<div class="card mt-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Vendedores/Equipe (período) <span class="badge rounded-pill text-bg-info" data-bs-toggle="tooltip" title="Resumo por vendedor: bruto, líquido, custo alocado, líquido apurado e comissão final.">?</span></span>
    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalVendedores">Como é calculado</button>
  </div>
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

<div class="card mt-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Atendimentos dos Vendedores <span class="badge rounded-pill text-bg-info" data-bs-toggle="tooltip" title="Resumo do período com número de atendimentos de hoje, concluídos e a data do último atendimento.">?</span></span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped mb-0 align-middle">
        <thead>
          <tr>
            <th>Vendedor</th>
            <th class="text-end">Atendimentos (hoje)</th>
            <th class="text-end">Concluídos (hoje)</th>
            <th class="text-end">Atendimentos (período)</th>
            <th class="text-end">Concluídos (período)</th>
            <th class="text-end">Último atendimento</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($comm['items'] ?? []) as $it): ?>
            <?php $uid = (int)($it['vendedor_id'] ?? 0); $att = $attendanceByUser[$uid] ?? null; ?>
            <tr>
              <td><?= htmlspecialchars($it['user']['name'] ?? '') ?></td>
              <td class="text-end"><?= (int)($att['today_total'] ?? 0) ?></td>
              <td class="text-end"><?= (int)($att['today_done'] ?? 0) ?></td>
              <td class="text-end"><?= (int)($att['period_total'] ?? 0) ?></td>
              <td class="text-end"><?= (int)($att['period_done'] ?? 0) ?></td>
              <td class="text-end"><?= htmlspecialchars((string)($att['last_att_date'] ?? '-')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
