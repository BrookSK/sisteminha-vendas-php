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

  <form class="row g-3" id="sim-form" onsubmit="return false;">
    <div class="col-12">
      <label class="form-label">Nome do Produto</label>
      <input type="text" class="form-control" id="nome_produto" placeholder="Ex: Apple Watch Series 10 Titanium 46mm">
    </div>
    <div class="col-md-4">
      <label class="form-label">Valor do Produto (USD)</label>
      <input type="number" step="0.01" min="0" class="form-control" id="valor_produto" value="0">
    </div>
    <div class="col-md-4">
      <label class="form-label">Peso do Produto (Kg)</label>
      <input type="number" step="0.01" min="0" class="form-control" id="peso" value="0">
      <div class="form-text">Peso é arredondado para cima (ceil).</div>
    </div>
    <div class="col-md-4">
      <label class="form-label">Taxa de câmbio (USD → BRL)</label>
      <input type="number" step="0.01" min="0" class="form-control" id="taxa_cambio" value="<?= htmlspecialchars((string)$usd_rate) ?>" readonly>
    </div>

    <div class="col-md-4">
      <div class="form-check form-switch mt-4">
        <input class="form-check-input" type="checkbox" id="precisa_frete">
        <label class="form-check-label" for="precisa_frete">Requer frete até a sede?</label>
      </div>
    </div>
    <div class="col-md-4" id="grupo_frete" style="display:none;">
      <label class="form-label">Valor do Frete até a sede (USD)</label>
      <input type="number" step="0.01" min="0" class="form-control" id="frete_usd" value="0">
    </div>

    <div class="col-md-4">
      <label class="form-label">Imposto local (USD)</label>
      <input type="number" step="0.01" min="0" class="form-control" id="imposto_local_usd" value="0">
      <div class="form-text">Informe o valor cobrado localmente (em USD). Não é calculado automaticamente.</div>
    </div>

    <div class="col-md-4">
      <div class="form-check form-switch mt-4">
        <input class="form-check-input" type="checkbox" id="envio_brasil">
        <label class="form-check-label" for="envio_brasil">Envio para o Brasil? (calcular impostos)</label>
      </div>
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
  const precisaFrete = document.getElementById('precisa_frete');
  const grupoFrete = document.getElementById('grupo_frete');
  precisaFrete.addEventListener('change', ()=>{
    grupoFrete.style.display = precisaFrete.checked ? '' : 'none';
  });

  function nfUSD(v){ return `$ ${Number(v||0).toFixed(2)}`; }
  function nfBRL(v){ return `R$ ${Number(v||0).toFixed(2)}`; }

  document.getElementById('btn-calcular').addEventListener('click', ()=>{
    const nome = document.getElementById('nome_produto').value.trim();
    const valorProduto = parseFloat(document.getElementById('valor_produto').value||0);
    const peso = Math.ceil(parseFloat(document.getElementById('peso').value||0));
    const taxaCambio = parseFloat(document.getElementById('taxa_cambio').value||0);
    const isFrete = precisaFrete.checked;
    const freteUsd = isFrete ? parseFloat(document.getElementById('frete_usd').value||0) : 0;
    const impLocal = parseFloat(document.getElementById('imposto_local_usd').value||0);
    const taxaServico = (peso > 0 ? (peso * 39.0) : 0);
    const subtotalUSD = valorProduto + taxaServico + freteUsd + impLocal;
    const subtotalBRL = subtotalUSD * taxaCambio;

    const envioBrasil = document.getElementById('envio_brasil').checked;
    // Base dos impostos de importação: APENAS o valor do produto em BRL
    const baseProdutoBRL = valorProduto * taxaCambio;
    let impostoImport = 0, icms = 0, subtotalComImport = baseProdutoBRL, totalBRL = subtotalBRL;
    if (envioBrasil) {
      impostoImport = baseProdutoBRL * 0.60; // 60% sobre valor do produto (BRL)
      subtotalComImport = baseProdutoBRL + impostoImport;
      icms = subtotalComImport * 0.20; // 20% sobre (produto + 60%)
      // Total final estimado em BRL: conversão do total em USD (produto + taxa + frete + imposto local) + impostos estimados
      totalBRL = subtotalBRL + impostoImport + icms;
    }

    // USD list
    const usdList = document.getElementById('usd-list');
    usdList.innerHTML = '';
    const usdItems = [
      ['Valor do produto', valorProduto],
      ['Taxa de serviço (US$ 39/kg)', taxaServico],
      ...(isFrete ? [['Frete até a sede', freteUsd]] : []),
      ...(impLocal>0 ? [['Imposto local (USD)', impLocal]] : []),
      ['Total em dólar', subtotalUSD],
    ];
    usdItems.forEach(([k,v])=>{
      const li = document.createElement('li'); li.className='list-group-item d-flex justify-content-between';
      li.innerHTML = `<span>${k}</span><span><strong>${nfUSD(v)}</strong></span>`; usdList.appendChild(li);
    });

    // BRL list
    const brlList = document.getElementById('brl-list');
    brlList.innerHTML = '';
    const brlItems = [
      ['Conversão do total em dólar', subtotalBRL],
      ...(envioBrasil ? [['Imposto de Importação (60%) sobre produto', impostoImport]] : []),
      ...(envioBrasil ? [['ICMS (20%) sobre (produto+60%)', icms]] : []),
      ['Total final estimado (BRL)', totalBRL],
    ];
    brlItems.forEach(([k,v])=>{
      const li = document.createElement('li'); li.className='list-group-item d-flex justify-content-between';
      li.innerHTML = `<span>${k}</span><span><strong>${nfBRL(v)}</strong></span>`; brlList.appendChild(li);
    });

    // Guardar no estado para geração
    window.__sim = { nome, valorProduto, taxaServico, freteUsd, impLocal, subtotalUSD, taxaCambio, subtotalBRL, envioBrasil, impostoImport, icms, totalBRL };
  });

  document.getElementById('btn-gerar').addEventListener('click', ()=>{
    const s = window.__sim || {};
    const nome = document.getElementById('nome_produto').value.trim() || 'produto';
    const linhas = [];
    linhas.push('💬 Simulação de compra internacional – Brasiliana');
    linhas.push(`O produto ${nome} tem o valor de ${nfUSD(s.valorProduto||0)}, e a taxa de serviço é de ${nfUSD(s.taxaServico||0)} (calculada a US$ 39 por kg).`);
    const compUSD = s.subtotalUSD||0;
    linhas.push(`O total, já com o frete até nossa sede e o imposto local (quando aplicável), fica aproximadamente em ${nfUSD(compUSD)}, o que convertido pela taxa de câmbio atual (${nfBRL(s.taxaCambio||0)}) fica em torno de ${nfBRL(s.subtotalBRL||0)}.`);
    if (s.envioBrasil) {
      linhas.push('A estimativa dos impostos de importação, calculados sobre o valor do produto em reais, seria:');
      linhas.push(`Imposto de Importação (60%): ${nfBRL(s.impostoImport||0)}`);
      linhas.push(`ICMS (20% sobre (produto + 60%)): ${nfBRL(s.icms||0)}`);
      linhas.push('⚠️ Lembrando que esses valores de impostos são apenas estimativas.');
      linhas.push('O pagamento dos impostos é feito diretamente à Receita Federal, quando o produto chega à alfândega.');
    }
    linhas.push('Pela Brasiliana, o valor da compra é referente apenas aos produtos + taxa de serviço.');
    document.getElementById('mensagem').value = linhas.join('\n');
  });

  document.getElementById('btn-copiar').addEventListener('click', ()=>{
    const ta = document.getElementById('mensagem');
    ta.select(); ta.setSelectionRange(0, 99999);
    document.execCommand('copy');
  });
})();
</script>
