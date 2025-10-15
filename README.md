# Sisteminha de Vendas (PHP MVC)

Sistema web em PHP com arquitetura MVC e MySQL.

## Requisitos
- PHP 8.1+
- MySQL 8+
- Apache com mod_rewrite habilitado

## Instalação
1. Crie um banco de dados MySQL (ex: `sisteminha_vendas`).
2. Importe `database/schema.sql` no banco.
3. Ajuste as variáveis de conexão em `app/config/config.php`.
4. Configure o VirtualHost apontando para `public/` (ou use um servidor local com raiz em `public/`).
5. Acesse o sistema no navegador.

## Usuários padrão (seed)
- Admin
  - Email: `admin@example.com`
  - Senha: `admin123`
- Vendas Orgânicas (não recebe comissão; pode lançar vendas)
  - Email: `organic@example.com`
  - Senha: `organico123`

Para garantir a criação desses usuários no seu banco, execute o seed:

```bash
php database/seeds/seed_users.php
```

## Estrutura de pastas
- `public/`: front controller (`index.php`), `.htaccess`, assets estáticos
- `app/core/`: núcleo MVC (Router, Controller, Model, View, Database, Auth)
- `app/controllers/`: controladores
- `app/models/`: modelos
- `app/views/`: views (com `layouts/`)
- `app/config/`: configurações
- `database/`: schema SQL

## Segurança
- Sessions com cookies HttpOnly/SameSite
- Hash de senha com password_hash (bcrypt)
- Prepared statements (PDO)
- CSRF token básico

## Build/Dependências
- Usa Bootstrap via CDN (sem dependências locais)

## Scripts úteis
- Ajuste a taxa do dólar em Configurações (persistido em tabela `settings`).

## Licença
MIT
