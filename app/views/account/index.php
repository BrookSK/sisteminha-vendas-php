<?php /** @var array $user */ ?>
<?php use Core\Auth; ?>
<div class="container py-4" style="max-width: 860px;">
  <h3 class="mb-4">Minha Conta</h3>

  <div class="row g-4">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">Perfil</div>
        <div class="card-body">
          <form method="post" action="/admin/account/update-profile">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
            <div class="mb-3">
              <label class="form-label">Nome</label>
              <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Permissão</label>
              <?php $role = (string)($user['role'] ?? '');
                    $roleMap = ['seller'=>'Vendedor','trainee'=>'Trainee','organic'=>'Orgânico','manager'=>'Gerente','admin'=>'Admin'];
                    $roleLabel = $roleMap[$role] ?? $role; ?>
              <input type="text" class="form-control" value="<?= htmlspecialchars($roleLabel) ?>" readonly>
            </div>
            <button class="btn btn-primary" type="submit">Salvar</button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card">
        <div class="card-header">Alterar Senha</div>
        <div class="card-body">
          <form method="post" action="/admin/account/update-password" onsubmit="return validatePwd();">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
            <div class="mb-3">
              <label class="form-label">Senha atual</label>
              <div class="input-group">
                <input type="password" name="current_password" id="current_password" class="form-control" required>
                <button class="btn btn-outline-secondary" type="button" id="toggleCurrentPwd" aria-label="Mostrar/ocultar senha">👁️</button>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Nova senha</label>
              <div class="input-group">
                <input type="password" name="new_password" id="new_password" class="form-control" minlength="6" required>
                <button class="btn btn-outline-secondary" type="button" id="toggleNewPwd" aria-label="Mostrar/ocultar senha">👁️</button>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Confirmar nova senha</label>
              <div class="input-group">
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" minlength="6" required>
                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPwd" aria-label="Mostrar/ocultar senha">👁️</button>
              </div>
              <div class="form-text" id="pwdHelp"></div>
            </div>
            <button class="btn btn-warning" type="submit">Atualizar Senha</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
function validatePwd(){
  const n = document.getElementById('new_password').value;
  const c = document.getElementById('confirm_password').value;
  const help = document.getElementById('pwdHelp');
  if(n!==c){ help.textContent='As senhas não conferem.'; help.classList.add('text-danger'); return false; }
  help.textContent=''; help.classList.remove('text-danger'); return true;
}
// toggles de visibilidade
(function(){
  const map = [
    {btn:'toggleCurrentPwd', input:'current_password'},
    {btn:'toggleNewPwd', input:'new_password'},
    {btn:'toggleConfirmPwd', input:'confirm_password'},
  ];
  map.forEach(({btn,input})=>{
    const b = document.getElementById(btn);
    const i = document.getElementById(input);
    if(b && i){
      b.addEventListener('click', ()=>{
        const t = i.getAttribute('type') === 'password' ? 'text' : 'password';
        i.setAttribute('type', t);
      });
    }
  });
})();
</script>
