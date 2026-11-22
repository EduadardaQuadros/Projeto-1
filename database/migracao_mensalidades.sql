-- Acrescenta mensalidades ao banco volei já em uso. Não apaga cadastros.

CREATE TABLE IF NOT EXISTS planos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(80) NOT NULL,
  treinos_semana TINYINT UNSIGNED NOT NULL,
  valor_centavos INT UNSIGNED NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT ck_planos_treinos CHECK (treinos_semana BETWEEN 1 AND 7),
  CONSTRAINT ck_planos_valor CHECK (valor_centavos >= 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS faixas_desconto (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  minimo_atletas TINYINT UNSIGNED NOT NULL,
  percentual DECIMAL(5,2) NOT NULL,
  UNIQUE KEY uq_faixas_minimo (minimo_atletas),
  CONSTRAINT ck_faixas_minimo CHECK (minimo_atletas >= 2),
  CONSTRAINT ck_faixas_percentual CHECK (percentual >= 0 AND percentual <= 100)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contas_aluno (
  aluno_id INT UNSIGNED NOT NULL PRIMARY KEY,
  plano_id INT UNSIGNED NOT NULL,
  pagador_id INT UNSIGNED NOT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_contas_aluno_pagador (pagador_id),
  CONSTRAINT fk_contas_aluno_aluno FOREIGN KEY (aluno_id) REFERENCES alunos (id),
  CONSTRAINT fk_contas_aluno_plano FOREIGN KEY (plano_id) REFERENCES planos (id),
  CONSTRAINT fk_contas_aluno_pagador FOREIGN KEY (pagador_id) REFERENCES responsaveis (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contas_pagador (
  responsavel_id INT UNSIGNED NOT NULL PRIMARY KEY,
  desconto_extra DECIMAL(5,2) NULL,
  CONSTRAINT fk_contas_pagador_responsavel FOREIGN KEY (responsavel_id) REFERENCES responsaveis (id),
  CONSTRAINT ck_contas_pagador_extra CHECK (desconto_extra IS NULL OR (desconto_extra >= 0 AND desconto_extra <= 100))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cobrancas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  aluno_id INT UNSIGNED NOT NULL,
  pagador_id INT UNSIGNED NOT NULL,
  competencia DATE NOT NULL,
  plano_id INT UNSIGNED NOT NULL,
  valor_cheio_centavos INT UNSIGNED NOT NULL,
  desconto_familia DECIMAL(5,2) NOT NULL DEFAULT 0,
  desconto_extra DECIMAL(5,2) NOT NULL DEFAULT 0,
  valor_final_centavos INT UNSIGNED NOT NULL,
  situacao ENUM('aberta', 'paga') NOT NULL DEFAULT 'aberta',
  pago_em DATE NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cobrancas_aluno_mes (aluno_id, competencia),
  KEY idx_cobrancas_pagador_mes (pagador_id, competencia),
  CONSTRAINT fk_cobrancas_aluno FOREIGN KEY (aluno_id) REFERENCES alunos (id),
  CONSTRAINT fk_cobrancas_pagador FOREIGN KEY (pagador_id) REFERENCES responsaveis (id),
  CONSTRAINT fk_cobrancas_plano FOREIGN KEY (plano_id) REFERENCES planos (id)
) ENGINE=InnoDB;

INSERT INTO planos (nome, treinos_semana, valor_centavos)
SELECT s.nome, s.treinos_semana, s.valor_centavos
FROM (
  SELECT 'Adulto 1 treino' AS nome, 1 AS treinos_semana, 22000 AS valor_centavos
  UNION ALL SELECT 'Adulto 2 treinos', 2, 39600
  UNION ALL SELECT 'Kids 1 treino', 1, 15000
  UNION ALL SELECT 'Adolescente 2 treinos', 2, 20000
  UNION ALL SELECT 'Iniciação 1 treino', 1, 15000
  UNION ALL SELECT 'Iniciação 2 treinos', 2, 20000
) AS s
WHERE NOT EXISTS (SELECT 1 FROM planos p WHERE p.nome = s.nome);

INSERT INTO faixas_desconto (minimo_atletas, percentual)
SELECT 2, 5.00 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faixas_desconto WHERE minimo_atletas = 2);

INSERT INTO faixas_desconto (minimo_atletas, percentual)
SELECT 3, 10.00 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faixas_desconto WHERE minimo_atletas = 3);

INSERT INTO faixas_desconto (minimo_atletas, percentual)
SELECT 4, 15.00 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faixas_desconto WHERE minimo_atletas = 4);
