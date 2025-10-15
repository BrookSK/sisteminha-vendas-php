<?php use Core\Auth; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Custos</h5>
</div>
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
      <div class="col-12 d-grid">
        <button class="btn btn-primary" type="submit">Adicionar Custo</button>
      </div>
    </form>
    <script>
      (function(){
        const recTipo = document.getElementById('recorrente_tipo');
        const pc = document.getElementById('parcelas_container');
        function toggRec(){ pc.classList.toggle('d-none', !recTipo || recTipo.value !== 'installments'); }
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
        <th>Valor (USD)</th>
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
          <td>$ <?= number_format((float)($c['valor_usd'] ?? 0), 2) ?></td>
          <td class="text-end">
            <form method="post" action="/admin/costs/delete" class="d-inline" onsubmit="return confirm('Excluir este custo?');">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
