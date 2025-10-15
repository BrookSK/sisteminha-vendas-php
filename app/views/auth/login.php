<?php use Core\Auth; ?>
<div class="row justify-content-center">
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3">Login</h5>
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" action="/login">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
          <div class="mb-3">
            <label class="form-label">E-mail</label>
            <input type="email" class="form-control" name="email" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Senha</label>
            <div class="input-group">
              <input type="password" class="form-control" name="password" id="login_password" required>
              <button class="btn btn-outline-secondary" type="button" id="toggleLoginPwd" aria-label="Mostrar/ocultar senha">👁️</button>
            </div>
          </div>
          <button class="btn btn-primary w-100" type="submit">Entrar</button>
          <div class="form-text mt-2">Se você não tiver usuário, entre em contato com o suporte.</div>
        </form>
        <script>
        (function(){
          const btn = document.getElementById('toggleLoginPwd');
          const input = document.getElementById('login_password');
          if(btn && input){
            btn.addEventListener('click', function(){
              const t = input.getAttribute('type') === 'password' ? 'text' : 'password';
              input.setAttribute('type', t);
            });
          }
        })();
        </script>
      </div>
    </div>
  </div>
</div>
