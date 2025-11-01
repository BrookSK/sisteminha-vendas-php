<?php
// expects $d array with keys as prepared in PerformanceController
$usd = function($v){ return '$ ' . number_format((float)$v, 2, ',', '.'); };
$brl = function($v){ return 'R$ ' . number_format((float)$v, 2, ',', '.'); };
$int = function($v){ return number_format((int)$v, 0, ',', '.'); };
$top = $d['top_clients'] ?? [];
?>
<div class="row g-3">
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-muted small">Bruto do vendedor</div>
        <div class="h5 mb-0"><?= $usd($d['bruto_total_usd'] ?? 0) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-muted small">Líquido do vendedor</div>
        <div class="h5 mb-0"><?= $usd($d['liquido_total_usd'] ?? 0) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-muted small">Líquido apurado</div>
        <div class="h5 mb-0"><?= $usd($d['liquido_apurado_usd'] ?? 0) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-muted small">Comissão</div>
        <div class="h5 mb-0"><?= $usd($d['comissao_usd'] ?? 0) ?></div>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-muted small">Quantidade de vendas (período)</div>
        <div class="h5 mb-0"><?= $int($d['sales_count'] ?? 0) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-muted small">Atendimentos (período)</div>
        <div class="h6 mb-0">Total: <?= $int($d['att_total'] ?? 0) ?></div>
        <div class="h6 mb-0">Concluídos: <?= $int($d['att_done'] ?? 0) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="text-muted small">Top 5 clientes</div>
          <?php if (!empty($top)): $best = $top[0]; ?>
            <div class="small"><strong>Melhor cliente:</strong> <?= htmlspecialchars($best['cliente_nome'] ?? '—') ?> (<?= $int($best['total_vendas'] ?? 0) ?> vendas)</div>
          <?php endif; ?>
        </div>
        <?php if (empty($top)): ?>
          <div class="text-muted">Sem clientes no período.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th>Cliente</th>
                  <th class="text-end">Vendas</th>
                  <th class="text-end">Bruto (USD)</th>
                  <th class="text-end">Líquido (USD)</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($top as $row): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['cliente_nome'] ?? ('#'.$row['cliente_id'])) ?></td>
                    <td class="text-end"><?= $int($row['total_vendas'] ?? 0) ?></td>
                    <td class="text-end"><?= $usd($row['total_bruto_usd'] ?? 0) ?></td>
                    <td class="text-end"><?= $usd($row['total_liquido_usd'] ?? 0) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
