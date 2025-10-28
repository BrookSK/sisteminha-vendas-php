<?php /** @var float $usd_rate */ ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Simulador de Cálculo</h3>
    <div class="d-flex gap-2">
      <a class="btn btn-sm btn-outline-secondary" target="_blank" href="https://www.ebay.com/">🔍 eBay</a>
      <a class="btn btn-sm btn-outline-secondary" target="_blank" href="https://www.amazon.com/">🔍 Amazon</a>
      <a class="btn btn-sm btn-outline-secondary" target="_blank" href="https://www.bestbuy.com/">🔍 Best Buy</a>
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
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="m-0">Produtos</h5>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-prod">Adicionar produto</button>
      </div>
      <div id="produtos" class="vstack gap-3"></div>
    </div>
    <div class="col-12">
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

<script>
(function(){
  function nfUSD(v){ return `$ ${Number(v||0).toFixed(2)}`; }
  function nfBRL(v){ return `R$ ${Number(v||0).toFixed(2)}`; }

  const produtos = document.getElementById('produtos');
  const btnAdd = document.getElementById('btn-add-prod');

  function makeItem(idx){
    const wrap = document.createElement('div');
    wrap.className = 'card prod-item';
    const id = Date.now()+''+Math.floor(Math.random()*1000);
    wrap.innerHTML = `
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fw-semibold">Produto</div>
          <div class="d-flex gap-2">
            <div class="form-check form-switch">
              <input class="form-check-input precisa_frete" type="checkbox" id="pf_${id}">
              <label class="form-check-label" for="pf_${id}">Frete até a sede?</label>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove">Remover</button>
          </div>
        </div>
        <div class="row g-2 align-items-end">
          <div class="col-md-6">
            <label class="form-label">Nome</label>
            <input type="text" class="form-control nome_produto" placeholder="Ex: Apple Watch Series 10 Titanium 46mm">
          </div>
          <div class="col-md-3">
            <label class="form-label">Valor (USD)</label>
            <input type="number" step="0.01" min="0" class="form-control valor_produto" value="0">
          </div>
          <div class="col-md-3">
            <label class="form-label">Peso (Kg)</label>
            <input type="number" step="0.01" min="0" class="form-control peso_produto" value="0">
          </div>
          <div class="col-md-3 frete_group" style="display:none;">
            <label class="form-label">Frete (USD)</label>
            <input type="number" step="0.01" min="0" class="form-control frete_usd" value="0">
          </div>
        </div>
      </div>`;
    const pf = wrap.querySelector('.precisa_frete');
    const fg = wrap.querySelector('.frete_group');
    pf.addEventListener('change', ()=>{ fg.style.display = pf.checked ? '' : 'none'; });
    wrap.querySelector('.btn-remove').addEventListener('click', ()=>{ wrap.remove(); });
    return wrap;
  }

  btnAdd.addEventListener('click', ()=>{ produtos.appendChild(makeItem(produtos.children.length)); });
  produtos.appendChild(makeItem(0));

  document.getElementById('btn-calcular').addEventListener('click', ()=>{
    const taxaCambio = parseFloat(document.getElementById('taxa_cambio').value||0);
    const envioBrasil = document.getElementById('envio_brasil').checked;
    const items = Array.from(produtos.querySelectorAll('.prod-item'));
    let somaValor = 0, somaPeso = 0, somaFrete = 0, somaImpLocal = 0;
    const nomes = [];
    items.forEach(w=>{
      const nome = w.querySelector('.nome_produto')?.value?.trim() || '';
      const valor = parseFloat(w.querySelector('.valor_produto')?.value||0);
      const peso = parseFloat(w.querySelector('.peso_produto')?.value||0);
      const pf = w.querySelector('.precisa_frete').checked;
      const frete = pf ? parseFloat(w.querySelector('.frete_usd')?.value||0) : 0;
      somaValor += Math.max(0, valor);
      somaPeso += Math.max(0, peso);
      somaFrete += Math.max(0, frete);
      const impLocal = Math.max(0, valor * 0.07);
      somaImpLocal += impLocal;
      if (nome) nomes.push(nome);
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

  document.getElementById('btn-gerar').addEventListener('click', ()=>{
    const s = window.__sim || {};
    const lista = (s.nomes||[]).length ? s.nomes.join(', ') : 'produtos';
    const linhas = [];
    linhas.push(`Os itens (${lista}) somam ${nfUSD(s.somaValor||0)}. A taxa de serviço é ${nfUSD(s.taxaServico||0)} (US$ 39 por kg, considerando peso total arredondado de ${s.pesoTotalArred||0} kg).`);
    const compUSD = s.subtotalUSD||0;
    linhas.push(`O total, já com fretes até a sede e imposto local quando aplicável, fica em ${nfUSD(compUSD)}, o que convertido pela taxa de câmbio atual (${nfBRL(s.taxaCambio||0)}) fica em ${nfBRL(s.subtotalBRL||0)}.`);
    if (s.envioBrasil) {
      linhas.push('A estimativa dos impostos de importação, calculados sobre o valor dos produtos em reais, seria:');
      linhas.push(`Imposto de Importação (60%): ${nfBRL(s.impostoImport||0)}`);
      linhas.push(`ICMS (20% sobre (produto + 60%)): ${nfBRL(s.icms||0)}`);
      linhas.push('⚠️ Lembrando que esses valores de impostos são apenas estimativas.');
      linhas.push('O pagamento dos impostos é feito diretamente à Receita Federal, quando o produto chega à alfândega.');
    }
    linhas.push('Pela Braziliana, o valor da compra é referente apenas aos produtos + taxa de serviço.');
    document.getElementById('mensagem').value = linhas.join('\n');
  });

  document.getElementById('btn-copiar').addEventListener('click', ()=>{
    const ta = document.getElementById('mensagem');
    ta.select(); ta.setSelectionRange(0, 99999);
    document.execCommand('copy');
  });
})();
</script>
