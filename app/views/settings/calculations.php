<?php /** @var float $rate */ /** @var float $cost_rate */ ?>
<div class="container py-3">
  <h3 class="mb-1">Cálculo das Comissões e Custos</h3>
  <p class="text-muted">Nesta seção, o administrador pode visualizar as fórmulas utilizadas pelo sistema para calcular comissões, metas e deduções. Qualquer alteração nos parâmetros deve ser feita com cuidado, pois impacta diretamente nos relatórios e resultados dos vendedores.</p>
  <div class="mb-3">
    <a class="btn btn-sm btn-outline-secondary" href="/admin/settings/calculations-simple">Ver versão simples (leiga)</a>
  </div>

  <div class="row g-3">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Fórmulas e Explicações</h5>

          <div class="mb-4">
            <h6 class="mb-1">Glossário de Variáveis</h6>
            <p class="mb-1">Lista das variáveis utilizadas nas fórmulas e como são derivadas quando aplicável.</p>
            <pre class="bg-light p-2 border rounded"><code>
// Conversões
usd_rate                = <?= number_format($rate, 2) ?>
valor_brl               = valor_usd * usd_rate

// Entradas comuns (por venda)
peso_kg                 = peso informado na venda
embalagem_usd_por_kg    = 9.7 // constante utilizada no cálculo de embalagem

// Internacional (entradas por venda)
valor_produto_usd       = informado na venda
frete_ups_usd           = informado na venda
valor_redirecionamento_usd = informado na venda
servico_compra_usd      = informado na venda
frete_etiqueta_usd      = informado na venda
produtos_compra_usd     = informado na venda

// Nacional (entradas por venda)
valor_produto_usd       = informado na venda
taxa_servico_usd        = informado na venda
servico_compra_usd      = informado na venda
produtos_compra_usd     = informado na venda
frete_correios_brl      = tabela_correios(peso_kg) // função de tarifa por kg
frete_correios_usd      = frete_correios_brl / usd_rate

// Bruto por venda
total_bruto_usd (INTL)  = valor_produto_usd + frete_ups_usd + valor_redirecionamento_usd + servico_compra_usd
total_bruto_usd (NAT)   = valor_produto_usd + taxa_servico_usd + servico_compra_usd
total_bruto_brl         = total_bruto_usd * usd_rate

// Líquido por venda
total_liquido_usd (INTL)= total_bruto_usd - frete_etiqueta_usd - produtos_compra_usd - (peso_kg * embalagem_usd_por_kg)
total_liquido_usd (NAT) = total_bruto_usd - frete_correios_usd - produtos_compra_usd - (peso_kg * embalagem_usd_por_kg)
total_liquido_brl       = total_liquido_usd * usd_rate

// Agregações do período (time)
bruto_time              = soma(total_bruto_usd) de todas as vendas no período (USD)
liquido_time            = soma(total_liquido_usd) de todas as vendas no período (USD)
team_bruto_total_brl    = bruto_time * usd_rate
meta_equipe_usd         = 50000
meta_equipe_brl         = meta_equipe_usd * usd_rate
vendedores_ativos       = quantidade de usuários com role 'seller' e ativo=1

// Custos globais do período (time)
cost_rate               = taxa percentual global definida nas Configurações (0..1)
team_cost_settings      = bruto_time * cost_rate
fixed_usd               = soma de custos fixos (em USD) em `custos` no período (valor_tipo != 'percent')
percent_sum             = soma de custos percentuais em `custos` no período (em %)
team_cost_percent       = bruto_time * (percent_sum / 100)
team_cost_total         = team_cost_settings + fixed_usd + team_cost_percent
team_cost_rate          = (bruto_time > 0 ? team_cost_total / bruto_time : 0)

// Alocação por vendedor i (rateio igualitário entre ativos seller+trainee+manager)
bruto_i                 = bruto total (USD) do vendedor i no período
liquido_i               = líquido total (USD) do vendedor i no período
ativos_seller_trainee_manager = quantidade de usuários ativos com role em {'seller','trainee','manager'}
allocated_cost_i        = (ativos_seller_trainee_manager > 0 ? team_cost_total / ativos_seller_trainee_manager : 0)
liquido_apurado_i       = max(0, liquido_i - allocated_cost_i)
bruto_i_brl             = bruto_i * usd_rate
liquido_apurado_i_brl   = liquido_apurado_i * usd_rate

// Faixas de comissão (em BRL, com referência em USD convertida)
// BRL thresholds = usd_threshold * usd_rate
perc_i                  = 0.15 se bruto_i_brl <= (30000 * usd_rate)
                          0.25 se (30000 * usd_rate) < bruto_i_brl <= (45000 * usd_rate)
                          0.25 se bruto_i_brl > (45000 * usd_rate)

// Comissão individual e bônus (por vendedor i)
comissao_individual_brl = liquido_apurado_i_brl * perc_i
apply_bonus             = (team_bruto_total_brl >= meta_equipe_brl)
bonus_rate_por_vendedor = (apply_bonus && vendedores_ativos>0 ? 0.05 / vendedores_ativos : 0)
bonus_brl               = (vendedor i é seller ativo ? liquido_apurado_i_brl * bonus_rate_por_vendedor : 0)
comissao_final_brl      = comissao_individual_brl + bonus_brl
// Conversões para USD (quando exibidas)
comissao_individual     = (usd_rate>0 ? comissao_individual_brl / usd_rate : 0)
bonus                   = (usd_rate>0 ? bonus_brl / usd_rate : 0)
comissao_final          = (usd_rate>0 ? comissao_final_brl / usd_rate : 0)
            </code></pre>
          </div>

          <div class="mb-4">
            <h6 class="mb-1">Comissão Individual</h6>
            <p class="mb-1">Valor calculado sobre o líquido apurado após alocação de custos percentuais da equipe.</p>
            <pre class="bg-light p-2 border rounded"><code>
liquido_apurado = liquido_total - (custo_percentual * (bruto_total / bruto_time))
comissao_individual = liquido_apurado * perc
// perc depende das faixas (em BRL), conforme Configuração do Sistema
            </code></pre>
            <small class="text-muted">Exemplo: se líquido apurado = 10.000 BRL e perc = 15%, comissão = 1.500 BRL.</small>
          </div>

          <div class="mb-4">
            <h6 class="mb-1">Bônus de Equipe</h6>
            <p class="mb-1">Aplica um bônus quando a equipe atinge a meta global mensal (50k USD). Divide 5% entre vendedores ativos.</p>
            <pre class="bg-light p-2 border rounded"><code>
meta_equipe_usd = 50000
bonus_rate_por_vendedor = (atingiu_meta ? 0.05 / vendedores_ativos : 0)
bonus = liquido_apurado_brl * bonus_rate_por_vendedor
            </code></pre>
          </div>

          <div class="mb-4">
            <h6 class="mb-1">Custos Percentuais Globais</h6>
            <p class="mb-1">Percentual aplicado sobre o bruto da equipe. Definido em Configurações &gt; Taxa de Custo Global.</p>
            <pre class="bg-light p-2 border rounded"><code>
cost_rate = <?= number_format($cost_rate, 2) ?> // (0 a 1)
custos_percentuais = bruto_time * cost_rate
            </code></pre>
          </div>

          <div class="mb-4">
            <h6 class="mb-1">Faixas de Comissão (referência atual)</h6>
            <p class="mb-1">Faixas utilizadas para determinar o percentual individual. Valores em USD (referência), convertidos para BRL na apuração.</p>
            <ul class="mb-0">
              <li>Até 30.000 USD (equivalente em BRL): 15%</li>
              <li>Entre 30.000 e 45.000 USD: 25%</li>
              <li>Acima de 45.000 USD: 25%</li>
            </ul>
          </div>

          <div class="mb-4">
            <h6 class="mb-1">Conversões</h6>
            <p class="mb-1">Conversão USD/BRL para fins de regras e exibição.</p>
            <pre class="bg-light p-2 border rounded"><code>
usd_rate = <?= number_format($rate, 2) ?>
valor_brl = valor_usd * usd_rate
            </code></pre>
          </div>

          <div class="mb-4">
            <h6 class="mb-1">🧾 Como é calculado o Bruto Total</h6>
            <p class="mb-1">Soma dos valores dos itens/serviços da venda em USD antes das deduções.</p>
            <pre class="bg-light p-2 border rounded"><code>
// Internacional
total_bruto_usd = valor_produto_usd + frete_ups_usd + valor_redirecionamento_usd + servico_compra_usd
// Nacional
total_bruto_usd = valor_produto_usd + taxa_servico_usd + servico_compra_usd
total_bruto_brl = total_bruto_usd * usd_rate
            </code></pre>
          </div>

          <div class="mb-4">
            <h6 class="mb-1">💰 Como é calculado o Líquido Total</h6>
            <p class="mb-1">Valor após as deduções operacionais (fretes, compras e embalagem/kg).</p>
            <pre class="bg-light p-2 border rounded"><code>
// Internacional
total_liquido_usd = total_bruto_usd - frete_etiqueta_usd - produtos_compra_usd - (peso_kg * embalagem_usd_por_kg)
// Nacional (frete dos Correios é estimado em BRL e convertido)
frete_correios_brl = tabela_correios(peso_kg)
frete_correios_usd = frete_correios_brl / usd_rate
total_liquido_usd = total_bruto_usd - frete_correios_usd - produtos_compra_usd - (peso_kg * embalagem_usd_por_kg)
total_liquido_brl = total_liquido_usd * usd_rate
            </code></pre>
          </div>

          <div class="mb-4">
            <h6 class="mb-1">Regras Adicionais</h6>
            <ul class="mb-0">
              <li>Custos recorrentes impactam o resultado líquido mensal automaticamente.</li>
              <li>Dívidas parceladas são distribuídas entre os meses até a quitação.</li>
              <li>Vendas importadas via Webhook seguem as mesmas regras de cálculo.</li>
              <li>Alterações na taxa ou fórmula não retroagem — valem para novos registros.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Pré-visualização de Simulação</h5>
          <div class="row g-2">
            <div class="col-sm-3">
              <label class="form-label">Bruto (USD)</label>
              <input type="number" step="0.01" class="form-control" id="sim_bruto" value="10000">
            </div>
            <div class="col-sm-3">
              <label class="form-label">Líquido (USD)</label>
              <input type="number" step="0.01" class="form-control" id="sim_liquido" value="7000">
            </div>
            <div class="col-sm-3">
              <label class="form-label">Bruto Time (USD)</label>
              <input type="number" step="0.01" class="form-control" id="sim_bruto_time" value="50000">
            </div>
            <div class="col-sm-3">
              <label class="form-label">% Comissão (0-1)</label>
              <input type="number" step="0.01" min="0" max="1" class="form-control" id="sim_perc" value="0.15">
            </div>
          </div>
          <div class="row g-2 mt-2">
            <div class="col-sm-3">
              <label class="form-label">Taxa Global (0-1)</label>
              <input type="number" step="0.01" min="0" max="1" class="form-control" id="sim_cost_rate" value="<?= htmlspecialchars((string)$cost_rate) ?>">
            </div>
            <div class="col-sm-3">
              <label class="form-label">USD Rate</label>
              <input type="number" step="0.01" min="0" class="form-control" id="sim_usd_rate" value="<?= htmlspecialchars((string)$rate) ?>">
            </div>
            <div class="col-sm-3">
              <label class="form-label">Sellers Ativos (para bônus)</label>
              <input type="number" step="1" min="0" class="form-control" id="sim_sellers_ativos" value="5">
            </div>
            <div class="col-sm-3 d-flex align-items-end">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="sim_meta_atingida">
                <label class="form-check-label" for="sim_meta_atingida">Meta da equipe atingida</label>
              </div>
            </div>
            <div class="col-sm-3 d-flex align-items-end">
              <button class="btn btn-primary w-100" id="sim_run">Simular</button>
            </div>
          </div>

          <hr>
          <div class="row g-2">
            <div class="col-sm-3">
              <div class="form-text">Custo % do Time (USD)</div>
              <div id="out_team_cost_usd" class="fw-semibold">-</div>
            </div>
            <div class="col-sm-3">
              <div class="form-text">Líquido Apurado (USD)</div>
              <div id="out_liquido_apurado_usd" class="fw-semibold">-</div>
            </div>
            <div class="col-sm-3">
              <div class="form-text">Comissão (USD)</div>
              <div id="out_comissao_usd" class="fw-semibold">-</div>
            </div>
            <div class="col-sm-3">
              <div class="form-text">Comissão (BRL)</div>
              <div id="out_comissao_brl" class="fw-semibold">-</div>
            </div>
            <div class="col-sm-3">
              <div class="form-text">Bônus de Equipe (USD)</div>
              <div id="out_bonus_usd" class="fw-semibold">-</div>
            </div>
            <div class="col-sm-3">
              <div class="form-text">Bônus de Equipe (BRL)</div>
              <div id="out_bonus_brl" class="fw-semibold">-</div>
            </div>
            <div class="col-sm-3">
              <div class="form-text">Comissão Final (USD)</div>
              <div id="out_final_usd" class="fw-semibold">-</div>
            </div>
            <div class="col-sm-3">
              <div class="form-text">Comissão Final (BRL)</div>
              <div id="out_final_brl" class="fw-semibold">-</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  function toNum(id){ const el=document.getElementById(id); return parseFloat(el.value||'0')||0; }
  document.getElementById('sim_run').addEventListener('click', function(){
    const bruto = toNum('sim_bruto');
    const liquido = toNum('sim_liquido');
    const teamBruto = toNum('sim_bruto_time');
    const perc = toNum('sim_perc');
    const costRate = toNum('sim_cost_rate');
    const usdRate = toNum('sim_usd_rate');
    const ativos = Math.max(0, Math.floor(toNum('sim_sellers_ativos')));
    const metaAtingida = document.getElementById('sim_meta_atingida').checked;
    const teamCost = Math.max(0, teamBruto * costRate);
    const allocated = (teamBruto>0) ? (teamCost * (bruto / teamBruto)) : 0;
    const liquidoAp = Math.max(0, liquido - allocated);
    const comUsd = liquidoAp * perc;
    const comBrl = comUsd * (usdRate>0 ? usdRate : 0);
    const bonusRate = (metaAtingida && ativos>0) ? (0.05 / ativos) : 0;
    const bonusBrl = (liquidoAp * (usdRate>0?usdRate:0)) * bonusRate;
    const bonusUsd = (usdRate>0) ? (bonusBrl / usdRate) : 0;
    const finalUsd = comUsd + bonusUsd;
    const finalBrl = comBrl + bonusBrl;
    document.getElementById('out_team_cost_usd').textContent = teamCost.toFixed(2);
    document.getElementById('out_liquido_apurado_usd').textContent = liquidoAp.toFixed(2);
    document.getElementById('out_comissao_usd').textContent = comUsd.toFixed(2);
    document.getElementById('out_comissao_brl').textContent = comBrl.toFixed(2);
    document.getElementById('out_bonus_usd').textContent = bonusUsd.toFixed(2);
    document.getElementById('out_bonus_brl').textContent = bonusBrl.toFixed(2);
    document.getElementById('out_final_usd').textContent = finalUsd.toFixed(2);
    document.getElementById('out_final_brl').textContent = finalBrl.toFixed(2);
  });
})();
</script>
