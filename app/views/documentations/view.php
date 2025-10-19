<?php
use Core\Auth;
$csrf = Auth::csrf();
$doc = $doc ?? null;
if (!$doc) { echo '<div class="alert alert-warning">Documento não encontrado.</div>'; return; }
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Documentação: <?= htmlspecialchars($doc['title']) ?></h3>
  <a class="btn btn-outline-secondary" href="/admin/documentations">Voltar</a>
</div>
<div class="row g-3">
  <div class="col-md-4">
    <div class="card"><div class="card-body">
      <div><strong>Status:</strong> <span class="badge text-bg-secondary"><?= htmlspecialchars($doc['status']) ?></span></div>
      <div class="mt-2"><strong>Visibilidade interna:</strong> <?= htmlspecialchars($doc['internal_visibility'] ?? 'all') ?></div>
      <div class="mt-2"><strong>Publicado externo:</strong> <?= ((int)($doc['published'] ?? 0)===1) ? 'Sim' : 'Não' ?></div>
      <?php if ((int)($doc['published'] ?? 0)===1 && !empty($doc['external_slug'])): ?>
        <div class="mt-2">
          <div><strong>Link público:</strong></div>
          <code>/docs?s=<?= htmlspecialchars($doc['external_slug']) ?>&email=SEU_EMAIL</code>
        </div>
      <?php endif; ?>
      <div class="mt-2"><strong>Criado em:</strong> <?= htmlspecialchars($doc['created_at'] ?? '') ?></div>
      <?php if (!empty($doc['updated_at'])): ?>
        <div class="mt-1"><strong>Editado em:</strong> <?= htmlspecialchars($doc['updated_at']) ?></div>
      <?php endif; ?>
    </div></div>

    <?php if ((Auth::user()['role'] ?? 'seller') === 'admin'): ?>
    <div class="card mt-3"><div class="card-body">
      <h6 class="mb-3">Publicação Externa</h6>
      <form id="publishForm" method="post" action="/admin/documentations/publish" class="d-flex align-items-end gap-2 flex-wrap">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="id" value="<?= (int)$doc['id'] ?>">
        <div>
          <label class="form-label">Publicado?</label>
          <select name="published" class="form-select">
            <option value="0" <?= ((int)($doc['published'] ?? 0)===0)?'selected':'' ?>>Não</option>
            <option value="1" <?= ((int)($doc['published'] ?? 0)===1)?'selected':'' ?>>Sim</option>
          </select>
        </div>
        <button id="publishSubmitBtn" class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#publishConfirmModal">Salvar</button>
      </form>
      <hr>
      <form method="post" action="/admin/documentations/set-slug" class="d-flex align-items-end gap-2 flex-wrap">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="id" value="<?= (int)$doc['id'] ?>">
        <div class="flex-grow-1">
          <label class="form-label">Slug público</label>
          <input type="text" name="slug" class="form-control" placeholder="ex: guia-onboarding" value="<?= htmlspecialchars($doc['external_slug'] ?? '') ?>">
        </div>
        <button class="btn btn-outline-primary" type="submit">Definir Slug</button>
      </form>
    </div></div>

    <div class="card mt-3"><div class="card-body">
      <h6 class="mb-3">E-mails Autorizados</h6>
      <form method="post" action="/admin/documentations/email-add" class="d-flex align-items-end gap-2 flex-wrap mb-3">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="id" value="<?= (int)$doc['id'] ?>">
        <div class="flex-grow-1">
          <label class="form-label">Novo e-mail</label>
          <input type="email" name="email" class="form-control" placeholder="usuario@dominio.com">
        </div>
        <button class="btn btn-outline-success" type="submit">Adicionar</button>
      </form>
      <div class="table-responsive">
        <table class="table table-sm table-striped">
          <thead><tr><th>E-mail</th><th style="width:120px;">Ações</th></tr></thead>
          <tbody>
            <?php foreach (($emails ?? []) as $em): ?>
              <tr>
                <td><?= htmlspecialchars($em['email']) ?></td>
                <td>
                  <form method="post" action="/admin/documentations/email-remove" onsubmit="return confirm('Remover este e-mail?');">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="id" value="<?= (int)$doc['id'] ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($em['email']) ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit">Remover</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div></div>
    <?php endif; ?>
  </div>
  <div class="col-md-8">
    <div class="card"><div class="card-body">
      <div class="content-body">
        <?= $doc['content'] ?? '<em>Sem conteúdo.</em>' ?>
      </div>
    </div></div>

    <div class="card mt-3"><div class="card-body">
      <h6 class="mb-3">Comentários</h6>
      <div class="mb-3">
        <?php if (empty($comments)): ?>
          <div class="text-muted">Nenhum comentário ainda.</div>
        <?php else: ?>
          <div class="list-group">
            <?php foreach (($comments ?? []) as $c): ?>
              <div class="list-group-item">
                <div class="small text-muted mb-1">
                  <strong><?= htmlspecialchars($c['user_name'] ?? 'Usuário') ?></strong>
                  <span class="ms-2">#<?= (int)$c['id'] ?></span>
                  <span class="ms-2"><?= htmlspecialchars($c['created_at'] ?? '') ?></span>
                </div>
                <div><?= nl2br(htmlspecialchars($c['content'])) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <form method="post" action="/admin/documentations/comment-add">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="id" value="<?= (int)$doc['id'] ?>">
        <div class="mb-2">
          <label class="form-label">Novo comentário</label>
          <textarea name="content" rows="3" class="form-control" placeholder="Digite seu comentário..."></textarea>
        </div>
        <button class="btn btn-outline-primary" type="submit">Enviar</button>
      </form>
    </div></div>
  </div>
</div>

<!-- Publish confirm modal -->
<div class="modal fade" id="publishConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmar alteração de publicação</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Tem certeza que deseja <span id="pubActionText"></span> esta documentação?</p>
        <p class="small text-muted mb-0">Ao publicar, o conteúdo poderá ser acessado via link público para e-mails autorizados.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="confirmPublishBtn">Confirmar</button>
      </div>
    </div>
  </div>
 </div>

<script>
  (function(){
    const form = document.getElementById('publishForm');
    const trigger = document.getElementById('publishSubmitBtn');
    const actionText = document.getElementById('pubActionText');
    const confirmBtn = document.getElementById('confirmPublishBtn');
    if (form && trigger && actionText && confirmBtn) {
      trigger.addEventListener('click', function(){
        const val = (form.querySelector('select[name="published"]').value || '0');
        actionText.textContent = (val === '1') ? 'PUBLICAR' : 'DESPUBLICAR';
      });
      confirmBtn.addEventListener('click', function(){
        form.submit();
      });
    }
  })();
  </script>
