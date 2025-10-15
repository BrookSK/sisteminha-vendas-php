<?php use Core\Auth; ?>
<div class="row">
  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3"><?= htmlspecialchars($title ?? 'Venda') ?></h5>
        <form method="post" action="<?= htmlspecialchars($action) ?>" id="sale-form" data-rate="<?= htmlspecialchars((string)$rate) ?>" data-emb="<?= htmlspecialchars((string)($emb ?? '9.70')) ?>">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Data/Hora</label>
              <input type="datetime-local" class="form-control" name="created_at" value="<?= isset($sale['created_at']) ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($sale['created_at']))) : htmlspecialchars(date('Y-m-d\TH:i')) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Número do Pedido</label>
              <input type="text" class="form-control" name="numero_pedido" value="<?= htmlspecialchars($sale['numero_pedido'] ?? '') ?>" placeholder="Opcional">
            </div>
            <div class="col-md-4">
              <label class="form-label">Suite (ex: AB1)</label>
              <input type="text" class="form-control" name="suite" value="<?= htmlspecialchars($sale['suite'] ?? '') ?>" placeholder="AB1">
            </div>

            <div class="col-md-6">
              <label class="form-label">Cliente</label>
              <select class="form-select" name="cliente_id" required>
                <option value="">Selecione...</option>
                <?php foreach ($clients as $c): ?>
                  <option value="<?= (int)$c['id'] ?>" <?= isset($sale['cliente_id']) && (int)$sale['cliente_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['nome']) ?> <?= $c['suite'] ? '(' . htmlspecialchars($c['suite']) . ')' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">Caso o cliente não exista, cadastre em Clientes.</div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Peso (kg)</label>
              <input type="number" step="0.01" min="0" class="form-control sale-input" name="peso_kg" value="<?= htmlspecialchars((string)($sale['peso_kg'] ?? '0')) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Valor Produto (USD)</label>
              <input type="number" step="0.01" min="0" class="form-control sale-input" name="valor_produto_usd" value="<?= htmlspecialchars((string)($sale['valor_produto_usd'] ?? '0')) ?>">
            </div>

            <div class="col-md-3">
              <label class="form-label">Taxa de Serviço (USD)</label>
              <input type="number" step="0.01" min="0" class="form-control sale-input" name="taxa_servico_usd" value="<?= htmlspecialchars((string)($sale['taxa_servico_usd'] ?? '0')) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Serviço Compra (USD)</label>
              <input type="number" step="0.01" min="0" class="form-control sale-input" name="servico_compra_usd" value="<?= htmlspecialchars((string)($sale['servico_compra_usd'] ?? '0')) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Produto Compra (USD)</label>
              <input type="number" step="0.01" min="0" class="form-control sale-input" name="produto_compra_usd" value="<?= htmlspecialchars((string)($sale['produto_compra_usd'] ?? '0')) ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label">Link do Produto (URL)</label>
              <input type="url" class="form-control" name="produto_link" value="<?= htmlspecialchars($sale['produto_link'] ?? '') ?>" placeholder="https://...">
            </div>
            <div class="col-md-3">
              <label class="form-label">Origem</label>
              <?php $origem = $sale['origem'] ?? ''; ?>
              <select class="form-select" name="origem">
                <option value="">-- selecione --</option>
                <option value="organica" <?= $origem==='organica'?'selected':'' ?>>Orgânica</option>
                <option value="lead" <?= $origem==='lead'?'selected':'' ?>>Lead</option>
                <option value="pay-per-click" <?= $origem==='pay-per-click'?'selected':'' ?>>Pay-per-click</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label d-block">NC tax (7%)</label>
              <?php $nc = (int)($sale['nc_tax'] ?? 0) === 1; ?>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="ncTax" name="nc_tax" value="1" <?= $nc?'checked':'' ?>>
                <label class="form-check-label" for="ncTax">Aplicar?</label>
              </div>
            </div>

            <div class="col-12"><hr></div>

            <div class="col-md-3">
              <label class="form-label">Frete Correios (BRL)</label>
              <input type="number" class="form-control" name="frete_brl" value="<?= htmlspecialchars((string)($sale['frete_brl'] ?? '0')) ?>" readonly>
            </div>
            <div class="col-md-3">
              <label class="form-label">Frete Correios (USD)</label>
              <input type="number" class="form-control" name="frete_usd" value="<?= htmlspecialchars((string)($sale['frete_usd'] ?? '0')) ?>" readonly>
            </div>
            <div class="col-md-3">
              <label class="form-label">Bruto (USD)</label>
              <input type="number" class="form-control" name="bruto_usd" value="<?= htmlspecialchars((string)($sale['bruto_usd'] ?? '0')) ?>" readonly>
            </div>
            <div class="col-md-3">
              <label class="form-label">Bruto (BRL)</label>
              <input type="number" class="form-control" name="bruto_brl" value="<?= htmlspecialchars((string)($sale['bruto_brl'] ?? '0')) ?>" readonly>
            </div>
            <div class="col-md-3">
              <label class="form-label">Embalagem (USD)</label>
              <input type="number" class="form-control" name="embalagem_usd" value="<?= htmlspecialchars((string)($sale['embalagem_usd'] ?? '0')) ?>" readonly>
            </div>

            <div class="col-md-3">
              <label class="form-label d-block">Frete aplicado manual?</label>
              <?php $freteAplic = isset($sale['frete_manual_valor']) && $sale['frete_manual_valor'] !== null; ?>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="freteAplic" name="frete_aplicado" value="1" <?= $freteAplic?'checked':'' ?>>
                <label class="form-check-label" for="freteAplic">Informar valor manual</label>
              </div>
            </div>
            <div class="col-md-3">
              <label class="form-label">Frete manual (USD)</label>
              <input type="number" step="0.01" min="0" class="form-control" name="frete_manual_valor" value="<?= htmlspecialchars((string)($sale['frete_manual_valor'] ?? '')) ?>" placeholder="0.00">
              <div class="form-text">Se informado, será gravado como frete manual.</div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Líquido (USD)</label>
              <input type="number" class="form-control" name="liquido_usd" value="<?= htmlspecialchars((string)($sale['liquido_usd'] ?? '0')) ?>" readonly>
            </div>
            <div class="col-md-3">
              <label class="form-label">Líquido (BRL)</label>
              <input type="number" class="form-control" name="liquido_brl" value="<?= htmlspecialchars((string)($sale['liquido_brl'] ?? '0')) ?>" readonly>
            </div>
            <div class="col-md-3">
              <label class="form-label">Comissão (USD)</label>
              <input type="number" class="form-control" name="comissao_usd" value="<?= htmlspecialchars((string)($sale['comissao_usd'] ?? '0')) ?>" readonly>
            </div>
            <div class="col-md-3">
              <label class="form-label">Comissão (BRL)</label>
              <input type="number" class="form-control" name="comissao_brl" value="<?= htmlspecialchars((string)($sale['comissao_brl'] ?? '0')) ?>" readonly>
            </div>
          </div>

          <div class="d-flex gap-2 mt-3">
            <a href="/admin/sales" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
