<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="text-center">
        <h1 class="display-5 fw-bold">403</h1>
        <p class="lead">Acesso negado</p>
        <p class="text-muted">Você não tem permissão para acessar esta página com seu perfil atual.</p>
        <div class="mt-4">
          <a href="/admin" class="btn btn-primary">Ir para o Dashboard</a>
          <a href="/admin/notifications" class="btn btn-outline-secondary ms-2">Ver Notificações</a>
        </div>
        <?php if (!empty($required_roles)): ?>
          <div class="mt-4 small text-muted">Requer: <?= htmlspecialchars(implode(', ', $required_roles)) ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
