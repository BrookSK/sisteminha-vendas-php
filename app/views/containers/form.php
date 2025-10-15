<?php /** @var array $row */ /** @var array $statuses */ ?>
<?php use Core\Auth; ?>
<div class="container py-3">
  <h3 class="mb-3"><?= htmlspecialchars($title ?? 'Container') ?></h3>

  <form method="post" action="<?= htmlspecialchars($action ?? '') ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">

    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label">Utilizador</label>
        <?php $uid = (string)($row['utilizador_id'] ?? (Auth::user()['id'] ?? '')); ?>
        <input type="hidden" name="utilizador_id" id="utilizador_id" value="<?= htmlspecialchars($uid) ?>">
        <input type="text" class="form-control" id="utilizador_search" placeholder="Buscar usuário" value="">
        <div id="utilizador_dd" class="list-group" style="position:absolute; z-index:1050; display:none;"></div>
      </div>
      <div class="col-md-3">
        <label class="form-label">Invoice ID</label>
        <input type="text" class="form-control" name="invoice_id" value="<?= htmlspecialchars((string)($row['invoice_id'] ?? '')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Criado em</label>
        <input type="date" class="form-control" name="created_at" value="<?= htmlspecialchars((string)($row['created_at'] ?? date('Y-m-d'))) ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <?php $st = (string)($row['status'] ?? 'Em preparo'); ?>
        <select class="form-select" name="status">
          <?php foreach (($statuses ?? []) as $s): ?>
            <option value="<?= htmlspecialchars($s) ?>" <?= $st===$s?'selected':'' ?>><?= htmlspecialchars($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">Peso (KG)</label>
        <input type="number" step="0.01" min="0" class="form-control" name="peso_kg" value="<?= htmlspecialchars((string)($row['peso_kg'] ?? '0')) ?>" required>
        <div class="form-text">lbs/kg atual: <?= htmlspecialchars((string)($lbs_per_kg ?? '2.2')) ?></div>
      </div>
      <div class="col-md-3">
        <label class="form-label">Transporte Aeroporto → Correios (R$)</label>
        <input type="number" step="0.01" min="0" class="form-control" name="transporte_aeroporto_correios_brl" value="<?= htmlspecialchars((string)($row['transporte_aeroporto_correios_brl'] ?? '0')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Transporte de Mercadoria (US$)</label>
        <input type="number" step="0.01" min="0" class="form-control" name="transporte_mercadoria_usd" value="<?= htmlspecialchars((string)($row['transporte_mercadoria_usd'] ?? '0')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Vendas</label>
        <?php $vids = (string)($row['vendas_ids'] ?? ''); ?>
        <input type="hidden" name="vendas_ids" id="vendas_ids" value="<?= htmlspecialchars($vids) ?>">
        <input type="text" class="form-control" id="vendas_search" placeholder="Buscar por número do pedido ou ID">
        <div class="small text-muted mt-1" id="vendas_tags"></div>
        <div id="vendas_dd" class="list-group" style="position:absolute; z-index:1050; display:none;"></div>
      </div>
    </div>

    <div class="mt-4">
      <button class="btn btn-primary" type="submit">Salvar</button>
      <a class="btn btn-outline-secondary" href="/admin/containers">Voltar</a>
    </div>
  </form>
</div>
<script>
(function(){
  function dd(el){ el.style.position='absolute'; el.style.zIndex='1050'; }
  const uIn = document.getElementById('utilizador_id');
  const uS = document.getElementById('utilizador_search');
  const uDd = document.getElementById('utilizador_dd');
  const vIn = document.getElementById('vendas_ids');
  const vS = document.getElementById('vendas_search');
  const vDd = document.getElementById('vendas_dd');
  const vTags = document.getElementById('vendas_tags');
  if (uDd) dd(uDd); if (vDd) dd(vDd);
  function posDD(inp, dd){ const r = inp.getBoundingClientRect(); dd.style.left=(r.left+window.scrollX)+'px'; dd.style.top=(r.bottom+window.scrollY+4)+'px'; dd.style.width=r.width+'px'; }
  function openDD(inp, dd){ posDD(inp, dd); dd.style.display='block'; }
  function closeDD(dd){ dd.style.display='none'; dd.innerHTML=''; }
  function renderTags(){ const ids=(vIn.value||'').split(',').map(s=>s.trim()).filter(Boolean); vTags.textContent = ids.length? ('Selecionadas: '+ids.join(', ')) : ''; }
  renderTags();
  let t1=null, t2=null;
  async function fetchUsers(q){ const res=await fetch('/admin/users/options?q='+encodeURIComponent(q)); return await res.json(); }
  async function renderUserList(q){ try{ const arr=await fetchUsers(q); uDd.innerHTML=''; arr.forEach(it=>{ const a=document.createElement('a'); a.href='#'; a.className='list-group-item list-group-item-action'; a.textContent = it.text+' (#'+it.id+')'; a.addEventListener('click', function(e){ e.preventDefault(); uIn.value=it.id; uS.value=it.text; closeDD(uDd); }); uDd.appendChild(a); }); openDD(uS, uDd); }catch(e){ closeDD(uDd); } }
  if (uS){
    uS.addEventListener('input', function(){
      const q=uS.value.trim(); if (t1) clearTimeout(t1);
      if (q.length<1){ closeDD(uDd); return; }
      t1=setTimeout(()=>{ renderUserList(q); }, 250);
    });
    uS.addEventListener('focus', function(){
      const q=uS.value.trim(); if (q.length>=1) renderUserList(q);
    });
    document.addEventListener('click', function(e){ if (!uDd.contains(e.target) && e.target!==uS) closeDD(uDd); });
  }
  async function fetchSales(q){ const res=await fetch('/admin/sales/search?q='+encodeURIComponent(q)); return await res.json(); }
  async function renderSalesList(q){ try{ const arr=await fetchSales(q); vDd.innerHTML=''; arr.forEach(it=>{ const a=document.createElement('a'); a.href='#'; a.className='list-group-item list-group-item-action'; a.textContent = it.text; a.addEventListener('click', function(e){ e.preventDefault(); const ids=(vIn.value||'').split(',').map(s=>s.trim()).filter(Boolean); if (!ids.includes(String(it.id))) ids.push(String(it.id)); vIn.value = ids.join(','); renderTags(); vS.value=''; closeDD(vDd); }); vDd.appendChild(a); }); openDD(vS, vDd); }catch(e){ closeDD(vDd); } }
  if (vS){
    vS.addEventListener('input', function(){
      const q=vS.value.trim(); if (t2) clearTimeout(t2);
      if (q.length<1){ closeDD(vDd); return; }
      t2=setTimeout(()=>{ renderSalesList(q); }, 250);
    });
    vS.addEventListener('focus', function(){
      const q=vS.value.trim(); if (q.length>=1) renderSalesList(q);
    });
    document.addEventListener('click', function(e){ if (!vDd.contains(e.target) && e.target!==vS) closeDD(vDd); });
  }
})();
</script>
