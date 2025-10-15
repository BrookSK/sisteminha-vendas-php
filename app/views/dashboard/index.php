<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="m-0">Dashboard</h4>
  <div class="d-flex gap-2 align-items-center">
    <span class="badge text-bg-secondary">Período: <?= htmlspecialchars($period_from ?? '') ?> a <?= htmlspecialchars($period_to ?? '') ?></span>
    <span class="badge text-bg-secondary">Câmbio: 1 USD = R$ <?= number_format((float)($rate ?? 0), 2) ?></span>
  </div>
</div>

<div class="card mt-4 mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Notificações Recentes</span>
    <?php if (isset($notifications_unread) && (int)$notifications_unread > 0): ?>
      <span class="badge text-bg-danger">Não lidas: <?= (int)$notifications_unread ?></span>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <?php if (empty($notifications_recent)): ?>
      <div class="text-muted">Sem notificações</div>
    <?php else: ?>
      <ul class="list-group">
        <?php foreach ($notifications_recent as $n): ?>
          <li class="list-group-item d-flex justify-content-between align-items-start">
            <div class="ms-2 me-auto">
              <div class="fw-semibold"><?= htmlspecialchars((string)($n['title'] ?? '')) ?></div>
              <div class="small text-muted" style="max-width: 900px; white-space: pre-wrap;"><?= nl2br(htmlspecialchars((string)($n['message'] ?? ''))) ?></div>
            </div>
            <span class="badge text-bg-secondary"><?= htmlspecialchars((string)($n['type'] ?? '')) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="mt-3">
        <a class="btn btn-sm btn-outline-primary" href="/admin/notifications">Ver todas</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-3">
    <div class="card text-bg-light">
      <div class="card-body">
        <div class="fs-6 text-muted">Total de Vendas (período)</div>
        <div class="fs-3 fw-bold"><?= (int)($total_count ?? 0) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-light">
      <div class="card-body">
        <div class="fs-6 text-muted">Bruto (USD)</div>
        <div class="fs-4 fw-bold">$ <?= number_format((float)($summary['total_bruto_usd'] ?? 0), 2) ?></div>
        <div class="text-muted">BRL R$ <?= number_format(((float)($summary['total_bruto_usd'] ?? 0)) * (float)($rate ?? 0), 2) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-light">
      <div class="card-body">
        <div class="fs-6 text-muted">Líquido (USD)</div>
        <div class="fs-4 fw-bold">$ <?= number_format((float)($summary['total_liquido_usd'] ?? 0), 2) ?></div>
        <div class="text-muted">BRL R$ <?= number_format(((float)($summary['total_liquido_usd'] ?? 0)) * (float)($rate ?? 0), 2) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-light">
      <div class="card-body">
        <div class="fs-6 text-muted">Comissão (USD)</div>
        <div class="fs-4 fw-bold">$ <?= number_format((float)($commission_total_usd ?? 0), 2) ?></div>
        <div class="text-muted">BRL R$ <?= number_format(((float)($commission_total_usd ?? 0)) * (float)($rate ?? 0), 2) ?></div>
      </div>
    </div>
  </div>
</div>

<div class="card mt-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Últimas Vendas (Hoje)</span>
    <span class="badge text-bg-secondary">Câmbio: 1 USD = R$ <?= number_format((float)($rate ?? 0), 2) ?></span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th>Data</th>
            <th>Cliente</th>
            <th>Pedido</th>
            <th>Bruto (USD)</th>
            <th>Líquido (USD)</th>
            <th>Comissão (USD)</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recent_today)): ?>
            <tr><td colspan="6" class="text-center text-muted">Sem vendas</td></tr>
          <?php else: foreach ($recent_today as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['dt'] ?? $r['created_at'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['cliente_nome'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['numero_pedido'] ?? '-') ?></td>
              <td>$ <?= number_format((float)($r['bruto_usd'] ?? 0), 2) ?></td>
              <td>$ <?= number_format((float)($r['liquido_usd'] ?? 0), 2) ?></td>
              <td>$ <?= number_format((float)($r['comissao_usd'] ?? 0), 2) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
