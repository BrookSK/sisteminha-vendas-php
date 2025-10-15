<?php use Core\Auth; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Vendas</h5>
  <a href="/admin/sales/new" class="btn btn-primary">Nova Venda</a>
</div>
<div class="table-responsive">
  <table class="table table-striped align-middle">
    <thead>
      <tr>
        <th>Data/Hora</th>
        <th>Cliente</th>
        <th>Pedido</th>
        <th>Suite</th>
        <th>Peso (kg)</th>
        <th>Bruto (USD)</th>
        <th>Líquido (USD)</th>
        <th>Comissão (USD)</th>
        <th class="text-end">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($sales)): ?>
        <tr><td colspan="9" class="text-center text-muted">Sem vendas</td></tr>
      <?php else: foreach ($sales as $s): ?>
        <tr>
          <td><?= htmlspecialchars($s['created_at']) ?></td>
          <td><?= htmlspecialchars($s['cliente_nome'] ?? '-') ?></td>
          <td><?= htmlspecialchars($s['numero_pedido'] ?? '-') ?></td>
          <td><span class="badge text-bg-secondary"><?= htmlspecialchars($s['suite'] ?? '-') ?></span></td>
          <td><?= number_format((float)($s['peso_kg'] ?? 0), 2) ?></td>
          <td>$ <?= number_format((float)($s['bruto_usd'] ?? 0), 2) ?></td>
          <td>$ <?= number_format((float)($s['liquido_usd'] ?? 0), 2) ?></td>
          <td>$ <?= number_format((float)($s['comissao_usd'] ?? 0), 2) ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="/admin/sales/edit?id=<?= (int)$s['id'] ?>">Editar</a>
            <form method="post" action="/admin/sales/delete" class="d-inline" onsubmit="return confirm('Excluir esta venda?');">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
              <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
              <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
