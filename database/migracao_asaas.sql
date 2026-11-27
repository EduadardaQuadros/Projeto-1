CREATE TABLE asaas_clientes (
  usuario_id INT UNSIGNED NOT NULL PRIMARY KEY,
  customer_id VARCHAR(40) NOT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_asaas_clientes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB;

CREATE TABLE pagamentos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  asaas_id VARCHAR(40) NULL,
  fatura_url VARCHAR(500) NULL,
  valor_centavos INT UNSIGNED NOT NULL,
  situacao ENUM('pendente', 'pago', 'cancelado') NOT NULL DEFAULT 'pendente',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_pagamentos_usuario (usuario_id, situacao),
  CONSTRAINT fk_pagamentos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB;

CREATE TABLE pagamento_itens (
  pagamento_id INT UNSIGNED NOT NULL,
  cobranca_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (pagamento_id, cobranca_id),
  CONSTRAINT fk_pagamento_itens_pagamento FOREIGN KEY (pagamento_id) REFERENCES pagamentos (id),
  CONSTRAINT fk_pagamento_itens_cobranca FOREIGN KEY (cobranca_id) REFERENCES cobrancas (id)
) ENGINE=InnoDB;

CREATE TABLE asaas_eventos (
  id VARCHAR(80) NOT NULL PRIMARY KEY,
  recebido_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
