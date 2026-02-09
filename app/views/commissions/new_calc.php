<?php /** @var string $period */ /** @var string $period_from */ /** @var string $period_to */ /** @var array $items */ /** @var array|null $mine */ ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Novo Cálculo</h3>
    <div class="small text-muted">Período: <?= htmlspecialchars($period_from ?? '') ?> a <?= htmlspecialchars($period_to ?? '') ?><?= !empty($has_snapshot) ? ' (congelado)' : '' ?></div>
  </div>

  <form class="row g-2 mb-3" method="get" action="/admin/commissions/new-calc">
    <div class="col-auto">
      <label class="form-label">Período</label>
      <input type="month" name="period" value="<?= htmlspecialchars($period ?? ($_GET['period'] ?? date('Y-m'))) ?>" class="form-control">
    </div>

    <?php if (($role ?? '') === 'admin'): ?>
    <div class="col-auto">
      <label class="form-label">Vendedor</label>
      <select class="form-select" name="seller_id">
        <option value="">Todos</option>
        <?php foreach (($users ?? []) as $u): ?>
          <option value="<?= (int)$u['id'] ?>" <?= isset($seller_id) && (int)$seller_id === (int)$u['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($u['name'] ?: $u['email']) ?> (<?= htmlspecialchars($u['role']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>

    <div class="col-auto align-self-end">
      <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
    </div>
  </form>

  <?php if (!empty($company) && ($role ?? '') === 'admin'): ?>
    <div class="row mb-3">
      <div class="col">
        <div class="p-3 bg-light border rounded">
          <div class="fw-bold mb-1">Totais (empresa)</div>
          <div class="d-flex flex-wrap gap-3 small">
            <div><strong>Bruto (BRL):</strong> R$ <?= number_format((float)($company['bruto_total_brl'] ?? 0), 2) ?></div>
            <div><strong>Líquido Novo (BRL):</strong> R$ <?= number_format((float)($company['liquido_novo_brl'] ?? 0), 2) ?></div>
            <div><strong>Comissão Nova (BRL):</strong> R$ <?= number_format((float)($company['comissao_brl'] ?? 0), 2) ?></div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if (($role ?? '') !== 'admin' && $mine): ?>
    <div class="row mb-3">
      <div class="col-md-4">
        <div class="p-3 bg-light border rounded">
          <div class="fw-bold">Bruto (BRL)</div>
          <div>R$ <?= number_format((float)($mine['bruto_total_brl'] ?? 0), 2) ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="p-3 bg-light border rounded">
          <div class="fw-bold">Líquido Novo (BRL)</div>
          <div>R$ <?= number_format((float)($mine['liquido_novo_brl'] ?? 0), 2) ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="p-3 bg-light border rounded">
          <div class="fw-bold">Comissão Nova (BRL)</div>
          <div>R$ <?= number_format((float)($mine['comissao_brl'] ?? 0), 2) ?></div>
          <div class="small text-muted">Percentual: <?= number_format(((float)($mine['percent'] ?? 0)) * 100, 2) ?>%</div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>Vendedor</th>
          <th>Status</th>
          <th class="text-end">Bruto (BRL)</th>
          <th class="text-end">Líquido Novo (BRL)</th>
          <th class="text-end">%</th>
          <th class="text-end">Comissão Nova (BRL)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr><td colspan="6" class="text-muted">Sem dados para o período selecionado.</td></tr>
        <?php else: ?>
          <?php foreach ($items as $it): ?>
            <tr>
              <td>
                <?= htmlspecialchars($it['seller_name'] ?? '') ?>
                <?php if (($role ?? '') === 'admin'): ?>
                  <div class="small text-muted">ID <?= (int)($it['seller_id'] ?? 0) ?><?= !empty($it['seller_role']) ? (' • '.htmlspecialchars((string)$it['seller_role'])) : '' ?></div>
                <?php endif; ?>
              </td>
              <td>
                <?= ((int)($it['seller_ativo'] ?? 0) === 1) ? '<span class="badge text-bg-success">Ativo</span>' : '<span class="badge text-bg-secondary">Inativo</span>' ?>
              </td>
              <td class="text-end">R$ <?= number_format((float)($it['bruto_total_brl'] ?? 0), 2) ?></td>
              <td class="text-end">R$ <?= number_format((float)($it['liquido_novo_brl'] ?? 0), 2) ?></td>
              <td class="text-end"><?= number_format(((float)($it['percent'] ?? 0)) * 100, 2) ?>%</td>
              <td class="text-end">R$ <?= number_format((float)($it['comissao_brl'] ?? 0), 2) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
