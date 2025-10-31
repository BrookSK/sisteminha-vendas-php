<?php /** @var array|null $user */ /** @var string $action */ /** @var array $roles */ /** @var string $_csrf */ ?>
<div class="container py-3" style="max-width: 720px;">
  <h3 class="mb-3"><?= htmlspecialchars($title ?? 'Usuário') ?></h3>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= htmlspecialchars($action) ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_csrf) ?>">

    <div class="mb-3">
      <label class="form-label">Nome</label>
      <input type="text" class="form-control" name="name" required value="<?= htmlspecialchars($user['name'] ?? '') ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">E-mail</label>
      <input type="email" class="form-control" name="email" required value="<?= htmlspecialchars($user['email'] ?? '') ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">WhatsApp (opcional)</label>
      <input type="text" class="form-control" name="whatsapp" placeholder="Ex: 5599999999999" value="<?= htmlspecialchars($user['whatsapp'] ?? '') ?>">
      <div class="form-text">Somente números com DDI (ex: 55...).</div>
    </div>

    <div class="mb-3">
      <label class="form-label">Senha <?= $user ? '(deixe em branco para manter)' : '' ?></label>
      <div class="input-group">
        <input type="password" class="form-control" name="password" id="user_password" <?= $user ? '' : 'required' ?>>
        <button class="btn btn-outline-secondary" type="button" id="toggleUserPwd" aria-label="Mostrar/ocultar senha">👁️</button>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Perfil</label>
      <select name="role" class="form-select">
        <?php $current = $user['role'] ?? 'seller'; foreach ($roles as $k=>$label): ?>
          <option value="<?= htmlspecialchars($k) ?>" <?= $current === $k ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <?php $supList = $supervisors ?? []; $supSel = (int)($user['supervisor_user_id'] ?? 0); ?>
    <div class="mb-3" id="supervisorRow" style="display: none;">
      <label class="form-label">Supervisor</label>
      <select name="supervisor_user_id" class="form-select">
        <option value="">Selecione...</option>
        <?php foreach ($supList as $s): ?>
          <option value="<?= (int)$s['id'] ?>" <?= $supSel===(int)$s['id']?'selected':'' ?>><?= htmlspecialchars($s['name'].' <'.$s['email'].'>') ?></option>
        <?php endforeach; ?>
      </select>
      <div class="form-text">Obrigatório para usuários com perfil Trainee.</div>
    </div>

    <div class="form-check form-switch mb-3">
      <?php $isActive = (int)($user['ativo'] ?? 1) === 1; ?>
      <input class="form-check-input" type="checkbox" role="switch" id="ativoSwitch" name="ativo" value="1" <?= $isActive ? 'checked' : '' ?>>
      <label class="form-check-label" for="ativoSwitch">Usuário ativo</label>
    </div>

    <div class="d-flex gap-2">
      <button class="btn btn-primary" type="submit">Salvar</button>
      <a href="/admin/users" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
  <script>
  (function(){
    const btn = document.getElementById('toggleUserPwd');
    const input = document.getElementById('user_password');
    const roleSel = document.querySelector('select[name="role"]');
    const supRow = document.getElementById('supervisorRow');
    if(btn && input){
      btn.addEventListener('click', function(){
        const t = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', t);
      });
    }
    function toggleSup(){ if(!roleSel||!supRow) return; var v = roleSel.value||''; supRow.style.display = (v==='trainee')? 'block':'none'; }
    if(roleSel){ roleSel.addEventListener('change', toggleSup); toggleSup(); }
  })();
  </script>
</div>
