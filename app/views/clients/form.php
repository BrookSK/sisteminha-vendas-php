<?php use Core\Auth; ?>
<div class="row">
  <div class="col-md-8 col-lg-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3"><?= htmlspecialchars($title ?? 'Cliente') ?></h5>
        <form method="post" action="<?= htmlspecialchars($action) ?>">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
          <div class="mb-3">
            <label class="form-label">Nome</label>
            <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($client['nome'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($client['email'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Telefone</label>
            <input type="text" name="telefone" class="form-control" value="<?= htmlspecialchars($client['telefone'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Suítes por Site</label>
            <div class="input-group mb-2">
              <span class="input-group-text">BR-</span>
              <input type="text" name="suite_br" class="form-control" placeholder="número" value="<?= htmlspecialchars($client['suite_br'] ?? '') ?>">
            </div>
            <div class="input-group mb-2">
              <span class="input-group-text">US-</span>
              <input type="text" name="suite_us" class="form-control" placeholder="número" value="<?= htmlspecialchars($client['suite_us'] ?? '') ?>">
            </div>
            <div class="input-group mb-2">
              <span class="input-group-text">RED-</span>
              <input type="text" name="suite_red" class="form-control" placeholder="número" value="<?= htmlspecialchars($client['suite_red'] ?? '') ?>">
            </div>
            <div class="input-group">
              <span class="input-group-text">GLOB-</span>
              <input type="text" name="suite_globe" class="form-control" placeholder="número" value="<?= htmlspecialchars($client['suite_globe'] ?? '') ?>">
            </div>
            <div class="form-text">Preencha só o número após o prefixo fixo.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Endereço</label>
            <input type="text" name="endereco" class="form-control" value="<?= htmlspecialchars($client['endereco'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Observações</label>
            <textarea name="observacoes" class="form-control" rows="3"><?= htmlspecialchars($client['observacoes'] ?? '') ?></textarea>
          </div>
          <div class="d-flex gap-2">
            <a href="/admin/clients" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
