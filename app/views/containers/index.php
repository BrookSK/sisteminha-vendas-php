<?php /** @var array $items */ ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Containers</h3>
    <div>
      <a class="btn btn-sm btn-primary" href="/admin/containers/new">Novo Container</a>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>ID</th>
          <th>Utilizador</th>
          <th>Status</th>
          <th>Peso (kg)</th>
          <th>Peso (lbs)</th>
          <th>Caminhão (US$)</th>
          <th>Aéreo (US$)</th>
          <th>Mercadoria (US$)</th>
          <th>Transp Aeroporto→Correios (R$)</th>
          <th>Final (US$)</th>
          <th>Final (R$)</th>
          <th>Criado em</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr><td colspan="13" class="text-center text-muted">Sem registros</td></tr>
        <?php else: foreach ($items as $c): ?>
          <tr>
            <td>#<?= (int)$c['id'] ?></td>
            <td><?= htmlspecialchars((string)($c['utilizador_id'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($c['status'] ?? '')) ?></td>
            <td><?= number_format((float)($c['peso_kg'] ?? 0), 2) ?></td>
            <td><?= number_format((float)($c['peso_lbs'] ?? 0), 2) ?></td>
            <td>$ <?= number_format((float)($c['transporte_caminhao_usd'] ?? 0), 2) ?></td>
            <td>$ <?= number_format((float)($c['aereo_usd'] ?? 0), 2) ?></td>
            <td>$ <?= number_format((float)($c['transporte_mercadoria_usd'] ?? 0), 2) ?></td>
            <td>R$ <?= number_format((float)($c['transporte_aeroporto_correios_brl'] ?? 0), 2) ?></td>
            <td>$ <?= number_format((float)($c['valor_debitado_final_usd'] ?? 0), 2) ?></td>
            <td>R$ <?= number_format((float)($c['valor_debitado_final_brl'] ?? 0), 2) ?></td>
            <td><?= htmlspecialchars((string)($c['created_at'] ?? '')) ?></td>
            <td style="min-width:200px">
              <a class="btn btn-sm btn-outline-primary" href="/admin/containers/edit?id=<?= (int)$c['id'] ?>">Editar</a>
              <form method="post" action="/admin/containers/delete" class="d-inline" onsubmit="return confirm('Excluir container?');">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Core\Auth::csrf()) ?>">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
