<?php /** @var array $client */ /** @var array $counts */ /** @var int $id */ ?>
<div class="container py-4">
  <h3 class="mb-3">Confirmar exclusão do cliente</h3>
  <div class="alert alert-warning">
    <p class="mb-1"><strong><?= htmlspecialchars($client['nome'] ?? ($client['email'] ?? 'Cliente #'.$id)) ?></strong></p>
    <p class="mb-2">Este cliente possui vendas vinculadas. Ao confirmar, as vendas serão <strong>desvinculadas (cliente_id = NULL)</strong> e o cliente será excluído.</p>
    <ul class="mb-0">
      <li>Vendas (legado): <strong><?= (int)($counts['vendas'] ?? 0) ?></strong></li>
      <li>Vendas Internacionais: <strong><?= (int)($counts['vendas_internacionais'] ?? 0) ?></strong></li>
      <li>Vendas Nacionais: <strong><?= (int)($counts['vendas_nacionais'] ?? 0) ?></strong></li>
    </ul>
  </div>
  <form method="post" action="/admin/clients/delete">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Core\Auth::csrf()) ?>">
    <input type="hidden" name="id" value="<?= (int)$id ?>">
    <input type="hidden" name="force" value="1">
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="/admin/clients">Cancelar</a>
      <button type="submit" class="btn btn-danger">Confirmar e excluir</button>
    </div>
  </form>
</div>
