<?php
$templates = $templates ?? [];
?>
<div class="card">
  <div class="card-body">
    <h5 class="card-title mb-3">Mensagens Padrão</h5>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label">Categoria</label>
        <select id="category" class="form-select"></select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Modelo</label>
        <select id="template" class="form-select"></select>
      </div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label">Gênero do atendente</label>
        <select id="article" class="form-select">
          <option value="a">Feminino (a)</option>
          <option value="o">Masculino (o)</option>
        </select>
      </div>
      <div class="col-md-8 d-flex align-items-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="includeSimulatorNotice">
          <label class="form-check-label" for="includeSimulatorNotice">
            Incluir aviso de orçamento de produto (Simulador de Cálculo)
          </label>
        </div>
      </div>
    </div>
    <div id="fields" class="row g-3 mb-3"></div>
    <div class="d-flex gap-2 mb-2">
      <button id="generate" class="btn btn-primary">Gerar mensagem</button>
      <button id="copy" class="btn btn-outline-secondary" type="button">Copiar</button>
    </div>
    <textarea id="output" class="form-control" rows="12" placeholder="A mensagem gerada aparecerá aqui..."></textarea>
  </div>
</div>
<script>
(function(){
  const data = <?php echo json_encode($templates, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
  const elCat = document.getElementById('category');
  const elTpl = document.getElementById('template');
  const elFields = document.getElementById('fields');
  const elOut = document.getElementById('output');
  const btnGen = document.getElementById('generate');
  const btnCopy = document.getElementById('copy');
  const elArticle = document.getElementById('article');
  const chkSimNotice = document.getElementById('includeSimulatorNotice');

  const categories = Object.keys(data);
  categories.forEach((c,i)=>{
    const opt = document.createElement('option');
    opt.value = c; opt.textContent = c; elCat.appendChild(opt);
  });

  function loadTemplates(){
    elTpl.innerHTML = '';
    const cat = elCat.value;
    const list = data[cat] || [];
    list.forEach((t,i)=>{
      const opt = document.createElement('option');
      opt.value = t.id; opt.textContent = t.titulo; elTpl.appendChild(opt);
    });
    loadFields();
  }

  function currentTemplate(){
    const cat = elCat.value; const id = elTpl.value;
    return (data[cat]||[]).find(t=>t.id===id) || null;
  }

  function loadFields(){
    elFields.innerHTML = '';
    const tpl = currentTemplate();
    if (!tpl) return;
    (tpl.campos||[]).forEach((c)=>{
      const col = document.createElement('div');
      col.className = 'col-md-6';
      const group = document.createElement('div');
      group.className = 'form-group';
      const label = document.createElement('label');
      label.className = 'form-label';
      label.textContent = c.rotulo || c.chave;
      const input = document.createElement('input');
      input.type = 'text';
      input.className = 'form-control';
      input.setAttribute('data-key', c.chave);
      if (c.valor) input.value = c.valor;
      group.appendChild(label); group.appendChild(input); col.appendChild(group);
      elFields.appendChild(col);
    });
  }

  function generate(){
    const tpl = currentTemplate(); if (!tpl) return;
    let text = String(tpl.texto||'');
    // Converte sequências literais \n, \r\n, \r em quebras reais
    text = text.replace(/\\r\\n/g, '\n').replace(/\\n/g, '\n').replace(/\\r/g, '\n');
    const inputs = elFields.querySelectorAll('input[data-key]');
    inputs.forEach(inp=>{
      const key = inp.getAttribute('data-key')||''; const val = inp.value||'';
      if (!key) return;
      const pattern = new RegExp('\\[' + key.replace(/[.*+?^${}()|\\\\]/g, '\\$&') + '\\]', 'g');
      text = text.replace(pattern, val);
    });
    // Gênero do atendente => [Artigo]
    const art = (elArticle && elArticle.value) ? elArticle.value : 'a';
    text = text.replace(/\[Artigo\]/g, art);

    // Aviso do Simulador de Cálculo (quando marcado)
    if (chkSimNotice && chkSimNotice.checked) {
      const sim = [
        'Se for orçamento de produto, faça na tela do Simulador de Cálculo: /admin/sales-simulator',
        '',
        'Preencha as informações do(s) produto(s):',
        '- Nome do produto, valor em dólar (US$) e peso em quilo (kg).',
        '- Se houver frete até a nossa sede, ative a opção e informe o valor do frete em dólar (US$).',
        '- Você pode adicionar ou remover produtos.',
        '- Você pode ativar ou desativar o envio para o Brasil (para calcular impostos).',
        '',
        'Depois:',
        '1) Clique em Calcular para gerar os valores.',
        '2) Clique em Gerar mensagem para o cliente para montar o orçamento.'
      ].join('\n');
      text = (text ? (text + '\n\n' + sim) : sim);
    }
    elOut.value = text;
  }

  function copy(){
    let v = elOut.value||''; if (!v) return;
    // Usa CRLF no clipboard para melhor compatibilidade em alguns apps no Windows
    v = v.replace(/\n/g, '\r\n');
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(v).then(()=>{
        btnCopy.classList.remove('btn-outline-secondary');
        btnCopy.classList.add('btn-success');
        btnCopy.textContent = 'Copiado!';
        setTimeout(()=>{
          btnCopy.classList.add('btn-outline-secondary');
          btnCopy.classList.remove('btn-success');
          btnCopy.textContent = 'Copiar';
        }, 1500);
      });
    } else {
      const ta = document.createElement('textarea'); ta.value = v; document.body.appendChild(ta); ta.select();
      try { document.execCommand('copy'); } catch(e) {}
      document.body.removeChild(ta);
    }
  }

  elCat.addEventListener('change', loadTemplates);
  elTpl.addEventListener('change', loadFields);
  btnGen.addEventListener('click', generate);
  btnCopy.addEventListener('click', copy);

  if (categories.length){ elCat.value = categories[0]; loadTemplates(); }
})();
</script>
