<?php
use Core\Auth;
$csrf = Auth::csrf();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="m-0"><?= htmlspecialchars($title ?? 'Backups do Sistema') ?></h3>
</div>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">Configurações</div>
      <div class="card-body">
        <form method="post" action="/admin/backups/save-settings" class="vstack gap-3">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
          <div>
            <label class="form-label">Recorrência automática</label>
            <select name="backup_recurrence" class="form-select">
              <option value="mensal" <?= ($recurrence==='mensal'?'selected':'') ?>>Mensal</option>
              <option value="semanal" <?= ($recurrence==='semanal'?'selected':'') ?>>Semanal</option>
              <option value="diario" <?= ($recurrence==='diario'?'selected':'') ?>>Diário</option>
            </select>
          </div>
          <div>
            <label class="form-label">Horário do backup</label>
            <input type="time" name="backup_time" class="form-control" value="<?= htmlspecialchars($time ?? '02:00') ?>">
          </div>
          <div>
            <label class="form-label">Reter quantos backups completos</label>
            <input type="number" min="1" max="50" name="backup_retention" class="form-control" value="<?= (int)($retention ?? 3) ?>">
          </div>
          <div>
            <label class="form-label">Token do CRON (para agendamento externo)</label>
            <input type="text" name="backup_cron_token" class="form-control" placeholder="defina um token seguro" value="<?= htmlspecialchars($cron_token ?? '') ?>">
            <div class="form-text">Use este token na URL abaixo para rodar o backup automático via agendamento do servidor.</div>
          </div>
          <div class="text-end">
            <button type="submit" class="btn btn-primary">Salvar</button>
          </div>
        </form>
      </div>
    </div>
    <div class="alert alert-info mt-3">
      <div>Pasta de backups: <code><?= htmlspecialchars($backup_dir ?? '') ?></code></div>
      <?php 
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $cronUrl = $scheme . '://' . $host . '/backups/cron-run' . ($cron_token ? ('?token=' . urlencode($cron_token)) : '');
      ?>
      <div class="mt-2">URL do cron: <code><?= htmlspecialchars($cronUrl) ?></code></div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Executar backup manual</span>
        <form method="post" action="/admin/backups/run" onsubmit="return confirm('Gerar backup completo agora?');">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
          <button type="submit" class="btn btn-sm btn-success">Fazer backup agora</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Backups realizados</div>
      <div class="card-body">
        <?php if (empty($backups)): ?>
          <p class="text-muted m-0">Nenhum backup encontrado.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th>Arquivo</th>
                  <th class="text-end">Tamanho</th>
                  <th>Data</th>
                  <th class="text-end">Ações</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($backups as $b): ?>
                <tr>
                  <td><?= htmlspecialchars($b['name']) ?></td>
                  <td class="text-end"><?= number_format(($b['size'] ?? 0)/1048576, 2, ',', '.') ?> MB</td>
                  <td><?= date('d/m/Y H:i:s', (int)($b['mtime'] ?? time())) ?></td>
                  <td class="text-end">
                    <div class="d-inline-flex gap-2">
                      <form method="post" action="/admin/backups/restore" onsubmit="return confirm('Restaurar?');" class="d-flex gap-2">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="name" value="<?= htmlspecialchars($b['name']) ?>">
                        <select name="what" class="form-select form-select-sm">
                          <option value="db">Restaurar Banco</option>
                          <option value="files">Arquivos (ver aviso)</option>
                        </select>
                        <button class="btn btn-sm btn-primary" type="submit">Restaurar</button>
                      </form>
                      <form method="post" action="/admin/backups/delete" onsubmit="return confirm('Excluir backup?');">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="name" value="<?= htmlspecialchars($b['name']) ?>">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="alert alert-warning mt-3">
      Para restaurar <strong>arquivos</strong> do sistema, utilize o fluxo do Git (revert/rollback do Pull Request) no GitHub. O sistema apenas armazena o ZIP para referência.
    </div>
  </div>
</div>
