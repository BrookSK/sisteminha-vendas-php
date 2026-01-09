<?php /** @var string $base */ ?>
<div class="container py-3">
  <h3 class="mb-3">Guia de Webhooks</h3>
  <div class="alert alert-warning">Todos os endpoints abaixo aceitam JSON sem autenticação.</div>

  <h5>URLs</h5>
  <ul>
    <li><code><?= htmlspecialchars($base) ?>/webhooks/containers</code></li>
    <li><code><?= htmlspecialchars($base) ?>/webhooks/sales</code></li>
    <li><code><?= htmlspecialchars($base) ?>/webhooks/demands</code></li>
    <li><code><?= htmlspecialchars($base) ?>/webhooks/simulator-products</code></li>
  </ul>

  <h5 class="mt-4">Exemplos de JSON</h5>
  <div class="mb-3">
    <h6>Containers (POST /webhooks/containers)</h6>
    <div class="small mb-2">
      <strong>Obrigatórios:</strong> <code>id</code> (string/number, id externo), <code>codigo_utilizador</code> (number), <code>peso_kg</code> (number), <code>status</code> (string), <code>valor_transporte</code> (number), <code>data_criacao</code> (YYYY-MM-DD).
      <br><strong>Opcionais/Extras:</strong> quaisquer outros campos enviados serão ignorados.
      <br><strong>Comportamento:</strong> upsert por <code>invoice_id</code> (mapeado de <code>id</code>). Se existir, atualiza; senão, cria.
    </div>
<pre><code class="language-json">{
  "id": 12345,
  "codigo_utilizador": 987,
  "peso_kg": 1500.5,
  "status": "Em preparo",
  "valor_transporte": 300.0,
  "data_criacao": "2025-10-14"
}
</code></pre>
  </div>

  <div class="mb-3">
    <h6>Produtos do Simulador (POST /webhooks/simulator-products)</h6>
    <div class="small mb-2">
      <strong>Obrigatórios:</strong> <code>id</code> (string/number, id externo), <code>nome</code> (string), <code>qtd</code> (number), <code>peso_kg</code> (number), <code>valor_usd</code> (number), <code>data</code> (YYYY-MM-DD).
      <br><strong>Opcionais:</strong> <code>image_url</code> (string), <code>store_id</code> (number), <code>store_name</code> (string), <code>links</code> (array).
      <br><strong>Extras:</strong> demais campos são ignorados.
      <br><strong>Comportamento:</strong> upsert por <code>id</code> externo (mapeado para <code>external_id</code>).
    </div>
<pre><code class="language-json">{
  "id": "EXT-PROD-001",
  "nome": "Shampoo XYZ 300ml",
  "qtd": 4,
  "peso_kg": 0.30,
  "valor_usd": 12.50,
  "data": "2026-01-09",
  "image_url": "https://.../produto.jpg",
  "store_id": 2,
  "store_name": "Loja Externa",
  "links": [
    {"url": "https://site.com/produto", "fonte": "site"}
  ]
}
</code></pre>
  </div>

  <div class="mb-3">
    <h6>Vendas (POST /webhooks/sales)</h6>
    <p>Campo <code>tipo</code> pode ser <code>"intl"</code> ou <code>"nat"</code>.</p>
    <div class="small mb-2">
      <strong>Obrigatórios:</strong> <code>id</code> (string/number, id externo), <code>tipo</code> ("intl"|"nat"), <code>cliente_id</code> (number), <code>valor_bruto_usd</code> (number), <code>valor_liquido_usd</code> (number), <code>peso_kg</code> (number), <code>data</code> (YYYY-MM-DD).
      <br><strong>Opcionais:</strong> <code>vendedor_id</code> (number). Se omitido, o sistema define um usuário existente (admin ativo se houver) para atender o vínculo obrigatório.
      <br><strong>Extras:</strong> demais campos são ignorados.
      <br><strong>Comportamento:</strong> upsert por <code>id</code> externo para a tabela correspondente ao <code>tipo</code>.
    </div>
<pre><code class="language-json">{
  "id": "EXT-1001",
  "tipo": "intl",
  "cliente_id": 55,
  "vendedor_id": 2,
  "valor_bruto_usd": 1200.00,
  "valor_liquido_usd": 1100.00,
  "peso_kg": 75.5,
  "data": "2025-10-14"
}
</code></pre>
    <div class="small mb-2">Exemplo para vendas nacionais (<code>"nat"</code>):</div>
<pre><code class="language-json">{
  "id": "NAT-9002",
  "tipo": "nat",
  "cliente_id": 78,
  "vendedor_id": 3,
  "valor_bruto_usd": 850.00,
  "valor_liquido_usd": 800.00,
  "peso_kg": 12.3,
  "data": "2025-10-14"
}
</code></pre>
  </div>

  <div class="mb-3">
    <h6>Demandas (POST /webhooks/demands)</h6>
    <div class="small mb-2">
      <strong>Obrigatórios:</strong> <code>title</code> (string), <code>type_desc</code> (string).
      <br><strong>Opcionais:</strong> <code>due_date</code> (YYYY-MM-DD), <code>priority</code> ("baixa"|"media"|"alta"|"urgente"|"ideia"), <code>status</code> (ex: "pendente", "trabalhando", "entregue", etc.), <code>project_id</code> (number), <code>assignee_id</code> (number), <code>classification</code> (ex: "erro_garantia", "ajuste_operacional", "alteracao_evolutiva", "orcamento_estimativa"), <code>details</code> (string).
      <br><strong>Extras:</strong> campos não listados serão ignorados.
      <br><strong>Comportamento:</strong> cria sempre uma nova demanda; o <code>created_by</code> é definido com fallback seguro: usa <code>assignee_id</code> válido se enviado; senão tenta um admin ativo; senão qualquer usuário; e, se nada existir, <code>NULL</code>.
    </div>
<pre><code class="language-json">{
  "title": "Ajustar cálculo de frete",
  "type_desc": "Ajuste",
  "due_date": "2025-10-20",
  "priority": "alta",
  "status": "pendente",
  "project_id": 10,
  "assignee_id": 2,
  "classification": "ajuste_operacional",
  "details": "Revisar regra para pedidos acima de 30kg"
}
</code></pre>
  </div>

  <p class="text-muted">Observações:
    <br>- Todos os campos além de <code>title</code> e <code>type_desc</code> em demandas são opcionais.
    <br>- Para vendas, será feito upsert por <code>id</code> (campo externo) e tabela correspondente ao <code>tipo</code>.
    <br>- Para containers, será feito upsert por <code>invoice_id</code> (mapeado de <code>id</code> do payload).
  </p>
</div>
