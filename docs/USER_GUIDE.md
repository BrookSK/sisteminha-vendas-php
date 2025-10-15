# Guia do Usuário

Este guia mostra como utilizar o Sisteminha de Vendas no dia a dia.

## Acesso

- Entre em `/login` com seu e-mail e senha.
- Em caso de primeiro uso e ausência de usuários, o sistema cria um Admin automaticamente.

## Navegação

- Dashboard: visão geral de vendas recentes e totais.
- Clientes: criar, editar, buscar e excluir clientes; cada cliente mostra sua suíte.
- Vendas: registrar novas vendas, editar e remover; cálculos saem automaticamente.
- Atendimentos: registrar os atendimentos diários (total e concluídos) para alimentar relatórios.
- Custos: registrar custos gerais (servidor, envios, freelancers etc.) em USD.
- Relatórios: ver semana/mês, comparação de meses e desempenho por vendedor.
- Configurações: (Admin) taxa do dólar e embalagem por kg.

## Cadastro de Vendas

- Preencha o cliente, suite, peso, valores de produto/serviços.
- O sistema calcula automaticamente:
  - Frete (BRL e USD)
  - Embalagem (USD) = peso_kg × embalagem_por_kg (se peso > 0)
  - Bruto (USD/BRL)
  - Líquido (USD/BRL)
  - Comissão (USD/BRL)

## Atendimentos

- Em `/admin/attendances`, selecione a data do dia, informe o total e o total concluído.
- Você pode exportar os atendimentos em CSV.

## Custos

- Em `/admin/costs`, cadastre custos externos em USD (categoria, descrição, valor, data).
- Você pode filtrar por período e exportar CSV.

## Relatórios

- Semana/Mês: totais de atendimentos, vendas (bruto/líquido) e custos (internos + externos), lucro líquido.
- Últimos 3 meses x Atual: série mensal com os mesmos indicadores.
- Por vendedor: volume de atendimentos e totais por usuário.
- Exportações:
  - PDF (Relatórios): `/admin/reports/export-pdf` (requer Dompdf).
  - Rateio de custos externos por período: `/admin/reports/cost-allocation.csv?from=YYYY-MM-DD&to=YYYY-MM-DD`.

## Dicas

- Mantenha as configurações atualizadas (taxa do dólar e embalagem por kg) para cálculos corretos.
- Registre atendimentos diariamente e custos externos para relatórios fidedignos.
- Use exportações CSV para análise em planilhas.
