# Guia do Administrador

Este guia explica como configurar e operar o Sisteminha de Vendas como Administrador.

## Instalação e Configuração

- Requisitos: PHP 8+, MySQL 5.7+/8+, Apache com mod_rewrite, Composer (opcional para PDF).
- Clone o projeto e aponte o DocumentRoot para `public/`.
- Configure `app/config/config.php` (DB, base_url, env, csrf_key).
- Importe o schema inicial em `database/schema.sql` (cria tabelas base) e depois as migrações:
  - `database/migrations_20251008.sql` (cria `atendimentos`, `custos`, adiciona `embalagem_usd` em `vendas` e define `settings.embalagem_usd_por_kg`).

## Primeiro Acesso

- Acesse `/login`. Se não houver usuários, o primeiro login cria o Admin automaticamente.
- Recomenda-se trocar a senha após o primeiro login.

## Módulos e Rotas

- Dashboard: `/admin`
- Clientes: `/admin/clients`
- Vendas: `/admin/sales`
- Atendimentos: `/admin/attendances`
- Custos: `/admin/costs`
- Relatórios: `/admin/reports`
- Configurações: `/admin/settings`
- Logs: `/admin/logs`

## Configurações Importantes

- Taxa do Dólar: usada para conversões BRL/ USD.
- Embalagem por KG (USD): valor por kg cobrado quando `peso_kg > 0`. Afeta `embalagem_usd` e o Bruto.

## Regras de Cálculo (Vendas)

- Frete BRL: ≤1kg=35, ≤2kg=43, >2kg=51; peso=0 → 0.
- Frete USD = Frete BRL / taxa_dólar.
- Embalagem USD = peso_kg × embalagem_por_kg (se peso>0).
- Bruto USD = produto + frete_usd + serviço_compra + taxa_serviço + embalagem_usd.
- Bruto BRL = bruto_usd × taxa_dólar.
- Líquido USD = (peso>0) ? (bruto_usd − frete_usd − produto_compra) : 0.
- Líquido BRL = líquido_usd × taxa_dólar.
- Comissão USD: bruto_usd ≤ 30k → líquido × 0.15; ≤ 45k → líquido × 0.25; > 45k → líquido × 0.25.
- Comissão BRL = comissão_usd × taxa_dólar.

## Relatórios

- Semana e Mês:
  - Nº de atendimentos e concluídos (tabela `atendimentos`).
  - Bruto/Líquido (vendas) e Custos Internos (frete + produto_compra + servico_compra + taxa_servico + embalagem).
  - Custos Externos (tabela `custos`).
  - Lucro Líquido = Bruto − (Custos Internos + Externos).
- Últimos 3 meses vs Atual: mesmos agregados mensalizados.
- Por vendedor: contagem de vendas e totais.

## Exportações

- CSV:
  - Atendimentos: `/admin/attendances/export`
  - Custos: `/admin/costs/export`
  - Rateio de custos externos por período: `/admin/reports/cost-allocation.csv?from=YYYY-MM-DD&to=YYYY-MM-DD`
- PDF (requer Dompdf):
  - Relatórios: `/admin/reports/export-pdf`
  - Atendimentos: `/admin/attendances/export-pdf` (placeholder)
  - Custos: `/admin/costs/export-pdf` (placeholder)

Instalação do Dompdf:
```
composer require dompdf/dompdf
```

## Segurança

- Login com sessão segura, CSRF e hashing de senha (bcrypt).
- Regeneração de Session ID no login.
- Throttling: no máx. 5 tentativas a cada 10min.

## Logs de Atividade

- CRUD de Clientes, Vendas, Custos, Atendimentos e alteração de Configurações são registrados em `logs`.

## Dicas Operacionais

- Cadastre a taxa do dólar e embalagem por kg em `/admin/settings` antes de registrar vendas.
- Cadastre Atendimentos diariamente em `/admin/attendances` para relatórios consistentes.
- Cadastre Custos externos em `/admin/costs` para refletirem no Lucro Líquido.
