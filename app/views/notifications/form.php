<?php use Core\Auth; ?>
<div class="container py-3">
  <h3 class="mb-3"><?= htmlspecialchars($title ?? 'Nova Notificação') ?></h3>
  <form method="post" action="<?= htmlspecialchars($action ?? '/admin/notifications/create') ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">

    <div class="mb-3">
      <label class="form-label">Título</label>
      <input type="text" class="form-control" name="title" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Mensagem</label>
      <textarea class="form-control" rows="6" name="message" required></textarea>
    </div>

    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Tipo</label>
        <select class="form-select" name="type">
          <option>Informação</option>
          <option>Alerta</option>
          <option>Atualização do Sistema</option>
          <option>Meta / Desempenho</option>
          <option>Outro</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
          <option value="ativa" selected>Ativa</option>
          <option value="arquivada">Arquivada</option>
          <option value="expirada">Expirada</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Destinatários</label>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="targets[]" value="admin" id="t_admin">
          <label class="form-check-label" for="t_admin">Admins</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="targets[]" value="manager" id="t_manager" checked>
          <label class="form-check-label" for="t_manager">Gerentes</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="targets[]" value="seller" id="t_seller" checked>
          <label class="form-check-label" for="t_seller">Vendedores</label>
        </div>
      </div>
    </div>

    <div class="mt-4">
      <button class="btn btn-primary" type="submit">Publicar</button>
      <a class="btn btn-outline-secondary" href="/admin/notifications">Cancelar</a>
    </div>
  </form>
</div>
