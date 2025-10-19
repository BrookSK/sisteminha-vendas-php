<?php
use Core\Auth;
$csrf = Auth::csrf();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Documentações e Procedimentos</h3>
  <a class="btn btn-primary" href="/admin/documentations/new">Nova Documentação</a>
</div>
<div class="card mb-3">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <?php $statuses = [''=>'Todos','nao_iniciada'=>'Não iniciada','em_andamento'=>'Em andamento','em_revisao'=>'Em revisão','concluida'=>'Concluída','arquivada'=>'Arquivada']; $sel = $filters['status'] ?? ''; foreach ($statuses as $k=>$v): ?>
            <option value="<?= $k ?>" <?= ($sel===$k)?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Área Técnica</label>
        <select name="area_id" class="form-select">
          <option value="">Todas</option>
          <?php $selA = (string)($filters['area_id'] ?? ''); foreach (($areas ?? []) as $a): $val=(string)$a['id']; ?>
            <option value="<?= (int)$a['id'] ?>" <?= ($selA!=='' && $selA===$val)?'selected':'' ?>><?= htmlspecialchars($a['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 position-relative">
        <label class="form-label">Projeto</label>
        <input type="hidden" name="project_id" id="projectIdField" value="<?= htmlspecialchars((string)($filters['project_id'] ?? '')) ?>">
        <input type="text" id="projectQuery" class="form-control" placeholder="Buscar por nome ou ID..." autocomplete="off">
        <div id="projectSuggestions" class="list-group position-absolute w-100" style="z-index: 1000; max-height: 240px; overflow:auto; display:none;"></div>
      </div>
      <div class="col-md-1 d-grid">
        <button class="btn btn-primary" type="submit">Filtrar</button>
      </div>
      <div class="col-12">
        <a class="small" href="/admin/documentations">Limpar filtros</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped align-middle" id="tblDocs">
        <thead>
          <tr>
            <th>Título</th>
            <th>Status</th>
            <th>Área</th>
            <th>Visibilidade</th>
            <th>Publicado?</th>
            <th>Criado em</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach (($docs ?? []) as $d): ?>
          <tr>
            <td><?= htmlspecialchars($d['title']) ?></td>
            <td><span class="badge text-bg-secondary"><?= htmlspecialchars($d['status']) ?></span></td>
            <td><?= htmlspecialchars($d['area_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($d['internal_visibility'] ?? 'all') ?></td>
            <td><?= ((int)($d['published'] ?? 0) === 1) ? 'Sim' : 'Não' ?></td>
            <td><?= htmlspecialchars($d['created_at'] ?? '') ?></td>
            <td>
              <a class="btn btn-sm btn-outline-primary" href="/admin/documentations/view?id=<?= (int)$d['id'] ?>">Abrir</a>
              <a class="btn btn-sm btn-outline-secondary" href="/admin/documentations/edit?id=<?= (int)$d['id'] ?>">Editar</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php
$page = (int)($page ?? 1);
$per = (int)($per_page ?? 25);
$total = (int)($total ?? 0);
$pages = max(1, (int)ceil($total / max(1,$per)));
$queryBase = $_GET; unset($queryBase['page']); unset($queryBase['per_page']);
function qbuild_docs($arr){ return htmlspecialchars(http_build_query($arr)); }
?>
<div class="d-flex justify-content-between align-items-center mt-3 card-body">
  <div class="small text-muted">Página <?= $page ?> de <?= $pages ?> • Total: <?= $total ?></div>
  <div class="btn-group">
    <?php $prev = max(1, $page-1); $next = min($pages, $page+1); ?>
    <?php $q = $queryBase; $q['page']=$prev; $q['per_page']=$per; ?>
    <a class="btn btn-outline-secondary btn-sm <?= $page<=1?'disabled':'' ?>" href="?<?= qbuild_docs($q) ?>">Anterior</a>
    <?php $q = $queryBase; $q['page']=$next; $q['per_page']=$per; ?>
    <a class="btn btn-outline-secondary btn-sm <?= $page>=$pages?'disabled':'' ?>" href="?<?= qbuild_docs($q) ?>">Próxima</a>
  </div>
</div>

<script>
  $(function(){
    // DataTables only for sorting; disable paging/search to avoid conflict with server pagination
    $('#tblDocs').DataTable({
      paging: false,
      searching: false,
      info: false,
      order: [[5, 'desc']],
      language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json' }
    });

    // Project autocomplete
    const $q = $('#projectQuery');
    const $id = $('#projectIdField');
    const $s = $('#projectSuggestions');
    const initId = $id.val();
    if (initId) { $q.val('ID ' + initId); }
    let t = null;
    $q.on('input', function(){
      const val = $(this).val().trim();
      if (t) clearTimeout(t);
      if (val.length < 1) { $s.hide().empty(); return; }
      t = setTimeout(function(){
        $.getJSON('/admin/projects/options', { q: val, limit: 10 }, function(data){
          $s.empty();
          (data.items || []).forEach(function(it){
            const $item = $('<button type="button" class="list-group-item list-group-item-action"></button>');
            $item.text('#'+it.id+' • '+it.name);
            $item.on('click', function(){
              $id.val(it.id);
              $q.val('#'+it.id+' • '+it.name);
              $s.hide().empty();
            });
            $s.append($item);
          });
          $s.toggle( (data.items||[]).length > 0 );
        });
      }, 250);
    });
    $(document).on('click', function(e){ if (!$(e.target).closest('#projectQuery, #projectSuggestions').length) { $s.hide(); } });
  });
</script>
