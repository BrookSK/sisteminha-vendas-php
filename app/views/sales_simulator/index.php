<?php /** @var float $usd_rate */ /** @var array|null $budget_data */ /** @var int $budget_id */ /** @var string $budget_name */ ?>
<?php use Core\Auth; ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Simulador de Cálculo</h3>
    <div class="d-flex gap-2">
      <a class="btn btn-sm btn-outline-secondary" target="_blank" href="https://www.ebay.com/">🔍 eBay</a>
      <a class="btn btn-sm btn-outline-secondary" target="_blank" href="https://www.amazon.com/">🔍 Amazon</a>
      <a class="btn btn-sm btn-outline-secondary" target="_blank" href="https://www.bestbuy.com/">🔍 Best Buy</a>
      <a class="btn btn-sm btn-outline-secondary" target="_blank" href="https://www.costco.com/">🔍 Costco</a>
      <a class="btn btn-sm btn-outline-secondary" target="_blank" href="https://www.walmart.com/">🔍 Walmart</a>
      <a class="btn btn-sm btn-outline-secondary" target="_blank" href="https://www.samsclub.com/">🔍 Sam's Club</a>
    </div>
  </div>

  <div class="alert alert-warning" role="alert">
    <div class="fw-bold mb-1">Orientação de pesquisa de produtos</div>
    <div class="mb-1">Priorize sites confiáveis: <strong>Costco</strong>, <strong>Sam's Club</strong> e os <strong>sites oficiais das marcas</strong>. Apenas se não encontrar nestes, busque em outros.</div>
    <div class="mb-0"><strong>Sobre eBay:</strong> lembre o cliente que qualquer pessoa pode criar conta e anunciar. A procedência do produto pode ser incerta e há riscos (produto não original, não entrega, etc.).</div>
  </div>

  <form class="row g-3" id="sim-form" onsubmit="return false;">
    <div class="col-md-4">
      <label class="form-label">Taxa de câmbio (USD → BRL)</label>
      <input type="number" step="0.01" min="0" class="form-control" id="taxa_cambio" value="<?= htmlspecialchars((string)$usd_rate) ?>" readonly>
    </div>
    <div class="col-md-4">
      <div class="form-check form-switch mt-4">
        <input class="form-check-input" type="checkbox" id="envio_brasil">
        <label class="form-check-label" for="envio_brasil">Envio para o Brasil? (calcular impostos)</label>
      </div>
    </div>
    <div class="col-12">
      <h5 class="mt-3">Cliente</h5>
      <div class="row g-2 align-items-end">
        <div class="col-md-6 position-relative">
          <label class="form-label">Buscar cliente (nome ou suíte)</label>
          <input type="text" class="form-control" id="cliente_busca" placeholder="Digite o nome ou a suíte (ex.: BR-123)">
          <div class="list-group" id="cliente_resultados" style="position:absolute;top:100%;left:0;right:0;z-index:1080;max-height:260px;overflow:auto;display:none;"></div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Cliente selecionado</label>
          <input type="text" class="form-control" id="cliente_resumo" readonly placeholder="Nenhum cliente selecionado">
        </div>
      </div>
      <div class="mt-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-cliente-criar">Criar novo cliente</button>
      </div>
      <div class="border rounded p-2 mt-2" id="cliente_criar_box" style="display:none;">
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">Nome do cliente</label>
            <input type="text" class="form-control" id="cliente_novo_nome" placeholder="Nome completo">
          </div>
          <div class="col-md-3">
            <label class="form-label">Suíte BR-</label>
            <input type="text" class="form-control" id="cliente_novo_suite_br" placeholder="Número da suíte BR">
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button type="button" class="btn btn-sm btn-primary w-100" id="btn-cliente-salvar-rapido">Salvar cliente</button>
          </div>
        </div>
        <div class="small text-muted mt-1">O cliente será criado na base principal e ficará disponível para uso em vendas.</div>
      </div>
      <input type="hidden" id="cliente_id" value="">
    </div>
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="m-0">Produtos</h5>
      </div>
      <div id="produtos" class="vstack gap-3"></div>
      <div class="mt-2">
        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-prod">Adicionar produto</button>
      </div>
    </div>
    <div class="col-12 d-flex flex-wrap gap-2">
      <button class="btn btn-primary" id="btn-calcular">Calcular</button>
      <button class="btn btn-outline-secondary" id="btn-gerar">Gerar mensagem para o cliente</button>
      <button class="btn btn-outline-dark" id="btn-copiar" type="button">Copiar mensagem</button>
    </div>
  </form>

  <div class="row mt-4">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">💵 Resultado em Dólares</div>
        <div class="card-body">
          <ul class="list-group list-group-flush" id="usd-list"></ul>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">💰 Resultado em Reais</div>
        <div class="card-body">
          <ul class="list-group list-group-flush" id="brl-list"></ul>
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-3">
    <div class="card-header">🧾 Mensagem automática</div>
    <div class="card-body">
      <textarea class="form-control" id="mensagem" rows="8" placeholder="Aperte 'Gerar mensagem para o cliente' para preencher."></textarea>
    </div>
  </div>
</div>

<!-- Pop-up de confirmação para envio ao Brasil -->
<div class="modal fade" id="modalEnvioBrasil" tabindex="-1" aria-labelledby="modalEnvioBrasilLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEnvioBrasilLabel">Confirmação de envio ao Brasil</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Este orçamento é para envio ao Brasil?</p>
        <p class="mb-1">Ao gerar a mensagem para o cliente, o orçamento será salvo automaticamente.</p>
        <p class="mb-0">Se não for envio ao Brasil, o orçamento será salvo sem o cálculo de impostos.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="modal-envio-nao-br">Não é envio ao Brasil</button>
        <button type="button" class="btn btn-primary" id="modal-envio-sim-br">Sim, é envio ao Brasil</button>
      </div>
    </div>
  </div>
  </div>

<script>
(function(){
  function nfUSD(v){ return `$ ${Number(v||0).toFixed(2)}`; }
  function nfBRL(v){ return `R$ ${Number(v||0).toFixed(2)}`; }
  const initialBudget = <?php echo json_encode($budget_data ?? null, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
  const currentBudgetId = <?php echo (int)($budget_id ?? 0); ?>;
  const currentBudgetName = <?php echo json_encode($budget_name ?? '', JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
  const csrfToken = '<?= htmlspecialchars(Auth::csrf()) ?>';
  const currentUserRole = <?= json_encode((string)(Auth::user()['role'] ?? 'seller')) ?>;
  const isTrainee = (currentUserRole === 'trainee');

  const produtos = document.getElementById('produtos');
  const btnAdd = document.getElementById('btn-add-prod');
  const clienteBusca = document.getElementById('cliente_busca');
  const clienteResultados = document.getElementById('cliente_resultados');
  const clienteResumo = document.getElementById('cliente_resumo');
  const clienteIdInput = document.getElementById('cliente_id');

  let selectedClient = null;

  function setSelectedClient(c) {
    selectedClient = c;
    if (!clienteResumo || !clienteIdInput) return;
    if (!c) {
      clienteResumo.value = '';
      clienteResumo.placeholder = 'Nenhum cliente selecionado';
      clienteIdInput.value = '';
      return;
    }
    // Alguns endpoints retornam apenas {id, text}; outros retornam nome/suite separados
    const nome = c.nome || c.text || '';
    const suiteBr = c.suite_br || null;
    const suiteRaw = c.suite || null;
    const suite = suiteBr ? `BR-${suiteBr}` : (suiteRaw || '');
    const label = suite ? `${nome} (${suite})` : nome;
    clienteResumo.value = label;
    clienteIdInput.value = c.id;
  }

  function renderClientResults(items){
    if (!clienteResultados) return;
    clienteResultados.innerHTML = '';
    if (!items || !items.length) {
      clienteResultados.style.display = 'none';
      return;
    }
    items.forEach(function(c){
      const a = document.createElement('button');
      a.type = 'button';
      a.className = 'list-group-item list-group-item-action';
      a.textContent = c.text || c.nome || '';
      a.addEventListener('click', function(){
        setSelectedClient(c);
        clienteResultados.innerHTML='';
        clienteResultados.style.display='none';
        if (clienteBusca) clienteBusca.value = '';
      });
      clienteResultados.appendChild(a);
    });
    clienteResultados.style.display = '';
  }

  let clientSearchTimer = null;
  if (clienteBusca) {
    clienteBusca.addEventListener('input', function(){
      const q = this.value.trim();
      if (clientSearchTimer) window.clearTimeout(clientSearchTimer);
      if (!q) {
        renderClientResults([]);
        return;
      }
      clientSearchTimer = window.setTimeout(function(){
        fetch('/admin/clients/options?q=' + encodeURIComponent(q))
          .then(r=>r.json())
          .then(function(rows){
            renderClientResults(rows || []);
          })
          .catch(function(){ renderClientResults([]); });
      }, 300);
    });
  }

  const btnClienteCriar = document.getElementById('btn-cliente-criar');
  const clienteCriarBox = document.getElementById('cliente_criar_box');
  const btnClienteSalvarRapido = document.getElementById('btn-cliente-salvar-rapido');
  if (btnClienteCriar && clienteCriarBox) {
    btnClienteCriar.addEventListener('click', function(){
      clienteCriarBox.style.display = clienteCriarBox.style.display === 'none' ? '' : 'none';
    });
  }
  if (btnClienteSalvarRapido) {
    btnClienteSalvarRapido.addEventListener('click', function(){
      const nome = document.getElementById('cliente_novo_nome')?.value.trim() || '';
      const suiteBr = document.getElementById('cliente_novo_suite_br')?.value.trim() || '';
      if (!nome) {
        alert('Informe o nome do cliente.');
        return;
      }
      const body = new URLSearchParams({
        _csrf: csrfToken,
        nome: nome,
        suite_br: suiteBr,
      });
      fetch('/admin/clients/create-ajax', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body,
      }).then(r=>r.json()).then(function(resp){
        if (!resp || !resp.id) {
          alert('Não foi possível criar o cliente.');
          return;
        }
        setSelectedClient({
          id: resp.id,
          nome: resp.nome,
          suite: resp.suite || null,
          suite_br: resp.suite_br || null,
        });
        if (clienteCriarBox) clienteCriarBox.style.display = 'none';
      }).catch(function(){
        alert('Erro ao criar o cliente.');
      });
    });
  }

  function makeItem(idx){
    const wrap = document.createElement('div');
    wrap.className = 'card prod-item';
    const id = Date.now()+''+Math.floor(Math.random()*1000);
    wrap.innerHTML = `
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fw-semibold">Produto</div>
          <div class="d-flex flex-wrap gap-2">
            <div class="form-check form-switch">
              <input class="form-check-input aplica_imp_local" type="checkbox" id="il_${id}" checked>
              <label class="form-check-label" for="il_${id}">Imposto local (7%)?</label>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input precisa_frete" type="checkbox" id="pf_${id}">
              <label class="form-check-label" for="pf_${id}">Frete até a sede?</label>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove">Remover</button>
          </div>
        </div>
        <div class="row g-2 align-items-end">
          <div class="col-md-4 position-relative">
            <label class="form-label">Nome do produto</label>
            <input type="text" class="form-control nome_produto" placeholder="Ex: Apple Watch Series 10 Titanium 46mm">
            <div class="small text-muted mt-1">Digite para buscar na base de produtos ou clique em "Criar produto".</div>
            <div class="list-group" data-prod-resultados style="position:absolute;top:100%;left:0;right:0;z-index:1080;max-height:260px;overflow:auto;display:none;"></div>
          </div>
          <div class="col-md-2">
            <label class="form-label">Qtd.</label>
            <input type="number" step="1" min="1" class="form-control qtd_produto" value="1">
          </div>
          <div class="col-md-2">
            <label class="form-label">Valor (USD)</label>
            <input type="number" step="0.01" min="0" class="form-control valor_produto" value="0">
          </div>
          <div class="col-md-2">
            <label class="form-label">Peso (Kg)</label>
            <input type="number" step="0.01" min="0" class="form-control peso_produto" value="0">
          </div>
          <div class="col-md-2 frete_group" style="display:none;">
            <label class="form-label">Frete (USD)</label>
            <input type="number" step="0.01" min="0" class="form-control frete_usd" value="0">
          </div>
          <div class="col-md-2 mt-2 d-flex align-items-end">
            <div class="d-flex flex-column flex-md-column w-100 gap-1">
              <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary btn-prod-criar">Criar produto</button>
              </div>
              <input type="hidden" class="produto_id" value="">
            </div>
          </div>
          <div class="col-md-12 mt-2" style="display:none;" data-prod-criar-box>
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label">Nome</label>
                <input type="text" class="form-control prod_criar_nome">
              </div>
              <div class="col-md-4">
                <label class="form-label">Marca</label>
                <input type="text" class="form-control prod_criar_marca">
              </div>
              <div class="col-md-4">
                <label class="form-label">Link para compra</label>
                <input type="text" class="form-control prod_criar_link" placeholder="https://...">
              </div>
              <div class="col-md-3 mt-2">
                <label class="form-label">Peso (Kg)</label>
                <input type="number" step="0.01" min="0" class="form-control prod_criar_peso">
              </div>
              <div class="col-md-3 mt-4 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-success w-100 btn-prod-salvar-rapido">Salvar produto</button>
              </div>
            </div>
            <div class="small text-muted mt-1">O produto será salvo no banco exclusivo de produtos e poderá ser reutilizado em outros orçamentos.</div>
          </div>
        </div>
      </div>`;
    const pf = wrap.querySelector('.precisa_frete');
    const fg = wrap.querySelector('.frete_group');
    if (pf && fg) {
      pf.addEventListener('change', ()=>{ fg.style.display = pf.checked ? '' : 'none'; });
    }
    // UX: ao focar valor/peso, limpa 0; ao sair vazio, volta 0
    ['.valor_produto','.peso_produto'].forEach(sel=>{
      const inp = wrap.querySelector(sel);
      if (!inp) return;
      inp.addEventListener('focus', function(){
        if (this.value === '0' || this.value === '0.00') { this.value = ''; }
      });
      inp.addEventListener('blur', function(){
        if (this.value === '') { this.value = '0'; }
      });
    });
    wrap.querySelector('.btn-remove').addEventListener('click', ()=>{ wrap.remove(); });

    // Integração com base de produtos
    const btnCriarProd = wrap.querySelector('.btn-prod-criar');
    const criarBox = wrap.querySelector('[data-prod-criar-box]');
    const inputNome = wrap.querySelector('.nome_produto');
    const inputPeso = wrap.querySelector('.peso_produto');
    const inputProdId = wrap.querySelector('.produto_id');
    const resultadosBox = wrap.querySelector('[data-prod-resultados]');

    function preencherProdutoFromApi(p){
      if (!p) return;
      if (inputNome) inputNome.value = p.nome || inputNome.value;
      if (inputPeso && typeof p.peso_kg !== 'undefined') inputPeso.value = String(p.peso_kg);
      if (inputProdId) inputProdId.value = p.id || '';
      if (resultadosBox) {
        resultadosBox.innerHTML = '';
        resultadosBox.style.display = 'none';
      }
    }

    function renderProdResults(items) {
      if (!resultadosBox) return;
      resultadosBox.innerHTML = '';
      if (!items || !items.length) {
        resultadosBox.style.display = 'none';
        return;
      }
      items.forEach(function(p){
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'list-group-item list-group-item-action';
        const pesoLabel = (typeof p.peso_kg !== 'undefined' && p.peso_kg !== null)
          ? ` - ${p.peso_kg} kg` : '';
        btn.textContent = (p.nome || '') + pesoLabel;
        btn.addEventListener('click', function(){
          preencherProdutoFromApi(p);
        });
        resultadosBox.appendChild(btn);
      });
      resultadosBox.style.display = '';
    }

    let prodSearchTimer = null;
    if (inputNome && resultadosBox) {
      inputNome.addEventListener('input', function(){
        const q = this.value.trim();
        if (prodSearchTimer) window.clearTimeout(prodSearchTimer);
        if (!q) {
          renderProdResults([]);
          return;
        }
        prodSearchTimer = window.setTimeout(function(){
          fetch('/admin/sales-simulator/products/search?q=' + encodeURIComponent(q))
            .then(r=>r.json())
            .then(function(rows){
              if (!rows || !rows.length) {
                renderProdResults([]);
                if (isTrainee) {
                  // Mantém a regra de trainee não criar produto rápido
                  alert('Nenhum produto encontrado na base. Como você é trainee, crie o produto pela tela "Produtos do Simulador" no menu de Vendas para enviar para aprovação do seu supervisor.');
                }
                return;
              }
              renderProdResults(rows);
            })
            .catch(function(){ renderProdResults([]); });
        }, 300);
      });
    }

    if (btnCriarProd && criarBox) {
      if (isTrainee) {
        // Trainee não pode criar produto direto pelo simulador
        btnCriarProd.addEventListener('click', function(){
          alert('Como você é trainee, a criação de produtos deve ser feita pela tela "Produtos do Simulador" no menu de Vendas, para que seu supervisor possa aprovar.');
        });
        criarBox.style.display = 'none';
      } else {
        btnCriarProd.addEventListener('click', function(){
          criarBox.style.display = criarBox.style.display === 'none' ? '' : 'none';
          const criarNome = criarBox.querySelector('.prod_criar_nome');
          if (criarNome && inputNome && !criarNome.value) criarNome.value = inputNome.value;
        });
      }
    }

    const btnProdSalvarRapido = wrap.querySelector('.btn-prod-salvar-rapido');
    if (btnProdSalvarRapido && criarBox && !isTrainee) {
      btnProdSalvarRapido.addEventListener('click', function(){
        const nome = criarBox.querySelector('.prod_criar_nome')?.value.trim() || '';
        const marca = criarBox.querySelector('.prod_criar_marca')?.value.trim() || '';
        const link = criarBox.querySelector('.prod_criar_link')?.value.trim() || '';
        const pesoQuick = parseFloat(criarBox.querySelector('.prod_criar_peso')?.value || '0') || 0;
        if (!nome) {
          alert('Informe o nome do produto.');
          return;
        }
        const payload = new URLSearchParams();
        payload.set('_csrf', csrfToken);
        payload.set('nome', nome);
        if (marca) payload.set('marca', marca);
        if (!isNaN(pesoQuick)) payload.set('peso_kg', String(pesoQuick));
        if (link) payload.set('links', link);
        fetch('/admin/sales-simulator/products/create-ajax', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: payload,
        }).then(r=>r.json()).then(function(resp){
          if (!resp || !resp.id) {
            alert('Não foi possível salvar o produto.');
            return;
          }
          preencherProdutoFromApi(resp);
          if (inputNome && !inputNome.value) inputNome.value = resp.nome || nome;
          if (inputPeso && !isNaN(resp.peso_kg)) inputPeso.value = String(resp.peso_kg);
          criarBox.style.display = 'none';
        }).catch(function(){
          alert('Erro ao salvar o produto.');
        });
      });
    }
    return wrap;
  }

  btnAdd.addEventListener('click', ()=>{ produtos.appendChild(makeItem(produtos.children.length)); });

  function applyItemState(wrap, state){
    if (!state) return;
    const nome = wrap.querySelector('.nome_produto');
    const qtd = wrap.querySelector('.qtd_produto');
    const valor = wrap.querySelector('.valor_produto');
    const peso = wrap.querySelector('.peso_produto');
    const pf = wrap.querySelector('.precisa_frete');
    const aplicaImp = wrap.querySelector('.aplica_imp_local');
    const frete = wrap.querySelector('.frete_usd');
    const prodId = wrap.querySelector('.produto_id');
    if (nome) nome.value = state.nome || '';
    if (qtd) qtd.value = state.qtd || 1;
    if (valor) valor.value = state.valor || 0;
    if (peso) peso.value = state.peso || 0;
    if (pf) {
      pf.checked = !!state.precisa_frete;
      const fg = wrap.querySelector('.frete_group');
      if (fg) fg.style.display = pf.checked ? '' : 'none';
    }
    if (aplicaImp) {
      aplicaImp.checked = (typeof state.aplica_imp_local === 'boolean') ? state.aplica_imp_local : true;
    }
    if (frete) frete.value = state.frete || 0;
    if (prodId) prodId.value = state.product_id || '';
  }

  function loadInitialBudget(){
    if (!initialBudget || !Array.isArray(initialBudget.items) || initialBudget.items.length === 0) {
      produtos.appendChild(makeItem(0));
      return;
    }
    const taxa = document.getElementById('taxa_cambio');
    if (typeof initialBudget.taxa_cambio === 'number' && taxa) {
      taxa.value = initialBudget.taxa_cambio;
    }
    const envioBrasil = document.getElementById('envio_brasil');
    if (envioBrasil && typeof initialBudget.envio_brasil !== 'undefined') {
      envioBrasil.checked = !!initialBudget.envio_brasil;
    }
    produtos.innerHTML = '';
    initialBudget.items.forEach(function(it){
      const w = makeItem(produtos.children.length);
      applyItemState(w, it);
      produtos.appendChild(w);
    });
  }

  loadInitialBudget();

  document.getElementById('btn-calcular').addEventListener('click', ()=>{
    const taxaCambio = parseFloat(document.getElementById('taxa_cambio').value||0);
    const envioBrasil = document.getElementById('envio_brasil').checked;
    const items = Array.from(produtos.querySelectorAll('.prod-item'));
    let somaValor = 0, somaPeso = 0, somaFrete = 0, somaImpLocal = 0;
    const nomes = [];
    items.forEach(w=>{
      const nome = w.querySelector('.nome_produto')?.value?.trim() || '';
      const qtd = parseInt(w.querySelector('.qtd_produto')?.value || '1', 10) || 1;
      const valor = parseFloat(w.querySelector('.valor_produto')?.value||0);
      const peso = parseFloat(w.querySelector('.peso_produto')?.value||0);
      const pf = !!(w.querySelector('.precisa_frete')?.checked);
      const aplicaImp = !!(w.querySelector('.aplica_imp_local')?.checked);
      const frete = pf ? parseFloat(w.querySelector('.frete_usd')?.value||0) : 0;
      const valorTotalItem = Math.max(0, valor) * Math.max(1, qtd);
      const pesoTotalItem = Math.max(0, peso) * Math.max(1, qtd);
      somaValor += valorTotalItem;
      somaPeso += pesoTotalItem;
      somaFrete += Math.max(0, frete);
      const impLocal = aplicaImp ? Math.max(0, valorTotalItem * 0.07) : 0;
      somaImpLocal += impLocal;
      if (nome) {
        const label = qtd > 1 ? `${nome} - x${qtd}` : nome;
        nomes.push(label);
      }
    });
    const pesoTotalArred = Math.ceil(somaPeso);
    const taxaServico = pesoTotalArred > 0 ? (pesoTotalArred * 39.0) : 0;
    const subtotalUSD = somaValor + taxaServico + somaFrete + somaImpLocal;
    const subtotalBRL = subtotalUSD * taxaCambio;
    const baseProdutoBRL = somaValor * taxaCambio;
    let impostoImport = 0, icms = 0, totalBRL = subtotalBRL;
    if (envioBrasil) {
      impostoImport = baseProdutoBRL * 0.60;
      const subtotalComImport = baseProdutoBRL + impostoImport;
      icms = subtotalComImport * 0.20;
      totalBRL = subtotalBRL + impostoImport + icms;
    }

    const usdList = document.getElementById('usd-list');
    usdList.innerHTML = '';
    const usdItems = [
      ['Valor dos produtos', somaValor],
      ['Taxa de serviço (US$ 39/kg)', taxaServico],
      ...(somaFrete>0 ? [['Frete até a sede (somado)', somaFrete]] : []),
      ...(somaImpLocal>0 ? [['Imposto local (USD) somado (7%)', somaImpLocal]] : []),
      ['Total em dólar', subtotalUSD],
      ['Conversão do total em dólar (BRL)', subtotalBRL],
    ];
    usdItems.forEach(([k,v])=>{
      const li = document.createElement('li'); li.className='list-group-item d-flex justify-content-between';
      const isBRLconv = k.includes('Conversão');
      li.innerHTML = `<span>${k}</span><span><strong>${isBRLconv ? nfBRL(v) : nfUSD(v)}</strong></span>`; usdList.appendChild(li);
    });

    const brlList = document.getElementById('brl-list');
    brlList.innerHTML = '';
    const brlItems = [
      ...(envioBrasil ? [['Imposto de Importação (60%) sobre produtos', impostoImport]] : []),
      ...(envioBrasil ? [['ICMS (20%) sobre (produto+60%)', icms]] : []),
      ...(envioBrasil ? [['Total de impostos (BRL)', (impostoImport + icms)]] : []),
    ];
    brlItems.forEach(([k,v])=>{
      const li = document.createElement('li'); li.className='list-group-item d-flex justify-content-between';
      li.innerHTML = `<span>${k}</span><span><strong>${nfBRL(v)}</strong></span>`; brlList.appendChild(li);
    });

    window.__sim = { nomes, somaValor, taxaServico, somaFrete, somaImpLocal, subtotalUSD, taxaCambio, subtotalBRL, envioBrasil, impostoImport, icms, totalBRL, pesoTotalArred };
  });

  function gerarMensagem(){
    const s = window.__sim || {};
    const lista = (s.nomes||[]).length ? s.nomes.join(', ') : 'produtos';
    const linhas = [];
    linhas.push(`Os itens (${lista}) somam ${nfUSD(s.somaValor||0)}. A taxa de serviço é ${nfUSD(s.taxaServico||0)} (US$ 39 por kg, considerando peso total arredondado de ${s.pesoTotalArred||0} kg).`);
    const compUSD = s.subtotalUSD||0;
    linhas.push('');
    linhas.push(`O total, já com fretes até a sede e imposto local quando aplicável, fica em ${nfUSD(compUSD)}, o que convertido pela taxa de câmbio atual (${nfBRL(s.taxaCambio||0)}) fica em ${nfBRL(s.subtotalBRL||0)}.`);
    if (s.envioBrasil) {
      linhas.push('');
      linhas.push('A estimativa dos impostos de importação, calculados sobre o valor dos produtos em reais, seria:');
      linhas.push(`Imposto de Importação (60%): ${nfBRL(s.impostoImport||0)}`);
      linhas.push(`ICMS (20% sobre (produto + 60%)): ${nfBRL(s.icms||0)}`);
      linhas.push(`Total de impostos (Imposto de Importação + ICMS): ${nfBRL((s.impostoImport||0) + (s.icms||0))}`);
      linhas.push('');
      linhas.push('⚠️ Lembrando que esses valores de impostos são apenas estimativas.');
      linhas.push('O pagamento dos impostos é feito diretamente à Receita Federal, quando o produto chega à alfândega.');
    }
    linhas.push('');
    linhas.push('Pela Braziliana, o valor da compra é referente apenas aos produtos + taxa de serviço.');
    document.getElementById('mensagem').value = linhas.join('\n');
  }

  const modalEnvioEl = document.getElementById('modalEnvioBrasil');
  const btnGerar = document.getElementById('btn-gerar');
  const btnModalEnvioSim = document.getElementById('modal-envio-sim-br');
  const btnModalEnvioNao = document.getElementById('modal-envio-nao-br');

  function fluxoGerarESalvar(){
    document.getElementById('btn-calcular').click();
    setTimeout(function(){
      gerarMensagem();
      salvarOrcamentoAutomatico();
    }, 50);
  }

  if (btnGerar) {
    btnGerar.addEventListener('click', function(){
      const envioBrasilMarcado = document.getElementById('envio_brasil').checked;
      if (!envioBrasilMarcado && modalEnvioEl) {
        // Tenta usar o modal do Bootstrap, se disponível; senão, segue direto com o fluxo
        if (window.bootstrap && window.bootstrap.Modal) {
          const modal = window.bootstrap.Modal.getOrCreateInstance(modalEnvioEl);
          modal.show();
        } else {
          fluxoGerarESalvar();
        }
      } else {
        fluxoGerarESalvar();
      }
    });
  }

  if (modalEnvioEl) {
    if (btnModalEnvioSim) {
      btnModalEnvioSim.addEventListener('click', function(){
        const chk = document.getElementById('envio_brasil');
        if (chk) chk.checked = true;
        // Fecha o modal se o Bootstrap estiver disponível
        if (window.bootstrap && window.bootstrap.Modal) {
          const modal = window.bootstrap.Modal.getOrCreateInstance(modalEnvioEl);
          modal.hide();
        }
        fluxoGerarESalvar();
      });
    }
    if (btnModalEnvioNao) {
      btnModalEnvioNao.addEventListener('click', function(){
        const chk = document.getElementById('envio_brasil');
        if (chk) chk.checked = false;
        if (window.bootstrap && window.bootstrap.Modal) {
          const modal = window.bootstrap.Modal.getOrCreateInstance(modalEnvioEl);
          modal.hide();
        }
        fluxoGerarESalvar();
      });
    }
  }

  document.getElementById('btn-copiar').addEventListener('click', ()=>{
    const ta = document.getElementById('mensagem');
    ta.select(); ta.setSelectionRange(0, 99999);
    document.execCommand('copy');
  });

  function collectCurrentState(){
    const taxaCambio = parseFloat(document.getElementById('taxa_cambio').value||0);
    const envioBrasil = document.getElementById('envio_brasil').checked;
    const clienteId = parseInt(document.getElementById('cliente_id')?.value || '0', 10) || null;
    const clienteNome = selectedClient ? (selectedClient.nome || null) : null;
    const clienteSuiteBr = selectedClient ? (selectedClient.suite_br || null) : null;
    const items = Array.from(produtos.querySelectorAll('.prod-item')).map(function(w){
      return {
        nome: (w.querySelector('.nome_produto')?.value || '').trim(),
        qtd: parseInt(w.querySelector('.qtd_produto')?.value || '1', 10) || 1,
        valor: parseFloat(w.querySelector('.valor_produto')?.value||0) || 0,
        peso: parseFloat(w.querySelector('.peso_produto')?.value||0) || 0,
        precisa_frete: !!(w.querySelector('.precisa_frete')?.checked),
        aplica_imp_local: !!(w.querySelector('.aplica_imp_local')?.checked),
        frete: parseFloat(w.querySelector('.frete_usd')?.value||0) || 0,
        product_id: (w.querySelector('.produto_id')?.value || '').trim() || null,
      };
    });
    return {
      taxa_cambio: taxaCambio,
      envio_brasil: envioBrasil,
      cliente_id: clienteId,
      cliente_nome: clienteNome,
      cliente_suite_br: clienteSuiteBr,
      items: items,
    };
  }
  function salvarOrcamentoAutomatico(){
    const payload = collectCurrentState();
    const agora = new Date();
    const pad = (n)=> String(n).padStart(2,'0');
    const dataStr = `${pad(agora.getDate())}/${pad(agora.getMonth()+1)}/${agora.getFullYear()} ${pad(agora.getHours())}:${pad(agora.getMinutes())}`;
    const nomeCliente = payload.cliente_nome || 'sem cliente';
    const nome = `Orçamento - ${nomeCliente} - ${dataStr}`;

    return fetch('/admin/sales-simulator/budgets/save', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: new URLSearchParams({
        _csrf: csrfToken,
        name: nome,
        payload: JSON.stringify(payload),
        id: currentBudgetId > 0 ? String(currentBudgetId) : '',
      }),
    }).then(r=>r.json()).then(function(resp){
      if (!resp || !resp.ok) {
        alert('Não foi possível salvar o orçamento automaticamente.');
        return;
      }
      if (resp.id && !currentBudgetId) {
        const url = new URL(window.location.href);
        url.searchParams.set('budget_id', String(resp.id));
        window.location.href = url.toString();
      }
    }).catch(function(){
      alert('Erro ao salvar o orçamento automaticamente.');
    });
  }
})();
</script>
