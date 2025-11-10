<?php use Core\Auth; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Custos</h5>
</div>

<script>
(function(){
  // Intercept delete submits to ask scope
  document.addEventListener('submit', function(ev){
    const form = ev.target;
    if (!form.classList || !form.classList.contains('form-cost-delete')) return;
    ev.preventDefault();
    let scope = 'one';
    const all = window.confirm('Excluir toda a recorrência (este e os seguintes)?\nOK = toda a recorrência\nCancelar = somente este');
    if (all) {
      scope = 'series_future';
    } else {
      const sureOne = window.confirm('Excluir somente este custo?');
      if (!sureOne) return; // user canceled
    }
    const inp = form.querySelector('input[name="scope"]');
    if (inp) inp.value = scope;
    form.submit();
  });
})();
</script>
<?php if ((Core\Auth::user()['role'] ?? 'seller') === 'admin'): ?>
<!-- Edit Modal -->
<div class="modal" id="editCostModal" tabindex="-1" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4);">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editar Custo</h5>
        <button type="button" class="btn-close" id="editCostClose"></button>
      </div>
      <form method="post" action="/admin/costs/update" id="editCostForm">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
        <input type="hidden" name="id" id="ec_id" value="0">
        <div class="modal-body">
          <div class="row g-2">
            <div class="col-md-4">
              <label class="form-label">Data</label>
              <input type="date" class="form-control" name="data" id="ec_data" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Categoria</label>
              <input type="text" class="form-control" name="categoria" id="ec_categoria" required>
            </div>
            <div class="col-md-12">
              <label class="form-label">Descrição</label>
              <input type="text" class="form-control" name="descricao" id="ec_descricao">
            </div>
            <div class="col-md-4">
              <label class="form-label">Tipo de Valor</label>
              <select class="form-select" name="valor_tipo" id="ec_tipo">
                <option value="usd">USD</option>
                <option value="brl">BRL</option>
                <option value="percent">%</option>
              </select>
            </div>
            <div class="col-md-4" id="ec_box_usd">
              <label class="form-label">Valor (USD)</label>
              <input type="number" step="0.01" min="0" class="form-control" name="valor_usd" id="ec_usd" value="0">
            </div>
            <div class="col-md-4 d-none" id="ec_box_brl">
              <label class="form-label">Valor (BRL)</label>
              <input type="number" step="0.01" min="0" class="form-control" name="valor_brl" id="ec_brl" value="0">
            </div>
            <div class="col-md-4" id="ec_box_percent">
              <label class="form-label">Valor (%)</label>
              <input type="number" step="0.01" min="0" max="100" class="form-control" name="valor_percent" id="ec_percent" value="0">
            </div>
            <div class="col-md-4">
              <label class="form-label">Recorrência</label>
              <select class="form-select" name="recorrente_tipo" id="ec_recorrente_tipo">
                <option value="none">Sem recorrência</option>
                <option value="weekly">Semanal</option>
                <option value="monthly">Mensal</option>
                <option value="yearly">Anual</option>
                <option value="installments">Parcelado / Dívida</option>
              </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="recorrente_ativo" value="1" id="ec_recorrente_ativo">
                <label class="form-check-label" for="ec_recorrente_ativo">Gerar automaticamente</label>
              </div>
            </div>
            <div class="col-md-4 d-none" id="ec_parcelas_container">
              <label class="form-label">Qtd Parcelas</label>
              <input type="number" class="form-control" min="1" name="parcelas_total" id="ec_parcelas_total" value="1">
            </div>
            <div class="col-md-6 d-none" id="ec_align_period_container">
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" name="align_period" value="1" id="ec_align_period">
                <label class="form-check-label" for="ec_align_period">Alinhar ao período (10→09)</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" id="editCostCancel">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>
  <script>
    (function(){
      const modal = document.getElementById('editCostModal');
      const closeBtn = document.getElementById('editCostClose');
      const cancelBtn = document.getElementById('editCostCancel');
      function openM(){ if (modal) modal.style.display='block'; }
      function closeM(){ if (modal) modal.style.display='none'; }
      if (closeBtn) closeBtn.addEventListener('click', closeM);
      if (cancelBtn) cancelBtn.addEventListener('click', closeM);
      document.addEventListener('click', function(e){
        const btn = e.target.closest('.btn-edit-cost');
        if (!btn) return;
        const id = btn.dataset.id;
        const data = btn.dataset.data;
        const cat = btn.dataset.categoria;
        const desc = btn.dataset.descricao;
        const tipo = btn.dataset.tipo || 'usd';
        const usd = btn.dataset.usd || '0';
        const brl = btn.dataset.brl || '0';
        const pct = btn.dataset.percent || '0';
        document.getElementById('ec_id').value = id;
        document.getElementById('ec_data').value = data;
        document.getElementById('ec_categoria').value = cat;
        document.getElementById('ec_descricao').value = desc;
        const tipoSel = document.getElementById('ec_tipo');
        tipoSel.value = tipo;
        const boxUsd = document.getElementById('ec_box_usd');
        const boxBrl = document.getElementById('ec_box_brl');
        const boxPct = document.getElementById('ec_box_percent');
        document.getElementById('ec_usd').value = usd;
        document.getElementById('ec_brl').value = brl;
        document.getElementById('ec_percent').value = pct;
        function togg(){
          const v = tipoSel.value;
          boxUsd.classList.toggle('d-none', v !== 'usd');
          boxBrl.classList.toggle('d-none', v !== 'brl');
          boxPct.classList.toggle('d-none', v !== 'percent');
        }
        tipoSel.onchange = togg;
        togg();
        // Recurrence toggles (edit)
        const recSel = document.getElementById('ec_recorrente_tipo');
        const parcelasBox = document.getElementById('ec_parcelas_container');
        const alignBox = document.getElementById('ec_align_period_container');
        function toggRecEdit(){
          const rt = recSel ? recSel.value : 'none';
          if (parcelasBox) parcelasBox.classList.toggle('d-none', rt !== 'installments');
          if (alignBox) alignBox.classList.toggle('d-none', !(rt === 'monthly' || rt === 'installments'));
        }
        if (recSel) { recSel.addEventListener('change', toggRecEdit); toggRecEdit(); }
        openM();
      });
      if (modal) modal.addEventListener('click', function(e){ if (e.target===modal) closeM(); });
    })();
  </script>
</div>
<?php endif; ?>
<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" method="post" action="/admin/costs/create">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
      <div class="col-md-3">
        <label class="form-label">Data</label>
        <input type="date" class="form-control" name="data" value="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Categoria</label>
        <input type="text" class="form-control" name="categoria" placeholder="ex: servidor, envio, freelancer" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Descrição</label>
        <input type="text" class="form-control" name="descricao" placeholder="Opcional">
      </div>
      <div class="col-md-2">
        <label class="form-label">Tipo de Valor</label>
        <select class="form-select" name="valor_tipo" id="valor_tipo">
          <option value="usd" selected>USD</option>
          <option value="brl">BRL</option>
          <option value="percent">%</option>
        </select>
      </div>
      <div class="col-md-2" id="box_valor_usd">
        <label class="form-label">Valor (USD)</label>
        <input type="number" step="0.01" min="0" class="form-control" name="valor_usd" value="0">
      </div>
      <div class="col-md-2 d-none" id="box_valor_brl">
        <label class="form-label">Valor (BRL)</label>
        <input type="number" step="0.01" min="0" class="form-control" name="valor_brl" value="0">
      </div>
      <div class="col-md-2 d-none" id="box_valor_percent">
        <label class="form-label">Valor (%)</label>
        <input type="number" step="0.01" min="0" max="100" class="form-control" name="valor_percent" value="0">
        <div class="form-text">Aplicado sobre o Bruto Total do período.</div>
      </div>
      <div class="col-md-3">
        <label class="form-label">Recorrência</label>
        <select class="form-select" name="recorrente_tipo" id="recorrente_tipo">
          <option value="none">Sem recorrência</option>
          <option value="weekly">Semanal</option>
          <option value="monthly">Mensal</option>
          <option value="yearly">Anual</option>
          <option value="installments">Parcelado / Dívida</option>
        </select>
      </div>
      <?php if (in_array((Core\Auth::user()['role'] ?? 'seller'), ['manager','admin'], true)): ?>
      <div class="col-md-3">
        <label class="form-label">&nbsp;</label>
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" name="recorrente_ativo" value="1" id="recorrente_ativo">
          <label class="form-check-label" for="recorrente_ativo">Gerar automaticamente</label>
        </div>
      </div>
      <?php endif; ?>
      <div class="col-md-2 d-none" id="parcelas_container">
        <label class="form-label">Qtd Parcelas</label>
        <input type="number" class="form-control" min="1" name="parcelas_total" value="1">
      </div>
      <div class="col-md-4 d-none" id="align_period_container">
        <label class="form-label">&nbsp;</label>
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" name="align_period" value="1" id="align_period">
          <label class="form-check-label" for="align_period">Alinhar ao período (10→09)</label>
        </div>
      </div>
      <div class="col-12 d-grid">
        <button class="btn btn-primary" type="submit">Adicionar Custo</button>
      </div>
    </form>
    <script>
      (function(){
        const recTipo = document.getElementById('recorrente_tipo');
        const pc = document.getElementById('parcelas_container');
        const alignC = document.getElementById('align_period_container');
        function toggRec(){
          const v = recTipo ? recTipo.value : 'none';
          pc.classList.toggle('d-none', v !== 'installments');
          if (alignC) alignC.classList.toggle('d-none', !(v === 'monthly' || v === 'installments'));
        }
        if (recTipo) { recTipo.addEventListener('change', toggRec); toggRec(); }

        const tipoValor = document.getElementById('valor_tipo');
        const boxUsd = document.getElementById('box_valor_usd');
        const boxBrl = document.getElementById('box_valor_brl');
        const boxPct = document.getElementById('box_valor_percent');
        function toggValor(){
          const v = tipoValor ? tipoValor.value : 'usd';
          if (boxUsd) boxUsd.classList.toggle('d-none', v !== 'usd');
          if (boxBrl) boxBrl.classList.toggle('d-none', v !== 'brl');
          if (boxPct) boxPct.classList.toggle('d-none', v !== 'percent');
        }
        if (tipoValor) { tipoValor.addEventListener('change', toggValor); toggValor(); }
      })();
    </script>
  </div>
</div>

<form class="row g-2 mb-3" method="get" action="/admin/costs">
  <div class="col-sm-3">
    <label class="form-label">De</label>
    <input type="date" class="form-control" name="from" value="<?= htmlspecialchars($from) ?>">
  </div>
  <div class="col-sm-3">
    <label class="form-label">Até</label>
    <input type="date" class="form-control" name="to" value="<?= htmlspecialchars($to) ?>">
  </div>
  <div class="col-sm-2 d-grid align-end">
    <label class="form-label">&nbsp;</label>
    <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
  </div>
  <?php if (in_array((Core\Auth::user()['role'] ?? 'seller'), ['manager','admin'], true)): ?>
  <div class="col-sm-3 d-grid align-end">
    <label class="form-label">&nbsp;</label>
    <form method="post" action="/admin/costs/recurrence/run">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
      <button class="btn btn-outline-secondary" type="submit">Executar recorrências</button>
    </form>
  </div>
  <?php endif; ?>
</form>

<div class="table-responsive">
  <table class="table table-striped align-middle">
    <thead>
      <tr>
        <th>Data</th>
        <th>Categoria</th>
        <th>Descrição</th>
        <th>Valor</th>
        <th class="text-end">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($items)): ?>
        <tr><td colspan="5" class="text-center text-muted">Sem custos</td></tr>
      <?php else: foreach ($items as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['data']) ?></td>
          <td><span class="badge text-bg-secondary"><?= htmlspecialchars($c['categoria']) ?></span></td>
          <td><?= htmlspecialchars($c['descricao'] ?? '') ?></td>
          <td>
            <?php $tipo = $c['valor_tipo'] ?? 'usd'; ?>
            <?php if ($tipo === 'percent'): ?>
              <?= number_format((float)($c['valor_percent'] ?? 0), 2) ?> %
            <?php elseif ($tipo === 'brl'): ?>
              R$ <?= number_format((float)($c['valor_brl'] ?? 0), 2, ',', '.') ?>
            <?php else: ?>
              <?php $usdVal = (float)($c['valor_usd'] ?? 0); $rate = isset($usd_rate) ? (float)$usd_rate : 5.83; $brlEq = $usdVal * ($rate > 0 ? $rate : 5.83); ?>
              $ <?= number_format($usdVal, 2) ?> <small class="text-muted">(≈ R$ <?= number_format($brlEq, 2, ',', '.') ?>)</small>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <?php if ((Core\Auth::user()['role'] ?? 'seller') === 'admin'): ?>
            <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-edit-cost"
              data-id="<?= (int)$c['id'] ?>"
              data-data="<?= htmlspecialchars($c['data']) ?>"
              data-categoria="<?= htmlspecialchars($c['categoria']) ?>"
              data-descricao="<?= htmlspecialchars($c['descricao'] ?? '') ?>"
              data-tipo="<?= htmlspecialchars($c['valor_tipo'] ?? 'usd') ?>"
              data-usd="<?= htmlspecialchars((string)($c['valor_usd'] ?? '0')) ?>"
              data-brl="<?= htmlspecialchars((string)($c['valor_brl'] ?? '0')) ?>"
              data-percent="<?= htmlspecialchars((string)($c['valor_percent'] ?? '0')) ?>"
            >Editar</button>
            <?php endif; ?>
            <form method="post" action="/admin/costs/delete" class="d-inline form-cost-delete">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <input type="hidden" name="scope" value="one">
              <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
