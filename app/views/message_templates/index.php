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
    const inputs = elFields.querySelectorAll('input[data-key]');
    inputs.forEach(inp=>{
      const key = inp.getAttribute('data-key')||''; const val = inp.value||'';
      if (!key) return;
      const pattern = new RegExp('\\[' + key.replace(/[.*+?^${}()|\\\\]/g, '\\$&') + '\\]', 'g');
      text = text.replace(pattern, val);
    });
    elOut.value = text;
  }

  function copy(){
    const v = elOut.value||''; if (!v) return;
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
