<?php /** @var array|null $sale */ /** @var array $clients */ ?>
<?php use Core\Auth; ?>
<div class="container py-3">
  <h3 class="mb-3"><?= htmlspecialchars($title ?? 'Venda Internacional') ?></h3>
  <?php if ((string)($_GET['dup'] ?? '') === '1'): ?>
    <div class="alert alert-warning">Essa edição da venda duplicada, edite as informações e salve para salvar a venda.</div>
  <?php endif; ?>

  <form method="post" action="<?= htmlspecialchars($action) ?>" id="intl-sale-form">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">

    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label">Data de Lançamento</label>
        <input type="date" class="form-control" name="data_lancamento" id="data_lancamento" value="<?= htmlspecialchars($sale['data_lancamento'] ?? ($now ?? date('Y-m-d'))) ?>" required>
        <div class="form-text" id="dateHelp"></div>
      </div>
      <div class="col-md-3">
        <label class="form-label">Número do Pedido</label>
        <input type="text" class="form-control" name="numero_pedido" value="<?= htmlspecialchars($sale['numero_pedido'] ?? '') ?>" placeholder="Opcional">
      </div>
      <div class="col-md-6">
        <label class="form-label">Cliente</label>
        <div class="input-group mb-2">
          <input type="text" class="form-control" id="cliente_search" placeholder="Buscar cliente por nome, e-mail, suite...">
          <button type="button" class="btn btn-outline-secondary" id="refreshClientsIntl">Atualizar lista</button>
        </div>
        <!-- anchored dropdown container (fixed, scrollable) -->
        <div class="client-dropdown" id="clientDropdown"></div>
        <select class="form-select d-none" name="cliente_id" id="cliente_id">
          <option value="">Selecione...</option>
          <?php foreach ($clients as $c): ?>
            <option value="<?= (int)$c['id'] ?>" data-suite="<?= htmlspecialchars($c['suite'] ?? '') ?>" <?= isset($sale['cliente_id']) && (int)$sale['cliente_id']===(int)$c['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['nome']) ?> <?= $c['suite'] ? '(' . htmlspecialchars($c['suite']) . ')' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="form-text" id="suites_info" style="white-space: pre-wrap;"></div>
      </div>

      <div class="col-md-3">
        <label class="form-label">Suite do Cliente</label>
        <input type="text" class="form-control" name="suite_cliente" id="suite_cliente" value="<?= htmlspecialchars($sale['suite_cliente'] ?? '') ?>" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">Peso (kg)</label>
        <input type="number" step="0.01" min="0" class="form-control calc" name="peso_kg" id="peso_kg" value="<?= htmlspecialchars((string)($sale['peso_kg'] ?? '0')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Valor Produto (USD)</label>
        <input type="number" step="0.01" min="0" class="form-control calc" name="valor_produto_usd" id="valor_produto_usd" value="<?= htmlspecialchars((string)($sale['valor_produto_usd'] ?? '0')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Frete UPS (USD)</label>
        <input type="number" step="0.01" min="0" class="form-control calc" name="frete_ups_usd" id="frete_ups_usd" value="<?= htmlspecialchars((string)($sale['frete_ups_usd'] ?? '0')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Redirecionamento (USD)</label>
        <input type="number" step="0.01" min="0" class="form-control calc" name="valor_redirecionamento_usd" id="valor_redirecionamento_usd" value="<?= htmlspecialchars((string)($sale['valor_redirecionamento_usd'] ?? '0')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Serviço Compra (USD)</label>
        <input type="number" step="0.01" min="0" class="form-control calc" name="servico_compra_usd" id="servico_compra_usd" value="<?= htmlspecialchars((string)($sale['servico_compra_usd'] ?? '0')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Frete Etiqueta (USD)</label>
        <input type="number" step="0.01" min="0" class="form-control calc" name="frete_etiqueta_usd" id="frete_etiqueta_usd" value="<?= htmlspecialchars((string)($sale['frete_etiqueta_usd'] ?? '0')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Produtos Compra (USD)</label>
        <input type="number" step="0.01" min="0" class="form-control calc" name="produtos_compra_usd" id="produtos_compra_usd" value="<?= htmlspecialchars((string)($sale['produtos_compra_usd'] ?? '0')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Taxa do Dólar</label>
        <input type="number" step="0.0001" min="0" class="form-control calc" name="taxa_dolar" id="taxa_dolar" value="<?= htmlspecialchars((string)($sale['taxa_dolar'] ?? ($rate ?? 0))) ?>" <?= ((Core\Auth::user()['role'] ?? 'seller')==='seller') ? 'readonly' : '' ?>>
      </div>

      <div class="col-12"><hr></div>

      <div class="col-md-3">
        <label class="form-label">Total Bruto (USD)</label>
        <input type="number" class="form-control" id="total_bruto_usd" value="<?= htmlspecialchars((string)($sale['total_bruto_usd'] ?? '0')) ?>" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">Total Bruto (BRL)</label>
        <input type="number" class="form-control" id="total_bruto_brl" value="<?= htmlspecialchars((string)($sale['total_bruto_brl'] ?? '0')) ?>" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">Total Líquido (USD)</label>
        <input type="number" class="form-control" id="total_liquido_usd" value="<?= htmlspecialchars((string)($sale['total_liquido_usd'] ?? '0')) ?>" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">Total Líquido (BRL)</label>
        <input type="number" class="form-control" id="total_liquido_brl" value="<?= htmlspecialchars((string)($sale['total_liquido_brl'] ?? '0')) ?>" readonly>
      </div>

      <div class="col-12">
        <button type="button" class="btn btn-sm btn-outline-secondary mb-2" id="btnVerCalculo">🧮 Ver Cálculo</button>
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Comissão Estimada (USD)</label>
            <input type="number" class="form-control" id="comissao_usd" value="<?= htmlspecialchars((string)($sale['comissao_usd'] ?? '0')) ?>" readonly>
          </div>
          <div class="col-md-3">
            <label class="form-label">Comissão Estimada (BRL)</label>
            <input type="number" class="form-control" id="comissao_brl" value="<?= htmlspecialchars((string)($sale['comissao_brl'] ?? '0')) ?>" readonly>
          </div>
        </div>
        <label class="form-label">Observações</label>
        <textarea class="form-control" rows="3" placeholder="Preenchimento automático pelo sistema ao editar a data." readonly disabled><?= htmlspecialchars($sale['observacao'] ?? '') ?></textarea>
        <div class="form-text text-muted">Campo bloqueado: anotado automaticamente quando a data é alterada.</div>
      </div>
    </div>

    <div class="d-flex gap-2 mt-3">
      <a href="/admin/international-sales" class="btn btn-outline-secondary">Cancelar</a>
      <button type="submit" class="btn btn-primary">Salvar</button>
    </div>
  </form>
</div>

<!-- Modal de Detalhamento do Cálculo -->
<div id="calcModal" class="modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:1050;">
  <div class="modal-dialog" style="max-width:720px; margin:5% auto;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalhamento do Cálculo</h5>
        <button type="button" class="btn-close" id="calcClose"></button>
      </div>
      <div class="modal-body">
        <div id="calcBody" style="white-space:pre-wrap; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" id="btnCopyCalc">Copiar cálculo completo</button>
        <button type="button" class="btn btn-secondary" id="calcClose2">Fechar</button>
      </div>
    </div>
  </div>
  <style>
    #calcModal .modal-content { box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15); }
  </style>
</div>

<!-- Modal: Adicionar novo cliente -->
<div id="newClientModal" class="modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:2000;">
  <div class="modal-dialog" style="max-width:640px; margin:3% auto;">
    <div class="modal-content" style="max-height:80vh; overflow:auto;">
      <div class="modal-header">
        <h5 class="modal-title">Adicionar novo cliente</h5>
        <button type="button" class="btn-close" id="ncClose"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">Nome completo</label>
            <input type="text" class="form-control" id="nc_nome" placeholder="Nome completo" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">E-mail</label>
            <input type="email" class="form-control" id="nc_email" placeholder="email@exemplo.com">
          </div>
          <div class="col-md-6">
            <label class="form-label">Suite</label>
            <input type="text" class="form-control" id="nc_suite" placeholder="ex: BR-112" required>
            <div class="form-text">Use BR- / US- / RED- / GLOB- seguido de números</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Telefone (opcional)</label>
            <input type="text" class="form-control" id="nc_tel" placeholder="+55...">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" id="ncCancel">Cancelar</button>
        <button type="button" class="btn btn-primary" id="ncSave">Salvar cliente</button>
      </div>
    </div>
  </div>
</div>

<script>
  (function(){
  const form = document.getElementById('intl-sale-form');
  const calcFields = Array.from(form.querySelectorAll('.calc'));
  const suite = document.getElementById('suite_cliente');
  const cliente = document.getElementById('cliente_id');
  const clienteSearch = document.getElementById('cliente_search');
  const dropdown = document.getElementById('clientDropdown');
  const suitesInfo = document.getElementById('suites_info');
  const dataLanc = document.getElementById('data_lancamento');
  const csrfToken = <?= json_encode(Auth::csrf()) ?>;
  const dateHelp = document.getElementById('dateHelp');

  function toNum(el){ return parseFloat(el.value||'0')||0; }
  function upd(){
    const valor_produto_usd = toNum(document.getElementById('valor_produto_usd'));
    const frete_ups_usd = toNum(document.getElementById('frete_ups_usd'));
    const valor_redirecionamento_usd = toNum(document.getElementById('valor_redirecionamento_usd'));
    // Auto-calc service fee: 39 USD per kg (ceil)
    const pesoEl = document.getElementById('peso_kg');
    const pesoKg = toNum(pesoEl);
    const autoServico = Math.ceil(Math.max(0, pesoKg)) * 39.0;
    const servField = document.getElementById('servico_compra_usd');
    if (servField) { servField.value = autoServico.toFixed(2); }
    const servico_compra_usd = toNum(servField);
    const frete_etiqueta_usd = toNum(document.getElementById('frete_etiqueta_usd'));
    const produtos_compra_usd = toNum(document.getElementById('produtos_compra_usd'));
    const taxa_dolar = toNum(document.getElementById('taxa_dolar'));
    const peso = pesoKg;

    const bruto_usd = valor_produto_usd + frete_ups_usd + valor_redirecionamento_usd + servico_compra_usd;
    const bruto_brl = bruto_usd * taxa_dolar;
    const liquido_usd = bruto_usd - frete_etiqueta_usd - produtos_compra_usd - (peso>0 ? peso*9.7 : 0);
    const liquido_brl = liquido_usd * taxa_dolar;
    document.getElementById('total_bruto_usd').value = bruto_usd.toFixed(2);
    document.getElementById('total_bruto_brl').value = bruto_brl.toFixed(2);
    document.getElementById('total_liquido_usd').value = liquido_usd.toFixed(2);
    document.getElementById('total_liquido_brl').value = liquido_brl.toFixed(2);
    // Estimativa de comissão (base 15%)
    const pct = 0.15;
    const com_brl = liquido_brl * pct;
    const com_usd = taxa_dolar>0 ? (com_brl / taxa_dolar) : 0;
    const elCusd = document.getElementById('comissao_usd');
    const elCbrl = document.getElementById('comissao_brl');
    if (elCusd && elCbrl) { elCusd.value = com_usd.toFixed(2); elCbrl.value = com_brl.toFixed(2); }

    // Preenche corpo do modal com seções
    const body = [
      '**[Taxa de câmbio]**',
      '  taxa_dolar                = ' + taxa_dolar.toFixed(4),
      '',
      '**[Bruto]**',
      '  valor_produto_usd         = ' + valor_produto_usd.toFixed(2),
      '  frete_ups_usd             = ' + frete_ups_usd.toFixed(2),
      '  valor_redirecionamento_usd= ' + valor_redirecionamento_usd.toFixed(2),
      '  servico_compra_usd        = ' + servico_compra_usd.toFixed(2),
      '=> total_bruto_usd          = ' + bruto_usd.toFixed(2),
      '=> total_bruto_brl          = ' + bruto_brl.toFixed(2),
      '',
      '**[Líquido]**',
      '  frete_etiqueta_usd        = ' + frete_etiqueta_usd.toFixed(2),
      '  produtos_compra_usd       = ' + produtos_compra_usd.toFixed(2),
      '  embalagem (9.7 * kg)      = ' + (peso>0?(peso*9.7).toFixed(2):'0.00'),
      '=> total_liquido_usd        = ' + liquido_usd.toFixed(2),
      '=> total_liquido_brl        = ' + liquido_brl.toFixed(2),
      '',
      '**[Comissão do Pedido]**',
      '  percentual                = ' + (pct*100).toFixed(2) + ' %',
      '  comissao_brl              = ' + com_brl.toFixed(2),
      '  comissao_usd              = ' + com_usd.toFixed(2),
      '',
      'TOTAL COMISSÃO: USD ' + com_usd.toFixed(2) + ' | BRL ' + com_brl.toFixed(2)
    ].join('\n');
    const calcBody = document.getElementById('calcBody');
    if (calcBody) calcBody.textContent = body;
  }
  calcFields.forEach(el=>el.addEventListener('input', upd));
  upd();

  // Auto suite from client
  cliente.addEventListener('change', function(){
    const opt = cliente.options[cliente.selectedIndex];
    suite.value = opt ? (opt.getAttribute('data-suite')||'') : '';
  });

  // Autocomplete de clientes (dropdown ancorado)
  let tId=null; let isOpen=false;
  function positionDropdown(){
    const r = clienteSearch.getBoundingClientRect();
    dropdown.style.left = (r.left + window.scrollX) + 'px';
    dropdown.style.top = (r.bottom + window.scrollY + 4) + 'px';
    dropdown.style.width = r.width + 'px';
  }
  function openDropdown(){ positionDropdown(); dropdown.style.display='block'; isOpen=true; }
  function closeDropdown(){ dropdown.style.display='none'; dropdown.innerHTML=''; isOpen=false; }
  clienteSearch.addEventListener('focus', function(){ openDropdown(); });
  window.addEventListener('resize', function(){ if (isOpen) positionDropdown(); });
  window.addEventListener('scroll', function(){ if (isOpen) positionDropdown(); }, true);
  document.addEventListener('click', function(e){ if (!dropdown.contains(e.target) && e.target !== clienteSearch) { closeDropdown(); } });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeDropdown(); });

  clienteSearch.addEventListener('input', function(){ openDropdown();
    const q = clienteSearch.value.trim();
    if (tId) clearTimeout(tId);
    if (q.length < 2) { dropdown.innerHTML=''; return; }
    tId = setTimeout(async ()=>{
      try {
        const res = await fetch('/admin/clients/search?q='+encodeURIComponent(q));
        const text = await res.text();
        let data = [];
        try { data = JSON.parse(text); } catch(parseErr) {
          console.error('Autocomplete parse error:', parseErr, text);
          dropdown.innerHTML = '<div class="list-group-item text-danger">Falha na busca</div>';
          return;
        }
        dropdown.innerHTML = '';
        if (!Array.isArray(data) || data.length === 0) {
          const add = document.createElement('div');
          add.className = 'list-group-item text-primary';
          add.style.cursor = 'pointer';
          add.textContent = '➕ Adicionar novo cliente';
          add.addEventListener('click', function(){ closeDropdown(); (function(){ var m=document.getElementById('newClientModal'); if(m) m.style.display='block'; })(); });
          dropdown.appendChild(add);
          return;
        }
        data.forEach(item=>{
          const a = document.createElement('div');
          a.className = 'list-group-item client-result';
          const sBR = item.suite_br ? ('BR-' + item.suite_br) : null;
          const sUS = item.suite_us ? ('US-' + item.suite_us) : null;
          const sRED = item.suite_red ? ('RED-' + item.suite_red) : null;
          const sGLOB = item.suite_globe ? ('GLOB-' + item.suite_globe) : null;
          const allPreview = [sBR,sUS,sRED,sGLOB].filter(Boolean);
          const needsSuffix = allPreview.length > 0 && !item.text.includes('(');
          const suffix = needsSuffix ? (' (' + allPreview.join(', ') + ')') : '';
          a.innerHTML = '<div class="fw-semibold">'+ item.text + suffix +'</div>';
          a.addEventListener('click', function(e){ e.preventDefault();
            cliente.value = item.id;
            const sBR = item.suite_br ? ('BR-' + item.suite_br) : null;
            const sUS = item.suite_us ? ('US-' + item.suite_us) : null;
            const sRED = item.suite_red ? ('RED-' + item.suite_red) : null;
            const sGLOB = item.suite_globe ? ('GLOB-' + item.suite_globe) : null;
            const all = [sBR,sUS,sRED,sGLOB].filter(Boolean);
            suitesInfo.textContent = all.length ? ('Suítes: ' + all.join(', ')) : '';
            suite.value = all.join(', ');
            closeDropdown();
            clienteSearch.value = item.text;
          });
          dropdown.appendChild(a);
        });
      } catch(e){ console.error(e); dropdown.innerHTML = '<div class="list-group-item text-danger">Falha na busca</div>'; }
    }, 250);
  });

  // Validate client selection on submit (required)
  form.addEventListener('submit', function(e){
    if (!cliente.value) {
      // Try auto-pick when there is a single match for current query
      const q = clienteSearch.value.trim();
      if (q.length >= 2) {
        e.preventDefault();
        fetch('/admin/clients/search?q=' + encodeURIComponent(q))
          .then(r=>r.json())
          .then(arr=>{
            if (Array.isArray(arr) && arr.length === 1) {
              const item = arr[0];
              cliente.value = item.id;
              const sBR = item.suite_br ? ('BR-' + item.suite_br) : null;
              const sUS = item.suite_us ? ('US-' + item.suite_us) : null;
              const sRED = item.suite_red ? ('RED-' + item.suite_red) : null;
              const sGLOB = item.suite_globe ? ('GLOB-' + item.suite_globe) : null;
              const all = [sBR,sUS,sRED,sGLOB].filter(Boolean);
              suitesInfo.textContent = all.length ? ('Suítes: ' + all.join(', ')) : '';
              suite.value = all.join(', ');
              clienteSearch.value = item.text;
              form.submit();
              return;
            }
            alert('Selecione um cliente antes de salvar.');
            clienteSearch.focus();
          })
          .catch(()=>{ alert('Selecione um cliente antes de salvar.'); clienteSearch.focus(); });
        return;
      }
      e.preventDefault();
      alert('Selecione um cliente antes de salvar.');
      clienteSearch.focus();
    }
  });

  // Refresh clients button
  const refreshBtn = document.getElementById('refreshClientsIntl');
  if (refreshBtn && cliente) {
    refreshBtn.addEventListener('click', async function(){
      const prev = cliente.value;
      refreshBtn.disabled = true; refreshBtn.textContent = 'Atualizando...';
      try {
        const r = await fetch('/admin/clients/options');
        if (!r.ok) throw new Error('fail');
        const list = await r.json();
        cliente.innerHTML = '<option value="">Selecione...</option>';
        (list||[]).forEach(function(it){
          const opt = document.createElement('option');
          opt.value = String(it.id);
          opt.textContent = String(it.text||'');
          cliente.appendChild(opt);
        });
        if (prev) cliente.value = prev;
        // Auto-select unique match for current query, if any
        const q = (clienteSearch.value||'').trim();
        if (q.length >= 2) {
          const res = await fetch('/admin/clients/search?q=' + encodeURIComponent(q));
          if (res.ok) {
            const arr = await res.json();
            if (Array.isArray(arr) && arr.length === 1) {
              const item = arr[0];
              cliente.value = String(item.id);
              const sBR = item.suite_br ? ('BR-' + item.suite_br) : null;
              const sUS = item.suite_us ? ('US-' + item.suite_us) : null;
              const sRED = item.suite_red ? ('RED-' + item.suite_red) : null;
              const sGLOB = item.suite_globe ? ('GLOB-' + item.suite_globe) : null;
              const all = [sBR,sUS,sRED,sGLOB].filter(Boolean);
              suitesInfo.textContent = all.length ? ('Suítes: ' + all.join(', ')) : '';
              suite.value = all.join(', ');
              clienteSearch.value = item.text;
            }
          }
        }
      } catch(e) {
        alert('Não foi possível atualizar a lista agora. Recarregue a página se o cliente não aparecer.');
      } finally {
        refreshBtn.disabled = false; refreshBtn.textContent = 'Atualizar lista';
      }
    });
  }

  // Zero-clearing on focus/blur for editable numeric inputs
  (function(){
    const editable = Array.from(form.querySelectorAll('input.calc:not([readonly]):not([disabled])'));
    editable.forEach(function(el){
      el.addEventListener('focus', function(){
        const v = (el.value||'').trim();
        if (v === '0' || v === '0.0' || v === '0.00' || v === '0.0000') { el.value = ''; }
      });
      el.addEventListener('blur', function(){
        const v = (el.value||'').trim();
        if (v === '') { el.value = '0'; }
      });
    });
  })();

  // Dica opcional pode ser adicionada aqui; edição de data liberada no servidor.

  // Modal handlers
  const btn = document.getElementById('btnVerCalculo');
  const modal = document.getElementById('calcModal');
  const close1 = document.getElementById('calcClose');
  const close2 = document.getElementById('calcClose2');
  const btnCopy = document.getElementById('btnCopyCalc');
  function openM(){ if(modal){ modal.style.display='block'; } }
  function closeM(){ if(modal){ modal.style.display='none'; } }
  if (btn) btn.addEventListener('click', openM);
  if (close1) close1.addEventListener('click', closeM);
  if (close2) close2.addEventListener('click', closeM);
  if (modal) modal.addEventListener('click', function(e){ if (e.target===modal) closeM(); });
  if (btnCopy) btnCopy.addEventListener('click', function(){
    const text = document.getElementById('calcBody')?.textContent || '';
    navigator.clipboard.writeText(text).then(()=>{
      btnCopy.textContent = 'Copiado!'; setTimeout(()=>btnCopy.textContent='Copiar cálculo completo', 1200);
    });
  });

  // Novo cliente: abrir modal a partir do dropdown
  const ncModal = document.getElementById('newClientModal');
  const ncSave = document.getElementById('ncSave');
  const ncCancel = document.getElementById('ncCancel');
  const ncClose = document.getElementById('ncClose');
  function openNc(){ if(ncModal) ncModal.style.display='block'; }
  function closeNc(){ if(ncModal) ncModal.style.display='none'; }
  if (ncCancel) ncCancel.addEventListener('click', closeNc);
  if (ncClose) ncClose.addEventListener('click', closeNc);

  // Atualiza dropdown de clientes e inclui opção "Adicionar novo cliente" quando vazio
  function renderClientDropdown(items){
    if (!dropdown) return;
    dropdown.innerHTML = '';
    if (!items || items.length === 0) {
      const li = document.createElement('div');
      li.className = 'dropdown-item text-primary';
      li.style.cursor = 'pointer';
      li.textContent = '➕ Adicionar novo cliente';
      li.addEventListener('click', function(){ dropdown.style.display='none'; openNc(); });
      dropdown.appendChild(li);
      dropdown.style.display = 'block';
      return;
    }
    items.forEach(function(it){
      const el = document.createElement('div');
      el.className = 'dropdown-item';
      el.textContent = it.text;
      el.dataset.id = it.id;
      el.addEventListener('click', function(){
        cliente.value = it.id;
        clienteSearch.value = it.text;
        suite.value = it.suite || '';
        dropdown.style.display='none';
      });
      dropdown.appendChild(el);
    });
    dropdown.style.display = 'block';
  }

  // Hook de busca existente: quando obter resultados, chamar renderClientDropdown
  if (clienteSearch) {
    clienteSearch.addEventListener('input', function(){
      const q = clienteSearch.value.trim();
      if (q.length < 2) { dropdown.style.display='none'; return; }
      fetch('/admin/clients/search?q='+encodeURIComponent(q))
        .then(r=>r.json())
        .then(arr=>{ renderClientDropdown(arr||[]); })
        .catch(()=>{ renderClientDropdown([]); });
    });
  }

  // Salvar novo cliente via AJAX
  if (ncSave) ncSave.addEventListener('click', function(){
    const nome = document.getElementById('nc_nome').value.trim();
    const email = document.getElementById('nc_email').value.trim();
    const suiteIn = document.getElementById('nc_suite').value.trim().toUpperCase();
    const telefone = document.getElementById('nc_tel').value.trim();
    if (!nome) { alert('Informe o nome.'); return; }
    if (!suiteIn) { alert('Informe a suite.'); return; }
    const m = suiteIn.match(/^\s*(BR|US|RED|GLOB)-(\d+)\s*$/i);
    if (!m) { alert('Suite inválida. Use BR- / US- / RED- / GLOB- seguido de números.'); return; }
    const prefix = m[1].toUpperCase();
    const num = m[2];
    const payload = { _csrf: csrfToken, nome, email, telefone, suite: prefix + '-' + num };
    if (prefix==='BR') payload['suite_br'] = num;
    if (prefix==='US') payload['suite_us'] = num;
    if (prefix==='RED') payload['suite_red'] = num;
    if (prefix==='GLOB') payload['suite_globe'] = num;
    fetch('/admin/clients/create-ajax', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams(payload).toString() })
      .then(r=>r.json())
      .then(c=>{
        // Atualiza seleção no formulário
        if (cliente) cliente.value = c.id;
        if (clienteSearch) clienteSearch.value = (c.nome||'') + (c.email?(' <'+c.email+'>'):'') + (payload.suite?(' ('+payload.suite+')'):'');
        if (suite) suite.value = payload.suite || '';
        closeNc();
      })
      .catch(()=>alert('Falha ao criar cliente.'));
  });
})();
</script>
