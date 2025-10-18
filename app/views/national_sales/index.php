<?php /** @var array $items */ ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Vendas (EUA / Brasil)</h3>
    <div class="d-flex gap-2">
      <a class="btn btn-sm btn-outline-secondary" href="/admin/international-sales">Aba EUA</a>
      <a class="btn btn-sm btn-primary" href="/admin/national-sales">Aba Brasil</a>
      <a class="btn btn-sm btn-success" href="/admin/national-sales/new">Nova Venda Brasil</a>
      <a class="btn btn-sm btn-outline-secondary" href="/admin/national-sales/export<?= isset($seller_id) && $seller_id ? ('?seller_id='.(int)$seller_id.($ym?('&ym='.urlencode($ym)):'') ) : ($ym?('?ym='.urlencode($ym)):'') ?>">Exportar CSV</a>
    </div>
  </div>

  <form class="row g-2 mb-3" method="get" action="/admin/national-sales">
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
    <div class="col-auto">
      <label class="form-label">Mês</label>
      <input type="month" class="form-control" name="ym" value="<?= htmlspecialchars((string)($ym ?? '')) ?>">
    </div>
    <div class="col-auto align-self-end">
      <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>Data</th>
          <th>Pedido</th>
          <th>Cliente</th>
          <th>Suite</th>
          <th class="text-end">Bruto (USD)</th>
          <th class="text-end">Bruto (BRL)</th>
          <th class="text-end">Líquido (USD)</th>
          <th class="text-end">Líquido (BRL)</th>
          <th class="text-end">Comissão (USD)</th>
          <th class="text-end">Comissão (BRL)</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr><td colspan="11" class="text-center text-muted">Sem registros</td></tr>
        <?php else: foreach ($items as $s): ?>
          <tr>
            <td><?= htmlspecialchars($s['data_lancamento']) ?></td>
            <td><?= htmlspecialchars($s['numero_pedido'] ?? '') ?></td>
            <td><?= htmlspecialchars($s['cliente_nome'] ?? ('#'.$s['cliente_id'])) ?></td>
            <td><?= htmlspecialchars($s['suite_cliente'] ?? '') ?></td>
            <td class="text-end">$ <?= number_format((float)$s['total_bruto_usd'], 2) ?></td>
            <td class="text-end">R$ <?= number_format((float)$s['total_bruto_brl'], 2, ',', '.') ?></td>
            <td class="text-end">$ <?= number_format((float)$s['total_liquido_usd'], 2) ?></td>
            <td class="text-end">R$ <?= number_format((float)$s['total_liquido_brl'], 2, ',', '.') ?></td>
            <td class="text-end">$ <?= number_format((float)$s['comissao_usd'], 2) ?></td>
            <td class="text-end">R$ <?= number_format((float)$s['comissao_brl'], 2, ',', '.') ?></td>
            <td>
              <a class="btn btn-sm btn-outline-primary me-1" href="/admin/national-sales/edit?id=<?= (int)$s['id'] ?>">Editar</a>
              <a class="btn btn-sm btn-outline-secondary me-1" href="/admin/national-sales/duplicate?id=<?= (int)$s['id'] ?>">Duplicar</a>
              <form method="post" action="/admin/national-sales/delete" class="d-inline" onsubmit="return confirm('Excluir esta venda?');">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Core\Auth::csrf()) ?>">
                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
