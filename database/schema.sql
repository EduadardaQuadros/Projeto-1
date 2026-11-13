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
  parentesco ENUM('mae', 'pai', 'outro') NOT NULL,
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
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT ck_turmas_capacidade CHECK (capacidade IS NULL OR capacidade >= 1)
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

-- Senha inicial: volei123
INSERT INTO usuarios (nome, email, senha_hash, papel) VALUES (
  'Administrador',
  'admin@escola.local',
  '$2y$12$Iip/BUyDV25.FqqtJIY7bObSu4pnZOO.IKMzFXwAveHBeuJScILAW',
  'equipe'
);
