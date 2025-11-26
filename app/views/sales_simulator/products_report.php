<?php /** @var array $items */ ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Relatório de Produtos do Simulador</h3>
    <a href="/admin/sales-simulator" class="btn btn-outline-secondary">Voltar para o Simulador</a>
  </div>

  <form class="row g-2 mb-3" method="get" action="/admin/sales-simulator/products-report">
    <div class="col-md-3">
      <label class="form-label mb-1">Data de pagamento (de)</label>
      <input type="date" name="from" value="<?= htmlspecialchars($from ?? '') ?>" class="form-control form-control-sm">
    </div>
    <div class="col-md-3">
      <label class="form-label mb-1">Data de pagamento (até)</label>
      <input type="date" name="to" value="<?= htmlspecialchars($to ?? '') ?>" class="form-control form-control-sm">
    </div>
    <div class="col-md-3">
      <label class="form-label mb-1">Buscar produto</label>
      <input type="text" name="q" value="<?= htmlspecialchars($q ?? '') ?>" class="form-control form-control-sm" placeholder="Nome do produto">
    </div>
    <div class="col-md-3 d-flex align-items-end gap-2">
      <button type="submit" class="btn btn-sm btn-primary">Aplicar filtros</button>
      <a href="/admin/sales-simulator/products-report/export-pdf?from=<?= urlencode($from ?? '') ?>&to=<?= urlencode($to ?? '') ?>&q=<?= urlencode($q ?? '') ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Exportar PDF</a>
    </div>
  </form>

  <div class="alert alert-info small">
    <strong>Legenda de cores:</strong><br>
    <span class="badge bg-success me-1">Comprado</span> Produto com quantidade comprada igual ou maior que a quantidade necessária no período.<br>
    <span class="badge bg-warning text-dark me-1">Parcial</span> Produto com parte da quantidade já comprada e parte ainda pendente.<br>
    <span class="badge bg-secondary me-1">Pendente</span> Produto ainda não marcado como comprado.
  </div>

  <?php if (empty($items)): ?>
    <div class="alert alert-info">Nenhum produto encontrado para os filtros selecionados.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-striped align-middle table-sm">
        <thead>
          <tr>
            <th>Produto</th>
            <th>Imagem</th>
            <th class="text-end">Qtd total</th>
            <th class="text-end">Peso total (kg)</th>
            <th class="text-end">Valor total (USD)</th>
            <th class="text-end">Qtd comprada</th>
            <th class="text-center">Status</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $row): ?>
          <?php
            $status = $row['status_compra'] ?? 'nao_comprado';
            $rowClass = '';
            if ($status === 'comprado_total') { $rowClass = 'table-success'; }
            elseif ($status === 'comprado_parcial') { $rowClass = 'table-warning'; }
          ?>
          <tr class="<?= $rowClass ?>">
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
            <td class="text-end"><?= number_format((float)($row['total_peso'] ?? 0), 2, ',', '.') ?></td>
            <td class="text-end">
              $ <?= number_format((float)($row['total_valor'] ?? 0), 2, '.', ',') ?>
            </td>
            <td class="text-end">
              <form method="post" action="/admin/sales-simulator/products-report/update-purchased" class="d-flex justify-content-end align-items-center gap-2">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Core\Auth::csrf()) ?>">
                <input type="hidden" name="product_key" value="<?= htmlspecialchars($row['key']) ?>">
                <input type="number" name="purchased_qtd" min="0" step="1" value="<?= (int)($row['purchased_qtd'] ?? 0) ?>" class="form-control form-control-sm" style="width:90px;">
                <button type="submit" class="btn btn-sm btn-outline-primary">Salvar</button>
              </form>
            </td>
            <td class="text-center">
              <?php if ($status === 'comprado_total'): ?>
                <span class="badge bg-success">Comprado</span>
              <?php elseif ($status === 'comprado_parcial'): ?>
                <span class="badge bg-warning text-dark">Parcial</span>
              <?php else: ?>
                <span class="badge bg-secondary">Pendente</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <a href="/admin/sales-simulator/products-report/product?product_key=<?= urlencode($row['key']) ?>&from=<?= urlencode($from ?? '') ?>&to=<?= urlencode($to ?? '') ?>" class="btn btn-sm btn-outline-secondary">Ver orçamentos</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
