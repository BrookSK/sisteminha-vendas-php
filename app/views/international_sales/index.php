<?php /** @var array $items */ ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Vendas (EUA / Brasil)</h3>
    <div class="d-flex gap-2">
      <a class="btn btn-sm btn-success" href="/admin/international-sales/new">Nova Venda EUA</a>
      <a class="btn btn-sm btn-success" href="/admin/national-sales/new">Nova Venda Brasil</a>
    </div>
  </div>

  <form class="row g-2 mb-3" method="get" action="/admin/international-sales">
    <?php if (in_array((Core\Auth::user()['role'] ?? 'seller'), ['manager','admin'], true)): ?>
    <div class="col-auto">
      <label class="form-label">Vendedor</label>
      <select class="form-select" name="seller_id">
        <option value="">Todos</option>
        <?php foreach (($users ?? []) as $u): ?>
          <option value="<?= (int)$u['id'] ?>" <?= isset($seller_id) && (int)$seller_id === (int)$u['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($u['name'] ?: $u['email']) ?> (<?= htmlspecialchars($u['role']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="col-auto">
      <label class="form-label">Mês</label>
      <input type="month" class="form-control" name="ym" value="<?= htmlspecialchars((string)($ym ?? '')) ?>">
    </div>
    <div class="col-auto align-self-end">
      <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
    </div>
  </form>

  <ul class="nav nav-tabs" id="salesTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="eua-tab" data-bs-toggle="tab" data-bs-target="#tab-eua" type="button" role="tab">Vendas EUA</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="br-tab" data-bs-toggle="tab" data-bs-target="#tab-br" type="button" role="tab">Vendas Brasil</button>
    </li>
  </ul>
  <div class="tab-content p-3 border border-top-0">
    <div class="tab-pane fade show active" id="tab-eua" role="tabpanel">
      <div class="d-flex justify-content-end mb-2">
        <a class="btn btn-sm btn-outline-secondary" href="/admin/international-sales/export<?= isset($seller_id) && $seller_id ? ('?seller_id='.(int)$seller_id.($ym?('&ym='.urlencode($ym)):'') ) : ($ym?('?ym='.urlencode($ym)):'') ?>">Exportar CSV (EUA)</a>
      </div>
      <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="small text-muted" id="info-eua"></div>
        <div class="btn-group" role="group">
          <button class="btn btn-sm btn-outline-secondary" id="prev-eua">Anterior</button>
          <button class="btn btn-sm btn-outline-secondary" id="next-eua">Próximo</button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-striped align-middle" id="tbl-eua">
          <thead>
            <tr>
              <th>Data</th>
              <th>Pedido</th>
              <th>Cliente</th>
              <th>Suite</th>
              <th class="text-end">Bruto (USD)</th>
              <th class="text-end">Bruto (BRL)</th>
              <th class="text-end">Líquido (USD)</th>
              <th class="text-end">Líquido (BRL)</th>
              <th class="text-end">Comissão (USD)</th>
              <th class="text-end">Comissão (BRL)</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
    <div class="tab-pane fade" id="tab-br" role="tabpanel">
      <div class="d-flex justify-content-end mb-2">
        <a class="btn btn-sm btn-outline-secondary" href="/admin/national-sales/export<?= isset($seller_id) && $seller_id ? ('?seller_id='.(int)$seller_id.($ym?('&ym='.urlencode($ym)):'') ) : ($ym?('?ym='.urlencode($ym)):'') ?>">Exportar CSV (Brasil)</a>
      </div>
      <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="small text-muted" id="info-br"></div>
        <div class="btn-group" role="group">
          <button class="btn btn-sm btn-outline-secondary" id="prev-br">Anterior</button>
          <button class="btn btn-sm btn-outline-secondary" id="next-br">Próximo</button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-striped align-middle" id="tbl-br">
          <thead>
            <tr>
              <th>Data</th>
              <th>Pedido</th>
              <th>Cliente</th>
              <th>Suite</th>
              <th class="text-end">Bruto (USD)</th>
              <th class="text-end">Bruto (BRL)</th>
              <th class="text-end">Líquido (USD)</th>
              <th class="text-end">Líquido (BRL)</th>
              <th class="text-end">Comissão (USD)</th>
              <th class="text-end">Comissão (BRL)</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  // Minimal DataTables-like loading via fetch (no dependency). If DataTables is available, replace with $('#tbl-eua').DataTable({ ajax: ... })
  const params = new URLSearchParams({
    seller_id: '<?= htmlspecialchars((string)($seller_id ?? '')) ?>',
    ym: '<?= htmlspecialchars((string)($ym ?? '')) ?>'
  });
  const CSRF = '<?= htmlspecialchars(Core\Auth::csrf()) ?>';
  let pageEua = 1, pageBr = 1, per = 20;
  function renderRows(tbl, rows) {
    const tbody = tbl.querySelector('tbody');
    tbody.innerHTML='';
    rows.forEach(s=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${(s.data_lancamento||'')}</td>
        <td>${(s.numero_pedido||'')}</td>
        <td>${(s.cliente_nome||('#'+s.cliente_id))}</td>
        <td>${(s.suite_cliente||'')}</td>
        <td class="text-end">$ ${(parseFloat(s.total_bruto_usd||0)).toFixed(2)}</td>
        <td class="text-end">R$ ${(parseFloat(s.total_bruto_brl||0)).toFixed(2)}</td>
        <td class="text-end">$ ${(parseFloat(s.total_liquido_usd||0)).toFixed(2)}</td>
        <td class="text-end">R$ ${(parseFloat(s.total_liquido_brl||0)).toFixed(2)}</td>
        <td class="text-end">$ ${(parseFloat(s.comissao_usd||0)).toFixed(2)}</td>
        <td class="text-end">R$ ${(parseFloat(s.comissao_brl||0)).toFixed(2)}</td>
        <td>
          <a class="btn btn-sm btn-outline-primary me-1" href="/admin/international-sales/edit?id=${s.id}">Editar</a>
          <a class="btn btn-sm btn-outline-secondary me-1" href="/admin/international-sales/duplicate?id=${s.id}">Duplicar</a>
          <form method="post" action="/admin/international-sales/delete" class="d-inline" onsubmit="return confirm('Excluir esta venda?');">
            <input type="hidden" name="_csrf" value="${CSRF}">
            <input type="hidden" name="id" value="${s.id}">
            <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
          </form>
        </td>
      `;
      tbody.appendChild(tr);
    });
  }
  function loadEua(){
    const p = new URLSearchParams(params);
    p.set('page', String(pageEua)); p.set('per', String(per));
    fetch('/admin/international-sales/data?'+p.toString())
      .then(r=>r.json())
      .then(j=>{
        renderRows(document.getElementById('tbl-eua'), j.data||[]);
        const total = j.total||0; const pages = Math.max(1, Math.ceil(total/per));
        document.getElementById('info-eua').textContent = `Página ${pageEua} de ${pages} (total ${total})`;
        document.getElementById('prev-eua').disabled = pageEua<=1;
        document.getElementById('next-eua').disabled = pageEua>=pages;
      });
  }
  function loadBr(){
    const p = new URLSearchParams(params);
    p.set('page', String(pageBr)); p.set('per', String(per));
    fetch('/admin/national-sales/data?'+p.toString())
      .then(r=>r.json())
      .then(j=>{
        // Link edit to national controller
        const rows = (j.data||[]).map(s=>{
          s._editUrl = '/admin/national-sales/edit?id='+s.id; return s;
        });
        const tbl = document.getElementById('tbl-br');
        const tbody = tbl.querySelector('tbody');
        tbody.innerHTML='';
        rows.forEach(s=>{
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${(s.data_lancamento||'')}</td>
            <td>${(s.numero_pedido||'')}</td>
            <td>${(s.cliente_nome||('#'+s.cliente_id))}</td>
            <td>${(s.suite_cliente||'')}</td>
            <td class="text-end">$ ${(parseFloat(s.total_bruto_usd||0)).toFixed(2)}</td>
            <td class="text-end">R$ ${(parseFloat(s.total_bruto_brl||0)).toFixed(2)}</td>
            <td class="text-end">$ ${(parseFloat(s.total_liquido_usd||0)).toFixed(2)}</td>
            <td class="text-end">R$ ${(parseFloat(s.total_liquido_brl||0)).toFixed(2)}</td>
            <td class="text-end">$ ${(parseFloat(s.comissao_usd||0)).toFixed(2)}</td>
            <td class="text-end">R$ ${(parseFloat(s.comissao_brl||0)).toFixed(2)}</td>
            <td>
              <a class="btn btn-sm btn-outline-primary me-1" href="${s._editUrl}">Editar</a>
              <a class="btn btn-sm btn-outline-secondary me-1" href="/admin/national-sales/duplicate?id=${s.id}">Duplicar</a>
              <form method="post" action="/admin/national-sales/delete" class="d-inline" onsubmit="return confirm('Excluir esta venda?');">
                <input type="hidden" name="_csrf" value="${CSRF}">
                <input type="hidden" name="id" value="${s.id}">
                <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
              </form>
            </td>
          `;
          tbody.appendChild(tr);
        });
        const total = j.total||0; const pages = Math.max(1, Math.ceil(total/per));
        document.getElementById('info-br').textContent = `Página ${pageBr} de ${pages} (total ${total})`;
        document.getElementById('prev-br').disabled = pageBr<=1;
        document.getElementById('next-br').disabled = pageBr>=pages;
      });
  }
  document.getElementById('prev-eua').addEventListener('click', ()=>{ if (pageEua>1){ pageEua--; loadEua(); } });
  document.getElementById('next-eua').addEventListener('click', ()=>{ pageEua++; loadEua(); });
  document.getElementById('prev-br').addEventListener('click', ()=>{ if (pageBr>1){ pageBr--; loadBr(); } });
  document.getElementById('next-br').addEventListener('click', ()=>{ pageBr++; loadBr(); });
  loadEua();
  loadBr();
})();
</script>
