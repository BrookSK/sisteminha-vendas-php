<?php /** @var array|null $product */ /** @var array $links */ ?>
<div class="row">
  <div class="col-md-8 col-lg-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3"><?= htmlspecialchars($title ?? 'Produto do Simulador') ?></h5>
        <form method="post" action="<?= htmlspecialchars($action ?? '') ?>">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_csrf ?? \Core\Auth::csrf()) ?>">
          <div class="mb-3">
            <label class="form-label">Nome</label>
            <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($product['nome'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">SKU</label>
            <input type="text" name="sku" class="form-control" value="<?= htmlspecialchars($product['sku'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Marca</label>
            <input type="text" name="marca" class="form-control" value="<?= htmlspecialchars($product['marca'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Peso (Kg)</label>
            <input type="number" step="0.01" min="0" name="peso_kg" class="form-control" value="<?= htmlspecialchars((string)($product['peso_kg'] ?? '0')) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Link da imagem (URL)</label>
            <input type="url" name="image_url" class="form-control" placeholder="https://..." value="<?= htmlspecialchars($product['image_url'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Links de compra</label>
            <div id="links-container" class="vstack gap-2">
              <?php if (!empty($links)): ?>
                <?php foreach ($links as $lnk): ?>
                  <div class="input-group">
                    <input type="url" name="links[]" class="form-control" placeholder="https://..." value="<?= htmlspecialchars($lnk['url'] ?? '') ?>">
                    <button type="button" class="btn btn-outline-danger btn-remove-link">X</button>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="input-group">
                  <input type="url" name="links[]" class="form-control" placeholder="https://...">
                  <button type="button" class="btn btn-outline-danger btn-remove-link">X</button>
                </div>
              <?php endif; ?>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btn-add-link">Adicionar link</button>
          </div>
          <div class="d-flex gap-2">
            <a href="/admin/simulator-products" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
  (function(){
    const container = document.getElementById('links-container');
    const btnAdd = document.getElementById('btn-add-link');
    if (btnAdd && container) {
      btnAdd.addEventListener('click', function(){
        const div = document.createElement('div');
        div.className = 'input-group mt-1';
        div.innerHTML = '<input type="url" name="links[]" class="form-control" placeholder="https://...">' +
          '<button type="button" class="btn btn-outline-danger btn-remove-link">X</button>';
        container.appendChild(div);
      });
    }
    if (container) {
      container.addEventListener('click', function(ev){
        const t = ev.target;
        if (t && t.classList.contains('btn-remove-link')) {
          const grp = t.closest('.input-group');
          if (grp && container.children.length > 1) {
            grp.remove();
          } else if (grp) {
            grp.querySelector('input').value = '';
          }
        }
      });
    }
  })();
</script>
