<?php /** @var array $items */ /** @var string $q */ /** @var int $page */ /** @var int $limit */ /** @var int $total */ /** @var string $_csrf */ ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Usuários</h3>
    <a href="/admin/users/new" class="btn btn-primary">Novo Usuário</a>
  </div>

  <form class="row g-2 mb-3" method="get" action="/admin/users">
    <div class="col-auto">
      <input type="text" class="form-control" name="q" placeholder="Buscar por nome ou e-mail" value="<?= htmlspecialchars($q) ?>">
    </div>
    <div class="col-auto">
      <button class="btn btn-outline-secondary" type="submit">Buscar</button>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nome</th>
          <th>E-mail</th>
          <th>Perfil</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $u): ?>
        <tr>
          <td><?= (int)$u['id'] ?></td>
          <td><?= htmlspecialchars($u['name']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td>
            <?php $role = ($u['role'] ?? 'seller'); ?>
            <?php if ($role === 'admin'): ?>
              <span class="badge bg-danger">admin</span>
            <?php elseif ($role === 'manager'): ?>
              <span class="badge bg-warning text-dark">manager</span>
            <?php elseif ($role === 'organic'): ?>
              <span class="badge bg-info text-dark">organic</span>
            <?php elseif ($role === 'trainee'): ?>
              <span class="badge bg-secondary">seller_trainee</span>
            <?php else: ?>
              <span class="badge bg-secondary">seller</span>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <a href="/admin/users/edit?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
            <?php if (((Core\Auth::user()['role'] ?? 'seller') === 'admin') && (($u['role'] ?? 'seller') !== 'admin')): ?>
            <form method="post" action="/admin/users/delete" class="d-inline" onsubmit="return confirm('Excluir este usuário?');">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_csrf) ?>">
              <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php 
    $pages = max(1, (int)ceil($total / $limit));
    if ($pages > 1):
  ?>
  <nav>
    <ul class="pagination">
      <?php for ($p=1; $p <= $pages; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
          <a class="page-link" href="/admin/users?page=<?= $p ?>&q=<?= urlencode($q) ?>"><?= $p ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
</div>
