<?php /** @var string $action */ ?>
<?php use Core\Auth; ?>
<div class="container py-3">
  <h3 class="mb-3"><?= htmlspecialchars($title ?? 'Nova Compra') ?></h3>
  <form method="post" action="<?= htmlspecialchars($action) ?>" class="row g-3">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_csrf ?? Auth::csrf()) ?>">

    <div class="col-md-3">
      <label class="form-label">Venda (opcional)</label>
      <input type="hidden" name="venda_id" id="venda_id" value="">
      <input type="text" class="form-control" id="venda_search" placeholder="Buscar número do pedido ou ID">
      <div id="venda_dd" class="list-group" style="position:absolute; z-index:1050; display:none;"></div>
    </div>
    <div class="col-md-3">
      <label class="form-label">Suite</label>
      <input type="text" class="form-control" name="suite" maxlength="3">
    </div>
    <div class="col-md-6">
      <label class="form-label">Cliente (nome)</label>
      <input type="text" class="form-control" name="cliente_nome" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Contato do Cliente</label>
      <input type="text" class="form-control" name="cliente_contato">
    </div>
    <div class="col-md-6">
      <label class="form-label">Link do Produto/Compra</label>
      <input type="url" class="form-control" name="produto_link" required>
    </div>

    <div class="col-md-3">
      <label class="form-label">Valor (USD)</label>
      <input type="number" step="0.01" min="0" class="form-control" name="valor_usd" required>
    </div>
    <div class="col-md-3 form-check mt-4">
      <input class="form-check-input" type="checkbox" value="1" id="nc_tax" name="nc_tax">
      <label class="form-check-label" for="nc_tax">NC 7%</label>
    </div>

    <div class="col-md-3 form-check mt-4">
      <input class="form-check-input" type="checkbox" value="1" id="frete_aplicado" name="frete_aplicado">
      <label class="form-check-label" for="frete_aplicado">Frete Aplicado</label>
    </div>
    <div class="col-md-3">
      <label class="form-label">Frete (USD)</label>
      <input type="number" step="0.01" min="0" class="form-control" name="frete_valor">
    </div>

    <div class="col-md-3 form-check mt-4">
      <input class="form-check-input" type="checkbox" value="1" id="comprado" name="comprado">
      <label class="form-check-label" for="comprado">Comprado</label>
    </div>
    <div class="col-md-3">
      <label class="form-label">Data da Compra</label>
      <input type="date" class="form-control" name="data_compra">
    </div>
    <div class="col-md-6">
      <label class="form-label">Responsável</label>
      <input type="hidden" class="form-control" name="responsavel_id" id="resp_id" value="<?= htmlspecialchars((string)(Auth::user()['id'] ?? '')) ?>">
      <input type="text" class="form-control" id="resp_search" placeholder="Buscar usuário">
      <div id="resp_dd" class="list-group" style="position:absolute; z-index:1050; display:none;"></div>
    </div>

    <div class="col-12">
      <label class="form-label">Observações</label>
      <textarea class="form-control" name="observacoes" rows="3"></textarea>
    </div>

    <div class="col-12 d-flex gap-2 mt-2">
      <a href="/admin/purchases" class="btn btn-outline-secondary">Cancelar</a>
      <button type="submit" class="btn btn-primary">Salvar</button>
    </div>
  </form>
</div>
