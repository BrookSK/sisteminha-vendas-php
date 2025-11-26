<?php /** @var array $items */ ?>
<?php $role = (string) (\Core\Auth::user()['role'] ?? 'seller'); ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Produtos do Simulador</h3>
    <?php if ($role === 'admin'): ?>
      <div class="d-flex flex-wrap gap-2">
        <a href="/admin/simulator-products/new" class="btn btn-sm btn-outline-primary">Novo produto</a>
        <a href="/admin/simulator-products/import" class="btn btn-sm btn-outline-secondary">Importar via planilha</a>
      </div>
    <?php endif; ?>
  </div>

  <?php if (empty($items)): ?>
    <div class="alert alert-info">Nenhum produto cadastrado ainda.</div>
  <?php else: ?>
    <div class="row mb-2">
      <div class="col-md-4 ms-auto">
        <label class="form-label mb-1">Filtrar produtos</label>
        <input type="text" id="sim-prod-filter" class="form-control form-control-sm" placeholder="Digite parte do nome ou da marca">
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-striped align-middle" id="tbl-sim-products">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Marca</th>
            <th>Peso (Kg)</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $p): ?>
            <tr>
              <td><?= htmlspecialchars($p['nome'] ?? '') ?></td>
              <td><?= htmlspecialchars($p['marca'] ?? '') ?></td>
              <td><?= htmlspecialchars(number_format((float)($p['peso_kg'] ?? 0), 2, ',', '.')) ?></td>
              <td class="text-end">
                <a href="/admin/simulator-products/edit?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                <?php $role = (string) (\Core\Auth::user()['role'] ?? 'seller'); ?>
                <?php if ($role !== 'trainee'): ?>
                  <form method="post" action="/admin/simulator-products/delete" class="d-inline" onsubmit="return confirm('Excluir este produto? Esta ação não pode ser desfeita.');">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Core\Auth::csrf()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <script>
      (function(){
        var table;
        if (window.jQuery && jQuery.fn.DataTable) {
          table = jQuery('#tbl-sim-products').DataTable({
            pageLength: 25,
            order: [[0,'asc']],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json' }
          });
        }

        var filterInput = document.getElementById('sim-prod-filter');
        if (filterInput) {
          filterInput.addEventListener('input', function(){
            var q = this.value || '';
            if (table) {
              table.search(q).draw();
            } else {
              // Fallback simples sem DataTables
              var rows = document.querySelectorAll('#tbl-sim-products tbody tr');
              var qLower = q.toLowerCase();
              rows.forEach(function(tr){
                var txt = (tr.textContent || '').toLowerCase();
                tr.style.display = (!qLower || txt.indexOf(qLower) !== -1) ? '' : 'none';
              });
            }
          });
        }
      })();
    </script>
  <?php endif; ?>
</div>
