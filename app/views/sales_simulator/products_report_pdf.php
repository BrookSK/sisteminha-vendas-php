<?php /** @var array $items */ ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Lista de Compras - Simulador') ?></title>
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: Arial, sans-serif;
      font-size: 11px;
      margin: 16px;
      color: #000;
    }
    h2 {
      margin: 0 0 4px 0;
      font-size: 16px;
    }
    .subtitle {
      font-size: 11px;
      margin-bottom: 12px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      border: 1px solid #000;
      padding: 4px;
      vertical-align: middle;
    }
    th {
      background: #f0f0f0;
      text-align: left;
      font-size: 11px;
    }
    .col-img {
      width: 80px;
      text-align: center;
    }
    .col-qty {
      width: 50px;
      text-align: center;
    }
    .col-check {
      width: 40px;
      text-align: center;
    }
    .col-qty-bought {
      width: 70px;
      text-align: center;
    }
    .checkbox-box {
      display: inline-block;
      width: 12px;
      height: 12px;
      border: 1px solid #000;
    }
    .img-thumb {
      max-width: 70px;
      max-height: 70px;
    }
  </style>
</head>
<body>
  <h2>Lista de Compras do Simulador</h2>
  <div class="subtitle">
    Período: <?= htmlspecialchars($fromDate ?? '') ?> até <?= htmlspecialchars($toDate ?? '') ?>
  </div>

  <table>
    <thead>
      <tr>
        <th>Produto</th>
        <th>Loja</th>
        <th class="col-img">Imagem</th>
        <th class="col-qty">Qtd total</th>
        <th class="col-check">Comprado?</th>
        <th class="col-qty-bought">Qtd comprada</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!empty($items)): ?>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= htmlspecialchars($it['name'] ?? '') ?></td>
          <td><?= htmlspecialchars($it['store_name'] ?? '') ?></td>
          <td class="col-img">
            <?php if (!empty($it['image_url'])): ?>
              <img src="<?= htmlspecialchars($it['image_url']) ?>" alt="" class="img-thumb">
            <?php endif; ?>
          </td>
          <td class="col-qty"><?= (int)($it['total_qtd'] ?? 0) ?></td>
          <td class="col-check"><span class="checkbox-box"></span></td>
          <td class="col-qty-bought"></td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr>
        <td colspan="6">Nenhum produto encontrado para o período/filtros selecionados.</td>
      </tr>
    <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
