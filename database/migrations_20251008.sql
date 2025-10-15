-- Migrações: Atendimentos, Custos e coluna de embalagem nas vendas

CREATE TABLE IF NOT EXISTS atendimentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  data DATE NOT NULL,
  total_atendimentos INT NOT NULL DEFAULT 0,
  total_concluidos INT NOT NULL DEFAULT 0,
  usuario_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_data (data),
  INDEX (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Vendas Internacionais
CREATE TABLE IF NOT EXISTS vendas_internacionais (
  id INT AUTO_INCREMENT PRIMARY KEY,
  data_lancamento DATE NOT NULL,
  numero_pedido VARCHAR(50) NULL,
  cliente_id INT NOT NULL,
  suite_cliente VARCHAR(50) NULL,
  peso_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
  valor_produto_usd DECIMAL(10,2) NOT NULL DEFAULT 0,
  frete_ups_usd DECIMAL(10,2) NOT NULL DEFAULT 0,
  valor_redirecionamento_usd DECIMAL(10,2) NOT NULL DEFAULT 0,
  servico_compra_usd DECIMAL(10,2) NOT NULL DEFAULT 0,
  frete_etiqueta_usd DECIMAL(10,2) NOT NULL DEFAULT 0,
  produtos_compra_usd DECIMAL(10,2) NOT NULL DEFAULT 0,
  taxa_dolar DECIMAL(10,4) NOT NULL DEFAULT 0,
  total_bruto_usd DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_bruto_brl DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_liquido_usd DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_liquido_brl DECIMAL(10,2) NOT NULL DEFAULT 0,
  comissao_usd DECIMAL(10,2) NOT NULL DEFAULT 0,
  comissao_brl DECIMAL(10,2) NOT NULL DEFAULT 0,
  observacao TEXT NULL,
  vendedor_id INT NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NULL DEFAULT NULL,
  INDEX (data_lancamento),
  INDEX (vendedor_id),
  CONSTRAINT fk_vendas_int_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
  CONSTRAINT fk_vendas_int_vendedor FOREIGN KEY (vendedor_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Campos adicionais em vendas para compras e origem
ALTER TABLE vendas
  ADD COLUMN IF NOT EXISTS produto_link VARCHAR(500) NULL,
  ADD COLUMN IF NOT EXISTS origem ENUM('organica','lead','pay-per-click') NULL,
  ADD COLUMN IF NOT EXISTS nc_tax TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS frete_manual_valor DECIMAL(12,2) NULL;

-- Tabela de doações
CREATE TABLE IF NOT EXISTS doacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  instituicao VARCHAR(200) NOT NULL,
  cnpj VARCHAR(32) NULL,
  descricao TEXT NULL,
  valor_brl DECIMAL(14,2) NOT NULL,
  data DATE NOT NULL,
  categoria VARCHAR(60) NULL,
  status ENUM('ativo','cancelado') NOT NULL DEFAULT 'ativo',
  criado_por INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  INDEX (data),
  CONSTRAINT fk_doacoes_usuario FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de compras (fila de aquisições)
CREATE TABLE IF NOT EXISTS compras (
  id INT AUTO_INCREMENT PRIMARY KEY,
  venda_id INT NOT NULL,
  suite VARCHAR(50) NULL,
  cliente_nome VARCHAR(150) NULL,
  cliente_contato VARCHAR(150) NULL,
  produto_link VARCHAR(500) NOT NULL,
  valor_usd DECIMAL(14,2) NULL,
  nc_tax TINYINT(1) NOT NULL DEFAULT 0,
  frete_aplicado TINYINT(1) NOT NULL DEFAULT 0,
  frete_valor DECIMAL(14,2) NULL,
  comprado TINYINT(1) NOT NULL DEFAULT 0,
  data_compra DATE NULL,
  responsavel_id INT NULL,
  observacoes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  UNIQUE KEY uq_compra_venda (venda_id),
  CONSTRAINT fk_compras_venda FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE,
  CONSTRAINT fk_compras_user FOREIGN KEY (responsavel_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Log de execuções de relatórios
CREATE TABLE IF NOT EXISTS relatorios_execucao (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  tipo VARCHAR(50) NOT NULL,
  parametros TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rel_exec_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS custos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  data DATE NOT NULL,
  categoria VARCHAR(100) NOT NULL,
  descricao VARCHAR(255) NULL,
  valor_usd DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (data),
  INDEX (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE vendas
  ADD COLUMN IF NOT EXISTS embalagem_usd DECIMAL(12,2) NOT NULL DEFAULT 0;

-- Adiciona coluna de perfil (role) aos usuários
ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS role VARCHAR(20) NOT NULL DEFAULT 'seller';

-- Status ativo do vendedor
ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS ativo TINYINT(1) NOT NULL DEFAULT 1;

-- Config padrão para embalagem por kg
INSERT INTO settings(`key`, `value`) VALUES ('embalagem_usd_por_kg', '9.70')
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`);

-- Usuário admin padrão
-- Senha padrão: password (recomenda-se alterar após o primeiro login)
INSERT IGNORE INTO usuarios (name, email, password_hash, role)
VALUES (
  'Admin',
  'admin@local',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'admin'
);

-- Tabela de comissões
CREATE TABLE IF NOT EXISTS comissoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vendedor_id INT NOT NULL,
  periodo VARCHAR(20) NOT NULL,
  bruto_total DECIMAL(14,2) NOT NULL DEFAULT 0,
  liquido_total DECIMAL(14,2) NOT NULL DEFAULT 0,
  comissao_individual DECIMAL(14,2) NOT NULL DEFAULT 0,
  bonus DECIMAL(14,2) NOT NULL DEFAULT 0,
  comissao_final DECIMAL(14,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  UNIQUE KEY vendedor_periodo (vendedor_id, periodo),
  INDEX (periodo),
  CONSTRAINT fk_comissoes_usuario FOREIGN KEY (vendedor_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Vendas Nacionais (Brasil)
CREATE TABLE IF NOT EXISTS vendas_nacionais (
  id INT AUTO_INCREMENT PRIMARY KEY,
  data_lancamento DATE NOT NULL,
  numero_pedido VARCHAR(50) NULL,
  cliente_id INT NOT NULL,
  suite_cliente VARCHAR(50) NULL,
  peso_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
  valor_produto_usd DECIMAL(10,2) NOT NULL DEFAULT 0,
  taxa_servico_usd DECIMAL(10,2) NOT NULL DEFAULT 0,
  servico_compra_usd DECIMAL(10,2) NOT NULL DEFAULT 0,
  frete_correios_brl DECIMAL(10,2) NOT NULL DEFAULT 0,
  frete_correios_usd DECIMAL(10,2) NOT NULL DEFAULT 0,
  produtos_compra_usd DECIMAL(10,2) NOT NULL DEFAULT 0,
  taxa_dolar DECIMAL(10,4) NOT NULL DEFAULT 0,
  total_bruto_usd DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_bruto_brl DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_liquido_usd DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_liquido_brl DECIMAL(10,2) NOT NULL DEFAULT 0,
  comissao_usd DECIMAL(10,2) NOT NULL DEFAULT 0,
  comissao_brl DECIMAL(10,2) NOT NULL DEFAULT 0,
  observacao TEXT NULL,
  vendedor_id INT NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NULL DEFAULT NULL,
  INDEX (data_lancamento),
  INDEX (vendedor_id),
  CONSTRAINT fk_vendas_nat_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
  CONSTRAINT fk_vendas_nat_vendedor FOREIGN KEY (vendedor_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Metas e Previsões
CREATE TABLE IF NOT EXISTS metas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(255) NOT NULL,
  descricao TEXT NULL,
  tipo ENUM('global','individual') NOT NULL,
  valor_meta DECIMAL(10,2) NOT NULL DEFAULT 0,
  moeda ENUM('USD','BRL') NOT NULL DEFAULT 'USD',
  data_inicio DATE NOT NULL,
  data_fim DATE NOT NULL,
  criado_por INT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (data_inicio),
  INDEX (data_fim),
  CONSTRAINT fk_metas_user FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS metas_vendedores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_meta INT NOT NULL,
  id_vendedor INT NOT NULL,
  valor_meta DECIMAL(10,2) NOT NULL DEFAULT 0,
  progresso_atual DECIMAL(10,2) NOT NULL DEFAULT 0,
  atualizado_em DATETIME NULL DEFAULT NULL,
  UNIQUE KEY uq_meta_vendedor (id_meta, id_vendedor),
  CONSTRAINT fk_metasvend_meta FOREIGN KEY (id_meta) REFERENCES metas(id) ON DELETE CASCADE,
  CONSTRAINT fk_metasvend_user FOREIGN KEY (id_vendedor) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notificacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  titulo VARCHAR(255) NOT NULL,
  mensagem TEXT NOT NULL,
  tipo ENUM('meta','sistema','aviso') NOT NULL DEFAULT 'meta',
  lida TINYINT(1) NOT NULL DEFAULT 0,
  criada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (id_usuario),
  INDEX (lida),
  CONSTRAINT fk_notif_user FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
