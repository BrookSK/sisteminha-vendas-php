<?php /** @var string $_csrf */ ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Importar Produtos do Simulador</h3>
    <a href="/admin/simulator-products" class="btn btn-outline-secondary">Voltar para lista</a>
  </div>

  <div class="mb-3">
    <p>Use esta tela para adicionar vários produtos de uma vez via planilha CSV.</p>
    <p class="mb-1">Passos sugeridos:</p>
    <ol class="mb-2">
      <li>Baixe o modelo de planilha.</li>
      <li>Preencha as colunas <strong>sku</strong>, <strong>imagem</strong>, <strong>descricao</strong> e <strong>peso_kg</strong>.</li>
      <li>Envie o arquivo CSV preenchido abaixo.</li>
    </ol>
    <a href="/admin/simulator-products/template" class="btn btn-sm btn-outline-secondary">Baixar planilha modelo</a>
  </div>

  <form method="post" action="/admin/simulator-products/import" enctype="multipart/form-data" class="border rounded p-3 bg-light">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_csrf) ?>">
    <div class="mb-3">
      <label class="form-label">Arquivo CSV</label>
      <input type="file" name="arquivo" accept=".csv" class="form-control" required>
      <div class="form-text">Envie um arquivo .csv seguindo o modelo fornecido acima.</div>
    </div>
    <button type="submit" class="btn btn-primary">Importar produtos</button>
  </form>
</div>
