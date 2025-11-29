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
    <div class="col-md-4">
      <div class="form-check form-switch mt-4">
        <input class="form-check-input" type="checkbox" id="cliente_clube">
        <label class="form-check-label" for="cliente_clube">Cliente faz parte do Clube?</label>
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
    <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
      <button class="btn btn-primary" id="btn-calcular">Calcular</button>
      <button class="btn btn-outline-secondary" id="btn-gerar">Gerar mensagem para o cliente</button>
      <button class="btn btn-outline-dark" id="btn-copiar" type="button">Copiar mensagem</button>
      <div class="form-check form-switch ms-3">
        <input class="form-check-input" type="checkbox" id="orcamento_pago">
        <label class="form-check-label" for="orcamento_pago">Orçamento pago?</label>
      </div>
    </div>
  </form>

  <div class="row mt-4">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">💵 Valores Finais</div>
        <div class="card-body">
          <ul class="list-group list-group-flush" id="usd-list"></ul>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">💰 Impostos do Brasil</div>
        <div class="card-body">
          <ul class="list-group list-group-flush" id="brl-list"></ul>
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-3">
    <div class="card-header">🧾 Mensagem final</div>
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
  const currentBudgetIdServer = <?php echo (int)($budget_id ?? 0); ?>;
  let currentBudgetId = currentBudgetIdServer;
  const currentBudgetName = <?php echo json_encode($budget_name ?? '', JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
  const currentBudgetPaid = <?php echo !empty($budget_paid) ? 'true' : 'false'; ?>;
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
    // Guarda a versão normalizada do cliente, garantindo que tenha "nome"
    selectedClient = {
      ...c,
      nome,
      suite_br: suiteBr,
      suite: suiteRaw,
    };
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
            <div class="small text-muted mt-1">Digite para buscar na base de produtos cadastrados.</div>
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
            <input type="hidden" class="produto_id" value="">
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

    // Integração com base de produtos (apenas busca/autocomplete)
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

    // Removida a criação direta de produtos no simulador: produtos devem ser cadastrados apenas via importação pelo administrador.
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
    const pagoEl = document.getElementById('orcamento_pago');
    if (pagoEl) {
      pagoEl.checked = !!currentBudgetPaid;
    }
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
    const clienteClube = document.getElementById('cliente_clube');
    if (clienteClube && typeof initialBudget.cliente_clube !== 'undefined') {
      clienteClube.checked = !!initialBudget.cliente_clube;
    }
    // Restaura cliente selecionado, se houver no orçamento salvo
    if (initialBudget.cliente_id) {
      const cid = parseInt(initialBudget.cliente_id, 10) || null;
      const cnome = initialBudget.cliente_nome || '';
      const csuiteBr = initialBudget.cliente_suite_br || null;
      if (cid && (cnome || csuiteBr)) {
        setSelectedClient({
          id: cid,
          nome: cnome,
          suite_br: csuiteBr,
        });
      }
    }
    produtos.innerHTML = '';
    initialBudget.items.forEach(function(it){
      const w = makeItem(produtos.children.length);
      applyItemState(w, it);
      produtos.appendChild(w);
    });
  }

  loadInitialBudget();

  // Confirmação ao marcar orçamento como pago no simulador
  (function(){
    const chk = document.getElementById('orcamento_pago');
    if (!chk) return;
    let lastState = chk.checked;
    chk.addEventListener('change', function(){
      // Só pede confirmação quando for marcar como pago
      if (this.checked && !lastState) {
        const ok = window.confirm('Tem certeza que deseja marcar este orçamento como pago?\n\nSe confirmar, este orçamento será marcado como pago e entrará na fila para compra pela Fabiana.');
        if (!ok) {
          this.checked = false;
          lastState = this.checked;
          return;
        }
        // Após confirmar que está pago, salva automaticamente o orçamento com o status atualizado
        try {
          salvarOrcamentoAutomatico();
        } catch (e) {
          console && console.error && console.error('Erro ao salvar orçamento automaticamente após marcar como pago', e);
        }
      }
      lastState = this.checked;
    });
  })();

  function calcularESincronizar(){
    const taxaCambio = parseFloat(document.getElementById('taxa_cambio').value||0);
    const envioBrasil = document.getElementById('envio_brasil').checked;
    const clienteClubeEl = document.getElementById('cliente_clube');
    const clienteClube = !!(clienteClubeEl && clienteClubeEl.checked);
    const clienteId = parseInt(document.getElementById('cliente_id')?.value || '0', 10) || null;
    const clienteNome = selectedClient ? (selectedClient.nome || null) : null;
    const clienteSuiteBr = selectedClient ? (selectedClient.suite_br || null) : null;
    const orcamentoPagoEl = document.getElementById('orcamento_pago');
    const orcamentoPago = !!(orcamentoPagoEl && orcamentoPagoEl.checked);

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

    // Cálculos básicos em USD
    let somaProdutosUSD = 0;
    let somaFretesUSD = 0;
    let pesoTotalKg = 0;
    const produtosDetalhes = [];

    items.forEach(function(it){
      if (!it || !it.nome) return;
      const qtd = it.qtd || 1;
      const valorUnit = it.valor || 0;
      const pesoUnit = it.peso || 0;
      const freteUnit = it.frete || 0;

      const valorTotalItem = valorUnit * qtd;
      somaProdutosUSD += valorTotalItem;
      if (it.precisa_frete) {
        somaFretesUSD += freteUnit;
      }
      pesoTotalKg += pesoUnit * qtd;

      produtosDetalhes.push({
        nome: it.nome,
        qtd: qtd,
        valorUnit: valorUnit,
        valorTotal: valorTotalItem,
      });
    });

    // Peso arredondado para taxa de serviço (regra simples: arredonda para cima em kg cheios)
    const pesoTotalArred = Math.ceil(pesoTotalKg || 0);
    const taxaServico = 39 * (pesoTotalArred || 0);
    const subtotalUSD = somaProdutosUSD + somaFretesUSD + taxaServico;
    const subtotalBRL = subtotalUSD * (taxaCambio || 0);

    // Cashback por faixas de peso, aplicado apenas sobre o subtotal dos valores dos produtos (somaProdutosUSD)
    let cashbackPercent = 0;
    if (clienteClube) {
      const p = pesoTotalKg || 0;
      if (p <= 5) cashbackPercent = 5;
      else if (p <= 10) cashbackPercent = 10;
      else if (p <= 20) cashbackPercent = 15;
      else if (p <= 30) cashbackPercent = 20;
      else if (p <= 40) cashbackPercent = 25;
      else if (p <= 50) cashbackPercent = 30;
      else if (p <= 60) cashbackPercent = 35;
      else if (p <= 70) cashbackPercent = 40;
      else if (p <= 80) cashbackPercent = 45;
      else cashbackPercent = 50; // acima de 80kg
    }
    const cashbackUSD = clienteClube && cashbackPercent > 0 ? (somaProdutosUSD * (cashbackPercent / 100)) : 0;
    const cashbackBRL = cashbackUSD * (taxaCambio || 0);

    // Imposto local (7%) em USD, somado apenas sobre itens com aplica_imp_local
    let impostoLocalUSD = 0;
    items.forEach(function(it){
      if (!it || !it.nome) return;
      if (!it.aplica_imp_local) return;
      const qtd = it.qtd || 1;
      const valorUnit = it.valor || 0;
      const valorTotalItem = valorUnit * qtd;
      impostoLocalUSD += valorTotalItem * 0.07;
    });

    // Impostos de importação (quando envioBrasil=true)
    let impostoImportBRL = 0;
    let icmsBRL = 0;
    if (envioBrasil) {
      const baseProdutosBRL = somaProdutosUSD * (taxaCambio || 0);
      impostoImportBRL = baseProdutosBRL * 0.60;
      icmsBRL = (baseProdutosBRL + impostoImportBRL) * 0.20;
    }

    // Atualiza window.__sim com os dados calculados
    window.__sim = {
      taxaCambio: taxaCambio,
      envioBrasil: envioBrasil,
      clienteClube: clienteClube,
      clienteId: clienteId,
      clienteNome: clienteNome,
      clienteSuiteBr: clienteSuiteBr,
      somaValor: somaProdutosUSD,
      somaFretes: somaFretesUSD,
      taxaServico: taxaServico,
      impostoLocalUSD: impostoLocalUSD,
      subtotalUSD: subtotalUSD + impostoLocalUSD,
      subtotalBRL: (subtotalUSD + impostoLocalUSD) * (taxaCambio || 0),
      pesoTotalKg: pesoTotalKg,
      pesoTotalArred: pesoTotalArred,
      impostoImport: impostoImportBRL,
      icms: icmsBRL,
      cashbackPercent: cashbackPercent,
      cashbackUSD: cashbackUSD,
      cashbackBRL: cashbackBRL,
      produtosDetalhes: produtosDetalhes,
      paid: orcamentoPago,
    };

    // Renderiza listas de resultado
    const usdList = document.getElementById('usd-list');
    const brlList = document.getElementById('brl-list');
    if (usdList) {
      usdList.innerHTML = '';

      // Soma dos valores dos produtos
      const liProd = document.createElement('li');
      liProd.className = 'list-group-item d-flex justify-content-between';
      liProd.innerHTML = '<span>Produtos</span><span>'+nfUSD(somaProdutosUSD)+'</span>';
      usdList.appendChild(liProd);

      // Taxa de serviço
      const liTaxa = document.createElement('li');
      liTaxa.className = 'list-group-item d-flex justify-content-between';
      liTaxa.innerHTML = '<span>Taxa de serviço (US$ 39/kg)</span><span>'+nfUSD(taxaServico)+'</span>';
      usdList.appendChild(liTaxa);

      // Frete até a sede (soma)
      const liFrete = document.createElement('li');
      liFrete.className = 'list-group-item d-flex justify-content-between';
      liFrete.innerHTML = '<span>Frete até a sede (soma)</span><span>'+nfUSD(somaFretesUSD)+'</span>';
      usdList.appendChild(liFrete);

      // Imposto local em dólar (7%)
      const liImpLocal = document.createElement('li');
      liImpLocal.className = 'list-group-item d-flex justify-content-between';
      liImpLocal.innerHTML = '<span>Imposto local (7%)</span><span>'+nfUSD(impostoLocalUSD)+'</span>';
      usdList.appendChild(liImpLocal);

      // Total em dólar (inclui imposto local)
      const simAtual = window.__sim || {};
      const totalUSDComImpLocal = typeof simAtual.subtotalUSD === 'number' ? simAtual.subtotalUSD : (subtotalUSD + impostoLocalUSD);
      const totalBRLComImpLocal = typeof simAtual.subtotalBRL === 'number' ? simAtual.subtotalBRL : ((subtotalUSD + impostoLocalUSD) * (taxaCambio || 0));

      const liTotal = document.createElement('li');
      liTotal.className = 'list-group-item d-flex justify-content-between fw-bold';
      liTotal.innerHTML = '<span>Total em USD</span><span>'+nfUSD(totalUSDComImpLocal)+'</span>';
      usdList.appendChild(liTotal);

      // Conversão total em reais (inclui imposto local)
      const liTotalBRL = document.createElement('li');
      liTotalBRL.className = 'list-group-item d-flex justify-content-between';
      liTotalBRL.innerHTML = '<span>Total convertido em BRL</span><span>'+nfBRL(totalBRLComImpLocal)+'</span>';
      usdList.appendChild(liTotalBRL);

      // Cashback (quando cliente é do clube e houver valor)
      const cashbackUSD = simAtual.cashbackUSD || 0;
      if (clienteClube && cashbackUSD > 0) {
        const liCash = document.createElement('li');
        liCash.className = 'list-group-item d-flex justify-content-between text-success';
        liCash.innerHTML = '<span>Cashback (Clube)</span><span>'+nfUSD(cashbackUSD)+'</span>';
        usdList.appendChild(liCash);
      }
    }

    if (brlList) {
      brlList.innerHTML = '';
      if (!envioBrasil) {
        const liInfo = document.createElement('li');
        liInfo.className = 'list-group-item';
        liInfo.textContent = 'Impostos não calculados (envio para o Brasil não marcado).';
        brlList.appendChild(liInfo);
      } else {
        const liImp = document.createElement('li');
        liImp.className = 'list-group-item d-flex justify-content-between';
        liImp.innerHTML = '<span>Imposto de Importação (60%)</span><span>'+nfBRL(impostoImportBRL)+'</span>';
        brlList.appendChild(liImp);

        const liIcms = document.createElement('li');
        liIcms.className = 'list-group-item d-flex justify-content-between';
        liIcms.innerHTML = '<span>ICMS (20% sobre produto + 60%)</span><span>'+nfBRL(icmsBRL)+'</span>';
        brlList.appendChild(liIcms);

        const liTotImp = document.createElement('li');
        liTotImp.className = 'list-group-item d-flex justify-content-between fw-bold';
        liTotImp.innerHTML = '<span>Total de impostos estimados</span><span>'+nfBRL(impostoImportBRL+icmsBRL)+'</span>';
        brlList.appendChild(liTotImp);
      }
    }
  }

  const btnCalcular = document.getElementById('btn-calcular');
  if (btnCalcular) {
    btnCalcular.addEventListener('click', function(){
      calcularESincronizar();
    });
  }

  function gerarMensagem(){
    const s = window.__sim || {};
    const linhas = [];

    // Lista de produtos
    linhas.push('O(s) produto(s):');
    const detalhados = Array.isArray(s.produtosDetalhes) ? s.produtosDetalhes : [];
    if (detalhados.length === 0) {
      linhas.push('- Produtos não informados.');
    } else {
      detalhados.forEach(function(p){
        const qtdLabel = (p.qtd && p.qtd > 1) ? ` x${p.qtd}` : '';
        linhas.push(`- ${p.nome}${qtdLabel} no valor de ${nfUSD(p.valorTotal || 0)}`);
      });
    }
    linhas.push('');

    // Somatório e taxa de serviço
    linhas.push(`Somam ${nfUSD(s.somaValor || 0)}.`);
    linhas.push(
      `A taxa de serviço é ${nfUSD(s.taxaServico || 0)} (US$ 39 por kg, considerando peso total estimado de ${s.pesoTotalArred || 0} kg. ` +
      'Se houver diferença de peso ao chegar em nossa sede, cobraremos a diferença se estiver mais pesado ou estornaremos o valor se estiver mais leve).'
    );
    linhas.push('');

    // Total com frete até a sede e imposto local quando aplicável
    const compUSD = s.subtotalUSD || 0;
    linhas.push(
      `O total, já com a entrega até a nossa sede e imposto local quando aplicável, fica em ${nfUSD(compUSD)}, ` +
      `o que convertido pela taxa de câmbio atual (${nfBRL(s.taxaCambio || 0)}) fica em ${nfBRL(s.subtotalBRL || 0)}.`
    );
    linhas.push('Você pode parcelar em até 12x no cartão, pagar no PIX ou boleto.');

    // Cashback do Clube Braziliana
    if (s.clienteClube && (s.cashbackUSD || 0) > 0) {
      linhas.push('');
      linhas.push(
        'Porque você é membro do Clube Braziliana, o seu pedido de hoje garante US$ ' +
        Number(s.cashbackUSD || 0).toFixed(2) +
        ' em cashback para usar na próxima compra. Esse valor será creditado na sua carteira virtual em até 48 horas.'
      );
    }

    // Bloco de impostos de importação (apenas se envioBrasil=true)
    if (s.envioBrasil) {
      linhas.push('');
      linhas.push('A estimativa dos impostos de importação considera a soma do valor total dos produtos e do envio. ' +
                  'Como na Braziliana o frete é grátis, o cálculo é feito apenas sobre o valor dos produtos. ' +
                  'Em reais, isso seria aproximadamente:');
      linhas.push(`Imposto de Importação (60%): ${nfBRL(s.impostoImport || 0)}`);
      linhas.push(`ICMS (20% sobre (produto + 60%)): ${nfBRL(s.icms || 0)}`);
      linhas.push(`Total de impostos (Imposto de Importação + ICMS): ${nfBRL((s.impostoImport || 0) + (s.icms || 0))}`);
      linhas.push('');
      linhas.push('⚠️ Lembrando que esses valores de impostos são apenas estimativas.');
      linhas.push('O pagamento dos impostos é feito diretamente à Receita Federal, quando o produto passa pela fiscalização da Receita Federal.');
    }

    linhas.push('');
    linhas.push('Pela Braziliana, o valor da compra é referente apenas aos produtos + taxa de serviço.');
    linhas.push('');
    linhas.push('Caso você queira parcelar o pagamento dos seus impostos (já que a Receita Federal aceita apenas pagamento à vista), a Braziliana pode te ajudar com isso. ' +
                'Assim que seus impostos estiverem disponíveis para pagamento, entre em contato conosco que cuidamos de todo o processo para você.');

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
    const clienteClubeEl = document.getElementById('cliente_clube');
    const clienteClube = !!(clienteClubeEl && clienteClubeEl.checked);
    const clienteId = parseInt(document.getElementById('cliente_id')?.value || '0', 10) || null;
    const clienteNome = selectedClient ? (selectedClient.nome || null) : null;
    const clienteSuiteBr = selectedClient ? (selectedClient.suite_br || null) : null;
    const orcamentoPagoEl = document.getElementById('orcamento_pago');
    const orcamentoPago = !!(orcamentoPagoEl && orcamentoPagoEl.checked);
    const dataPagamentoEl = document.getElementById('data_pagamento');
    const dataPagamento = (dataPagamentoEl && dataPagamentoEl.value) ? dataPagamentoEl.value : null;
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
    const sim = window.__sim || {};
    return {
      taxa_cambio: taxaCambio,
      envio_brasil: envioBrasil,
      cliente_clube: clienteClube,
      cliente_id: clienteId,
      cliente_nome: clienteNome,
      cliente_suite_br: clienteSuiteBr,
      paid: orcamentoPago,
      paid_at: dataPagamento,
      cashback_percent: sim.cashbackPercent || 0,
      cashback_usd: sim.cashbackUSD || 0,
      cashback_brl: sim.cashbackBRL || 0,
      peso_total_kg: sim.pesoTotalArred || null,
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
        paid: payload.paid ? '1' : '0',
      }),
    }).then(r=>r.json()).then(function(resp){
      if (!resp || !resp.ok) {
        alert('Não foi possível salvar o orçamento automaticamente.');
        return;
      }
      if (resp.id && !currentBudgetId) {
        // Atualiza o ID atual em memória, sem recarregar a página
        currentBudgetId = resp.id;
        // Atualiza a URL para conter o budget_id, sem reload
        try {
          const url = new URL(window.location.href);
          url.searchParams.set('budget_id', String(resp.id));
          window.history.replaceState({}, document.title, url.toString());
        } catch (e) {
          // se der erro, simplesmente segue sem quebrar o fluxo
        }
      }
    }).catch(function(){
      alert('Erro ao salvar o orçamento automaticamente.');
    });
  }
})();
</script>
