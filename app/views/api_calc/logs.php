<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Logs da API de Cálculo</h5>
</div>
<div class="table-responsive">
  <table class="table table-striped align-middle">
    <thead>
      <tr>
        <th>Data</th>
        <th>Resumo</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($items)): ?>
      <tr><td colspan="2" class="text-muted text-center">Sem logs</td></tr>
      <?php else: foreach ($items as $l): $det = json_decode($l['detalhes'] ?? '[]', true) ?: []; ?>
      <tr>
        <td><?= htmlspecialchars($l['created_at'] ?? '') ?></td>
        <td>
          <div><strong>Gross:</strong> $ <?= number_format((float)($det['gross_usd'] ?? 0), 2) ?> → <strong>Net:</strong> $ <?= number_format((float)($det['net_usd'] ?? 0), 2) ?></div>
          <div class="small text-muted">Rate: <?= htmlspecialchars((string)($det['rate'] ?? '')) ?> | Inc: USD <?= !empty(($det['inc']['usd'] ?? false))?'on':'off' ?>, BRL <?= !empty(($det['inc']['brl'] ?? false))?'on':'off' ?>, % <?= !empty(($det['inc']['percent'] ?? false))?'on':'off' ?></div>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
