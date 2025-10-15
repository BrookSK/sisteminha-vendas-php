<?php /** @var array $items */ ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Minhas Metas</h3>
  </div>

  <?php
    $totalMeta = 0.0; $totalAtual = 0.0; $totalPrev = 0.0; $diasRest = 0; $diasTot = 0;
    foreach ($items as $it) {
      $totalMeta += (float)($it['valor_meta'] ?? 0);
      $totalAtual += (float)($it['progresso_atual'] ?? 0);
      $totalPrev += (float)($it['_calc']['previsaoFinal'] ?? 0);
      $diasRest += (int)($it['_calc']['diasRestantes'] ?? 0);
      $diasTot += (int)($it['_calc']['diasTotais'] ?? 0);
    }
    $perc = $totalMeta>0 ? ($totalAtual/$totalMeta*100.0) : 0;
    $valorDiaNec = ($diasRest>0) ? max(0.0, ($totalMeta - $totalAtual) / $diasRest) : 0.0;
  ?>

  <div class="row g-3 mb-3">
    <?php if (isset($global_target_usd)): ?>
    <div class="col-md-12">
      <div class="card border-warning">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
          <div>
            <div class="text-muted small">Meta Global do Mês (<?= htmlspecialchars($global_from ?? '') ?> a <?= htmlspecialchars($global_to ?? '') ?>)</div>
            <div class="fs-5">Alvo: $ <?= number_format((float)($global_target_usd ?? 50000), 2, ',', '.') ?> • Realizado: $ <?= number_format((float)($global_actual_usd ?? 0), 2, ',', '.') ?></div>
          </div>
          <div class="text-end">
            <div class="text-muted small">% atingido</div>
            <div class="fs-5"><?= number_format((float)($global_percent ?? 0), 1, ',', '.') ?>%</div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <div class="col-md-3">
      <div class="card"><div class="card-body">
        <div class="text-muted small">Meta total</div>
        <div class="fs-5">$ <?= number_format($totalMeta, 2, ',', '.') ?></div>
      </div></div>
    </div>
    <div class="col-md-3">
      <div class="card"><div class="card-body">
        <div class="text-muted small">Realizado</div>
        <div class="fs-5">$ <?= number_format($totalAtual, 2, ',', '.') ?></div>
      </div></div>
    </div>
    <div class="col-md-3">
      <div class="card"><div class="card-body">
        <div class="text-muted small">% atingido</div>
        <div class="fs-5"><?= number_format($perc, 1, ',', '.') ?>%</div>
      </div></div>
    </div>
    <div class="col-md-3">
      <div class="card"><div class="card-body">
        <div class="text-muted small">Previsão final</div>
        <div class="fs-5">$ <?= number_format($totalPrev, 2, ',', '.') ?></div>
      </div></div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
      <span>Gráfico (Meta x Realizado x Previsão)</span>
      <small class="text-muted">Por meta</small>
    </div>
    <div class="card-body">
      <canvas id="goalsChart" height="120"></canvas>
      <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
      <script>
        (function(){
          const labels = <?= json_encode(array_map(fn($x)=>$x['titulo'].' ('.($x['moeda'] ?? 'USD').')', $items)) ?>;
          const metas = <?= json_encode(array_map(fn($x)=>round((float)($x['valor_meta'] ?? 0),2), $items)) ?>;
          const realizados = <?= json_encode(array_map(fn($x)=>round((float)($x['progresso_atual'] ?? 0),2), $items)) ?>;
          const previsoes = <?= json_encode(array_map(fn($x)=>round((float)($x['_calc']['previsaoFinal'] ?? 0),2), $items)) ?>;
          const ctx = document.getElementById('goalsChart');
          if (ctx && window.Chart) {
            new Chart(ctx, {
              type: 'bar',
              data: {
                labels,
                datasets: [
                  { label: 'Meta', data: metas, backgroundColor: 'rgba(13,110,253,0.4)', borderColor: 'rgba(13,110,253,1)', borderWidth: 1 },
                  { label: 'Realizado', data: realizados, backgroundColor: 'rgba(25,135,84,0.5)', borderColor: 'rgba(25,135,84,1)', borderWidth: 1 },
                  { label: 'Previsão', data: previsoes, backgroundColor: 'rgba(255,193,7,0.5)', borderColor: 'rgba(255,193,7,1)', borderWidth: 1 }
                ]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } }
              }
            });
          }
        })();
      </script>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>Título</th>
          <th>Tipo</th>
          <th>Período</th>
          <th class="text-end">Meta</th>
          <th class="text-end">Realizado</th>
          <th class="text-end">% atingido</th>
          <th class="text-end">Previsão</th>
          <th class="text-end">Dias restantes</th>
          <th class="text-end">Necessário/dia</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr><td colspan="9" class="text-center text-muted">Sem metas atribuídas</td></tr>
        <?php else: foreach ($items as $g):
          $calc = $g['_calc'] ?? [];
          $valorMeta = (float)($g['valor_meta'] ?? 0);
          $valorAtual = (float)($g['progresso_atual'] ?? 0);
          $pct = $valorMeta>0 ? ($valorAtual/$valorMeta*100.0) : 0;
          $necessarioDia = (float)($calc['valorNecessarioPorDia'] ?? 0);
        ?>
          <tr>
            <td><?= htmlspecialchars($g['titulo']) ?></td>
            <td><?= htmlspecialchars($g['tipo'] ?? 'global') ?></td>
            <td><?= htmlspecialchars($g['data_inicio']) ?> a <?= htmlspecialchars($g['data_fim']) ?></td>
            <td class="text-end">$ <?= number_format($valorMeta, 2, ',', '.') ?></td>
            <td class="text-end">$ <?= number_format($valorAtual, 2, ',', '.') ?></td>
            <td class="text-end"><?= number_format($pct, 1, ',', '.') ?>%</td>
            <td class="text-end">$ <?= number_format((float)($calc['previsaoFinal'] ?? 0), 2, ',', '.') ?></td>
            <td class="text-end"><?= (int)($calc['diasRestantes'] ?? 0) ?></td>
            <td class="text-end">$ <?= number_format($necessarioDia, 2, ',', '.') ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
