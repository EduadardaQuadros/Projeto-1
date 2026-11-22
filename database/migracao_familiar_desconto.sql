-- Atualiza o banco volei já em uso. Não apaga dados.
USE volei;

ALTER TABLE aluno_responsavel
  MODIFY parentesco ENUM('mae', 'pai', 'outro', 'familiar') NOT NULL;

ALTER TABLE planos
  ADD COLUMN desconto_inicial DECIMAL(5,2) NULL AFTER valor_centavos,
  ADD COLUMN desconto_meses TINYINT UNSIGNED NULL AFTER desconto_inicial;

ALTER TABLE contas_aluno
  ADD COLUMN desconto_desde DATE NULL AFTER pagador_id;

UPDATE contas_aluno
SET desconto_desde = DATE_FORMAT(criado_em, '%Y-%m-01')
WHERE desconto_desde IS NULL;

ALTER TABLE cobrancas
  ADD COLUMN desconto_inicial DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER valor_cheio_centavos;
