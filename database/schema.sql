-- Recria o banco volei do zero.
-- Acesso inicial da equipe: admin@escola.local / volei123

DROP DATABASE IF EXISTS volei;
CREATE DATABASE volei CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE volei;

CREATE TABLE usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(180) NOT NULL,
  senha_hash VARCHAR(255) NOT NULL,
  papel ENUM('equipe', 'responsavel', 'professor', 'aluno') NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB;

CREATE TABLE responsaveis (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  telefone VARCHAR(20) NULL,
  cpf CHAR(11) NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_responsaveis_usuario (usuario_id),
  UNIQUE KEY uq_responsaveis_cpf (cpf),
  CONSTRAINT fk_responsaveis_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB;

CREATE TABLE alunos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  nome VARCHAR(120) NOT NULL,
  data_nascimento DATE NOT NULL,
  telefone VARCHAR(20) NULL,
  cpf CHAR(11) NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_alunos_usuario (usuario_id),
  UNIQUE KEY uq_alunos_cpf (cpf),
  CONSTRAINT fk_alunos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB;

CREATE TABLE professores (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  nome VARCHAR(120) NOT NULL,
  telefone VARCHAR(20) NULL,
  cpf CHAR(11) NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_professores_usuario (usuario_id),
  UNIQUE KEY uq_professores_cpf (cpf),
  CONSTRAINT fk_professores_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB;

CREATE TABLE aluno_responsavel (
  aluno_id INT UNSIGNED NOT NULL,
  responsavel_id INT UNSIGNED NOT NULL,
  parentesco ENUM('mae', 'pai', 'outro', 'familiar') NOT NULL,
  PRIMARY KEY (aluno_id, responsavel_id),
  CONSTRAINT fk_ar_aluno FOREIGN KEY (aluno_id) REFERENCES alunos (id) ON DELETE CASCADE,
  CONSTRAINT fk_ar_responsavel FOREIGN KEY (responsavel_id) REFERENCES responsaveis (id)
) ENGINE=InnoDB;

CREATE TABLE turmas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  nivel ENUM('iniciante', 'intermediario', 'avancado') NOT NULL,
  local VARCHAR(120) NULL,
  capacidade INT UNSIGNED NULL,
  ativa TINYINT(1) NOT NULL DEFAULT 1,
  convite CHAR(32) NOT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT ck_turmas_capacidade CHECK (capacidade IS NULL OR capacidade >= 1),
  UNIQUE KEY uq_turmas_convite (convite)
) ENGINE=InnoDB;

CREATE TABLE horarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  turma_id INT UNSIGNED NOT NULL,
  dia_semana TINYINT UNSIGNED NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fim TIME NOT NULL,
  CONSTRAINT fk_horarios_turma FOREIGN KEY (turma_id) REFERENCES turmas (id) ON DELETE CASCADE,
  CONSTRAINT ck_horarios_dia CHECK (dia_semana BETWEEN 1 AND 7),
  CONSTRAINT ck_horarios_ordem CHECK (hora_fim > hora_inicio)
) ENGINE=InnoDB;

CREATE TABLE turma_professor (
  turma_id INT UNSIGNED NOT NULL,
  professor_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (turma_id, professor_id),
  CONSTRAINT fk_tp_turma FOREIGN KEY (turma_id) REFERENCES turmas (id) ON DELETE CASCADE,
  CONSTRAINT fk_tp_professor FOREIGN KEY (professor_id) REFERENCES professores (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE matriculas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  aluno_id INT UNSIGNED NOT NULL,
  turma_id INT UNSIGNED NOT NULL,
  situacao ENUM('ativa', 'encerrada') NOT NULL DEFAULT 'ativa',
  data_inicio DATE NOT NULL,
  data_fim DATE NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_matriculas_turma (turma_id, situacao),
  KEY idx_matriculas_aluno (aluno_id, situacao),
  CONSTRAINT fk_matriculas_aluno FOREIGN KEY (aluno_id) REFERENCES alunos (id),
  CONSTRAINT fk_matriculas_turma FOREIGN KEY (turma_id) REFERENCES turmas (id)
) ENGINE=InnoDB;

CREATE TABLE planos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(80) NOT NULL,
  treinos_semana TINYINT UNSIGNED NOT NULL,
  valor_centavos INT UNSIGNED NOT NULL,
  desconto_inicial DECIMAL(5,2) NULL,
  desconto_meses TINYINT UNSIGNED NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT ck_planos_treinos CHECK (treinos_semana BETWEEN 1 AND 7),
  CONSTRAINT ck_planos_valor CHECK (valor_centavos >= 0),
  CONSTRAINT ck_planos_desconto CHECK (
    (desconto_inicial IS NULL AND desconto_meses IS NULL)
    OR (desconto_inicial > 0 AND desconto_inicial <= 100 AND desconto_meses BETWEEN 1 AND 36)
  )
) ENGINE=InnoDB;

CREATE TABLE faixas_desconto (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  minimo_atletas TINYINT UNSIGNED NOT NULL,
  percentual DECIMAL(5,2) NOT NULL,
  UNIQUE KEY uq_faixas_minimo (minimo_atletas),
  CONSTRAINT ck_faixas_minimo CHECK (minimo_atletas >= 2),
  CONSTRAINT ck_faixas_percentual CHECK (percentual >= 0 AND percentual <= 100)
) ENGINE=InnoDB;

CREATE TABLE contas_aluno (
  aluno_id INT UNSIGNED NOT NULL PRIMARY KEY,
  plano_id INT UNSIGNED NOT NULL,
  pagador_id INT UNSIGNED NOT NULL,
  desconto_desde DATE NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_contas_aluno_pagador (pagador_id),
  CONSTRAINT fk_contas_aluno_aluno FOREIGN KEY (aluno_id) REFERENCES alunos (id),
  CONSTRAINT fk_contas_aluno_plano FOREIGN KEY (plano_id) REFERENCES planos (id),
  CONSTRAINT fk_contas_aluno_pagador FOREIGN KEY (pagador_id) REFERENCES responsaveis (id)
) ENGINE=InnoDB;

CREATE TABLE contas_pagador (
  responsavel_id INT UNSIGNED NOT NULL PRIMARY KEY,
  desconto_extra DECIMAL(5,2) NULL,
  CONSTRAINT fk_contas_pagador_responsavel FOREIGN KEY (responsavel_id) REFERENCES responsaveis (id),
  CONSTRAINT ck_contas_pagador_extra CHECK (desconto_extra IS NULL OR (desconto_extra >= 0 AND desconto_extra <= 100))
) ENGINE=InnoDB;

CREATE TABLE cobrancas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  aluno_id INT UNSIGNED NOT NULL,
  pagador_id INT UNSIGNED NOT NULL,
  competencia DATE NOT NULL,
  plano_id INT UNSIGNED NOT NULL,
  valor_cheio_centavos INT UNSIGNED NOT NULL,
  desconto_inicial DECIMAL(5,2) NOT NULL DEFAULT 0,
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

INSERT INTO planos (nome, treinos_semana, valor_centavos) VALUES
  ('Adulto 1 treino', 1, 22000),
  ('Adulto 2 treinos', 2, 39600),
  ('Kids 1 treino', 1, 15000),
  ('Adolescente 2 treinos', 2, 20000),
  ('Iniciação 1 treino', 1, 15000),
  ('Iniciação 2 treinos', 2, 20000);

INSERT INTO faixas_desconto (minimo_atletas, percentual) VALUES
  (2, 5.00),
  (3, 10.00),
  (4, 15.00);

-- Senha inicial: volei123
INSERT INTO usuarios (nome, email, senha_hash, papel) VALUES (
  'Administrador',
  'admin@escola.local',
  '$2y$12$Iip/BUyDV25.FqqtJIY7bObSu4pnZOO.IKMzFXwAveHBeuJScILAW',
  'equipe'
);
