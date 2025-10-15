<?php /** @var array $items */ ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Compras</h3>
    <div>
      <a class="btn btn-sm btn-primary me-2" href="/admin/purchases/new">Nova Compra</a>
      <?php
        $qs = [];
        if (!empty($status)) $qs['status'] = $status;
        if (!empty($responsavel_id)) $qs['responsavel_id'] = $responsavel_id;
        if (!empty($from)) $qs['from'] = $from;
        if (!empty($to)) $qs['to'] = $to;
        $qstr = http_build_query($qs);
      ?>
      <a class="btn btn-sm btn-outline-secondary" href="/admin/purchases/export<?= $qstr?('?'.$qstr):'' ?>">Exportar CSV</a>
    </div>
<script>
(function(){
  const idInp = document.getElementById('resp_id');
  const sInp = document.getElementById('resp_search');
  const dd = document.getElementById('resp_dd');
  if (!idInp || !sInp || !dd) return;
  function pos(){ const r=sInp.getBoundingClientRect(); dd.style.left=(r.left+window.scrollX)+'px'; dd.style.top=(r.bottom+window.scrollY+4)+'px'; dd.style.width=r.width+'px'; dd.style.position='absolute'; dd.style.zIndex='1050'; }
  function open(){ pos(); dd.style.display='block'; }
  function close(){ dd.style.display='none'; dd.innerHTML=''; }
  let t=null;
  sInp.addEventListener('input', function(){ const q=sInp.value.trim(); if (t) clearTimeout(t); if (q.length<1){ close(); return; } t=setTimeout(async()=>{ try{ const res=await fetch('/admin/users/options?q='+encodeURIComponent(q)); const arr=await res.json(); dd.innerHTML=''; arr.forEach(it=>{ const a=document.createElement('a'); a.href='#'; a.className='list-group-item list-group-item-action'; a.textContent = it.text+' (#'+it.id+')'; a.addEventListener('click', function(e){ e.preventDefault(); idInp.value=it.id; sInp.value=it.text; close(); }); dd.appendChild(a); }); open(); }catch(e){ close(); } }, 250); });
  document.addEventListener('click', function(e){ if (!dd.contains(e.target) && e.target!==sInp) close(); });
})();
</script>
  </div>

  <form class="row g-2 mb-3" method="get" action="/admin/purchases">
    <div class="col-auto">
      <label class="form-label">Status</label>
      <?php $status = $status ?? ''; ?>
      <select class="form-select" name="status">
        <option value="">Todos</option>
        <option value="pendente" <?= $status==='pendente'?'selected':'' ?>>Pendentes</option>
        <option value="comprado" <?= $status==='comprado'?'selected':'' ?>>Comprados</option>
      </select>
    </div>
    <div class="col-auto">
      <label class="form-label">Responsável</label>
      <input type="hidden" name="responsavel_id" id="resp_id" value="<?= htmlspecialchars((string)($responsavel_id ?? '')) ?>">
      <input type="text" id="resp_search" class="form-control" placeholder="Buscar usuário">
      <div id="resp_dd" class="list-group" style="position:absolute; z-index:1050; display:none;"></div>
    </div>
    <div class="col-auto">
      <label class="form-label">De</label>
      <input type="date" name="from" value="<?= htmlspecialchars($from ?? '') ?>" class="form-control">
    </div>
    <div class="col-auto">
      <label class="form-label">Até</label>
      <input type="date" name="to" value="<?= htmlspecialchars($to ?? '') ?>" class="form-control">
    </div>
    <div class="col-auto align-self-end">
      <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>Venda</th>
          <th>Cliente</th>
          <th>Link</th>
          <th class="text-end">Valor (USD)</th>
          <th>NC 7%</th>
          <th>Frete</th>
          <th>Status</th>
          <th>Responsável</th>
          <th>Data Compra</th>
          <th>Obs</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr><td colspan="11" class="text-center text-muted">Sem registros</td></tr>
        <?php else: foreach ($items as $p): ?>
          <tr>
            <td>#<?= (int)$p['venda_id'] ?> <?= htmlspecialchars($p['suite'] ?? '') ?></td>
            <td>
              <div class="fw-semibold"><?= htmlspecialchars($p['cliente_nome'] ?? '-') ?></div>
              <div class="text-muted small">Contato: <?= htmlspecialchars($p['cliente_contato'] ?? '-') ?></div>
            </td>
            <td class="text-break"><a href="<?= htmlspecialchars($p['produto_link']) ?>" target="_blank" rel="noopener">Abrir</a></td>
            <td class="text-end">$ <?= number_format((float)($p['valor_usd'] ?? 0), 2) ?></td>
            <td><?= ((int)($p['nc_tax'] ?? 0) === 1) ? 'Sim' : 'Não' ?></td>
            <td>
              <?php if ((int)($p['frete_aplicado'] ?? 0) === 1): ?>
                <span class="badge text-bg-info">Manual</span>
                <div class="small">$ <?= number_format((float)($p['frete_valor'] ?? 0), 2) ?></div>
              <?php else: ?>
                <span class="text-muted small">N/D</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge <?= ((int)($p['comprado'] ?? 0) === 1) ? 'bg-success' : 'bg-warning text-dark' ?>">
                <?= ((int)($p['comprado'] ?? 0) === 1) ? 'Comprado' : 'Pendente' ?>
              </span>
            </td>
            <td><?= htmlspecialchars((string)($p['responsavel_id'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($p['data_compra'] ?? '')) ?></td>
            <td class="small text-muted" style="max-width: 240px;"><?= nl2br(htmlspecialchars((string)($p['observacoes'] ?? ''))) ?></td>
            <td style="min-width: 280px;">
              <form method="post" action="/admin/purchases/update" class="row g-1 align-items-end">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Core\Auth::csrf()) ?>">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <div class="col-auto">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="nc_tax" value="1" <?= ((int)($p['nc_tax'] ?? 0) === 1)?'checked':'' ?>>
                    <label class="form-check-label">NC 7%</label>
                  </div>
                </div>
                <div class="col-auto">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="frete_aplicado" value="1" <?= ((int)($p['frete_aplicado'] ?? 0) === 1)?'checked':'' ?>>
                    <label class="form-check-label">Frete</label>
                  </div>
                </div>
                <div class="col-auto" style="width:120px">
                  <input type="number" step="0.01" min="0" class="form-control" name="frete_valor" value="<?= htmlspecialchars((string)($p['frete_valor'] ?? '')) ?>" placeholder="Frete $">
                </div>
                <div class="col-auto">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="comprado" value="1" <?= ((int)($p['comprado'] ?? 0) === 1)?'checked':'' ?>>
                    <label class="form-check-label">Comprado</label>
                  </div>
                </div>
                <div class="col-auto">
                  <input type="date" class="form-control" name="data_compra" value="<?= htmlspecialchars((string)($p['data_compra'] ?? '')) ?>">
                </div>
                <div class="col-auto" style="width:110px">
                  <input type="number" class="form-control" name="responsavel_id" value="<?= htmlspecialchars((string)($p['responsavel_id'] ?? '')) ?>" placeholder="Resp ID">
                </div>
                <div class="col-auto" style="width:200px">
                  <input type="text" class="form-control" name="observacoes" value="<?= htmlspecialchars((string)($p['observacoes'] ?? '')) ?>" placeholder="Observações">
                </div>
                <div class="col-auto">
                  <button class="btn btn-sm btn-primary">Salvar</button>
                </div>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
