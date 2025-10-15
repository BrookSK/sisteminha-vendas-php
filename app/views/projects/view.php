<?php /** @var array $project */ /** @var array $demands */ ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Projeto: <?= htmlspecialchars($project['name'] ?? '') ?></h3>
    <a href="/admin/projects" class="btn btn-outline-secondary btn-sm">Voltar</a>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2">
        <div class="col-md-3"><strong>Status:</strong> <?= htmlspecialchars($project['status'] ?? '-') ?></div>
        <div class="col-md-3"><strong>Início:</strong> <?= htmlspecialchars($project['start_date'] ?? '-') ?></div>
        <div class="col-md-3"><strong>Prazo:</strong> <?= htmlspecialchars($project['due_date'] ?? '-') ?></div>
        <div class="col-12"><strong>Descrição:</strong><br><?= nl2br(htmlspecialchars($project['description'] ?? '-')) ?></div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>Demandas Relacionadas</span>
      <a class="btn btn-sm btn-outline-primary" href="/admin/demands?tab=pendentes">Ver Demandas</a>
    </div>
    <div class="card-body">
      <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
        <?php if (empty($demands)): ?>
          <div class="col"><div class="text-muted">Sem demandas vinculadas</div></div>
        <?php else: foreach ($demands as $d): ?>
          <div class="col">
            <div class="card h-100 shadow-sm">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="badge text-bg-secondary"><?= htmlspecialchars(strtoupper($d['priority'])) ?></span>
                  <span class="badge text-bg-light text-dark"><?= htmlspecialchars($d['status']) ?></span>
                </div>
                <div class="fw-semibold mb-1"><?= htmlspecialchars($d['title']) ?></div>
                <div class="small text-muted mb-2">Resp: <?= htmlspecialchars($d['assignee_name'] ?? '-') ?></div>
                <div class="small">Prazo: <strong><?= htmlspecialchars($d['due_date'] ?? '-') ?></strong></div>
                <div class="small">Classificação: <?= htmlspecialchars($d['classification'] ?? '-') ?></div>
              </div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>
