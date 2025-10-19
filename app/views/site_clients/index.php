<?php use Core\Auth; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Clientes (Sites)</h5>
</div>
<div class="row g-3 mb-4">
  <div class="col-lg-5">
    <div class="card"><div class="card-body">
      <h6 class="mb-3">Novo Cliente</h6>
      <form method="post" action="/admin/site-clients/create" class="row g-2">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
        <div class="col-md-6"><label class="form-label">Nome</label><input type="text" class="form-control" name="name" required></div>
        <div class="col-md-6"><label class="form-label">Telefone</label><input type="text" class="form-control" name="phone"></div>
        <div class="col-12"><label class="form-label">E-mail</label><input type="email" class="form-control" name="email"></div>
        <div class="col-12 d-grid"><button class="btn btn-primary" type="submit">Criar</button></div>
      </form>
    </div></div>
  </div>
  <div class="col-lg-7">
    <div class="card"><div class="card-body">
      <h6 class="mb-3">Lista</h6>
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead>
            <tr>
              <th>Nome</th>
              <th>E-mail</th>
              <th>Telefone</th>
              <th class="text-end">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($clients)): ?>
              <tr><td colspan="4" class="text-center text-muted">Nenhum cliente</td></tr>
            <?php else: foreach ($clients as $c): ?>
              <tr>
                <td><?= htmlspecialchars($c['name'] ?? '') ?></td>
                <td><?= htmlspecialchars($c['email'] ?? '-') ?></td>
                <td><?= htmlspecialchars($c['phone'] ?? '-') ?></td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#editRow<?= (int)$c['id'] ?>">Editar</button>
                  <form method="post" action="/admin/site-clients/delete" class="d-inline" onsubmit="return confirm('Excluir este cliente?');">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
                  </form>
                </td>
              </tr>
              <tr class="collapse" id="editRow<?= (int)$c['id'] ?>">
                <td colspan="4">
                  <form method="post" action="/admin/site-clients/update" class="row g-2">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                    <div class="col-md-4"><label class="form-label">Nome</label><input type="text" class="form-control" name="name" value="<?= htmlspecialchars($c['name'] ?? '') ?>" required></div>
                    <div class="col-md-4"><label class="form-label">E-mail</label><input type="email" class="form-control" name="email" value="<?= htmlspecialchars($c['email'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="form-label">Telefone</label><input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($c['phone'] ?? '') ?>"></div>
                    <div class="col-12 d-grid"><button class="btn btn-success" type="submit">Salvar</button></div>
                  </form>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div></div>
  </div>
</div>
