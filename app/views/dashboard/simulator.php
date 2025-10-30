<?php
// Simulator view: mirrors admin dashboard layout, plus a form to adjust costs
$role = (string) ((\Core\Auth::user()['role'] ?? 'seller'));
if ($role !== 'admin') { echo '<div class="alert alert-danger">Acesso restrito.</div>'; return; }
$k = $admin_data['admin_kpis'] ?? [];
$c = $admin_data['charts'] ?? [];
$sim = $admin_data['sim'] ?? [];
$rate = (float)($rate ?? 0);
$tot = $admin_data['totals'] ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="m-0">Simulador de Custos</h4>
  <div class="d-flex gap-2 align-items-center">
    <span class="badge text-bg-secondary">Período: <?= htmlspecialchars($period_from ?? '') ?> a <?= htmlspecialchars($period_to ?? '') ?></span>
    <span class="badge text-bg-secondary">Câmbio: 1 USD = R$ <?= number_format((float)($rate ?? 0), 2) ?></span>
  </div>
</div>

<div class="row g-3 mt-2">
  <div class="col-md-3">
    <div class="card text-white" style="background: linear-gradient(135deg, #557CFF, #4E5DFF);">
      <div class="card-body">
        <div class="small">Bruto da Empresa</div>
        <div class="display-6 fw-bold">$ <?= number_format((float)($k['team_bruto_total'] ?? 0), 2) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-white" style="background: linear-gradient(135deg, #557CFF, #4E5DFF);">
      <div class="card-body">
        <div class="small">Pedidos</div>
        <div class="display-6 fw-bold"><?= (int)($k['orders_count'] ?? 0) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-white" style="background: linear-gradient(135deg, #557CFF, #4E5DFF);">
      <div class="card-body">
        <div class="small">Impostos (Global)</div>
        <div class="display-6 fw-bold">$ <?= number_format(((float)($k['team_bruto_total'] ?? 0)) * ((float)($k['global_cost_rate'] ?? 0)), 2) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-white" style="background: linear-gradient(135deg, #557CFF, #4E5DFF);">
      <div class="card-body">
        <div class="small">Total de Custos (Simulado)</div>
        <div class="display-6 fw-bold">$ <?= number_format((float)($tot['total_sim'] ?? 0), 2) ?></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-2">
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-muted small">Vendedores Ativos</div>
        <div class="fs-3 fw-bold mb-3"><?= (int)($k['active_sellers'] ?? 0) ?></div>
        <div class="text-muted small">Comissões a Pagar</div>
        <div class="fs-5 fw-bold">$ <?= number_format((float)($k['sum_commissions_usd'] ?? 0), 2) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body d-flex flex-column justify-content-between">
        <div>
          <div class="text-muted small">Pro-labore</div>
          <div class="fs-4 fw-bold">$ <?= number_format((float)($k['prolabore_usd'] ?? 0), 2) ?></div>
        </div>
        <div class="mt-2">
          <div class="text-muted small">Caixa</div>
          <div class="fs-5 fw-bold">$ <?= number_format((float)($k['company_cash_usd'] ?? 0), 2) ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-2">
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="fs-6 text-muted mb-2">Vendas por Vendedor (Qtd)</div>
        <canvas id="chartPie" height="180"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card h-100">
      <div class="card-body">
        <div class="fs-6 text-muted mb-2">Valor Vendido por Vendedor (USD)</div>
        <canvas id="chartLine" height="180"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-2">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fs-6 text-muted">Custos da Empresa (Simulado)</div>
          <small class="text-muted">Somente nesta tela</small>
        </div>
        <canvas id="chartBar" height="200"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="fs-6 text-muted mb-2">Custos (Ajuste Rápido)</div>
        <form method="post">
          <div class="mb-2">Custos explícitos</div>
          <!-- Impostos (Global) inline -->
          <div class="row g-2 align-items-end mb-2">
            <div class="col-md-5">
              <label class="form-label">Descrição</label>
              <input type="text" class="form-control" value="Impostos (Global)" disabled>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tipo</label>
              <select class="form-select" disabled>
                <option selected>Percentual (%)</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Valor (%)</label>
              <input type="text" class="form-control num-percent" name="sim[cost_rate_pct]" value="<?= htmlspecialchars((string)($sim['cost_rate_pct'] ?? 0)) ?>" placeholder="ex.: 15,00">
            </div>
          </div>
          <!-- Demais custos explícitos do período -->
          <?php $source = $sim['explicit_source'] ?? []; foreach ($source as $i => $row): ?>
            <div class="row g-2 align-items-end mb-2">
              <div class="col-md-5">
                <label class="form-label">Descrição</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars((string)($row['descricao'] ?? ($row['categoria'] ?? 'Custo'))) ?>" disabled>
              </div>
              <div class="col-md-3">
                <label class="form-label">Tipo</label>
                <select class="form-select" name="sim[explicit][<?= $i ?>][valor_tipo]">
                  <?php $t = $row['valor_tipo'] ?? 'fixed'; $ov = $sim['explicit'][$i]['valor_tipo'] ?? null; $sel = $ov ?? $t; ?>
                  <option value="fixed" <?= $sel==='fixed'?'selected':'' ?>>USD Fixo</option>
                  <option value="fixed_brl" <?= $sel==='fixed_brl'?'selected':'' ?>>BRL Fixo</option>
                  <option value="percent" <?= $sel==='percent'?'selected':'' ?>>Percentual (%)</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Valor</label>
                <?php $defaultVal = ($sel==='percent') ? (float)($row['valor_percent'] ?? 0) : (float)($row['valor_usd'] ?? 0); $cur = $sim['explicit'][$i]['valor'] ?? $defaultVal; $cls = ($sel==='percent') ? 'num-percent' : 'num-money'; ?>
                <input type="text" class="form-control <?= $cls ?>" name="sim[explicit][<?= $i ?>][valor]" value="<?= htmlspecialchars((string)$cur) ?>" placeholder="ex.: 1.234,56">
              </div>
              <div class="col-md-12 form-check mt-1">
                <input class="form-check-input" type="checkbox" value="1" id="rem<?= $i ?>" name="sim[explicit][<?= $i ?>][remove]">
                <label class="form-check-label" for="rem<?= $i ?>">Remover este custo do simulado</label>
              </div>
            </div>
          <?php endforeach; ?>

          <hr>
          <div class="mb-2 d-flex justify-content-between align-items-center">
            <div>Adicionar novos custos (não serão salvos)</div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddCost">+ Novo custo</button>
          </div>
          <div id="addCosts" data-count="<?= (int)count($sim['add_existing'] ?? []) ?>">
            <?php $adds = $sim['add_existing'] ?? []; foreach ($adds as $j => $r): ?>
              <div class="row g-2 align-items-end mb-2">
                <div class="col-md-5">
                  <label class="form-label">Descrição</label>
                  <input type="text" class="form-control" name="sim[add][<?= $j ?>][descricao]" value="<?= htmlspecialchars((string)($r['descricao'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Tipo</label>
                  <?php $ts = (string)($r['valor_tipo'] ?? 'fixed'); ?>
                  <select class="form-select" name="sim[add][<?= $j ?>][valor_tipo]">
                    <option value="fixed" <?= $ts==='fixed'?'selected':'' ?>>USD Fixo</option>
                    <option value="fixed_brl" <?= $ts==='fixed_brl'?'selected':'' ?>>BRL Fixo</option>
                    <option value="percent" <?= $ts==='percent'?'selected':'' ?>>Percentual (%)</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Valor</label>
                  <?php $clsa = ($ts==='percent') ? 'num-percent' : 'num-money'; ?>
                  <input type="text" class="form-control <?= $clsa ?>" name="sim[add][<?= $j ?>][valor]" value="<?= htmlspecialchars((string)($r['valor'] ?? '0')) ?>" placeholder="0,00">
                </div>
                <div class="col-md-12 form-check mt-1">
                  <input class="form-check-input" type="checkbox" value="1" id="addrem<?= $j ?>" name="sim[add][<?= $j ?>][remove]">
                  <label class="form-check-label" for="addrem<?= $j ?>">Remover este custo adicionado</label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="mt-3">
            <button type="submit" class="btn btn-primary">Aplicar</button>
            <a href="?" class="btn btn-outline-secondary">Limpar</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php $tot = $admin_data['totals'] ?? []; ?>
<div class="row g-3 mt-2">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fs-6 text-muted">Total de Custos (Base)</div>
        </div>
        <div class="row g-2">
          <div class="col-6 small text-muted">Impostos</div><div class="col-6 text-end fw-bold">$ <?= number_format((float)($tot['impostos_base'] ?? 0), 2) ?></div>
          <div class="col-6 small text-muted">Pro-labore</div><div class="col-6 text-end fw-bold">$ <?= number_format((float)($tot['prolabore_base'] ?? 0), 2) ?></div>
          <div class="col-6 small text-muted">Explícitos</div><div class="col-6 text-end fw-bold">$ <?= number_format((float)($tot['explicit_base'] ?? 0), 2) ?></div>
          <div class="col-12"><hr></div>
          <div class="col-6 small text-muted">Total</div><div class="col-6 text-end fw-bold">$ <?= number_format((float)($tot['total_base'] ?? 0), 2) ?></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fs-6 text-muted">Total de Custos (Simulado)</div>
        </div>
        <div class="row g-2">
          <div class="col-6 small text-muted">Impostos</div><div class="col-6 text-end fw-bold">$ <?= number_format((float)($tot['impostos_sim'] ?? 0), 2) ?></div>
          <div class="col-6 small text-muted">Pro-labore</div><div class="col-6 text-end fw-bold">$ <?= number_format((float)($tot['prolabore_sim'] ?? 0), 2) ?></div>
          <div class="col-6 small text-muted">Explícitos</div><div class="col-6 text-end fw-bold">$ <?= number_format((float)($tot['explicit_sim'] ?? 0), 2) ?></div>
          <div class="col-12"><hr></div>
          <div class="col-6 small text-muted">Total</div><div class="col-6 text-end fw-bold">$ <?= number_format((float)($tot['total_sim'] ?? 0), 2) ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function(){
  var charts = <?= json_encode($c ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
  function byId(id){ return document.getElementById(id); }
  var palette = ['#4e79a7','#f28e2b','#e15759','#76b7b2','#59a14f','#edc949','#af7aa1','#ff9da7','#9c755f','#bab0ab','#1f77b4','#ff7f0e','#2ca02c','#d62728','#9467bd'];
  var fmtUSD = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });
  if (charts && byId('chartPie')) {
    var colors = (charts.pie.labels || []).map(function(_, i) { return palette[i % palette.length]; });
    new Chart(byId('chartPie'), {
      type: 'pie',
      data: {
        labels: charts.pie.labels || [],
        datasets: [{
          label: 'Pedidos',
          data: charts.pie.data || [],
          backgroundColor: colors,
          borderColor: '#fff',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'bottom' },
          tooltip: {
            callbacks: {
              label: function(ctx) {
                var l = ctx.label || '';
                var v = ctx.parsed || 0;
                var ds = (ctx.dataset && Array.isArray(ctx.dataset.data)) ? ctx.dataset.data : [];
                var total = 0;
                for (var i = 0; i < ds.length; i++) { total += (ds[i] || 0); }
                if (!total) total = 1;
                var pct = ((v / total) * 100).toFixed(1) + '%';
                return l + ': ' + v + ' (' + pct + ')';
              }
            }
          }
        }
      }
    });
  }
  if (charts && byId('chartLine')) {
    new Chart(byId('chartLine'), {
      type: 'line',
      data: {
        labels: charts.line.labels || [],
        datasets: [{
          label: 'Valor Vendido (USD)',
          data: charts.line.data || [],
          borderColor: '#4e79a7',
          backgroundColor: 'rgba(78,121,167,0.15)',
          tension: 0.25,
          fill: true
        }]
      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true } },
        plugins: {
          tooltip: {
            callbacks: {
              label: function(ctx) { return 'USD ' + fmtUSD.format((ctx.parsed && ctx.parsed.y) || 0); }
            }
          }
        }
      }
    });
  }
  if (charts && byId('chartBar')) {
    var labels = charts.bar.labels || [];
    var colorsSim = labels.map(function(_,i){ return palette[i % palette.length]; });
    var ds = [
      { label: 'Base', data: (charts.bar_base && charts.bar_base.data) ? charts.bar_base.data : [], backgroundColor: 'rgba(78,121,167,0.35)' },
      { label: 'Simulado', data: charts.bar.data || [], backgroundColor: colorsSim }
    ];
    new Chart(byId('chartBar'), { type: 'bar', data: { labels: labels, datasets: ds }, options: { responsive:true, scales:{ y:{ beginAtZero:true } } } });
  }

  // Add cost dynamic rows
  (function(){
    var btn = document.getElementById('btnAddCost');
    if (!btn) return;
    var wrap = document.getElementById('addCosts');
    var idx = parseInt(wrap.getAttribute('data-count') || '0', 10);
    btn.addEventListener('click', function(){
      var row = document.createElement('div');
      row.className = 'row g-2 align-items-end mb-2';
      row.innerHTML = ''+
        '<div class="col-md-5">'+
          '<label class="form-label">Descrição</label>'+
          '<input type="text" class="form-control" name="sim[add]['+idx+'][descricao]" placeholder="Ex.: Pro-Labore Extra">'+
        '</div>'+
        '<div class="col-md-3">'+
          '<label class="form-label">Tipo</label>'+
          '<select class="form-select" name="sim[add]['+idx+'][valor_tipo]">'+
            '<option value="fixed" selected>USD Fixo</option>'+
            '<option value="fixed_brl">BRL Fixo</option>'+
            '<option value="percent">Percentual (%)</option>'+
          '</select>'+
        '</div>'+
        '<div class="col-md-4">'+
          '<label class="form-label">Valor</label>'+
          '<input type="text" class="form-control num-money" name="sim[add]['+idx+'][valor]" placeholder="0,00">'+
        '</div>'+
        '<div class="col-md-12 form-check mt-1">'+
          '<input class="form-check-input" type="checkbox" value="1" id="addrem'+idx+'" name="sim[add]['+idx+'][remove]">'+
          '<label class="form-check-label" for="addrem'+idx+'">Remover este custo adicionado</label>'+
        '</div>';
      wrap.appendChild(row);
      idx++;
    });
  })();

  // BR-style numeric mask
  (function(){
    function addThousands(intStr){
      var out = '', cnt = 0;
      for (var i = intStr.length - 1; i >= 0; i--) {
        out = intStr[i] + out; cnt++;
        if (cnt % 3 === 0 && i !== 0) out = '.' + out;
      }
      return out;
    }
    // Money: 1.234,56 (two decimals, cents shift)
    function formatMoneyBR(val){
      var s = (''+(val||'')).replace(/[^0-9]/g,'');
      if (!s) return '';
      if (s.length === 1) s = '00'+s; else if (s.length === 2) s = '0'+s;
      var intPart = s.slice(0,-2), decPart = s.slice(-2);
      return addThousands(intPart) + ',' + decPart;
    }
    // Percent: keep scale as typed (no cent-shift). Allow optional decimals.
    function formatPercentBR(val){
      var s = (''+(val||''));
      // convert dot to comma if last typed decimal
      s = s.replace(/[^0-9,\.]/g,'').replace(/\./g,',');
      var parts = s.split(',');
      var intDigits = parts[0].replace(/[^0-9]/g,'');
      var decDigits = parts[1] ? parts[1].replace(/[^0-9]/g,'') : '';
      var intFmt = addThousands(intDigits);
      return intFmt + (decDigits !== '' ? (','+decDigits) : '');
    }
    function bindMoney(inp){
      function onInput(e){
        var el = e.target; var posFromEnd = el.value.length - (el.selectionStart||0);
        el.value = formatMoneyBR(el.value);
        var newPos = el.value.length - posFromEnd; if (newPos < 0) newPos = 0; try{ el.setSelectionRange(newPos,newPos); }catch(_){ }
      }
      inp.addEventListener('input', onInput);
      inp.addEventListener('blur', function(){ inp.value = formatMoneyBR(inp.value); });
      if (inp.value && /[0-9]/.test(inp.value)) inp.value = formatMoneyBR(inp.value);
    }
    function bindPercent(inp){
      function onInput(e){ e.target.value = formatPercentBR(e.target.value); }
      inp.addEventListener('input', onInput);
      inp.addEventListener('blur', function(){ inp.value = formatPercentBR(inp.value); });
      if (inp.value) inp.value = formatPercentBR(inp.value);
    }
    document.querySelectorAll('.num-money').forEach(bindMoney);
    document.querySelectorAll('.num-percent').forEach(bindPercent);

    // Toggle input class when type changes
    function toggleForSelect(sel){
      var row = sel.closest('.row'); if (!row) return;
      var input = row.querySelector('input[name$="[valor]"]'); if (!input) return;
      input.classList.remove('num-money','num-percent');
      if (sel.value === 'percent') { input.classList.add('num-percent'); bindPercent(input); }
      else { input.classList.add(sel.value === 'fixed_brl' ? 'num-money' : 'num-money'); bindMoney(input); }
    }
    document.querySelectorAll('select[name^="sim[explicit]"][name$="[valor_tipo]"]').forEach(function(s){ s.addEventListener('change', function(){ toggleForSelect(s); }); });
    document.querySelectorAll('select[name^="sim[add]"][name$="[valor_tipo]"]').forEach(function(s){ s.addEventListener('change', function(){ toggleForSelect(s); }); });
  })();

  // Optional normalize on submit (server also normalizes)
  (function(){
    var form = document.querySelector('form[method="post"]');
    if (!form) return;
    form.addEventListener('submit', function(){
      // noop: backend normaliza; aqui apenas trim
      document.querySelectorAll('.num').forEach(function(inp){ inp.value = (inp.value||'').trim(); });
    });
  })();
})();
</script>
