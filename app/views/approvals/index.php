<?php /** @var array $items */ /** @var string $_csrf */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Aprovações Pendentes</h5>
  <a href="/admin/approvals" class="btn btn-sm btn-outline-secondary">Atualizar</a>
</div>
<?php if (empty($items)): ?>
  <div class="alert alert-info">Nenhuma aprovação pendente no momento.</div>
<?php else: ?>
<div class="table-responsive">
  <table class="table table-striped align-middle">
    <thead>
      <tr>
        <th>Tipo</th>
        <th>Ação</th>
        <th>Criado por</th>
        <th>Criado em</th>
        <th class="text-end">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= htmlspecialchars($it['entity_type']) ?></td>
          <td><?= htmlspecialchars($it['action']) ?></td>
          <td>#<?= (int)($it['created_by'] ?? 0) ?></td>
          <td><?= htmlspecialchars($it['created_at'] ?? '') ?></td>
          <td class="text-end">
            <form method="post" action="/admin/approvals/approve" class="d-inline">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_csrf) ?>">
              <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
              <button class="btn btn-sm btn-success" type="submit">Aprovar</button>
            </form>
            <form method="post" action="/admin/approvals/reject" class="d-inline" onsubmit="return confirm('Rejeitar esta solicitação?');">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_csrf) ?>">
              <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
              <button class="btn btn-sm btn-outline-danger" type="submit">Rejeitar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
