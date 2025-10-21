<?php /** @var string|null $error */ ?>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">Debug de Comissões (Protegido)</h5>
          <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
          <form method="get" action="/admin/commissions/debug">
            <div class="mb-3">
              <label class="form-label">Senha</label>
              <input type="password" class="form-control" name="pwd" required>
              <div class="form-text">Informe a senha definida nas configurações do admin.</div>
            </div>
            <button type="submit" class="btn btn-primary">Entrar</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
