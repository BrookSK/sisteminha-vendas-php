<?php /** @var array $items */ /** @var string $tab */ /** @var array $projects */ /** @var array $users */ /** @var int $adminId */ ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Demandas</h3>
    <div>
      <a href="/admin/demands?tab=pendentes" class="btn btn-sm <?= ($tab==='pendentes'?'btn-primary':'btn-outline-primary') ?>">Pendentes</a>
      <a href="/admin/demands?tab=entregues" class="btn btn-sm <?= ($tab==='entregues'?'btn-primary':'btn-outline-primary') ?>">Entregues</a>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header">Nova Demanda</div>
    <div class="card-body">
      <form class="row g-2" method="post" action="/admin/demands/create">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Core\Auth::csrf()) ?>">
        <div class="col-md-3">
          <label class="form-label">Tipo/Descrição</label>
          <input type="text" name="type_desc" class="form-control" required placeholder="Ex: Bug, Ajuste, Ideia">
        </div>
        <div class="col-md-4">
          <label class="form-label">Título</label>
          <input type="text" name="title" class="form-control" required placeholder="Ex: Corrigir bug do login">
        </div>
        <div class="col-md-2">
          <label class="form-label">Prazo</label>
          <input type="date" name="due_date" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Prioridade</label>
          <select name="priority" class="form-select">
            <option value="baixa">Baixa</option>
            <option value="media">Média</option>
            <option value="alta">Alta</option>
            <option value="urgente">Urgente</option>
            <option value="ideia">Ideia</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Projeto</label>
          <select name="project_id" class="form-select">
            <option value="">-</option>
            <?php foreach (($projects ?? []) as $p): ?>
              <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Responsável</label>
          <select name="assignee_id" class="form-select">
            <?php foreach (($users ?? []) as $u): $sel = ((int)$adminId === (int)$u['id']) ? 'selected' : ''; ?>
              <option value="<?= (int)$u['id'] ?>" <?= $sel ?>><?= htmlspecialchars($u['name'] ?? ('#'.$u['id'])) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="pendente">Pendente</option>
            <option value="ideia">Ideia</option>
            <option value="trabalhando">Trabalhando</option>
            <option value="estimativa">Estimativa</option>
            <option value="recusado">Recusado</option>
            <option value="aguardando">Aguardando</option>
            <option value="entregue">Entregue</option>
            <option value="arquivado">Arquivado</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Classificação</label>
          <select name="classification" class="form-select">
            <option value="">-</option>
            <option value="erro_garantia">Erro / Garantia</option>
            <option value="ajuste_operacional">Ajuste / Operacional</option>
            <option value="alteracao_evolutiva">Alteração / Evolutiva</option>
            <option value="orcamento_estimativa">Orçamento / Estimativa</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Descrição</label>
          <textarea name="details" rows="3" class="form-control" placeholder="Detalhamento da demanda"></textarea>
        </div>
        <div class="col-12">
          <button class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>

  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
    <?php if (empty($items)): ?>
      <div class="col"><div class="text-muted">Sem demandas</div></div>
    <?php else: foreach ($items as $d): ?>
      <div class="col">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="badge text-bg-secondary"><?= htmlspecialchars(strtoupper($d['priority'])) ?></span>
              <span class="badge text-bg-light text-dark"><?= htmlspecialchars($d['status']) ?></span>
            </div>
            <div class="fw-semibold mb-1"><?= htmlspecialchars($d['title']) ?></div>
            <div class="small text-muted mb-2">Resp: <?= htmlspecialchars($d['assignee_name'] ?? '-') ?> <?= isset($d['project_name']) && $d['project_name'] ? '· Projeto: '.htmlspecialchars($d['project_name']) : '' ?></div>
            <div class="small">Prazo: <strong><?= htmlspecialchars($d['due_date'] ?? '-') ?></strong></div>
            <div class="small">Classificação: <?= htmlspecialchars($d['classification'] ?? '-') ?></div>
            <div class="mt-2">
              <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editDemandModal<?= (int)$d['id'] ?>">Editar</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Editar Demanda -->
      <div class="modal fade" id="editDemandModal<?= (int)$d['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <form method="post" action="/admin/demands/update">
              <div class="modal-header">
                <h5 class="modal-title">Editar Demanda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Core\Auth::csrf()) ?>">
                <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                <div class="row g-2">
                  <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <input type="text" name="type_desc" class="form-control" value="<?= htmlspecialchars($d['type_desc']) ?>" required>
                  </div>
                  <div class="col-md-5">
                    <label class="form-label">Título</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($d['title']) ?>" required>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Prazo</label>
                    <input type="date" name="due_date" class="form-control" value="<?= htmlspecialchars($d['due_date'] ?? '') ?>">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Prioridade</label>
                    <select name="priority" class="form-select">
                      <?php foreach (['baixa','media','alta','urgente','ideia'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= ($d['priority']===$opt?'selected':'') ?>><?= ucfirst($opt) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                      <?php foreach (['pendente','ideia','trabalhando','estimativa','recusado','aguardando','entregue','arquivado'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= ($d['status']===$opt?'selected':'') ?>><?= ucfirst($opt) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Projeto</label>
                    <select name="project_id" class="form-select">
                      <option value="">-</option>
                      <?php foreach (($projects ?? []) as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= ((int)($d['project_id'] ?? 0)===(int)$p['id']?'selected':'') ?>><?= htmlspecialchars($p['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-5">
                    <label class="form-label">Responsável</label>
                    <select name="assignee_id" class="form-select">
                      <?php foreach (($users ?? []) as $u): ?>
                        <option value="<?= (int)$u['id'] ?>" <?= ((int)($d['assignee_id'] ?? 0)===(int)$u['id']?'selected':'') ?>><?= htmlspecialchars($u['name'] ?? ('#'.$u['id'])) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Classificação</label>
                    <select name="classification" class="form-select">
                      <?php $opts=[''=>'-','erro_garantia'=>'Erro / Garantia','ajuste_operacional'=>'Ajuste / Operacional','alteracao_evolutiva'=>'Alteração / Evolutiva','orcamento_estimativa'=>'Orçamento / Estimativa'];
                      foreach ($opts as $val=>$lab): ?>
                        <option value="<?= $val ?>" <?= (($d['classification'] ?? '')===$val?'selected':'') ?>><?= $lab ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Descrição</label>
                    <textarea name="details" class="form-control" rows="4"><?= htmlspecialchars($d['details'] ?? '') ?></textarea>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>
