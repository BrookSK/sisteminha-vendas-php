<?php use Core\Auth; ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Meus Orçamentos do Simulador</h3>
    <a href="/admin/sales-simulator" class="btn btn-outline-secondary">Voltar para o Simulador</a>
  </div>

  <?php if (empty($items)): ?>
    <div class="alert alert-info">Você ainda não salvou nenhum orçamento no simulador.</div>
  <?php else: ?>
    <div class="row mb-2">
      <div class="col-md-4 ms-auto">
        <label class="form-label mb-1">Filtrar orçamentos</label>
        <input type="text" id="sim-budgets-filter" class="form-control form-control-sm" placeholder="Digite parte do nome ou data">
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-striped align-middle" id="tbl-sim-budgets">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Criado em</th>
            <th>Atualizado em</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $b): ?>
          <tr>
            <td><?= htmlspecialchars($b['name'] ?? '') ?></td>
            <td><?= htmlspecialchars($b['created_at'] ?? '') ?></td>
            <td><?= htmlspecialchars($b['updated_at'] ?? '') ?></td>
            <td class="text-end">
              <a href="/admin/sales-simulator?budget_id=<?= (int)$b['id'] ?>" class="btn btn-sm btn-primary">Abrir</a>
              <form method="post" action="/admin/sales-simulator/budgets/duplicate" class="d-inline">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Duplicar</button>
              </form>
              <form method="post" action="/admin/sales-simulator/budgets/delete" class="d-inline" onsubmit="return confirm('Excluir este orçamento? Esta ação não pode ser desfeita.');">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <script>
      (function(){
        var filterInput = document.getElementById('sim-budgets-filter');
        if (!filterInput) return;
        filterInput.addEventListener('input', function(){
          var q = (this.value || '').toLowerCase();
          var rows = document.querySelectorAll('#tbl-sim-budgets tbody tr');
          rows.forEach(function(tr){
            var txt = (tr.textContent || '').toLowerCase();
            tr.style.display = (!q || txt.indexOf(q) !== -1) ? '' : 'none';
          });
        });
      })();
    </script>
  <?php endif; ?>
</div>
