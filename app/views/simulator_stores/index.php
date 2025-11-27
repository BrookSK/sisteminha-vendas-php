<?php use Core\Auth; ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Lojas do Simulador</h3>
    <a href="/admin/simulator-products" class="btn btn-outline-secondary">Voltar para Produtos do Simulador</a>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <h5 class="card-title mb-3">Nova loja</h5>
      <form method="post" action="/admin/simulator-stores/create" class="row g-2">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
        <div class="col-md-6">
          <input type="text" name="name" class="form-control" placeholder="Nome da loja (ex: Costco, Walmart)" required>
        </div>
        <div class="col-md-2 d-grid">
          <button type="submit" class="btn btn-primary">Adicionar loja</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h5 class="card-title mb-3">Lojas cadastradas</h5>
      <?php if (empty($stores)): ?>
        <div class="alert alert-info mb-0">Nenhuma loja cadastrada ainda.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr>
                <th style="width:80px;">ID</th>
                <th>Nome</th>
                <th class="text-end">Ações</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($stores as $s): ?>
              <tr>
                <td><?= (int)($s['id'] ?? 0) ?></td>
                <td><?= htmlspecialchars($s['name'] ?? '') ?></td>
                <td class="text-end">
                  <form method="post" action="/admin/simulator-stores/delete" class="d-inline" onsubmit="return confirm('Excluir esta loja? Esta ação não pode ser desfeita.');">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
                    <input type="hidden" name="id" value="<?= (int)($s['id'] ?? 0) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
