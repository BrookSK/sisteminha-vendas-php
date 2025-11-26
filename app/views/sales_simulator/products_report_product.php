<?php /** @var array $budgets */ ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Orçamentos com o produto selecionado</h3>
    <a href="/admin/sales-simulator/products-report?from=<?= urlencode($from ?? '') ?>&to=<?= urlencode($to ?? '') ?>" class="btn btn-outline-secondary">Voltar para o relatório</a>
  </div>

  <?php if (empty($budgets)): ?>
    <div class="alert alert-info">Nenhum orçamento encontrado para este produto no período informado.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-striped align-middle table-sm">
        <thead>
          <tr>
            <th>ID do orçamento</th>
            <th>Nome</th>
            <th>Data de pagamento</th>
            <th>Itens deste produto</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($budgets as $b): ?>
          <tr>
            <td>#<?= (int)($b['id'] ?? 0) ?></td>
            <td><?= htmlspecialchars($b['name'] ?? '') ?></td>
            <td><?= htmlspecialchars($b['paid_at'] ?? '') ?></td>
            <td>
              <?php foreach (($b['items'] ?? []) as $it): ?>
                <div class="small">
                  <strong><?= htmlspecialchars($it['nome'] ?? '') ?></strong>
                  - Qtd: <?= (int)($it['qtd'] ?? 0) ?>,
                  Peso un.: <?= number_format((float)($it['peso'] ?? 0), 2, ',', '.') ?> kg,
                  Valor un.: $ <?= number_format((float)($it['valor'] ?? 0), 2, '.', ',') ?>
                </div>
              <?php endforeach; ?>
            </td>
            <td class="text-end">
              <a href="/admin/sales-simulator?budget_id=<?= (int)($b['id'] ?? 0) ?>" class="btn btn-sm btn-outline-primary">Abrir orçamento</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
