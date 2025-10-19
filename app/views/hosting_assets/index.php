<?php
use Core\Auth;
$csrf = Auth::csrf();
$hostings = $hostings ?? [];
$assets = $assets ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Ativos (Sites/Sistemas/E-mails)</h3>
  <a class="btn btn-outline-secondary" href="/admin">Voltar</a>
</div>
<div class="card mb-3"><div class="card-body">
  <form method="get" class="row g-2 align-items-end">
    <div class="col-md-4">
      <label class="form-label">Filtrar por Hospedagem</label>
      <select name="hosting_id" class="form-select" onchange="this.form.submit()">
        <option value="">Todas</option>
        <?php $sel = (string)($_GET['hosting_id'] ?? ''); foreach ($hostings as $h): $val=(string)$h['id']; ?>
          <option value="<?= (int)$h['id'] ?>" <?= ($sel!=='' && $sel===$val)?'selected':'' ?>><?= htmlspecialchars(($h['provider'] ?? '').' • '.($h['server_name'] ?? '')) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Ações</label><br>
      <form method="post" action="/admin/hosting-assets/refresh-dns-all" class="d-inline">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="hosting_id" value="<?= htmlspecialchars($sel) ?>">
        <button class="btn btn-outline-primary btn-sm" type="submit">Verificar todos desta hospedagem</button>
      </form>
      <a class="btn btn-outline-secondary btn-sm" href="/admin/settings/dns" target="_blank" rel="noopener">Configurações DNS</a>
    </div>
  </form>
</div></div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card"><div class="card-body">
      <h6 class="mb-3">Novo Ativo</h6>
      <?php $role = (string) (\Core\Auth::user()['role'] ?? 'seller'); $ro = ($role==='seller'); ?>
      <form method="post" action="/admin/hosting-assets/create" class="row g-2" <?= $ro?'onsubmit="return false;"':'' ?>>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <div class="col-12">
          <label class="form-label">Título</label>
          <input type="text" name="title" class="form-control" required <?= $ro?'disabled':'' ?>>
        </div>
        <div class="col-12">
          <label class="form-label">URL</label>
          <input type="url" name="url" class="form-control" placeholder="https://exemplo.com" <?= $ro?'disabled':'' ?>>
        </div>
        <div class="col-12">
          <label class="form-label">Hospedagem</label>
          <select name="hosting_id" class="form-select" <?= $ro?'disabled':'' ?>>
            <option value="">Selecione...</option>
            <?php foreach ($hostings as $h): ?>
              <option value="<?= (int)$h['id'] ?>"><?= htmlspecialchars(($h['provider'] ?? '').' • '.($h['server_name'] ?? '')) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6">
          <label class="form-label">Tipo</label>
          <select name="type" class="form-select" <?= $ro?'disabled':'' ?>>
            <?php foreach (['site','sistema','email'] as $t): ?>
              <option value="<?= $t ?>"><?= ucfirst($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6">
          <label class="form-label">Cliente</label>
          <input type="hidden" name="client_id" id="client_id_new">
          <input type="text" id="client_search_new" class="form-control" placeholder="Digite para buscar cliente" autocomplete="off" <?= $ro?'disabled':'' ?>>
          <div id="client_suggest_new" class="list-group position-absolute w-100" style="z-index:1000; display:none;"></div>
        </div>
        <div class="col-6">
          <label class="form-label">IP do Servidor</label>
          <input type="text" name="server_ip" class="form-control" placeholder="herdado da hospedagem, se escolhida" <?= $ro?'disabled':'' ?>>
        </div>
        <div class="col-6">
          <label class="form-label">IP Real (DNS)</label>
          <input type="text" name="real_ip" class="form-control" placeholder="pode ser preenchido automaticamente via DNS" <?= $ro?'disabled':'' ?>>
        </div>
        <div class="col-12 d-grid">
          <button class="btn btn-primary" type="submit" <?= $ro?'disabled':'' ?>>Adicionar</button>
        </div>
      </form>
      <div class="text-muted small mt-3">
        Dica: Para resolução via Cloudflare, defina as variáveis de ambiente <code>CF_API_TOKEN</code> e <code>CF_ACCOUNT_EMAIL</code>.
      </div>
    </div></div>
  </div>
  <div class="col-lg-8">
    <div class="card"><div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead><tr>
            <th>Título</th>
            <th>Tipo</th>
            <th>URL</th>
            <th>Servidor (IP)</th>
            <th>Real IP</th>
            <th>Cliente</th>
            <th>Apontamento</th>
            <th>Últ. verificação</th>
            <th style="width: 260px;">Ações</th>
          </tr></thead>
          <tbody>
            <?php foreach ($assets as $a): ?>
              <tr>
                <td><?= htmlspecialchars($a['title']) ?></td>
                <td><?= htmlspecialchars($a['type']) ?></td>
                <td><a href="<?= htmlspecialchars($a['url'] ?? '#') ?>" target="_blank" rel="noopener"><?= htmlspecialchars($a['url'] ?? '-') ?></a></td>
                <td><?= htmlspecialchars($a['server_ip'] ?? '-') ?></td>
                <td><?= htmlspecialchars($a['real_ip'] ?? '-') ?></td>
                <td><?= htmlspecialchars($a['client_name'] ?? '-') ?></td>
                <td>
                  <?php $ok = (int)($a['pointing_ok'] ?? -1);
                    if ($ok === 1) echo '<span class="badge text-bg-success">OK</span>'; 
                    elseif ($ok === 0) echo '<span class="badge text-bg-danger">INCORRETO</span>'; 
                    else echo '<span class="badge text-bg-secondary">N/D</span>'; ?>
                </td>
                <td><?= htmlspecialchars($a['checked_at'] ?? '-') ?></td>
                <td>
                  <form method="post" action="/admin/hosting-assets/refresh-dns" class="d-inline">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                    <button class="btn btn-sm btn-outline-primary" type="submit">Atualizar DNS</button>
                  </form>
                  <?php if ($role !== 'seller'): ?>
                  <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#edit<?= (int)$a['id'] ?>">Editar</button>
                  <form method="post" action="/admin/hosting-assets/delete" class="d-inline" onsubmit="return confirm('Excluir este ativo?');">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
                  </form>
                  <?php endif; ?>
                </td>
              </tr>
              <tr class="collapse" id="edit<?= (int)$a['id'] ?>"><td colspan="7">
                <form method="post" action="/admin/hosting-assets/update" class="row g-2">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                  <div class="col-md-4">
                    <label class="form-label">Título</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($a['title']) ?>" <?= $ro?'disabled':'' ?>>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">URL</label>
                    <input type="url" name="url" class="form-control" value="<?= htmlspecialchars($a['url'] ?? '') ?>" <?= $ro?'disabled':'' ?>>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Hospedagem</label>
                    <select name="hosting_id" class="form-select" <?= $ro?'disabled':'' ?>>
                      <option value="">Selecione...</option>
                      <?php foreach ($hostings as $h): ?>
                        <option value="<?= (int)$h['id'] ?>" <?= ((int)($a['hosting_id'] ?? 0) === (int)$h['id'])?'selected':'' ?>><?= htmlspecialchars(($h['provider'] ?? '').' • '.($h['server_name'] ?? '')) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <select name="type" class="form-select" <?= $ro?'disabled':'' ?>>
                      <?php foreach (['site','sistema','email'] as $t): ?>
                        <option value="<?= $t ?>" <?= (($a['type'] ?? '')===$t)?'selected':'' ?>><?= ucfirst($t) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Cliente</label>
                    <input type="hidden" name="client_id" id="client_id_<?= (int)$a['id'] ?>" value="<?= htmlspecialchars((string)($a['client_id'] ?? '')) ?>">
                    <input type="text" id="client_search_<?= (int)$a['id'] ?>" class="form-control" placeholder="Digite para buscar cliente" autocomplete="off" <?= $ro?'disabled':'' ?> value="<?= htmlspecialchars($a['client_name'] ?? '') ?>">
                    <div id="client_suggest_<?= (int)$a['id'] ?>" class="list-group position-absolute w-100" style="z-index:1000; display:none;"></div>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">IP do Servidor</label>
                    <input type="text" name="server_ip" class="form-control" value="<?= htmlspecialchars($a['server_ip'] ?? '') ?>" <?= $ro?'disabled':'' ?>>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">IP Real (DNS)</label>
                    <input type="text" name="real_ip" class="form-control" value="<?= htmlspecialchars($a['real_ip'] ?? '') ?>" <?= $ro?'disabled':'' ?>>
                  </div>
                  <?php if ($role !== 'seller'): ?>
                    <div class="col-12 d-grid">
                      <button class="btn btn-outline-primary" type="submit">Salvar</button>
                    </div>
                  <?php endif; ?>
                </form>
              </td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div></div>
  </div>
</div>

<script>
(function(){
  function setupAuto(idBase){
    var input = document.getElementById('client_search_'+idBase);
    var hidden = document.getElementById('client_id_'+idBase);
    var list = document.getElementById('client_suggest_'+idBase);
    if(!input||!hidden||!list) return;
    var timer=null;
    input.addEventListener('input', function(){
      var q = this.value.trim();
      hidden.value='';
      if(timer) clearTimeout(timer);
      if(q.length<2){ list.style.display='none'; list.innerHTML=''; return; }
      timer=setTimeout(function(){
        fetch('/admin/clients/options?q='+encodeURIComponent(q))
          .then(r=>r.json()).then(arr=>{
            list.innerHTML='';
            arr.forEach(function(o){
              var a=document.createElement('a'); a.href='#'; a.className='list-group-item list-group-item-action';
              a.textContent=o.text; a.dataset.id=o.id; a.addEventListener('click', function(e){ e.preventDefault(); hidden.value=this.dataset.id; input.value=this.textContent; list.style.display='none';});
              list.appendChild(a);
            });
            list.style.display = arr.length? 'block':'none';
          });
      }, 250);
    });
    document.addEventListener('click', function(e){ if(!list.contains(e.target) && e.target!==input){ list.style.display='none'; } });
  }
  // new form
  setupAuto('new');
  // edits
  <?php foreach ($assets as $a): ?>
    setupAuto('<?= (int)$a['id'] ?>');
  <?php endforeach; ?>
})();
</script>
