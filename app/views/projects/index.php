<?php /** @var array $items */ ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Projetos</h3>
  </div>

  <div class="card mb-3">
    <div class="card-header">Novo Projeto</div>
    <div class="card-body">
      <form class="row g-2" method="post" action="/admin/projects/create">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Core\Auth::csrf()) ?>">
        <div class="col-md-4">
          <label class="form-label">Nome do projeto</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="nao_iniciada">Não iniciada</option>
            <option value="em_construcao">Em construção</option>
            <option value="aguardando">Aguardando</option>
            <option value="em_teste">Em teste</option>
            <option value="em_revisao">Em revisão</option>
            <option value="em_manutencao">Em manutenção</option>
            <option value="cancelado">Cancelado</option>
            <option value="finalizado">Finalizado</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Início</label>
          <input type="date" name="start_date" class="form-control" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Prazo</label>
          <input type="date" name="due_date" class="form-control" required>
        </div>
        <div class="col-12">
          <label class="form-label">Descrição (opcional)</label>
          <textarea name="description" rows="2" class="form-control"></textarea>
        </div>
        <div class="col-12">
          <button class="btn btn-primary">Criar</button>
        </div>
      </form>
    </div>
  </div>

  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
    <?php if (empty($items)): ?>
      <div class="col"><div class="text-muted">Sem projetos</div></div>
    <?php else: foreach ($items as $p): ?>
      <div class="col">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <div class="fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
              <span class="badge text-bg-light text-dark"><?= htmlspecialchars($p['status']) ?></span>
            </div>
            <div class="small text-muted">Início: <?= htmlspecialchars($p['start_date']) ?> · Prazo: <?= htmlspecialchars($p['due_date']) ?></div>
            <div class="mt-2"><a class="btn btn-sm btn-outline-primary" href="/admin/projects/view?id=<?= (int)$p['id'] ?>">Abrir</a></div>
          </div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>
