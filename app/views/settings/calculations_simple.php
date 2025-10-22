<?php

/** @var float $rate */ /** @var float $cost_rate */ ?>
<div class="container py-3">
    <h3 class="mb-1">Entenda os Cálculos (versão simples)</h3>
    <p class="text-muted">Esta é uma explicação em linguagem simples de como calculamos valores nas vendas e comissões. Ideal para quem quer entender sem fórmulas técnicas.</p>

    <div class="mb-3">
        <?php if ((\Core\Auth::user()['role'] ?? 'seller') === 'admin'): ?>
            <a class="btn btn-sm btn-outline-secondary" href="/admin/settings/calculations">Ver versão completa (técnica)</a>
        <?php endif; ?>
    </div>

    <script>
        (function() {
            const USD_RATE = <?= json_encode($rate) ?>;
            const COST_RATE = <?= json_encode($cost_rate) ?>; // 0..1
            function num(id) {
                var el = document.getElementById(id);
                return parseFloat(el && el.value || '0') || 0;
            }
            function faixaPerc(brutoUsd){
                // thresholds em USD: 30k, 45k
                if (brutoUsd <= 30000) return 0.15;
                if (brutoUsd <= 45000) return 0.25;
                return 0.25;
            }
            function bindSim() {
                var run = document.getElementById('s_run');
                if (!run) return;
                run.addEventListener('click', function(e) {
                    e.preventDefault();
                var teamBruto = num('s_team_bruto'); // USD (equipe)
                var bruto = num('s_bruto'); // USD (vendedor)
                var liquido = num('s_liquido'); // USD (vendedor)
                var ativos = Math.max(0, Math.floor(num('s_ativos')));
                var meta = document.getElementById('s_meta')?.checked || false;

                // custo do time (simplificado): aplica apenas taxa global
                var teamCost = Math.max(0, teamBruto * COST_RATE);
                // cota igualitária por ativo (seller+trainee)
                var equalShare = (ativos > 0) ? (teamCost / ativos) : 0;
                var liquidoAp = Math.max(0, liquido - equalShare);
                var perc = faixaPerc(bruto);
                var comUsd = liquidoAp * perc;
                var comBrl = comUsd * (USD_RATE > 0 ? USD_RATE : 0);
                var bonusRate = (meta && ativos > 0) ? (0.05 / ativos) : 0;
                var bonusBrl = (liquidoAp * (USD_RATE>0?USD_RATE:0)) * bonusRate;
                var bonusUsd = (USD_RATE>0) ? (bonusBrl / USD_RATE) : 0;
                var finalUsd = comUsd + bonusUsd;
                var finalBrl = comBrl + bonusBrl;

                var outEq = document.getElementById('s_out_equal_share_usd'); if (outEq) outEq.textContent = equalShare.toFixed(2);
                document.getElementById('s_out_liquido_apurado_usd').textContent = liquidoAp.toFixed(2);
                document.getElementById('s_out_comissao_usd').textContent = comUsd.toFixed(2);
                document.getElementById('s_out_comissao_brl').textContent = comBrl.toFixed(2);
                document.getElementById('s_out_bonus_usd').textContent = bonusUsd.toFixed(2);
                document.getElementById('s_out_bonus_brl').textContent = bonusBrl.toFixed(2);
                document.getElementById('s_out_final_usd').textContent = finalUsd.toFixed(2);
                document.getElementById('s_out_final_brl').textContent = finalBrl.toFixed(2);
                var percEl = document.getElementById('s_out_perc'); if (percEl) percEl.textContent = (perc*100).toFixed(0) + '%';
                });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bindSim);
            } else {
                bindSim();
            }
        })();
    </script>

    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">1) Conversão de Moeda</h5>
                    <p>
                        Trabalhamos em Dólar (USD) e Real (BRL). Para transformar de USD para BRL, multiplicamos pela taxa:
                        <strong>taxa do dólar = <?= number_format($rate, 2) ?></strong>.
                    </p>
                    <p class="mb-0"><strong>Exemplo:</strong> 100 USD x taxa (<?= number_format($rate, 2) ?>) = <?= number_format($rate * 100, 2) ?> BRL.</p>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">2) O que é o BRUTO do pedido</h5>
                    <p class="mb-1"><strong>Venda Internacional:</strong> somamos os valores do produto e serviços (frete UPS, redirecionamento e serviço de compra).</p>
                    <p class="mb-2"><strong>Venda Nacional:</strong> somamos o valor do produto, a taxa de serviço e o serviço de compra.</p>
                    <p class="mb-0"><em>É o total antes de tirar fretes do envio, compras e custos de embalagem.</em></p>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">3) O que é o LÍQUIDO do pedido</h5>
                    <p class="mb-1">Do BRUTO, descontamos os custos diretos daquele pedido:</p>
                    <ul>
                        <li><strong>Internacional:</strong> frete da etiqueta, compras de produtos e embalagem por kg.</li>
                        <li><strong>Nacional:</strong> frete dos Correios (estimado por peso, convertido em USD), compras de produtos e embalagem por kg.</li>
                    </ul>
                    <p class="mb-0"><em>Resultado: o LÍQUIDO é o que sobra por pedido após esses descontos diretos.</em></p>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">4) Custos da Equipe (do mês)</h5>
                    <p>
                        Além dos custos diretos de cada pedido, existe um <strong>custo da equipe</strong> aplicado no mês.
                        Ele tem três partes:
                    </p>
                    <ul>
                        <li><strong>Taxa Global:</strong> uma porcentagem sobre o BRUTO da equipe (<strong><?= number_format($cost_rate * 100, 2) ?>%</strong> hoje).</li>
                        <li><strong>Custos Fixos:</strong> valores em USD cadastrados em "Custos" para o período.</li>
                        <li><strong>Custos Percentuais:</strong> percentuais cadastrados em "Custos" que aplicam sobre o BRUTO da equipe.</li>
                    </ul>
                    <p class="mb-0">Somamos essas três coisas e <strong>dividimos igualmente</strong> entre os vendedores <strong>ativos (seller + trainee)</strong>.</p>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">5) Comissão do Vendedor</h5>
                    <p>
                        Para cada vendedor, pegamos o <strong>LÍQUIDO</strong> dele e tiramos a parte do <strong>custo da equipe</strong> que cabe a ele.
                        Sobre o que sobra, aplicamos um <strong>percentual de comissão</strong> que depende do BRUTO do vendedor no mês.
                    </p>
                    <ul class="mb-1">
                        <li>Até 30 mil USD (equivalente em BRL): 15%</li>
                        <li>De 30 a 45 mil USD: 25%</li>
                        <li>Acima de 45 mil USD: 25%</li>
                    </ul>
                    <p class="mb-0"><em>Resultado: a comissão é uma porcentagem do líquido do vendedor após os custos da equipe.</em></p>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">6) Bônus de Equipe</h5>
                    <p>
                        Se a equipe toda bater a meta do mês (50 mil USD em BRUTO), existe um <strong>bônus adicional</strong>:
                        pegamos 5% e dividimos igualmente entre os vendedores ativos.
                    </p>
                    <p class="mb-0">Esse bônus é somado em cima da comissão do vendedor.</p>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">7) Comissão Estimada da Venda</h5>
                    <p class="mb-2">A comissão estimada usa os mesmos componentes explicados acima, de forma simplificada:</p>
                    <ol class="mb-2">
                        <li><strong>Calcular o custo do time</strong> do mês (simplificado): <code>teamCost = BRUTO_EQUIPE × <?= number_format($cost_rate*100, 2) ?>%</code>.</li>
                        <li><strong>Cota igualitária</strong> por vendedor ativo (seller+trainee): <code>equalShare = (ativos &gt; 0 ? teamCost ÷ ativos : 0)</code>.</li>
                        <li><strong>Líquido apurado</strong> para comissão: <code>liquido_apurado = LÍQUIDO − equalShare</code>.</li>
                        <li><strong>Definir a faixa de comissão</strong> pelo BRUTO do vendedor no mês:
                            <ul>
                                <li>Até 30.000 USD: 15%</li>
                                <li>De 30.000 a 45.000 USD: 25%</li>
                                <li>Acima de 45.000 USD: 25%</li>
                            </ul>
                        </li>
                        <li><strong>Comissão (USD)</strong>: <code>comissao_usd = liquido_apurado × percentual</code>.</li>
                        <li><strong>Comissão (BRL)</strong>: <code>comissao_brl = comissao_usd × taxa_dolar</code> (taxa atual = <?= number_format($rate, 2) ?>).</li>
                        <li><strong>Se a meta da equipe for atingida</strong>, soma-se o <em>bônus</em> proporcional: <code>bonus_brl = (liquido_apurado × taxa_dolar) × (5% ÷ vendedores_ativos)</code>.</li>
                    </ol>
                    <p class="mb-0"><strong>Resumo:</strong> <code>Comissão Estimada (BRL) = [(Líquido − (BRUTO_EQUIPE×<?= number_format($cost_rate*100, 2) ?>% ÷ ativos)) × percentual] × taxa_dólar [+ bônus se houver]</code>.</p>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Exemplo resumido</h5>
                    <p class="mb-1"><strong>Pedido internacional:</strong> BRUTO = produto + UPS + redirecionamento + compra.</p>
                    <p class="mb-1">LÍQUIDO = BRUTO - etiqueta - compras - embalagem por kg.</p>
                    <p class="mb-1">No mês: calculamos o custo da equipe e dividimos proporcionalmente.</p>
                    <p class="mb-0">Comissão = (LÍQUIDO do vendedor - sua parte do custo da equipe) x percentual da faixa. Se houver meta batida, soma o bônus.</p>
                </div>
            </div>
        </div>

        <!-- Simulador simples -->
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Simulador (pré-visualização)</h5>
                    <div class="text-muted small mb-2">Taxa do dólar atual: <strong><?= number_format($rate, 2) ?></strong> • Taxa Global aplicada: <strong><?= number_format($cost_rate*100, 2) ?>%</strong></div>
                    <div class="row g-2">
                        <div class="col-sm-4">
                            <label class="form-label">Bruto da equipe (USD)</label>
                            <input type="number" step="0.01" class="form-control" id="s_team_bruto" value="50000">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Bruto do vendedor (USD)</label>
                            <input type="number" step="0.01" class="form-control" id="s_bruto" value="10000">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Líquido do vendedor (USD)</label>
                            <input type="number" step="0.01" class="form-control" id="s_liquido" value="7000">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Vendedores Ativos</label>
                            <input type="number" step="1" min="0" class="form-control" id="s_ativos" value="5">
                        </div>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-sm-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="s_meta">
                                <label class="form-check-label" for="s_meta">Meta da equipe atingida</label>
                            </div>
                        </div>
                        <div class="col-sm-6 d-flex align-items-end">
                            <button class="btn btn-primary w-100" id="s_run">Calcular</button>
                        </div>
                    </div>
                    <hr>
                    <div class="row g-2">
                        <div class="col-sm-3">
                            <div class="form-text">Cota Igualitária (USD)</div>
                            <div id="s_out_equal_share_usd" class="fw-semibold">-</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-text">Líquido Apurado (USD)</div>
                            <div id="s_out_liquido_apurado_usd" class="fw-semibold">-</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-text">Comissão (USD)</div>
                            <div id="s_out_comissao_usd" class="fw-semibold">-</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-text">Comissão (BRL)</div>
                            <div id="s_out_comissao_brl" class="fw-semibold">-</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-text">Bônus (USD)</div>
                            <div id="s_out_bonus_usd" class="fw-semibold">-</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-text">Bônus (BRL)</div>
                            <div id="s_out_bonus_brl" class="fw-semibold">-</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-text">Final (USD)</div>
                            <div id="s_out_final_usd" class="fw-semibold">-</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-text">Final (BRL)</div>
                            <div id="s_out_final_brl" class="fw-semibold">-</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-text">Faixa de Comissão (%)</div>
                            <div id="s_out_perc" class="fw-semibold">-</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>