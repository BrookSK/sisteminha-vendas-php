<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Logs de Atividade</h5>
</div>
<form class="row g-2 mb-3" method="get" action="/admin/logs">
  <div class="col-sm-2">
    <input type="text" class="form-control" name="entidade" placeholder="Entidade" value="<?= htmlspecialchars($filters['entidade'] ?? '') ?>">
  </div>
  <div class="col-sm-2">
    <input type="text" class="form-control" name="acao" placeholder="Ação" value="<?= htmlspecialchars($filters['acao'] ?? '') ?>">
  </div>
  <div class="col-sm-3">
    <select class="form-select" name="usuario_id">
      <option value="0">Todos usuários</option>
      <?php foreach ($users as $u): ?>
        <option value="<?= (int)$u['id'] ?>" <?= !empty($filters['usuario_id']) && (int)$filters['usuario_id'] === (int)$u['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($u['name'] ?: $u['email']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-sm-2">
    <input type="date" class="form-control" name="de" value="<?= htmlspecialchars($filters['de'] ?? '') ?>">
  </div>
  <div class="col-sm-2">
    <input type="date" class="form-control" name="ate" value="<?= htmlspecialchars($filters['ate'] ?? '') ?>">
  </div>
  <div class="col-sm-1 d-grid">
    <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
  </div>
</form>
<div class="table-responsive">
  <table class="table table-striped align-middle">
    <thead>
      <tr>
        <th>Data</th>
        <th>Usuário</th>
        <th>Entidade</th>
        <th>Ação</th>
        <th>Ref</th>
        <th>Detalhes</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($items)): ?>
        <tr><td colspan="6" class="text-center text-muted">Sem logs</td></tr>
      <?php else: foreach ($items as $i): ?>
        <tr>
          <td><?= htmlspecialchars($i['created_at']) ?></td>
          <td><?= htmlspecialchars($i['usuario_email'] ?? '-') ?></td>
          <td><span class="badge text-bg-secondary"><?= htmlspecialchars($i['entidade']) ?></span></td>
          <td><?= htmlspecialchars($i['acao']) ?></td>
          <td><?= htmlspecialchars((string)($i['ref_id'] ?? '')) ?></td>
          <td><code style="white-space: pre-wrap;"><?= htmlspecialchars($i['detalhes'] ?? '') ?></code></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
