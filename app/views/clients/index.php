<?php use Core\Auth; ?>
<?php $role = (string)((Auth::user()['role'] ?? 'seller')); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Clientes</h5>
  <a href="/admin/clients/new" class="btn btn-primary">Novo Cliente</a>
</div>
<form class="row g-2 mb-3" method="get" action="/admin/clients">
  <div class="col-md-7 col-sm-12">
    <input type="text" class="form-control" name="q" placeholder="Buscar por nome, e-mail, telefone ou suite" value="<?= htmlspecialchars($q ?? '') ?>">
  </div>
  <div class="col-md-3 col-sm-6">
    <?php $sortVal = $sort ?? 'created_at_desc'; ?>
    <select class="form-select" name="sort">
      <option value="created_at_desc" <?= $sortVal==='created_at_desc'?'selected':'' ?>>Mais recentes</option>
      <option value="created_at_asc" <?= $sortVal==='created_at_asc'?'selected':'' ?>>Mais antigos</option>
      <option value="nome_asc" <?= $sortVal==='nome_asc'?'selected':'' ?>>Nome (A-Z)</option>
      <option value="nome_desc" <?= $sortVal==='nome_desc'?'selected':'' ?>>Nome (Z-A)</option>
      <option value="total_vendas_desc" <?= $sortVal==='total_vendas_desc'?'selected':'' ?>>Total vendas (maior→menor)</option>
      <option value="total_vendas_asc" <?= $sortVal==='total_vendas_asc'?'selected':'' ?>>Total vendas (menor→maior)</option>
    </select>
  </div>
  <div class="col-md-2 col-sm-6 d-grid">
    <button class="btn btn-outline-secondary" type="submit">Buscar</button>
  </div>
</form>
<div id="clients-list">
  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>Nome</th>
          <th>E-mail</th>
          <th>Telefone</th>
          <th>Suite</th>
          <th>Data Cadastro</th>
          <th>Total Vendas</th>
          <th>Cashback (US$)</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($clients)): ?>
          <tr><td colspan="8" class="text-center text-muted">Nenhum cliente encontrado</td></tr>
        <?php else: foreach ($clients as $c): ?>
          <tr>
            <td><?= htmlspecialchars($c['nome']) ?></td>
            <td><?= htmlspecialchars($c['email'] ?? '-') ?></td>
            <td><?= htmlspecialchars($c['telefone'] ?? '-') ?></td>
            <?php
              $br = trim((string)($c['suite_br'] ?? ''));
              $us = trim((string)($c['suite_us'] ?? ''));
              $red = trim((string)($c['suite_red'] ?? ''));
              $glob = trim((string)($c['suite_globe'] ?? ''));
              $legacy = trim((string)($c['suite'] ?? ''));
              $display = '-';
              if ($br !== '') { $display = 'BR-' . $br; }
              elseif ($us !== '') { $display = 'US-' . $us; }
              elseif ($red !== '') { $display = 'RED-' . $red; }
              elseif ($legacy !== '') { $display = strtoupper($legacy); }
              $all = [];
              if ($br !== '') { $all[] = 'BR-' . $br; }
              if ($us !== '') { $all[] = 'US-' . $us; }
              if ($red !== '') { $all[] = 'RED-' . $red; }
              if ($glob !== '') { $all[] = 'GLOB-' . $glob; }
              if ($legacy !== '') { $all[] = strtoupper($legacy); }
              $tooltip = $all ? implode(" | ", array_map('htmlspecialchars', $all)) : 'Sem suites';
            ?>
            <td>
              <span class="badge text-bg-secondary"><?= htmlspecialchars($display) ?></span>
              <span class="ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= $tooltip ?>" style="cursor:help;">?</span>
            </td>
            <td><?= htmlspecialchars($c['created_at']) ?></td>
            <td><?= (int)($c['total_vendas'] ?? 0) ?></td>
            <td>
              <?php $cb = isset($c['cashback_balance_usd']) ? (float)$c['cashback_balance_usd'] : 0.0; ?>
              <?= '$ ' . number_format($cb, 2, '.', ',') ?>
            </td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="/admin/clients/edit?id=<?= (int)$c['id'] ?>">Editar</a>
              <?php $isPlaceholder = (trim($c['nome'] ?? '') === '[REMOVIDO]') || (strtolower(trim($c['email'] ?? '')) === 'removed@system.local'); ?>
              <?php if ($role !== 'trainee' && !$isPlaceholder): ?>
                <form method="post" action="/admin/clients/delete" class="d-inline" onsubmit="return confirm('Excluir este cliente?');">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <?php if (($totalPages ?? 1) > 1): ?>
  <nav aria-label="Clientes pagination" class="mt-3">
    <ul class="pagination justify-content-center">
      <?php $qp = $q ?? ''; $sp = $sort ?? 'created_at_desc'; ?>
      <?php $p = (int)($page ?? 1); $tp = (int)($totalPages ?? 1); ?>
      <li class="page-item <?= $p <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" href="/admin/clients?page=<?= max(1,$p-1) ?>&q=<?= urlencode($qp) ?>&sort=<?= urlencode($sp) ?>" tabindex="-1">Anterior</a>
      </li>
      <?php 
        $start = max(1, $p-2);
        $end = min($tp, $p+2);
        for ($i=$start; $i<=$end; $i++):
      ?>
        <li class="page-item <?= $i === $p ? 'active' : '' ?>">
          <a class="page-link" href="/admin/clients?page=<?= $i ?>&q=<?= urlencode($qp) ?>&sort=<?= urlencode($sp) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item <?= $p >= $tp ? 'disabled' : '' ?>">
        <a class="page-link" href="/admin/clients?page=<?= min($tp,$p+1) ?>&q=<?= urlencode($qp) ?>&sort=<?= urlencode($sp) ?>">Próxima</a>
      </li>
    </ul>
  </nav>
  <?php endif; ?>
</div>
<script>
  (function(){
    function initTooltips(){
      var els = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
      if (window.bootstrap && els.length) {
        els.map(function (el) { return new bootstrap.Tooltip(el); });
      }
    }
    function initAutoSearch(){
      var form = document.querySelector('form[action="/admin/clients"]');
      if (!form) return;
      var list = document.getElementById('clients-list');
      var qInput = form.querySelector('input[name="q"]');
      var sortSel = form.querySelector('select[name="sort"]');
      function getUrl(resetPage){
        var fd = new FormData(form);
        var params = new URLSearchParams(fd);
        if (resetPage) params.set('page','1');
        return form.action + '?' + params.toString();
      }
      function bindPagination(){
        list = document.getElementById('clients-list');
        if (!list) return;
        var links = list.querySelectorAll('.pagination a');
        links.forEach(function(a){
          a.addEventListener('click', function(e){
            e.preventDefault();
            var keep = qInput ? { s: qInput.selectionStart||0, e: qInput.selectionEnd||0, st: qInput.scrollTop||0 } : null;
            updateList(this.href, keep);
          });
        });
      }
      function updateList(url, keep){
        if (!window.fetch) { window.location.href = url; return; }
        fetch(url, { headers: { 'X-Requested-With': 'fetch' }})
          .then(function(r){ return r.text(); })
          .then(function(html){
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var newList = doc.getElementById('clients-list');
            if (newList && list) {
              list.replaceWith(newList);
              list = newList;
              initTooltips();
              bindPagination();
              if (qInput) {
                var qi = document.querySelector('form[action="/admin/clients"] input[name="q"]');
                if (qi) {
                  qi.focus();
                  try { if (keep) qi.setSelectionRange(keep.s, keep.e); } catch(e) {}
                  if (keep) qi.scrollTop = keep.st;
                  qInput = qi;
                }
              }
            }
            if (history && history.replaceState) { history.replaceState({}, '', url); }
          })
          .catch(function(){ /* silently ignore */ });
      }
      form.addEventListener('submit', function(e){
        e.preventDefault();
        var keep = qInput ? { s: qInput.selectionStart||0, e: qInput.selectionEnd||0, st: qInput.scrollTop||0 } : null;
        updateList(getUrl(false), keep);
      });
      if (qInput) {
        var timer = null;
        qInput.addEventListener('input', function(){
          if (timer) clearTimeout(timer);
          var keep = { s: qInput.selectionStart||0, e: qInput.selectionEnd||0, st: qInput.scrollTop||0 };
          timer = setTimeout(function(){ updateList(getUrl(true), keep); }, 400);
        });
      }
      if (sortSel) {
        sortSel.addEventListener('change', function(){
          var keep = qInput ? { s: qInput.selectionStart||0, e: qInput.selectionEnd||0, st: qInput.scrollTop||0 } : null;
          updateList(getUrl(true), keep);
        });
      }
      bindPagination();
    }
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function(){
        if (!window.bootstrap) {
          var s = document.createElement('script');
          s.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';
          s.onload = function(){ initTooltips(); initAutoSearch(); };
          document.body.appendChild(s);
        } else {
          initTooltips(); initAutoSearch();
        }
      });
    } else {
      if (!window.bootstrap) {
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';
        s.onload = function(){ initTooltips(); initAutoSearch(); };
        document.body.appendChild(s);
      } else {
        initTooltips(); initAutoSearch();
      }
    }
  })();
</script>
