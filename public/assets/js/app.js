// JS global. Cálculos dinâmicos para Vendas serão adicionados nas próximas etapas.
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('sale-form');
  if (!form) return;
  const rate = parseFloat(form.dataset.rate || '5.83');
  const embRate = parseFloat(form.dataset.emb || '9.70');
  const inputs = form.querySelectorAll('.sale-input');
  const fields = {
    peso: form.querySelector('[name="peso_kg"]'),
    produto: form.querySelector('[name="valor_produto_usd"]'),
    taxa: form.querySelector('[name="taxa_servico_usd"]'),
    servico: form.querySelector('[name="servico_compra_usd"]'),
    produtoCompra: form.querySelector('[name="produto_compra_usd"]'),
    freteBRL: form.querySelector('[name="frete_brl"]'),
    freteUSD: form.querySelector('[name="frete_usd"]'),
    brutoUSD: form.querySelector('[name="bruto_usd"]'),
    brutoBRL: form.querySelector('[name="bruto_brl"]'),
    embalagemUSD: form.querySelector('[name="embalagem_usd"]'),
    liquidoUSD: form.querySelector('[name="liquido_usd"]'),
    liquidoBRL: form.querySelector('[name="liquido_brl"]'),
    comissaoUSD: form.querySelector('[name="comissao_usd"]'),
    comissaoBRL: form.querySelector('[name="comissao_brl"]'),
  };

  function calc() {
    const peso = parseFloat(fields.peso.value || '0');
    const produto = parseFloat(fields.produto.value || '0');
    const taxa = parseFloat(fields.taxa.value || '0');
    const servico = parseFloat(fields.servico.value || '0');
    const produtoCompra = parseFloat(fields.produtoCompra.value || '0');

    let freteBRL = 0;
    if (peso > 0) {
      if (peso <= 1) freteBRL = 35;
      else if (peso <= 2) freteBRL = 43;
      else freteBRL = 51; // aproximação para >2kg
    }
    const freteUSD = rate > 0 ? freteBRL / rate : 0;

    const embalagemUSD = (peso > 0) ? Math.max(0, embRate) : 0;
    const brutoUSD = produto + freteUSD + servico + taxa + embalagemUSD;
    const brutoBRL = brutoUSD * rate;

    const liquidoUSD = peso <= 0 ? 0 : (brutoUSD - freteUSD - produtoCompra);
    const liquidoBRL = liquidoUSD * rate;

    let perc = 0.15;
    if (brutoUSD <= 30000) perc = 0.15;
    else if (brutoUSD <= 45000) perc = 0.25;
    else perc = 0.25;
    const comissaoUSD = liquidoUSD * perc;
    const comissaoBRL = comissaoUSD * rate;

    fields.freteBRL.value = freteBRL.toFixed(2);
    fields.freteUSD.value = freteUSD.toFixed(2);
    fields.brutoUSD.value = brutoUSD.toFixed(2);
    fields.brutoBRL.value = brutoBRL.toFixed(2);
    if (fields.embalagemUSD) fields.embalagemUSD.value = embalagemUSD.toFixed(2);
    fields.liquidoUSD.value = liquidoUSD.toFixed(2);
    fields.liquidoBRL.value = liquidoBRL.toFixed(2);
    fields.comissaoUSD.value = comissaoUSD.toFixed(2);
    fields.comissaoBRL.value = comissaoBRL.toFixed(2);
  }

  inputs.forEach(i => i.addEventListener('input', calc));
  calc();
});
