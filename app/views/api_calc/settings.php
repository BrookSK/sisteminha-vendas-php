<?php use Core\Auth; ?>
<div class="row">
  <div class="col-lg-7">
    <div class="card mb-3 shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3">Configurações da API de Cálculo</h5>
        <form method="post" action="/admin/api-calc/save">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrf()) ?>">
          <div class="mb-3">
            <label class="form-label">Token de Autenticação (Bearer)</label>
            <input type="text" class="form-control" name="api_calc_token" value="<?= htmlspecialchars((string)($token ?? '')) ?>" placeholder="Informe um token seguro">
            <div class="form-text">Use este token no header Authorization: <code>Bearer &lt;token&gt;</code> ao chamar a API.</div>
          </div>
          <div class="row g-3">
            <div class="col-md-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="api_calc_include_usd" id="inc_usd" <?= !empty($include_usd) ? 'checked' : '' ?>>
                <label class="form-check-label" for="inc_usd">Incluir Custos Fixos (USD)</label>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="api_calc_include_brl" id="inc_brl" <?= !empty($include_brl) ? 'checked' : '' ?>>
                <label class="form-check-label" for="inc_brl">Incluir Custos Fixos (BRL → USD)</label>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="api_calc_include_percent" id="inc_pct" <?= !empty($include_percent) ? 'checked' : '' ?>>
                <label class="form-check-label" for="inc_pct">Incluir Custos Percentuais (%)</label>
              </div>
            </div>
          </div>
          <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Salvar</button>
            <a class="btn btn-outline-secondary" href="/admin">Voltar</a>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="card-title">Como usar</h6>
        <p class="text-muted small">A API calcula o valor líquido a partir de um valor bruto (USD) subtraindo custos fixos (USD), custos fixos em BRL (convertidos para USD pela taxa <?= number_format((float)($usd_rate ?? 0),2) ?>), e custos percentuais somados (sobre o bruto).</p>
        <div class="mb-2"><strong>Endpoint</strong><br><code>POST /admin/api/calc-net</code></div>
        <div class="mb-2"><strong>Autenticação</strong><br><code>Authorization: Bearer &lt;token&gt;</code></div>
        <div class="mb-2"><strong>Exemplo de Payload</strong>
          <pre class="bg-light p-2 border rounded small"><code>{
  "gross_usd": 10000,
  "detail": true
}</code></pre>
        </div>
        <div class="mb-2"><strong>Resposta</strong>
          <pre class="bg-light p-2 border rounded small"><code>{
  "gross_usd": 10000.0,
  "net_usd": 8420.0,
  "deductions": {
    "fixed_usd": 300.0,
    "fixed_brl_converted_to_usd": 80.0,
    "percent_total_points": 12.0,
    "percent_deduction_usd": 1200.0,
    "usd_rate_used": 5.83
  }
}</code></pre>
        </div>
        <div class="mb-2"><strong>Lógica Interna (resumo)</strong>
          <ul class="small">
            <li>Somatória de <code>custos.valor_usd</code> quando <code>valor_tipo='usd'</code> → custos fixos USD.</li>
            <li>Somatória de <code>custos.valor_brl</code> quando <code>valor_tipo='brl'</code> → convertidos para USD pela taxa do sistema.</li>
            <li>Somatória de <code>custos.valor_percent</code> quando <code>valor_tipo='percent'</code> → pontos percentuais aplicados sobre o <em>gross_usd</em>.</li>
            <li><code>net = gross - (fixed_usd + fixed_brl/usd_rate + gross * (percent/100))</code>.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
