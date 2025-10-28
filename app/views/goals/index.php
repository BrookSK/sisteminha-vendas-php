<?php /** @var array $goals */ ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Metas e Previsões</h3>
    <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#goalForm">Nova Meta</button>
  </div>

  <div class="collapse mb-3" id="goalForm">
    <div class="card">
      <div class="card-body">
        <form method="post" action="/admin/goals/create">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_csrf ?? '') ?>">
          <div class="row g-2">
            <div class="col-md-4">
              <label class="form-label">Título</label>
              <input type="text" name="titulo" class="form-control" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">Tipo</label>
              <select name="tipo" class="form-select">
                <option value="global">Global</option>
                <option value="individual">Individual</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Moeda</label>
              <select name="moeda" class="form-select">
                <option value="USD">USD</option>
                <option value="BRL">BRL</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Valor Meta</label>
              <input type="number" step="0.01" name="valor_meta" class="form-control" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">Início</label>
              <input type="date" name="data_inicio" class="form-control" value="<?= date('Y-m-01') ?>" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">Fim</label>
              <input type="date" name="data_fim" class="form-control" value="<?= date('Y-m-t') ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label">Descrição</label>
              <textarea name="descricao" class="form-control" rows="2"></textarea>
            </div>
          </div>
          <div class="mt-3">
            <button class="btn btn-primary" type="submit">Salvar Meta</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Título</th>
          <th>Tipo</th>
          <th>Moeda</th>
          <th class="text-end">Valor Meta</th>
          <th>Período</th>
          <th>Criado por</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($goals)): ?>
          <tr><td colspan="7" class="text-center text-muted">Sem metas</td></tr>
        <?php else: foreach ($goals as $g): ?>
          <tr>
            <td><?= htmlspecialchars($g['titulo']) ?></td>
            <td><?= htmlspecialchars($g['tipo']) ?></td>
            <td><?= htmlspecialchars($g['moeda']) ?></td>
            <td class="text-end"><?= number_format((float)$g['valor_meta'], 2, ',', '.') ?></td>
            <td><?= htmlspecialchars($g['data_inicio']) ?> a <?= htmlspecialchars($g['data_fim']) ?></td>
            <td><?= htmlspecialchars($g['criador'] ?? '-') ?></td>
            <td>
              <div class="d-flex flex-wrap gap-2 align-items-center">
                <form class="d-inline" method="post" action="/admin/goals/assign">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_csrf ?? '') ?>">
                  <input type="hidden" name="goal_id" value="<?= (int)$g['id'] ?>">
                  <div class="input-group input-group-sm" style="max-width:520px;">
                    <select name="seller_id" class="form-select">
                      <option value="">Atribuir vendedor…</option>
                      <?php foreach (($users ?? []) as $u): ?>
                        <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['role']) ?>)</option>
                      <?php endforeach; ?>
                    </select>
                    <input type="number" step="0.01" name="valor_meta" class="form-control" placeholder="Valor meta p/ vendedor">
                    <button class="btn btn-outline-primary" type="submit">Atribuir</button>
                  </div>
                </form>
                <form class="d-inline" method="post" action="/admin/goals/delete" onsubmit="return confirm('Excluir esta meta?');">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_csrf ?? '') ?>">
                  <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger">Excluir</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="mt-4">
    <div class="row g-3">
      <div class="col-md-3">
        <div class="card"><div class="card-body">
          <div class="text-muted small">Meta total do mês</div>
          <div class="fs-5">$ <?= number_format((float)($dash_meta ?? 0), 2, ',', '.') ?></div>
        </div></div>
      </div>
      <div class="col-md-3">
        <div class="card"><div class="card-body">
          <div class="text-muted small">Valor atual (realizado)</div>
          <div class="fs-5">$ <?= number_format((float)($dash_real ?? 0), 2, ',', '.') ?></div>
        </div></div>
      </div>
      <div class="col-md-3">
        <div class="card"><div class="card-body">
          <div class="text-muted small">% atingido</div>
          <?php $__dm = (float)($dash_meta ?? 0); $__dr=(float)($dash_real ?? 0); $__pct = $__dm>0 ? ($__dr/$__dm*100.0) : 0; ?>
          <div class="fs-5"><?= number_format($__pct, 1, ',', '.') ?>%</div>
        </div></div>
      </div>
      <div class="col-md-3">
        <div class="card"><div class="card-body">
          <div class="text-muted small">Previsão final</div>
          <div class="fs-5">$ <?= number_format((float)($dash_prev ?? 0), 2, ',', '.') ?></div>
        </div></div>
      </div>
    </div>
  </div>
</div>
