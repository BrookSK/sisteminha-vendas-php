<?php /** @var array $items */ ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Dashboard de Compras - Fabiana</h3>
    <a href="/admin/sales-simulator/products-report" class="btn btn-outline-secondary">Ver relatório de produtos</a>
  </div>

  <form class="row g-2 mb-3" method="get" action="/admin/sales-simulator/fabiana">
    <div class="col-md-3">
      <label class="form-label mb-1">Data de pagamento (de)</label>
      <input type="date" name="from" value="<?= htmlspecialchars($from ?? '') ?>" class="form-control form-control-sm">
    </div>
    <div class="col-md-3">
      <label class="form-label mb-1">Data de pagamento (até)</label>
      <input type="date" name="to" value="<?= htmlspecialchars($to ?? '') ?>" class="form-control form-control-sm">
    </div>
    <div class="col-md-3">
      <label class="form-label mb-1">Loja</label>
      <select name="store_id" class="form-select form-select-sm">
        <option value="">Todas as lojas</option>
        <option value="0" <?= ($store_id ?? '') === '0' ? 'selected' : '' ?>>Loja não selecionada</option>
        <?php foreach (($stores ?? []) as $st): ?>
          <option value="<?= (int)($st['id'] ?? 0) ?>" <?= (string)($store_id ?? '') === (string)($st['id'] ?? '') ? 'selected' : '' ?>>
            <?= htmlspecialchars($st['name'] ?? '') ?> (ID: <?= (int)($st['id'] ?? 0) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-flex align-items-end gap-2">
      <button type="submit" class="btn btn-sm btn-primary">Aplicar filtros</button>
    </div>
  </form>

  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="p-2 border rounded h-100">
        <div class="text-muted small">Saldo total em caixa com a Fabiana (manual)</div>
        <div class="fw-bold">$ <?= number_format((float)($fabiana_cash_total_usd ?? 0), 2, '.', ',') ?></div>
        <form method="post" action="/admin/sales-simulator/fabiana/save-cash" class="mt-2 d-flex align-items-center gap-2">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Core\Auth::csrf()) ?>">
          <span class="text-muted small">$</span>
          <input type="number" name="fabiana_cash_total_usd" min="0" step="0.01" value="<?= number_format((float)($fabiana_cash_total_usd ?? 0), 2, '.', '') ?>" class="form-control form-control-sm" style="max-width:130px;">
          <button type="submit" class="btn btn-sm btn-outline-success">Salvar</button>
        </form>
      </div>
    </div>
    <div class="col-md-3">
      <div class="p-2 border rounded h-100">
        <div class="text-muted small">Valor total planejado (produtos no período)</div>
        <div class="fw-bold">$ <?= number_format((float)($total_needed_usd ?? 0), 2, '.', ',') ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="p-2 border rounded h-100">
        <div class="text-muted small">Valor restante (produtos ainda não comprados)</div>
        <div class="fw-bold">$ <?= number_format((float)($total_remaining_usd ?? 0), 2, '.', ',') ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="p-2 border rounded h-100">
        <div class="text-muted small">Quanto falta enviar (pós caixa por produto)</div>
        <div class="fw-bold text-danger">$ <?= number_format((float)($total_to_send_usd ?? 0), 2, '.', ',') ?></div>
      </div>
    </div>
  </div>

  <?php if (empty($items)): ?>
    <div class="alert alert-info">Nenhum produto encontrado para os filtros selecionados.</div>
  <?php else: ?>
    <?php
      $grouped = [];
      foreach ($items as $row) {
        $sname = $row['store_name'] ?? null;
        if ($sname === null || $sname === '') { $sname = 'Loja não selecionada'; }
        $grouped[$sname][] = $row;
      }
      ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);
    ?>
    <?php foreach ($grouped as $storeName => $rowsByStore): ?>
      <?php
        $storeTotalRemaining = 0.0;
        foreach ($rowsByStore as $r) {
          $storeTotalRemaining += (float)($r['remaining_valor'] ?? 0);
        }
      ?>
      <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0">Loja: <?= htmlspecialchars($storeName) ?></h5>
          <div class="small text-muted">
            Restante (não comprado): $ <?= number_format($storeTotalRemaining, 2, '.', ',') ?>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-striped align-middle table-sm">
            <thead>
              <tr>
                <th>Produto</th>
                <th>Imagem</th>
                <th class="text-end">Qtd total</th>
                <th class="text-end">Qtd comprada</th>
                <th class="text-end">Qtd restante</th>
                <th class="text-end">Valor total (USD)</th>
                <th class="text-end">Valor restante (USD)</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($rowsByStore as $row): ?>
              <tr>
                <td>
                  <div class="fw-semibold mb-1"><?= htmlspecialchars($row['name'] ?? '') ?></div>
                  <?php if (!empty($row['links'])): ?>
                    <div class="small text-muted">
                      <?php foreach ($row['links'] as $lnk): ?>
                        <div><a href="<?= htmlspecialchars($lnk['url'] ?? '') ?>" target="_blank" rel="noopener">Link para compra (<?= htmlspecialchars($lnk['fonte'] ?? 'site') ?>)</a></div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td style="max-width:120px;">
                  <?php if (!empty($row['image_url'])): ?>
                    <img src="<?= htmlspecialchars($row['image_url']) ?>" alt="Imagem do produto" class="img-fluid" style="max-height:70px;">
                  <?php else: ?>
                    <span class="text-muted small">Sem imagem</span>
                  <?php endif; ?>
                </td>
                <td class="text-end"><?= (int)($row['total_qtd'] ?? 0) ?></td>
                <td class="text-end"><?= (int)($row['purchased_qtd'] ?? 0) ?></td>
                <td class="text-end"><?= (int)($row['remaining_qtd'] ?? 0) ?></td>
                <td class="text-end">$ <?= number_format((float)($row['total_valor'] ?? 0), 2, '.', ',') ?></td>
                <td class="text-end">$ <?= number_format((float)($row['remaining_valor'] ?? 0), 2, '.', ',') ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
