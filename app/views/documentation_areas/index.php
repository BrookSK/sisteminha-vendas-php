<?php
use Core\Auth;
$csrf = Auth::csrf();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Áreas Técnicas de Documentação</h3>
  <a class="btn btn-outline-secondary" href="/admin/documentations">Voltar</a>
</div>
<div class="row g-3">
  <div class="col-md-5">
    <div class="card"><div class="card-body">
      <h6 class="mb-3">Nova Área</h6>
      <form method="post" action="/admin/documentation-areas/create" class="d-flex gap-2">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <input type="text" name="name" class="form-control" placeholder="Nome da área">
        <button class="btn btn-primary" type="submit">Adicionar</button>
      </form>
    </div></div>
  </div>
  <div class="col-md-7">
    <div class="card"><div class="card-body">
      <h6 class="mb-3">Áreas Cadastradas</h6>
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead><tr><th>Nome</th><th style="width: 220px;">Ações</th></tr></thead>
          <tbody>
            <?php foreach (($areas ?? []) as $a): ?>
            <tr>
              <td>
                <form method="post" action="/admin/documentation-areas/update" class="d-flex gap-2">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                  <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($a['name']) ?>">
                  <button class="btn btn-outline-primary btn-sm" type="submit">Salvar</button>
                </form>
              </td>
              <td>
                <form method="post" action="/admin/documentation-areas/delete" onsubmit="return confirm('Excluir esta área?');">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                  <button class="btn btn-outline-danger btn-sm" type="submit">Excluir</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div></div>
  </div>
</div>
