<?php /** @var array $items */ ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Produtos do Simulador</h3>
    <a href="/admin/simulator-products/new" class="btn btn-primary">Novo Produto</a>
  </div>

  <?php if (empty($items)): ?>
    <div class="alert alert-info">Nenhum produto cadastrado ainda.</div>
  <?php else: ?>
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
        if (window.jQuery && jQuery.fn.DataTable) {
          jQuery('#tbl-sim-products').DataTable({
            pageLength: 25,
            order: [[0,'asc']],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json' }
          });
        }
      })();
    </script>
  <?php endif; ?>
</div>
