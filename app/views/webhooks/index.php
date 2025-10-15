<?php /** @var array $logs */ ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Logs de Webhooks</h3>
  </div>

  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>ID</th>
          <th>Tipo</th>
          <th>Status</th>
          <th>Mensagem</th>
          <th>Recebido em</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
          <tr><td colspan="6" class="text-center text-muted">Sem logs</td></tr>
        <?php else: foreach ($logs as $l): ?>
          <tr>
            <td><?= (int)$l['id'] ?></td>
            <td><span class="badge bg-secondary"><?= htmlspecialchars($l['tipo']) ?></span></td>
            <td>
              <?php
                $st = (string)($l['status'] ?? '');
                $cls = 'bg-secondary';
                if ($st === 'sucesso') $cls = 'bg-success';
                elseif ($st === 'erro') $cls = 'bg-danger';
                elseif ($st === 'atualizacao') $cls = 'bg-info text-dark';
              ?>
              <span class="badge <?= $cls ?>"><?= htmlspecialchars($st) ?></span>
            </td>
            <td><?= htmlspecialchars($l['mensagem'] ?? '') ?></td>
            <td><?= htmlspecialchars($l['created_at'] ?? '') ?></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#payload-<?= (int)$l['id'] ?>">Ver payload</button>
            </td>
          </tr>
          <tr class="collapse" id="payload-<?= (int)$l['id'] ?>">
            <td colspan="6">
              <pre class="mb-0 small bg-light p-2 border rounded"><code><?= htmlspecialchars($l['payload_json'] ?? '') ?></code></pre>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
