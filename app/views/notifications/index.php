<?php /** @var array $items */ ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Notificações</h3>
    <div>
      <?php if (in_array((\Core\Auth::user()['role'] ?? 'seller'), ['admin','manager'], true)): ?>
        <a class="btn btn-sm btn-primary" href="/admin/notifications/new">Nova Notificação</a>
      <?php endif; ?>
    </div>
  </div>

  <?php $filters = $filters ?? []; $fType = (string)($filters['type'] ?? ''); $fStatus = (string)($filters['status'] ?? ''); $fFrom = (string)($filters['from'] ?? ''); $fTo = (string)($filters['to'] ?? ''); $fCb = (string)($filters['created_by'] ?? ''); $fArch = (string)($filters['arch'] ?? '0'); ?>
  <form class="row g-2 mb-3" method="get" action="/admin/notifications">
    <div class="col-auto">
      <label class="form-label">Tipo</label>
      <select class="form-select" name="type">
        <option value="">Todos</option>
        <?php foreach (["Informação","Alerta","Atualização do Sistema","Meta / Desempenho","Outro"] as $opt): ?>
          <option value="<?= htmlspecialchars($opt) ?>" <?= $fType===$opt?'selected':'' ?>><?= htmlspecialchars($opt) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <label class="form-label">Status</label>
      <select class="form-select" name="status">
        <option value="">Todos</option>
        <?php foreach (["ativa","arquivada","expirada"] as $opt): ?>
          <option value="<?= htmlspecialchars($opt) ?>" <?= $fStatus===$opt?'selected':'' ?>><?= htmlspecialchars(ucfirst($opt)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <label class="form-label">De</label>
      <input type="date" class="form-control" name="from" value="<?= htmlspecialchars($fFrom) ?>">
    </div>
    <div class="col-auto">
      <label class="form-label">Até</label>
      <input type="date" class="form-control" name="to" value="<?= htmlspecialchars($fTo) ?>">
    </div>
    <div class="col-auto">
      <label class="form-label">Criado por (ID)</label>
      <input type="number" class="form-control" name="created_by" value="<?= htmlspecialchars($fCb) ?>" placeholder="Opcional">
    </div>
    <div class="col-auto">
      <label class="form-label">Arquivadas</label>
      <select class="form-select" name="arch">
        <option value="0" <?= $fArch==='0'?'selected':'' ?>>Ativas</option>
        <option value="1" <?= $fArch==='1'?'selected':'' ?>>Arquivadas</option>
        <option value="" <?= $fArch===''?'selected':'' ?>>Todas</option>
      </select>
    </div>
    <div class="col-auto align-self-end">
      <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>Título</th>
          <th>Tipo</th>
          <th>Status</th>
          <th>Criado em</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr><td colspan="5" class="text-center text-muted">Sem notificações</td></tr>
        <?php else: foreach ($items as $n): ?>
          <tr>
            <td>
              <div class="fw-semibold"><?= htmlspecialchars((string)($n['title'] ?? '')) ?></div>
              <?php 
                $rawMsg = (string)($n['message'] ?? '');
                $apprId = null; 
                if (preg_match('/\[approval-id:(\d+)\]/', $rawMsg, $mm)) { $apprId = (int)($mm[1] ?? 0); }
                $cleanMsg = preg_replace('/\[approval-id:\d+\]/', '', $rawMsg);
                $appr = null; $etype = ''; $eid = 0; $eaction = '';$payload = [];
                if ($apprId) {
                  try {
                    $appr = (new \Models\Approval())->find($apprId);
                    $etype = (string)($appr['entity_type'] ?? '');
                    $eid = (int)($appr['entity_id'] ?? 0);
                    $eaction = (string)($appr['action'] ?? '');
                    $payload = json_decode((string)($appr['payload'] ?? '[]'), true) ?: [];
                  } catch (\Throwable $e) {}
                }
              ?>
              <div class="small text-muted" style="max-width: 700px; white-space: pre-wrap;"><?= nl2br(htmlspecialchars($cleanMsg)) ?></div>
            </td>
            <td><?= htmlspecialchars((string)($n['type'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($n['status'] ?? '')) ?><?= !empty($n['lida'])? ' · <span class="text-success">Lida</span>':'' ?><?= !empty($n['arquivada'])? ' · <span class="text-muted">Arquivada</span>':'' ?></td>
            <td><?= htmlspecialchars((string)($n['created_at'] ?? '')) ?></td>
            <td style="min-width:520px">
              <?php if (!empty($n['lida'])): ?>
                <form class="d-inline" method="post" action="/admin/notifications/mark-unread">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Core\Auth::csrf()) ?>">
                  <input type="hidden" name="id" value="<?= (int)($n['id'] ?? 0) ?>">
                  <button class="btn btn-sm btn-outline-warning" type="submit">Marcar como não lida</button>
                </form>
              <?php else: ?>
                <form class="d-inline" method="post" action="/admin/notifications/mark-read">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Core\Auth::csrf()) ?>">
                  <input type="hidden" name="id" value="<?= (int)($n['id'] ?? 0) ?>">
                  <button class="btn btn-sm btn-outline-success" type="submit">Marcar como lida</button>
                </form>
              <?php endif; ?>
              <?php if ($apprId): ?>
                <?php if ($etype === 'client' && $eid > 0): ?>
                  <a class="btn btn-sm btn-outline-primary" href="/admin/clients/edit?id=<?= (int)$eid ?>">Editar Cliente #<?= (int)$eid ?></a>
                <?php elseif ($etype === 'intl_sale' && $eid > 0): ?>
                  <a class="btn btn-sm btn-outline-primary" href="/admin/international-sales/edit?id=<?= (int)$eid ?>">Editar Venda (Int) #<?= (int)$eid ?></a>
                <?php elseif ($etype === 'nat_sale' && $eid > 0): ?>
                  <a class="btn btn-sm btn-outline-primary" href="/admin/national-sales/edit?id=<?= (int)$eid ?>">Editar Venda (Nac) #<?= (int)$eid ?></a>
                <?php else: ?>
                  <?php $collapseId = 'apprp_'.$apprId; ?>
                  <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-expanded="false">Ver detalhes</button>
                  <div class="collapse mt-2" id="<?= $collapseId ?>">
                    <pre class="small bg-light p-2 border rounded" style="max-height:240px; overflow:auto;"><?= htmlspecialchars(json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)) ?></pre>
                  </div>
                <?php endif; ?>
              <?php endif; ?>
              <?php if ($apprId): ?>
                <form class="d-inline" method="post" action="/admin/approvals/approve">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Core\Auth::csrf()) ?>">
                  <input type="hidden" name="id" value="<?= (int)$apprId ?>">
                  <button class="btn btn-sm btn-primary" type="submit">Aprovar</button>
                </form>
                <form class="d-inline" method="post" action="/admin/approvals/reject" onsubmit="return confirm('Rejeitar esta solicitação?');">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Core\Auth::csrf()) ?>">
                  <input type="hidden" name="id" value="<?= (int)$apprId ?>">
                  <input type="text" name="reason" class="form-control form-control-sm d-inline-block me-1 mb-1" style="width:260px" placeholder="Motivo da rejeição" required>
                  <button class="btn btn-sm btn-outline-danger" type="submit">Rejeitar</button>
                </form>
              <?php endif; ?>
              <form class="d-inline" method="post" action="/admin/notifications/archive">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Core\Auth::csrf()) ?>">
                <input type="hidden" name="id" value="<?= (int)($n['id'] ?? 0) ?>">
                <button class="btn btn-sm btn-outline-secondary" type="submit">Arquivar</button>
              </form>
              <?php if (in_array((\Core\Auth::user()['role'] ?? 'seller'), ['admin','manager'], true)): ?>
              <form class="d-inline" method="post" action="/admin/notifications/delete" onsubmit="return confirm('Excluir notificação?');">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Core\Auth::csrf()) ?>">
                <input type="hidden" name="id" value="<?= (int)($n['id'] ?? 0) ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
