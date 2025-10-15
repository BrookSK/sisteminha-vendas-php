<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Relatórios</h5>
  <div class="d-flex gap-2 align-items-center">
    <span class="badge text-bg-secondary">Câmbio: 1 USD = R$ <?= number_format((float)$rate, 2) ?></span>
    <?php if (isset($cost_rate)): ?>
      <span class="badge text-bg-warning">Custo Global: <?= number_format((float)$cost_rate*100, 2) ?>%</span>
    <?php endif; ?>
  </div>
  <a class="btn btn-sm btn-outline-secondary" href="/admin/commissions">Comissões (Admin)</a>
</div>

<form class="row g-2 mb-3" method="get" action="/admin/reports">
  <div class="col-auto">
    <label class="form-label">De</label>
    <input type="date" name="from" value="<?= htmlspecialchars($_GET['from'] ?? date('Y-m-01')) ?>" class="form-control">
  </div>
  <div class="col-auto">
    <label class="form-label">Até</label>
    <input type="date" name="to" value="<?= htmlspecialchars($_GET['to'] ?? date('Y-m-t')) ?>" class="form-control">
  </div>
  <div class="col-auto">
    <label class="form-label">Vendedor</label>
    <select name="seller_id" class="form-select">
      <option value="">Todos</option>
      <?php foreach (($users ?? []) as $u): ?>
        <?php $sid = $_GET['seller_id'] ?? ''; ?>
        <option value="<?= (int)$u['id'] ?>" <?= ($sid !== '' && (int)$sid === (int)$u['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($u['name'] ?: $u['email']) ?> (<?= htmlspecialchars($u['role']) ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto align-self-end">
    <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
    <a class="btn btn-outline-primary" href="/admin/commissions/export?period=<?= urlencode(date('Y-m')) ?>">Exportar Comissões CSV</a>
  </div>
</form>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Resumo da Semana (ISO)</div>
      <div class="card-body">
        <div class="row">
          <div class="col-6">
            <div class="text-muted small">Nº de atendimentos</div>
            <div class="fs-4 fw-bold"><?= (int)($week['atendimentos'] ?? 0) ?></div>
          </div>
          <div class="col-6">
            <div class="text-muted small">Concluídos</div>
            <div class="fs-4 fw-bold"><?= (int)($week['atendimentos_concluidos'] ?? 0) ?></div>
          </div>
          <div class="col-6 mt-3">
            <div class="text-muted small">Bruto (USD)</div>
            <div class="fw-bold">$ <?= number_format((float)($week['total_bruto_usd'] ?? 0), 2) ?></div>
            <div class="text-muted small">BRL R$ <?= number_format(((float)($week['total_bruto_usd'] ?? 0))*$rate, 2) ?></div>
          </div>
          <div class="col-6 mt-3">
            <div class="text-muted small">Custos (USD)</div>
            <div class="fw-bold">$ <?= number_format((float)($week['custos_usd'] ?? 0), 2) ?></div>
            <?php if (isset($week['custos_percentuais_usd'])): ?>
              <div class="small text-muted">Custo Global: $ <?= number_format((float)$week['custos_percentuais_usd'], 2) ?></div>
            <?php endif; ?>
          </div>
          <div class="col-6 mt-3">
            <div class="text-muted small">Líquido (USD)</div>
            <div class="fw-bold">$ <?= number_format((float)($week['total_liquido_usd'] ?? 0), 2) ?></div>
          </div>
          <div class="col-6 mt-3">
            <div class="text-muted small">Lucro Líquido (USD)</div>
            <div class="fw-bold">$ <?= number_format((float)($week['lucro_liquido_usd'] ?? 0), 2) ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Resumo do Mês</div>
      <div class="card-body">
        <div class="row">
          <div class="col-6">
            <div class="text-muted small">Nº de atendimentos</div>
            <div class="fs-4 fw-bold"><?= (int)($month['atendimentos'] ?? 0) ?></div>
          </div>
          <div class="col-6">
            <div class="text-muted small">Concluídos</div>
            <div class="fs-4 fw-bold"><?= (int)($month['atendimentos_concluidos'] ?? 0) ?></div>
          </div>
          <div class="col-6 mt-3">
            <div class="text-muted small">Bruto (USD)</div>
            <div class="fw-bold">$ <?= number_format((float)($month['total_bruto_usd'] ?? 0), 2) ?></div>
            <div class="text-muted small">BRL R$ <?= number_format(((float)($month['total_bruto_usd'] ?? 0))*$rate, 2) ?></div>
          </div>
          <div class="col-6 mt-3">
            <div class="text-muted small">Custos (USD)</div>
            <div class="fw-bold">$ <?= number_format((float)($month['custos_usd'] ?? 0), 2) ?></div>
            <?php if (isset($month['custos_percentuais_usd'])): ?>
              <div class="small text-muted">Custo Global: $ <?= number_format((float)$month['custos_percentuais_usd'], 2) ?></div>
            <?php endif; ?>
          </div>
          <div class="col-6 mt-3">
            <div class="text-muted small">Líquido (USD)</div>
            <div class="fw-bold">$ <?= number_format((float)($month['total_liquido_usd'] ?? 0), 2) ?></div>
          </div>
          <div class="col-6 mt-3">
            <div class="text-muted small">Lucro Líquido (USD)</div>
            <div class="fw-bold">$ <?= number_format((float)($month['lucro_liquido_usd'] ?? 0), 2) ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card mt-3">
  <div class="card-header">Últimos 3 meses x Atual</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th>Mês</th>
            <th>Bruto (USD)</th>
            <th>Líquido (USD)</th>
            <th>Custos (USD)</th>
            <th>Lucro Líquido (USD)</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($months)): ?>
            <tr><td colspan="5" class="text-center text-muted">Sem dados</td></tr>
          <?php else: foreach ($months as $m): ?>
            <tr>
              <td><?= htmlspecialchars($m['ym']) ?></td>
              <td>$ <?= number_format((float)($m['total_bruto_usd'] ?? 0), 2) ?></td>
              <td>$ <?= number_format((float)($m['total_liquido_usd'] ?? 0), 2) ?></td>
              <td>$ <?= number_format((float)($m['custos_usd'] ?? 0), 2) ?></td>
              <td>$ <?= number_format((float)($m['lucro_liquido_usd'] ?? 0), 2) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card mt-3">
  <div class="card-header">Atendimentos por Vendedor</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th>Vendedor</th>
            <th>Atendimentos</th>
            <th>Bruto (USD)</th>
            <th>Líquido (USD)</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($sellers)): ?>
            <tr><td colspan="4" class="text-center text-muted">Sem dados</td></tr>
          <?php else: foreach ($sellers as $s): ?>
            <tr>
              <td><?= htmlspecialchars($s['name'] ?: $s['email']) ?></td>
              <td><?= (int)($s['atendimentos'] ?? 0) ?></td>
              <td>$ <?= number_format((float)($s['total_bruto_usd'] ?? 0), 2) ?></td>
              <td>$ <?= number_format((float)($s['total_liquido_usd'] ?? 0), 2) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
