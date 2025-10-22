<?php /** @var array $items */ /** @var array $tot */ ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Doações</h3>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a class="btn btn-sm btn-outline-secondary" href="/admin/donations/export<?= ($from?('?from='.urlencode($from)):'') . ($to?($from?'&':'?').'to='.urlencode($to):'') . ($q?(($from||$to)?'&':'?').'q='.urlencode($q):'') ?>">Exportar CSV</a>
      <span class="badge text-bg-success">Total Doações: R$ <?= number_format((float)($tot['total_doacoes_brl'] ?? 0), 2, ',', '.') ?></span>
      <?php if (isset($lucro_final_brl)): ?>
        <span class="badge text-bg-primary">Caixa da Empresa (período): R$ <?= number_format((float)$lucro_final_brl, 2, ',', '.') ?></span>
      <?php endif; ?>
      <?php if (isset($doado_brl)): ?>
        <span class="badge text-bg-warning text-dark">Doado no período: R$ <?= number_format((float)$doado_brl, 2, ',', '.') ?></span>
      <?php endif; ?>
      <?php if (isset($orcamento_disponivel_brl)): ?>
        <span class="badge text-bg-info text-dark">Disponível para doar: R$ <?= number_format((float)$orcamento_disponivel_brl, 2, ',', '.') ?></span>
      <?php endif; ?>
    </div>
  </div>

  <form class="row g-2 mb-3" method="get" action="/admin/donations">
    <div class="col-auto">
      <label class="form-label">De</label>
      <input type="date" name="from" value="<?= htmlspecialchars($from ?? '') ?>" class="form-control">
    </div>
    <div class="col-auto">
      <label class="form-label">Até</label>
      <input type="date" name="to" value="<?= htmlspecialchars($to ?? '') ?>" class="form-control">
    </div>
    <div class="col-auto">
      <label class="form-label">Busca</label>
      <input type="text" name="q" value="<?= htmlspecialchars($q ?? '') ?>" class="form-control" placeholder="Instituição ou descrição">
    </div>
    <div class="col-auto align-self-end">
      <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
    </div>
  </form>

  <div class="card mb-4">
    <div class="card-header">Registrar Doação</div>
    <div class="card-body">
      <form method="post" action="/admin/donations/create" class="row g-2">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Core\Auth::csrf()) ?>">
        <div class="col-md-3">
          <label class="form-label">Instituição</label>
          <input type="text" name="instituicao" class="form-control" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">CNPJ (opcional)</label>
          <input type="text" name="cnpj" class="form-control">
        </div>
        <div class="col-md-2">
          <label class="form-label">Valor (BRL)</label>
          <input type="number" step="0.01" min="0" name="valor_brl" class="form-control" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Data</label>
          <input type="date" name="data" value="<?= htmlspecialchars(date('Y-m-d')) ?>" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Categoria</label>
          <input type="text" name="categoria" class="form-control" placeholder="Opcional">
        </div>
        <div class="col-12">
          <label class="form-label">Descrição</label>
          <input type="text" name="descricao" class="form-control" placeholder="Opcional">
        </div>
        <div class="col-12">
          <button class="btn btn-primary" type="submit">Salvar Doação</button>
        </div>
      </form>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>Data</th>
          <th>Instituição</th>
          <th>Categoria</th>
          <th class="text-end">Valor (BRL)</th>
          <th>Status</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr><td colspan="6" class="text-center text-muted">Sem registros</td></tr>
        <?php else: foreach ($items as $d): ?>
          <tr>
            <td><?= htmlspecialchars($d['data']) ?></td>
            <td>
              <div class="fw-semibold"><?= htmlspecialchars($d['instituicao']) ?></div>
              <div class="text-muted small">CNPJ: <?= htmlspecialchars($d['cnpj'] ?? '-') ?></div>
              <div class="text-muted small">Desc: <?= htmlspecialchars($d['descricao'] ?? '-') ?></div>
            </td>
            <td><?= htmlspecialchars($d['categoria'] ?? '-') ?></td>
            <td class="text-end">R$ <?= number_format((float)($d['valor_brl'] ?? 0), 2, ',', '.') ?></td>
            <td>
              <span class="badge <?= ($d['status'] ?? 'ativo')==='ativo'?'bg-success':'bg-secondary' ?>"><?= htmlspecialchars($d['status'] ?? '-') ?></span>
            </td>
            <td>
              <?php if (($d['status'] ?? 'ativo') === 'ativo'): ?>
              <form method="post" action="/admin/donations/cancel" class="d-inline" onsubmit="return confirm('Cancelar esta doação?');">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Core\Auth::csrf()) ?>">
                <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                <button class="btn btn-sm btn-outline-danger">Cancelar</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
