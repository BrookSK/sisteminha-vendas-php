<?php use Core\Auth; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Atendimentos</h5>
</div>
<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" method="post" action="/admin/attendances/save">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
      <div class="col-md-3">
        <label class="form-label">Data</label>
        <input type="date" class="form-control" name="data" value="<?= htmlspecialchars($today) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Nº Atendimentos do dia</label>
        <input type="number" min="0" class="form-control" name="total_atendimentos" value="0" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Concluídos</label>
        <input type="number" min="0" class="form-control" name="total_concluidos" value="0" required>
      </div>
      <div class="col-md-3 d-grid align-end">
        <label class="form-label">&nbsp;</label>
        <button class="btn btn-primary" type="submit">Salvar</button>
      </div>
    </form>
  </div>
</div>
<div class="table-responsive">
  <table class="table table-striped align-middle">
    <thead>
      <tr>
        <th>Data</th>
        <th>Total</th>
        <th>Concluídos</th>
        <th>Usuário</th>
        <th>Criado em</th>
        <th>Editado em</th>
        <th class="text-end">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($items)): ?>
        <tr><td colspan="7" class="text-center text-muted">Sem registros</td></tr>
      <?php else: foreach ($items as $i): ?>
        <?php $rowId = 'edit_'. (int)($i['usuario_id'] ?? 0) . '_' . preg_replace('/[^0-9]/','', $i['data']); ?>
        <tr>
          <td><?= htmlspecialchars($i['data']) ?></td>
          <td><?= (int)($i['total_atendimentos'] ?? 0) ?></td>
          <td><?= (int)($i['total_concluidos'] ?? 0) ?></td>
          <td><?= htmlspecialchars($i['usuario_email'] ?? '-') ?></td>
          <td><?= htmlspecialchars($i['created_at']) ?></td>
          <td><?= htmlspecialchars($i['updated_at'] ?? '-') ?></td>
          <td class="text-end">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="(function(){var el=document.getElementById('<?= $rowId ?>'); if(el){ el.style.display = (el.style.display==='none'?'table-row':'none'); }})();">Editar</button>
          </td>
        </tr>
        <tr id="<?= $rowId ?>" style="display:none; background:#fafafa;">
          <td colspan="7">
            <form class="row g-2 align-items-end" method="post" action="/admin/attendances/save">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
              <input type="hidden" name="usuario_id" value="<?= (int)($i['usuario_id'] ?? 0) ?>">
              <div class="col-sm-3">
                <label class="form-label">Data</label>
                <input type="date" class="form-control" name="data" value="<?= htmlspecialchars($i['data']) ?>" required>
              </div>
              <div class="col-sm-3">
                <label class="form-label">Total</label>
                <input type="number" class="form-control" min="0" name="total_atendimentos" value="<?= (int)($i['total_atendimentos'] ?? 0) ?>" required>
              </div>
              <div class="col-sm-3">
                <label class="form-label">Concluídos</label>
                <input type="number" class="form-control" min="0" name="total_concluidos" value="<?= (int)($i['total_concluidos'] ?? 0) ?>" required>
              </div>
              <div class="col-sm-3 d-grid">
                <button class="btn btn-success" type="submit">Salvar alterações</button>
              </div>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
