<?php /** @var array $backlog */ /** @var array $today */ /** @var array $late */ /** @var array $week */ /** @var array $time_off */ ?>
<div class="container py-3">
  <h3 class="mb-3">Dashboard de Demandas</h3>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>📋 Backlog (pendentes sem responsável)</span>
          <a href="/admin/demands?tab=pendentes" class="btn btn-sm btn-outline-secondary">Ver todas</a>
        </div>
        <div class="card-body p-0">
          <div class="list-group list-group-flush">
            <?php if (empty($backlog)): ?>
              <div class="list-group-item text-muted">Vazio</div>
            <?php else: foreach ($backlog as $d): ?>
              <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-semibold">[<?= htmlspecialchars(strtoupper($d['priority'])) ?>] <?= htmlspecialchars($d['title']) ?></div>
                  <div class="small text-muted">Prazo: <?= htmlspecialchars($d['due_date'] ?? '-') ?> · Criado em: <?= htmlspecialchars(substr($d['created_at'] ?? '',0,10)) ?> <?= isset($d['project_name']) && $d['project_name'] ? '· Projeto: '.htmlspecialchars($d['project_name']) : '' ?></div>
                </div>
                <form method="post" action="/admin/demands/assign" class="d-flex gap-2">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Core\Auth::csrf()) ?>">
                  <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                  <select name="assignee_id" class="form-select form-select-sm" style="min-width:180px">
                    <option value="">Selecione responsável...</option>
                    <?php if (!empty($users)) foreach ($users as $u): ?>
                      <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['name'] ?? ('#'.$u['id'])) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-sm btn-primary">Atribuir</button>
                </form>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>📢 Avisos gerais</span>
          <a href="/admin/notifications" class="btn btn-sm btn-outline-secondary">Abrir notificações</a>
        </div>
        <div class="card-body p-0">
          <div class="list-group list-group-flush">
            <?php if (empty($notices)): ?>
              <div class="list-group-item text-muted">Sem avisos</div>
            <?php else: foreach ($notices as $n): ?>
              <div class="list-group-item">
                <div class="fw-semibold"><?= htmlspecialchars($n['title'] ?? '') ?></div>
                <div class="small text-muted" style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars($n['message'] ?? '')) ?></div>
                <div class="small text-muted">Criado em: <?= htmlspecialchars(substr((string)($n['created_at'] ?? ''),0,16)) ?></div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>🕓 Time Off (hoje)</span>
          <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalTimeOff">Registrar</button>
        </div>
        <div class="card-body p-0">
          <div class="list-group list-group-flush">
            <?php if (empty($time_off)): ?>
              <div class="list-group-item text-muted">Ninguém off hoje</div>
            <?php else: foreach ($time_off as $t): ?>
              <div class="list-group-item d-flex justify-content-between">
                <div><?= htmlspecialchars($t['user_name'] ?? 'Usuário') ?></div>
                <div class="text-muted">Motivo: <?= htmlspecialchars($t['reason'] ?? '-') ?></div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header">📅 Demandas do dia</div>
        <div class="card-body p-0">
          <div class="list-group list-group-flush">
            <?php if (empty($today)): ?>
              <div class="list-group-item text-muted">Nenhuma demanda para hoje</div>
            <?php else: foreach ($today as $d): ?>
              <div class="list-group-item">
                <div class="fw-semibold"><?= htmlspecialchars($d['title']) ?></div>
                <div class="small text-muted">Resp: <?= htmlspecialchars($d['assignee_name'] ?? '-') ?> · Status: <?= htmlspecialchars($d['status']) ?> · Prioridade: <?= htmlspecialchars($d['priority']) ?></div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header">⏰ Demandas atrasadas</div>
        <div class="card-body p-0">
          <div class="list-group list-group-flush">
            <?php if (empty($late)): ?>
              <div class="list-group-item text-muted">Sem atrasos</div>
            <?php else: foreach ($late as $d): ?>
              <div class="list-group-item">
                <div class="fw-semibold"><?= htmlspecialchars($d['title']) ?></div>
                <div class="small text-muted">Resp: <?= htmlspecialchars($d['assignee_name'] ?? '-') ?> · Prazo: <?= htmlspecialchars($d['due_date'] ?? '-') ?> · Status: <?= htmlspecialchars($d['status']) ?></div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header">🗓️ Programação semanal</div>
        <div class="card-body">
          <?php
            $days = [];
            for ($i=0;$i<7;$i++) { $days[] = date('Y-m-d', strtotime('sunday last week +'.$i.' day')); }
          ?>
          <div class="row row-cols-1 row-cols-md-7 g-2">
            <?php foreach ($days as $day): ?>
              <div class="col">
                <div class="border rounded p-2" style="min-height:160px;">
                  <div class="fw-semibold mb-2"><?= htmlspecialchars(date('D d/m', strtotime($day))) ?></div>
                  <?php $list = $week[$day] ?? []; if (!$list): ?>
                    <div class="text-muted small">Sem demandas</div>
                  <?php else: foreach ($list as $d): ?>
                    <div class="p-2 mb-1 rounded border js-demand-card" style="background:#f8f9fa; cursor:pointer;" title="Clique para detalhes"
                         data-title="<?= htmlspecialchars($d['title']) ?>"
                         data-status="<?= htmlspecialchars($d['status']) ?>"
                         data-assignee="<?= htmlspecialchars($d['assignee_name'] ?? '-') ?>"
                         data-due="<?= htmlspecialchars($d['due_date'] ?? '-') ?>"
                         data-details="<?= htmlspecialchars($d['details'] ?? '-') ?>">
                      <div class="small fw-semibold text-truncate">[<?= htmlspecialchars($d['status']) ?>] <?= htmlspecialchars($d['title']) ?></div>
                      <div class="small text-muted text-truncate">Resp: <?= htmlspecialchars($d['assignee_name'] ?? '-') ?></div>
                    </div>
                  <?php endforeach; endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Detalhes da Demanda (semana) -->
  <div class="modal fade" id="modalDemandDetails" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="md-title">Detalhes da demanda</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div><strong>Status:</strong> <span id="md-status"></span></div>
          <div><strong>Responsável:</strong> <span id="md-assignee"></span></div>
          <div><strong>Prazo:</strong> <span id="md-due"></span></div>
          <hr>
          <div id="md-details" class="small"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Registrar Time Off -->
  <div class="modal fade" id="modalTimeOff" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post" action="/admin/timeoff/create">
          <div class="modal-header">
            <h5 class="modal-title">Registrar Time Off</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Core\Auth::csrf()) ?>">
            <div class="mb-2">
              <label class="form-label">Data</label>
              <input type="date" class="form-control" name="date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Motivo</label>
              <input type="text" class="form-control" name="reason" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('click', function(ev){
  var el = ev.target.closest('.js-demand-card');
  if (!el) return;
  document.getElementById('md-title').textContent = el.getAttribute('data-title') || '';
  document.getElementById('md-status').textContent = el.getAttribute('data-status') || '';
  document.getElementById('md-assignee').textContent = el.getAttribute('data-assignee') || '';
  document.getElementById('md-due').textContent = el.getAttribute('data-due') || '';
  document.getElementById('md-details').textContent = el.getAttribute('data-details') || '';
  var m = new bootstrap.Modal(document.getElementById('modalDemandDetails'));
  m.show();
});
</script>
</div>
