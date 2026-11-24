<?php

declare(strict_types=1);

function criar_usuario(PDO $pdo, string $nome, string $email, string $senhaHash, string $papel): int
{
    $st = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, papel) VALUES (?, ?, ?, ?)');
    $st->execute([$nome, $email, $senhaHash, $papel]);
    return (int) $pdo->lastInsertId();
}

function dados_pessoa(): array
{
    return dados_campos('');
}

function dados_campos(string $prefixo): array
{
    return [
        'nome' => nome_valido(campo($prefixo . 'nome')),
        'email' => email_valido(campo($prefixo . 'email')),
        'telefone' => telefone_ou_nulo(campo($prefixo . 'telefone')),
        'cpf' => cpf_ou_nulo(campo($prefixo . 'cpf')),
    ];
}

function garantir_email_cpf(string $tabela, string $email, ?string $cpf, ?int $ignorarUsuarioId = null, ?int $ignorarId = null): void
{
    if (email_em_uso($email, $ignorarUsuarioId)) {
        throw new ValidacaoException('Este e-mail já está em uso.');
    }
    if ($cpf !== null && cpf_em_uso($tabela, $cpf, $ignorarId)) {
        throw new ValidacaoException('Este CPF já está cadastrado.');
    }
}

function criar_aluno(): int
{
    $dados = dados_pessoa();
    $nascimento = campo('data_nascimento');
    validar_nascimento($nascimento);
    $senhaHash = validar_nova_senha(true);
    $responsavelId = campo_int('responsavel_id');
    $parentesco = campo('parentesco');
    if (eh_menor($nascimento) && $responsavelId <= 0) {
        throw new ValidacaoException('Aluno menor de 18 anos precisa de um responsável.');
    }
    if ($responsavelId > 0) {
        conferir_vinculo($nascimento, $parentesco);
        if (q1('SELECT id FROM responsaveis WHERE id = ?', [$responsavelId]) === null) {
            throw new ValidacaoException('Pessoa não encontrada.');
        }
    }
    garantir_email_cpf('alunos', $dados['email'], $dados['cpf']);

    return (int) transacao(function (PDO $pdo) use ($dados, $nascimento, $senhaHash, $responsavelId, $parentesco): int {
        $usuarioId = criar_usuario($pdo, $dados['nome'], $dados['email'], $senhaHash, 'aluno');
        $st = $pdo->prepare('INSERT INTO alunos (usuario_id, nome, data_nascimento, telefone, cpf) VALUES (?, ?, ?, ?, ?)');
        $st->execute([$usuarioId, $dados['nome'], $nascimento, $dados['telefone'], $dados['cpf']]);
        $alunoId = (int) $pdo->lastInsertId();
        if ($responsavelId > 0) {
            $st = $pdo->prepare('INSERT INTO aluno_responsavel (aluno_id, responsavel_id, parentesco) VALUES (?, ?, ?)');
            $st->execute([$alunoId, $responsavelId, $parentesco]);
        }
        return $alunoId;
    });
}

function atualizar_aluno(int $id): void
{
    $aluno = q1('SELECT id, usuario_id, data_nascimento FROM alunos WHERE id = ?', [$id]);
    if ($aluno === null) {
        nao_encontrado('Aluno não encontrado.');
    }
    $dados = dados_pessoa();
    $nascimento = campo('data_nascimento');
    validar_nascimento($nascimento);
    $vinculos = q1(
        "SELECT COUNT(*) AS n FROM aluno_responsavel WHERE aluno_id = ? AND parentesco <> 'familiar'",
        [$id]
    );
    if (eh_menor($nascimento) && (int) $vinculos['n'] < 1) {
        throw new ValidacaoException('Aluno menor de 18 anos precisa de um responsável. Vincule um responsável antes de alterar a data.');
    }
    garantir_email_cpf('alunos', $dados['email'], $dados['cpf'], (int) $aluno['usuario_id'], $id);
    transacao(function (PDO $pdo) use ($aluno, $dados, $nascimento): void {
        $st = $pdo->prepare('UPDATE usuarios SET nome = ?, email = ? WHERE id = ?');
        $st->execute([$dados['nome'], $dados['email'], (int) $aluno['usuario_id']]);
        $st = $pdo->prepare('UPDATE alunos SET nome = ?, data_nascimento = ?, telefone = ?, cpf = ? WHERE id = ?');
        $st->execute([$dados['nome'], $nascimento, $dados['telefone'], $dados['cpf'], (int) $aluno['id']]);
    });
}

function validar_nascimento(string $nascimento): void
{
    if (!data_valida($nascimento)) {
        throw new ValidacaoException('Informe a data de nascimento.');
    }
    $data = DateTimeImmutable::createFromFormat('!Y-m-d', $nascimento);
    $hoje = new DateTimeImmutable('today');
    if ($data > $hoje) {
        throw new ValidacaoException('A data de nascimento não pode ser no futuro.');
    }
    if ($data < new DateTimeImmutable('1920-01-01')) {
        throw new ValidacaoException('Confira a data de nascimento.');
    }
}

function exigir_parentesco(string $parentesco): void
{
    if (!isset(PARENTESCOS[$parentesco])) {
        throw new ValidacaoException('Escolha o parentesco.');
    }
}

function conferir_vinculo(string $nascimento, string $parentesco): void
{
    if ($parentesco === 'familiar') {
        if (eh_menor($nascimento)) {
            throw new ValidacaoException('Menor de idade precisa de um responsável, não de um familiar.');
        }
        return;
    }
    exigir_parentesco($parentesco);
    if (!eh_menor($nascimento)) {
        throw new ValidacaoException('Adulto não recebe responsável. Vincule um familiar.');
    }
}

function criar_professor(): int
{
    $dados = dados_pessoa();
    $senhaHash = validar_nova_senha(true);
    garantir_email_cpf('professores', $dados['email'], $dados['cpf']);
    return (int) transacao(function (PDO $pdo) use ($dados, $senhaHash): int {
        $usuarioId = criar_usuario($pdo, $dados['nome'], $dados['email'], $senhaHash, 'professor');
        $st = $pdo->prepare('INSERT INTO professores (usuario_id, nome, telefone, cpf) VALUES (?, ?, ?, ?)');
        $st->execute([$usuarioId, $dados['nome'], $dados['telefone'], $dados['cpf']]);
        return (int) $pdo->lastInsertId();
    });
}

function atualizar_professor(int $id): void
{
    $professor = q1('SELECT id, usuario_id FROM professores WHERE id = ?', [$id]);
    if ($professor === null) {
        nao_encontrado('Professor não encontrado.');
    }
    $dados = dados_pessoa();
    garantir_email_cpf('professores', $dados['email'], $dados['cpf'], (int) $professor['usuario_id'], $id);
    transacao(function (PDO $pdo) use ($professor, $dados): void {
        $st = $pdo->prepare('UPDATE usuarios SET nome = ?, email = ? WHERE id = ?');
        $st->execute([$dados['nome'], $dados['email'], (int) $professor['usuario_id']]);
        $st = $pdo->prepare('UPDATE professores SET nome = ?, telefone = ?, cpf = ? WHERE id = ?');
        $st->execute([$dados['nome'], $dados['telefone'], $dados['cpf'], (int) $professor['id']]);
    });
}

function criar_responsavel(): int
{
    $dados = dados_pessoa();
    $senhaHash = validar_nova_senha(true);
    garantir_email_cpf('responsaveis', $dados['email'], $dados['cpf']);
    return (int) transacao(function (PDO $pdo) use ($dados, $senhaHash): int {
        $usuarioId = criar_usuario($pdo, $dados['nome'], $dados['email'], $senhaHash, 'responsavel');
        $st = $pdo->prepare('INSERT INTO responsaveis (usuario_id, telefone, cpf) VALUES (?, ?, ?)');
        $st->execute([$usuarioId, $dados['telefone'], $dados['cpf']]);
        return (int) $pdo->lastInsertId();
    });
}

function atualizar_responsavel(int $id): void
{
    $responsavel = q1('SELECT id, usuario_id FROM responsaveis WHERE id = ?', [$id]);
    if ($responsavel === null) {
        nao_encontrado('Responsável não encontrado.');
    }
    $dados = dados_pessoa();
    garantir_email_cpf('responsaveis', $dados['email'], $dados['cpf'], (int) $responsavel['usuario_id'], $id);
    transacao(function (PDO $pdo) use ($responsavel, $dados): void {
        $st = $pdo->prepare('UPDATE usuarios SET nome = ?, email = ? WHERE id = ?');
        $st->execute([$dados['nome'], $dados['email'], (int) $responsavel['usuario_id']]);
        $st = $pdo->prepare('UPDATE responsaveis SET telefone = ?, cpf = ? WHERE id = ?');
        $st->execute([$dados['telefone'], $dados['cpf'], (int) $responsavel['id']]);
    });
}

function dados_turma(): array
{
    $nome = nome_valido(campo('nome'));
    $nivel = campo('nivel');
    if (!isset(NIVEIS[$nivel])) {
        throw new ValidacaoException('Escolha o nível.');
    }
    $local = texto_ou_nulo(campo('local'), 120, 'O local está longo demais.');
    $capacidade = capacidade_ou_nulo(campo('capacidade'));
    return [
        'nome' => $nome,
        'nivel' => $nivel,
        'local' => $local,
        'capacidade' => $capacidade,
        'ativa' => isset($_POST['ativa']) ? 1 : 0,
    ];
}

function capacidade_ou_nulo(string $valor): ?int
{
    if ($valor === '') {
        return null;
    }
    if (!preg_match('/^\d+$/', $valor) || (int) $valor < 1 || (int) $valor > 500) {
        throw new ValidacaoException('A capacidade precisa ser um número maior que zero, ou ficar em branco.');
    }
    return (int) $valor;
}

function criar_turma(): int
{
    $dados = dados_turma();
    exec_sql(
        'INSERT INTO turmas (nome, nivel, local, capacidade, ativa, convite) VALUES (?, ?, ?, ?, ?, ?)',
        [$dados['nome'], $dados['nivel'], $dados['local'], $dados['capacidade'], $dados['ativa'], novo_convite()]
    );
    return (int) db()->lastInsertId();
}

function renovar_convite(int $turmaId): void
{
    if (q1('SELECT id FROM turmas WHERE id = ?', [$turmaId]) === null) {
        nao_encontrado('Turma não encontrada.');
    }
    exec_sql('UPDATE turmas SET convite = ? WHERE id = ?', [novo_convite(), $turmaId]);
}

function turma_por_convite(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
        return null;
    }
    return q1('SELECT * FROM turmas WHERE convite = ?', [$token]);
}

function garantir_link_convite(array $turma): string
{
    $token = (string) ($turma['convite'] ?? '');
    if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
        $token = novo_convite();
        exec_sql('UPDATE turmas SET convite = ? WHERE id = ?', [$token, (int) $turma['id']]);
    }
    return url_absoluta('/convite/' . $token);
}

function cadastrar_aluno_na_turma(int $turmaId): int
{
    $dados = dados_pessoa();
    $nascimento = campo('data_nascimento');
    validar_nascimento($nascimento);
    $senhaHash = validar_senha_campos('senha', 'senha_confirmacao');
    $menor = eh_menor($nascimento);
    $responsavel = null;
    $parentesco = '';
    $senhaResponsavel = null;
    if ($menor) {
        $responsavel = dados_campos('resp_');
        $parentesco = campo('parentesco');
        exigir_parentesco($parentesco);
        $senhaResponsavel = validar_senha_campos('resp_senha', 'resp_senha_confirmacao');
        if ($responsavel['email'] === $dados['email']) {
            throw new ValidacaoException('O e-mail do aluno e o do responsável precisam ser diferentes.');
        }
        garantir_email_cpf('responsaveis', $responsavel['email'], $responsavel['cpf']);
    }
    garantir_email_cpf('alunos', $dados['email'], $dados['cpf']);

    return (int) transacao(function (PDO $pdo) use ($dados, $nascimento, $senhaHash, $menor, $responsavel, $parentesco, $senhaResponsavel, $turmaId): int {
        $responsavelId = 0;
        if ($menor && $responsavel !== null && $senhaResponsavel !== null) {
            $usuarioResponsavel = criar_usuario($pdo, $responsavel['nome'], $responsavel['email'], $senhaResponsavel, 'responsavel');
            $st = $pdo->prepare('INSERT INTO responsaveis (usuario_id, telefone, cpf) VALUES (?, ?, ?)');
            $st->execute([$usuarioResponsavel, $responsavel['telefone'], $responsavel['cpf']]);
            $responsavelId = (int) $pdo->lastInsertId();
        }
        $usuarioId = criar_usuario($pdo, $dados['nome'], $dados['email'], $senhaHash, 'aluno');
        $st = $pdo->prepare('INSERT INTO alunos (usuario_id, nome, data_nascimento, telefone, cpf) VALUES (?, ?, ?, ?, ?)');
        $st->execute([$usuarioId, $dados['nome'], $nascimento, $dados['telefone'], $dados['cpf']]);
        $alunoId = (int) $pdo->lastInsertId();
        if ($responsavelId > 0) {
            $st = $pdo->prepare('INSERT INTO aluno_responsavel (aluno_id, responsavel_id, parentesco) VALUES (?, ?, ?)');
            $st->execute([$alunoId, $responsavelId, $parentesco]);
        }
        matricular($alunoId, $turmaId, hoje());
        return $usuarioId;
    });
}

function atualizar_turma(int $id): void
{
    if (q1('SELECT id FROM turmas WHERE id = ?', [$id]) === null) {
        nao_encontrado('Turma não encontrada.');
    }
    $dados = dados_turma();
    if ($dados['capacidade'] !== null) {
        $total = q1("SELECT COUNT(*) AS n FROM matriculas WHERE turma_id = ? AND situacao = 'ativa'", [$id]);
        if ($dados['capacidade'] < (int) $total['n']) {
            throw new ValidacaoException('A capacidade não pode ser menor que o número de alunos matriculados (' . (int) $total['n'] . ').');
        }
    }
    exec_sql(
        'UPDATE turmas SET nome = ?, nivel = ?, local = ?, capacidade = ?, ativa = ? WHERE id = ?',
        [$dados['nome'], $dados['nivel'], $dados['local'], $dados['capacidade'], $dados['ativa'], $id]
    );
}

function adicionar_horario(int $turmaId): void
{
    if (q1('SELECT id FROM turmas WHERE id = ?', [$turmaId]) === null) {
        throw new ValidacaoException('Turma não encontrada.');
    }
    $dia = campo_int('dia_semana');
    $inicio = campo('hora_inicio');
    $fim = campo('hora_fim');
    if (!isset(DIAS[$dia])) {
        throw new ValidacaoException('Escolha o dia da semana.');
    }
    if (!hora_valida($inicio) || !hora_valida($fim)) {
        throw new ValidacaoException('Informe o horário de início e de fim.');
    }
    $inicio = hora_curta($inicio);
    $fim = hora_curta($fim);
    $ini = minutos($inicio);
    $end = minutos($fim);
    if ($ini >= $end) {
        throw new ValidacaoException('O horário de fim precisa ser depois do início.');
    }
    $existentes = q('SELECT hora_inicio, hora_fim FROM horarios WHERE turma_id = ? AND dia_semana = ?', [$turmaId, $dia]);
    foreach ($existentes as $horario) {
        $a = minutos($horario['hora_inicio']);
        $b = minutos($horario['hora_fim']);
        if ($ini < $b && $a < $end) {
            throw new ValidacaoException('Esse horário cruza com outro já cadastrado neste dia.');
        }
    }
    exec_sql(
        'INSERT INTO horarios (turma_id, dia_semana, hora_inicio, hora_fim) VALUES (?, ?, ?, ?)',
        [$turmaId, $dia, $inicio . ':00', $fim . ':00']
    );
}

function excluir_horario(int $id): int
{
    $horario = q1('SELECT id, turma_id FROM horarios WHERE id = ?', [$id]);
    if ($horario === null) {
        throw new ValidacaoException('Horário não encontrado.');
    }
    exec_sql('DELETE FROM horarios WHERE id = ?', [$id]);
    return (int) $horario['turma_id'];
}

function vincular_professor(int $turmaId, int $professorId): void
{
    if (q1('SELECT id FROM turmas WHERE id = ?', [$turmaId]) === null) {
        throw new ValidacaoException('Turma não encontrada.');
    }
    $professor = q1(
        'SELECT p.id FROM professores p JOIN usuarios u ON u.id = p.usuario_id WHERE p.id = ? AND u.ativo = 1',
        [$professorId]
    );
    if ($professor === null) {
        throw new ValidacaoException('Professor não encontrado.');
    }
    if (q1('SELECT 1 AS ok FROM turma_professor WHERE turma_id = ? AND professor_id = ?', [$turmaId, $professorId])) {
        throw new ValidacaoException('Este professor já está nesta turma.');
    }
    exec_sql('INSERT INTO turma_professor (turma_id, professor_id) VALUES (?, ?)', [$turmaId, $professorId]);
}

function desvincular_professor(int $turmaId, int $professorId): void
{
    exec_sql('DELETE FROM turma_professor WHERE turma_id = ? AND professor_id = ?', [$turmaId, $professorId]);
}

function matricular(int $alunoId, int $turmaId, string $inicio): void
{
    if (!data_valida($inicio)) {
        throw new ValidacaoException('Informe a data de início.');
    }
    $turma = q1('SELECT id, capacidade, ativa FROM turmas WHERE id = ?', [$turmaId]);
    if ($turma === null) {
        throw new ValidacaoException('Turma não encontrada.');
    }
    if (!(int) $turma['ativa']) {
        throw new ValidacaoException('Esta turma está inativa.');
    }
    $aluno = q1(
        'SELECT a.id FROM alunos a JOIN usuarios u ON u.id = a.usuario_id WHERE a.id = ? AND u.ativo = 1',
        [$alunoId]
    );
    if ($aluno === null) {
        throw new ValidacaoException('Aluno não encontrado.');
    }
    $duplicada = q1(
        "SELECT id FROM matriculas WHERE aluno_id = ? AND turma_id = ? AND situacao = 'ativa'",
        [$alunoId, $turmaId]
    );
    if ($duplicada !== null) {
        throw new ValidacaoException('Este aluno já está matriculado nesta turma.');
    }
    if ($turma['capacidade'] !== null) {
        $total = q1("SELECT COUNT(*) AS n FROM matriculas WHERE turma_id = ? AND situacao = 'ativa'", [$turmaId]);
        if ((int) $total['n'] >= (int) $turma['capacidade']) {
            throw new ValidacaoException('A turma já atingiu a capacidade.');
        }
    }
    exec_sql(
        "INSERT INTO matriculas (aluno_id, turma_id, situacao, data_inicio) VALUES (?, ?, 'ativa', ?)",
        [$alunoId, $turmaId, $inicio]
    );
}

function encerrar_matricula(int $id): void
{
    $matricula = q1('SELECT id, situacao FROM matriculas WHERE id = ?', [$id]);
    if ($matricula === null || $matricula['situacao'] !== 'ativa') {
        throw new ValidacaoException('Matrícula não encontrada.');
    }
    exec_sql(
        "UPDATE matriculas SET situacao = 'encerrada', data_fim = ? WHERE id = ?",
        [hoje(), $id]
    );
}

function vincular_responsavel(int $alunoId, int $responsavelId, string $parentesco): void
{
    $aluno = q1('SELECT id, data_nascimento FROM alunos WHERE id = ?', [$alunoId]);
    if ($aluno === null) {
        throw new ValidacaoException('Aluno não encontrado.');
    }
    conferir_vinculo((string) $aluno['data_nascimento'], $parentesco);
    $responsavel = q1(
        'SELECT r.id FROM responsaveis r JOIN usuarios u ON u.id = r.usuario_id WHERE r.id = ? AND u.ativo = 1',
        [$responsavelId]
    );
    if ($responsavel === null) {
        throw new ValidacaoException('Responsável não encontrado.');
    }
    if (q1('SELECT 1 AS ok FROM aluno_responsavel WHERE aluno_id = ? AND responsavel_id = ?', [$alunoId, $responsavelId])) {
        throw new ValidacaoException('Esta pessoa já está vinculada ao aluno.');
    }
    exec_sql(
        'INSERT INTO aluno_responsavel (aluno_id, responsavel_id, parentesco) VALUES (?, ?, ?)',
        [$alunoId, $responsavelId, $parentesco]
    );
}

function desvincular_responsavel(int $alunoId, int $responsavelId): void
{
    $aluno = q1('SELECT id, data_nascimento FROM alunos WHERE id = ?', [$alunoId]);
    if ($aluno === null) {
        throw new ValidacaoException('Aluno não encontrado.');
    }
    $vinculo = q1(
        'SELECT parentesco FROM aluno_responsavel WHERE aluno_id = ? AND responsavel_id = ?',
        [$alunoId, $responsavelId]
    );
    if ($vinculo !== null && eh_menor($aluno['data_nascimento']) && $vinculo['parentesco'] !== 'familiar') {
        $total = q1(
            "SELECT COUNT(*) AS n FROM aluno_responsavel WHERE aluno_id = ? AND parentesco <> 'familiar'",
            [$alunoId]
        );
        if ((int) $total['n'] <= 1) {
            throw new ValidacaoException('Aluno menor de idade precisa ter pelo menos um responsável.');
        }
    }
    exec_sql(
        'DELETE FROM aluno_responsavel WHERE aluno_id = ? AND responsavel_id = ?',
        [$alunoId, $responsavelId]
    );
}

function redefinir_senha(int $usuarioId): void
{
    $hash = validar_nova_senha(true);
    exec_sql('UPDATE usuarios SET senha_hash = ? WHERE id = ?', [$hash, $usuarioId]);
}

function definir_ativo(int $usuarioId, int $ativo): void
{
    exec_sql('UPDATE usuarios SET ativo = ? WHERE id = ? AND papel <> \'equipe\'', [$ativo ? 1 : 0, $usuarioId]);
}

function trocar_propria_senha(int $usuarioId): void
{
    $atual = q1('SELECT senha_hash FROM usuarios WHERE id = ?', [$usuarioId]);
    if ($atual === null || !password_verify(campo_raw('senha_atual'), $atual['senha_hash'])) {
        throw new ValidacaoException('A senha atual não confere.');
    }
    redefinir_senha($usuarioId);
}

function perfil_aluno(int $usuarioId): ?array
{
    return q1('SELECT id, nome, data_nascimento FROM alunos WHERE usuario_id = ?', [$usuarioId]);
}

function perfil_responsavel(int $usuarioId): ?array
{
    return q1('SELECT id FROM responsaveis WHERE usuario_id = ?', [$usuarioId]);
}

function planos_todos(): array
{
    return q(
        'SELECT id, nome, treinos_semana, valor_centavos, desconto_inicial, desconto_meses, ativo
         FROM planos
         ORDER BY ativo DESC, nome'
    );
}

function planos_para_escolha(?int $planoAtual): array
{
    return q(
        'SELECT id, nome, treinos_semana, valor_centavos, desconto_inicial, desconto_meses, ativo
         FROM planos
         WHERE ativo = 1 OR id = ?
         ORDER BY ativo DESC, nome',
        [$planoAtual ?? 0]
    );
}

function pagadores_do_aluno(int $alunoId, bool $menor): array
{
    $filtro = $menor ? "ar.parentesco <> 'familiar'" : "ar.parentesco = 'familiar'";
    return q(
        "SELECT r.id, u.nome
         FROM aluno_responsavel ar
         JOIN responsaveis r ON r.id = ar.responsavel_id
         JOIN usuarios u ON u.id = r.usuario_id
         WHERE ar.aluno_id = ? AND u.ativo = 1 AND {$filtro}
         ORDER BY u.nome",
        [$alunoId]
    );
}

function pagadores_ativos(): array
{
    return q(
        'SELECT r.id, u.nome
         FROM responsaveis r
         JOIN usuarios u ON u.id = r.usuario_id
         WHERE u.ativo = 1
         ORDER BY u.nome'
    );
}

function conta_do_aluno(int $alunoId): ?array
{
    return q1(
        'SELECT ca.aluno_id, ca.plano_id, ca.pagador_id, ca.desconto_desde, p.nome AS plano_nome,
                p.valor_centavos, p.treinos_semana, p.ativo AS plano_ativo,
                p.desconto_inicial, p.desconto_meses, u.nome AS pagador_nome
         FROM contas_aluno ca
         JOIN planos p ON p.id = ca.plano_id
         JOIN responsaveis r ON r.id = ca.pagador_id
         JOIN usuarios u ON u.id = r.usuario_id
         WHERE ca.aluno_id = ?',
        [$alunoId]
    );
}

function faixas_desconto(): array
{
    return q('SELECT id, minimo_atletas, percentual FROM faixas_desconto ORDER BY minimo_atletas');
}

function percentual_familia(int $atletas, array $faixas): string
{
    $escolhida = '0.00';
    $pisoEscolhido = 0;
    foreach ($faixas as $faixa) {
        $piso = (int) $faixa['minimo_atletas'];
        if ($atletas >= $piso && $piso >= $pisoEscolhido) {
            $pisoEscolhido = $piso;
            $escolhida = number_format((float) $faixa['percentual'], 2, '.', '');
        }
    }
    return $escolhida;
}

function atletas_ativos_por_pagador(): array
{
    $linhas = q(
        "SELECT ca.pagador_id, COUNT(*) AS n
         FROM contas_aluno ca
         WHERE EXISTS (
             SELECT 1 FROM matriculas m
             WHERE m.aluno_id = ca.aluno_id AND m.situacao = 'ativa'
         )
         GROUP BY ca.pagador_id"
    );
    $mapa = [];
    foreach ($linhas as $linha) {
        $mapa[(int) $linha['pagador_id']] = (int) $linha['n'];
    }
    return $mapa;
}

function extras_por_pagador(): array
{
    $linhas = q('SELECT responsavel_id, desconto_extra FROM contas_pagador WHERE desconto_extra IS NOT NULL');
    $mapa = [];
    foreach ($linhas as $linha) {
        $mapa[(int) $linha['responsavel_id']] = number_format((float) $linha['desconto_extra'], 2, '.', '');
    }
    return $mapa;
}

function calcular_mensalidade(int $cheio, int $pagadorId, array $contagens, array $faixas, array $extras, string $inicial = '0.00'): array
{
    $atletas = $contagens[$pagadorId] ?? 0;
    $familia = percentual_familia($atletas, $faixas);
    $extra = $extras[$pagadorId] ?? '0.00';
    $aposInicial = aplicar_desconto_centavos($cheio, $inicial);
    $aposFamilia = aplicar_desconto_centavos($aposInicial, $familia);
    return [
        'atletas' => $atletas,
        'inicial' => $inicial,
        'familia' => $familia,
        'extra' => $extra,
        'apos_inicial' => $aposInicial,
        'apos_familia' => $aposFamilia,
        'final' => aplicar_desconto_centavos($aposFamilia, $extra),
    ];
}

function inicial_da_linha(array $linha, string $competencia): string
{
    return percentual_inicial_vigente(
        $linha['desconto_inicial'] !== null && $linha['desconto_inicial'] !== '' ? (string) $linha['desconto_inicial'] : null,
        $linha['desconto_meses'] !== null && $linha['desconto_meses'] !== '' ? (int) $linha['desconto_meses'] : null,
        isset($linha['desconto_desde']) && $linha['desconto_desde'] !== null && $linha['desconto_desde'] !== '' ? (string) $linha['desconto_desde'] : null,
        $competencia
    );
}

function alunos_sem_conta(): array
{
    return q(
        "SELECT a.id, a.nome
         FROM alunos a
         JOIN usuarios u ON u.id = a.usuario_id
         WHERE u.ativo = 1
           AND EXISTS (
               SELECT 1 FROM matriculas m
               WHERE m.aluno_id = a.id AND m.situacao = 'ativa'
           )
           AND NOT EXISTS (SELECT 1 FROM contas_aluno c WHERE c.aluno_id = a.id)
         ORDER BY a.nome"
    );
}

function grupos_contas(): array
{
    $linhas = q(
        "SELECT r.id AS pagador_id, u.nome AS pagador_nome, a.id AS aluno_id, a.nome AS aluno_nome,
                p.nome AS plano_nome, p.valor_centavos, p.treinos_semana,
                p.desconto_inicial, p.desconto_meses, ca.desconto_desde
         FROM contas_aluno ca
         JOIN responsaveis r ON r.id = ca.pagador_id
         JOIN usuarios u ON u.id = r.usuario_id
         JOIN alunos a ON a.id = ca.aluno_id
         JOIN planos p ON p.id = ca.plano_id
         WHERE EXISTS (
             SELECT 1 FROM matriculas m
             WHERE m.aluno_id = a.id AND m.situacao = 'ativa'
         )
         ORDER BY u.nome, a.nome"
    );
    $faixas = faixas_desconto();
    $contagens = atletas_ativos_por_pagador();
    $extras = extras_por_pagador();
    $grupos = [];
    foreach ($linhas as $linha) {
        $pagadorId = (int) $linha['pagador_id'];
        if (!isset($grupos[$pagadorId])) {
            $atletas = $contagens[$pagadorId] ?? 0;
            $grupos[$pagadorId] = [
                'pagador_id' => $pagadorId,
                'pagador_nome' => $linha['pagador_nome'],
                'extra' => $extras[$pagadorId] ?? null,
                'atletas' => $atletas,
                'familia' => percentual_familia($atletas, $faixas),
                'total' => 0,
                'itens' => [],
            ];
        }
        $calculo = calcular_mensalidade(
            (int) $linha['valor_centavos'],
            $pagadorId,
            $contagens,
            $faixas,
            $extras,
            inicial_da_linha($linha, mes_atual())
        );
        $grupos[$pagadorId]['total'] += $calculo['final'];
        $grupos[$pagadorId]['itens'][] = [
            'aluno_id' => (int) $linha['aluno_id'],
            'aluno_nome' => $linha['aluno_nome'],
            'plano_nome' => $linha['plano_nome'],
            'treinos_semana' => (int) $linha['treinos_semana'],
            'cheio' => (int) $linha['valor_centavos'],
            'inicial' => $calculo['inicial'],
            'familia' => $calculo['familia'],
            'extra' => $calculo['extra'],
            'apos_familia' => $calculo['apos_familia'],
            'final' => $calculo['final'],
        ];
    }
    return array_values($grupos);
}

function resumo_conta_aluno(int $alunoId): ?array
{
    $conta = conta_do_aluno($alunoId);
    if ($conta === null) {
        return null;
    }
    $calculo = calcular_mensalidade(
        (int) $conta['valor_centavos'],
        (int) $conta['pagador_id'],
        atletas_ativos_por_pagador(),
        faixas_desconto(),
        extras_por_pagador(),
        inicial_da_linha($conta, mes_atual())
    );
    $conta['atletas'] = $calculo['atletas'];
    $conta['inicial'] = $calculo['inicial'];
    $conta['familia'] = $calculo['familia'];
    $conta['extra'] = $calculo['extra'];
    $conta['apos_familia'] = $calculo['apos_familia'];
    $conta['final'] = $calculo['final'];
    $conta['inicial_ate'] = (float) $calculo['inicial'] > 0 && !empty($conta['desconto_desde']) && !empty($conta['desconto_meses'])
        ? fim_desconto_inicial((string) $conta['desconto_desde'], (int) $conta['desconto_meses'])
        : null;
    return $conta;
}

function avisos_desconto_inicial(): array
{
    $linhas = q(
        "SELECT a.id AS aluno_id, a.nome AS aluno_nome, p.id AS plano_id, p.nome AS plano_nome,
                p.desconto_inicial, p.desconto_meses, ca.desconto_desde
         FROM contas_aluno ca
         JOIN alunos a ON a.id = ca.aluno_id
         JOIN planos p ON p.id = ca.plano_id
         WHERE p.desconto_inicial IS NOT NULL
           AND p.desconto_meses IS NOT NULL
           AND ca.desconto_desde IS NOT NULL
           AND EXISTS (
               SELECT 1 FROM matriculas m
               WHERE m.aluno_id = a.id AND m.situacao = 'ativa'
           )
         ORDER BY a.nome"
    );
    $hoje = mes_atual();
    $proximo = somar_meses($hoje, 1);
    $avisos = [];
    foreach ($linhas as $linha) {
        $fim = fim_desconto_inicial((string) $linha['desconto_desde'], (int) $linha['desconto_meses']);
        if ($fim !== $hoje && $fim !== $proximo) {
            continue;
        }
        $avisos[] = [
            'aluno_id' => (int) $linha['aluno_id'],
            'aluno_nome' => $linha['aluno_nome'],
            'plano_id' => (int) $linha['plano_id'],
            'plano_nome' => $linha['plano_nome'],
            'percentual' => number_format((float) $linha['desconto_inicial'], 2, '.', ''),
            'acaba_em' => $fim,
        ];
    }
    return $avisos;
}

function dados_plano(): array
{
    $nome = trim(campo('nome'));
    $tamanho = mb_strlen($nome);
    if ($tamanho < 2 || $tamanho > 80) {
        throw new ValidacaoException('Informe o nome do plano.');
    }
    $treinos = campo_int('treinos_semana');
    if ($treinos < 1 || $treinos > 7) {
        throw new ValidacaoException('Informe de 1 a 7 treinos por semana.');
    }
    $inicial = percentual_ou_nulo(campo('desconto_inicial'));
    $meses = null;
    if ($inicial === null || $inicial === '0.00') {
        $inicial = null;
    } else {
        $meses = campo_int('desconto_meses');
        if ($meses < 1 || $meses > 36) {
            throw new ValidacaoException('Informe de 1 a 36 meses para o desconto inicial.');
        }
    }
    return [
        'nome' => $nome,
        'treinos' => $treinos,
        'valor' => centavos_de_texto(campo('valor')),
        'desconto_inicial' => $inicial,
        'desconto_meses' => $meses,
        'ativo' => isset($_POST['ativo']) ? 1 : 0,
    ];
}

function criar_plano(): int
{
    $dados = dados_plano();
    exec_sql(
        'INSERT INTO planos (nome, treinos_semana, valor_centavos, desconto_inicial, desconto_meses, ativo) VALUES (?, ?, ?, ?, ?, ?)',
        [$dados['nome'], $dados['treinos'], $dados['valor'], $dados['desconto_inicial'], $dados['desconto_meses'], $dados['ativo']]
    );
    return (int) db()->lastInsertId();
}

function atualizar_plano(int $id): void
{
    if (q1('SELECT id FROM planos WHERE id = ?', [$id]) === null) {
        nao_encontrado('Plano não encontrado.');
    }
    $dados = dados_plano();
    exec_sql(
        'UPDATE planos SET nome = ?, treinos_semana = ?, valor_centavos = ?, desconto_inicial = ?, desconto_meses = ?, ativo = ? WHERE id = ?',
        [$dados['nome'], $dados['treinos'], $dados['valor'], $dados['desconto_inicial'], $dados['desconto_meses'], $dados['ativo'], $id]
    );
}

function definir_plano_ativo(int $id, int $ativo): void
{
    if (q1('SELECT id FROM planos WHERE id = ?', [$id]) === null) {
        nao_encontrado('Plano não encontrado.');
    }
    exec_sql('UPDATE planos SET ativo = ? WHERE id = ?', [$ativo ? 1 : 0, $id]);
}

function excluir_plano(int $id): void
{
    if (q1('SELECT id FROM planos WHERE id = ?', [$id]) === null) {
        nao_encontrado('Plano não encontrado.');
    }
    $uso = q1(
        'SELECT 1 AS ok FROM contas_aluno WHERE plano_id = ?
         UNION
         SELECT 1 FROM cobrancas WHERE plano_id = ?
         LIMIT 1',
        [$id, $id]
    );
    if ($uso !== null) {
        throw new ValidacaoException('Este plano está em uma conta ou em uma cobrança. Troque o plano dessas pessoas, ou desative este.');
    }
    exec_sql('DELETE FROM planos WHERE id = ?', [$id]);
}

function dados_faixa(): array
{
    $minimo = campo_int('minimo_atletas');
    if ($minimo < 2 || $minimo > 30) {
        throw new ValidacaoException('O mínimo de atletas começa em 2.');
    }
    $percentual = percentual_ou_nulo(campo('percentual'));
    if ($percentual === null) {
        throw new ValidacaoException('Informe o percentual.');
    }
    return ['minimo' => $minimo, 'percentual' => $percentual];
}

function faixa_minimo_livre(int $minimo, ?int $ignorarId = null): void
{
    $sql = 'SELECT id FROM faixas_desconto WHERE minimo_atletas = ?';
    $params = [$minimo];
    if ($ignorarId !== null) {
        $sql .= ' AND id <> ?';
        $params[] = $ignorarId;
    }
    if (q1($sql, $params) !== null) {
        throw new ValidacaoException('Já existe uma faixa para essa quantidade de atletas.');
    }
}

function criar_faixa(): void
{
    $dados = dados_faixa();
    faixa_minimo_livre($dados['minimo']);
    exec_sql(
        'INSERT INTO faixas_desconto (minimo_atletas, percentual) VALUES (?, ?)',
        [$dados['minimo'], $dados['percentual']]
    );
}

function atualizar_faixa(int $id): void
{
    if (q1('SELECT id FROM faixas_desconto WHERE id = ?', [$id]) === null) {
        nao_encontrado('Faixa não encontrada.');
    }
    $dados = dados_faixa();
    faixa_minimo_livre($dados['minimo'], $id);
    exec_sql(
        'UPDATE faixas_desconto SET minimo_atletas = ?, percentual = ? WHERE id = ?',
        [$dados['minimo'], $dados['percentual'], $id]
    );
}

function excluir_faixa(int $id): void
{
    exec_sql('DELETE FROM faixas_desconto WHERE id = ?', [$id]);
}

function salvar_desconto_extra(int $responsavelId): void
{
    $responsavel = q1('SELECT id FROM responsaveis WHERE id = ?', [$responsavelId]);
    if ($responsavel === null) {
        nao_encontrado('Responsável não encontrado.');
    }
    $percentual = percentual_ou_nulo(campo('desconto_extra'));
    if ($percentual === null || $percentual === '0.00') {
        exec_sql('DELETE FROM contas_pagador WHERE responsavel_id = ?', [$responsavelId]);
        return;
    }
    $existe = q1('SELECT responsavel_id FROM contas_pagador WHERE responsavel_id = ?', [$responsavelId]);
    if ($existe === null) {
        exec_sql(
            'INSERT INTO contas_pagador (responsavel_id, desconto_extra) VALUES (?, ?)',
            [$responsavelId, $percentual]
        );
        return;
    }
    exec_sql(
        'UPDATE contas_pagador SET desconto_extra = ? WHERE responsavel_id = ?',
        [$percentual, $responsavelId]
    );
}

function garantir_responsavel_do_aluno(array $aluno): int
{
    $existente = q1('SELECT id FROM responsaveis WHERE usuario_id = ?', [(int) $aluno['usuario_id']]);
    if ($existente !== null) {
        return (int) $existente['id'];
    }
    $cpf = $aluno['cpf'] !== null && $aluno['cpf'] !== '' ? (string) $aluno['cpf'] : null;
    if ($cpf !== null && cpf_em_uso('responsaveis', $cpf)) {
        throw new ValidacaoException('O CPF deste aluno já está em outro responsável.');
    }
    exec_sql(
        'INSERT INTO responsaveis (usuario_id, telefone, cpf) VALUES (?, ?, ?)',
        [(int) $aluno['usuario_id'], $aluno['telefone'], $cpf]
    );
    return (int) db()->lastInsertId();
}

function gravar_conta_aluno(int $alunoId, int $planoId, int $pagadorId): void
{
    $existe = q1('SELECT aluno_id, plano_id, desconto_desde FROM contas_aluno WHERE aluno_id = ?', [$alunoId]);
    $desde = mes_atual();
    if ($existe !== null && (int) $existe['plano_id'] === $planoId && !empty($existe['desconto_desde'])) {
        $desde = (string) $existe['desconto_desde'];
    }
    if ($existe === null) {
        exec_sql(
            'INSERT INTO contas_aluno (aluno_id, plano_id, pagador_id, desconto_desde) VALUES (?, ?, ?, ?)',
            [$alunoId, $planoId, $pagadorId, $desde]
        );
        return;
    }
    exec_sql(
        'UPDATE contas_aluno SET plano_id = ?, pagador_id = ?, desconto_desde = ? WHERE aluno_id = ?',
        [$planoId, $pagadorId, $desde, $alunoId]
    );
}

function mover_filhos_para_pagador(int $pagadorId, int $excetoAlunoId): int
{
    $filhos = q(
        'SELECT ca.aluno_id
         FROM contas_aluno ca
         JOIN aluno_responsavel ar ON ar.aluno_id = ca.aluno_id
         WHERE ar.responsavel_id = ? AND ca.aluno_id <> ? AND ca.pagador_id <> ?',
        [$pagadorId, $excetoAlunoId, $pagadorId]
    );
    if ($filhos === []) {
        return 0;
    }
    exec_sql(
        'UPDATE contas_aluno ca
         JOIN aluno_responsavel ar ON ar.aluno_id = ca.aluno_id
         SET ca.pagador_id = ?
         WHERE ar.responsavel_id = ? AND ca.aluno_id <> ?',
        [$pagadorId, $pagadorId, $excetoAlunoId]
    );
    return count($filhos);
}

function salvar_conta_aluno(int $alunoId, bool $pagarProprio): int
{
    $aluno = q1(
        'SELECT a.id, a.usuario_id, a.data_nascimento, a.telefone, a.cpf
         FROM alunos a
         WHERE a.id = ?',
        [$alunoId]
    );
    if ($aluno === null) {
        throw new ValidacaoException('Aluno não encontrado.');
    }
    $plano = q1('SELECT id FROM planos WHERE id = ? AND ativo = 1', [campo_int('plano_id')]);
    if ($plano === null) {
        $atual = conta_do_aluno($alunoId);
        $planoId = campo_int('plano_id');
        if ($atual !== null && $planoId === (int) $atual['plano_id']) {
            $plano = ['id' => $planoId];
        }
    }
    if ($plano === null) {
        throw new ValidacaoException('Escolha um plano ativo.');
    }
    $planoId = (int) $plano['id'];
    if ($pagarProprio) {
        if (eh_menor((string) $aluno['data_nascimento'])) {
            throw new ValidacaoException('Aluno menor de 18 anos precisa de um adulto para pagar.');
        }
        return (int) transacao(function (PDO $pdo) use ($aluno, $alunoId, $planoId): int {
            $pagadorId = garantir_responsavel_do_aluno($aluno);
            gravar_conta_aluno($alunoId, $planoId, $pagadorId);
            return mover_filhos_para_pagador($pagadorId, $alunoId);
        });
    }
    $menor = eh_menor((string) $aluno['data_nascimento']);
    $pagadorId = campo_int('pagador_id');
    $permitidos = array_map(
        static fn (array $pagador): int => (int) $pagador['id'],
        pagadores_do_aluno($alunoId, $menor)
    );
    if (!in_array($pagadorId, $permitidos, true)) {
        throw new ValidacaoException($menor
            ? 'Para uma criança, quem paga precisa ser um responsável vinculado.'
            : 'Para um adulto, quem paga precisa ser um familiar vinculado, ou marque que este aluno também paga.');
    }
    gravar_conta_aluno($alunoId, $planoId, $pagadorId);
    return 0;
}

function salvar_conta_da_turma(int $turmaId, bool $pagarProprio): int
{
    $alunoId = campo_int('aluno_id');
    $matricula = q1(
        "SELECT id FROM matriculas WHERE aluno_id = ? AND turma_id = ? AND situacao = 'ativa'",
        [$alunoId, $turmaId]
    );
    if ($matricula === null) {
        throw new ValidacaoException('Este aluno não está matriculado nesta turma.');
    }
    return salvar_conta_aluno($alunoId, $pagarProprio);
}

function criar_aluno_do_responsavel(int $responsavelId): int
{
    $responsavel = q1(
        'SELECT r.id, r.usuario_id, r.telefone, r.cpf, u.nome
         FROM responsaveis r
         JOIN usuarios u ON u.id = r.usuario_id
         WHERE r.id = ?',
        [$responsavelId]
    );
    if ($responsavel === null) {
        nao_encontrado('Responsável não encontrado.');
    }
    if (q1('SELECT id FROM alunos WHERE usuario_id = ?', [(int) $responsavel['usuario_id']]) !== null) {
        throw new ValidacaoException('Esta pessoa já tem cadastro de aluno.');
    }
    $nascimento = campo('data_nascimento');
    validar_nascimento($nascimento);
    if (eh_menor($nascimento)) {
        throw new ValidacaoException('Quem tem menos de 18 anos precisa ser cadastrado como aluno, com um responsável.');
    }
    $cpf = $responsavel['cpf'] !== null && $responsavel['cpf'] !== '' ? (string) $responsavel['cpf'] : null;
    if ($cpf !== null && cpf_em_uso('alunos', $cpf)) {
        throw new ValidacaoException('O CPF deste responsável já está em outro aluno.');
    }
    exec_sql(
        'INSERT INTO alunos (usuario_id, nome, data_nascimento, telefone, cpf) VALUES (?, ?, ?, ?, ?)',
        [(int) $responsavel['usuario_id'], $responsavel['nome'], $nascimento, $responsavel['telefone'], $cpf]
    );
    return (int) db()->lastInsertId();
}

function familia_para_cobranca(int $responsavelId): ?array
{
    $responsavel = q1(
        'SELECT r.id, r.usuario_id, u.nome
         FROM responsaveis r
         JOIN usuarios u ON u.id = r.usuario_id
         WHERE r.id = ?',
        [$responsavelId]
    );
    if ($responsavel === null) {
        return null;
    }
    $linhas = q(
        "SELECT a.id, a.nome, a.data_nascimento, ar.parentesco, ca.plano_id, p.nome AS plano_nome,
                EXISTS (
                    SELECT 1 FROM matriculas m
                    WHERE m.aluno_id = a.id AND m.situacao = 'ativa'
                ) AS matriculado
         FROM aluno_responsavel ar
         JOIN alunos a ON a.id = ar.aluno_id
         LEFT JOIN contas_aluno ca ON ca.aluno_id = a.id
         LEFT JOIN planos p ON p.id = ca.plano_id
         WHERE ar.responsavel_id = ?
         ORDER BY a.nome",
        [$responsavelId]
    );
    $temMenorNaTurma = false;
    foreach ($linhas as $linha) {
        if ((int) $linha['matriculado'] === 1 && eh_menor((string) $linha['data_nascimento'])) {
            $temMenorNaTurma = true;
            break;
        }
    }
    if (!$temMenorNaTurma) {
        return null;
    }
    $pessoas = [];
    $ids = [];
    $proprio = q1(
        'SELECT a.id, a.nome, a.data_nascimento, ca.plano_id, p.nome AS plano_nome,
                EXISTS (
                    SELECT 1 FROM matriculas m
                    WHERE m.aluno_id = a.id AND m.situacao = \'ativa\'
                ) AS matriculado
         FROM alunos a
         LEFT JOIN contas_aluno ca ON ca.aluno_id = a.id
         LEFT JOIN planos p ON p.id = ca.plano_id
         WHERE a.usuario_id = ?',
        [(int) $responsavel['usuario_id']]
    );
    if ($proprio !== null) {
        $pessoas[] = [
            'id' => (int) $proprio['id'],
            'nome' => $proprio['nome'],
            'parentesco' => '',
            'menor' => eh_menor((string) $proprio['data_nascimento']),
            'proprio' => true,
            'matriculado' => (int) $proprio['matriculado'] === 1,
            'plano_id' => $proprio['plano_id'] !== null ? (int) $proprio['plano_id'] : null,
            'plano_nome' => $proprio['plano_nome'],
        ];
        $ids[] = (int) $proprio['id'];
    }
    foreach ($linhas as $linha) {
        $id = (int) $linha['id'];
        if (in_array($id, $ids, true)) {
            continue;
        }
        $pessoas[] = [
            'id' => $id,
            'nome' => $linha['nome'],
            'parentesco' => (string) $linha['parentesco'],
            'menor' => eh_menor((string) $linha['data_nascimento']),
            'proprio' => false,
            'matriculado' => (int) $linha['matriculado'] === 1,
            'plano_id' => $linha['plano_id'] !== null ? (int) $linha['plano_id'] : null,
            'plano_nome' => $linha['plano_nome'],
        ];
        $ids[] = $id;
    }
    $extra = q1('SELECT desconto_extra FROM contas_pagador WHERE responsavel_id = ?', [$responsavelId]);
    return [
        'pagador_id' => $responsavelId,
        'extra' => $extra !== null && $extra['desconto_extra'] !== null
            ? number_format((float) $extra['desconto_extra'], 2, '.', '')
            : null,
        'pessoas' => $pessoas,
    ];
}

function salvar_mensalidades_familia(int $responsavelId): int
{
    $familia = familia_para_cobranca($responsavelId);
    if ($familia === null) {
        throw new ValidacaoException('Esta conta não tem menor de idade matriculado.');
    }
    $planos = $_POST['plano'] ?? [];
    if (!is_array($planos)) {
        throw new ValidacaoException('Escolha o plano de pelo menos uma pessoa.');
    }
    $porId = [];
    foreach ($familia['pessoas'] as $pessoa) {
        $porId[(int) $pessoa['id']] = $pessoa;
    }
    $salvos = 0;
    transacao(function (PDO $pdo) use ($planos, $porId, $responsavelId, &$salvos): void {
        foreach ($planos as $alunoId => $planoId) {
            $alunoId = (int) $alunoId;
            $planoId = (int) $planoId;
            if ($planoId <= 0 || !isset($porId[$alunoId])) {
                continue;
            }
            if (!$porId[$alunoId]['matriculado']) {
                throw new ValidacaoException($porId[$alunoId]['nome'] . ' ainda não está em uma turma.');
            }
            $plano = q1('SELECT id FROM planos WHERE id = ? AND ativo = 1', [$planoId]);
            if ($plano === null) {
                throw new ValidacaoException('Escolha um plano ativo.');
            }
            gravar_conta_aluno($alunoId, $planoId, $responsavelId);
            $salvos++;
        }
    });
    if ($salvos === 0) {
        throw new ValidacaoException('Escolha o plano de pelo menos uma pessoa.');
    }
    return $salvos;
}

function atletas_do_pagador(int $responsavelId): array
{
    return q(
        'SELECT a.id, a.nome, p.nome AS plano_nome
         FROM contas_aluno ca
         JOIN alunos a ON a.id = ca.aluno_id
         JOIN planos p ON p.id = ca.plano_id
         WHERE ca.pagador_id = ?
         ORDER BY a.nome',
        [$responsavelId]
    );
}

function gerar_cobrancas(string $competencia): array
{
    $competencia = competencia_de_texto($competencia);
    $atletas = q(
        "SELECT ca.aluno_id, ca.plano_id, ca.pagador_id, ca.desconto_desde, p.valor_centavos,
                p.desconto_inicial, p.desconto_meses
         FROM contas_aluno ca
         JOIN planos p ON p.id = ca.plano_id
         WHERE EXISTS (
             SELECT 1 FROM matriculas m
             WHERE m.aluno_id = ca.aluno_id AND m.situacao = 'ativa'
         )"
    );
    $faixas = faixas_desconto();
    $contagens = atletas_ativos_por_pagador();
    $extras = extras_por_pagador();
    $resultado = ['novas' => 0, 'atualizadas' => 0, 'pagas' => 0];
    transacao(function (PDO $pdo) use ($atletas, $competencia, $faixas, $contagens, $extras, &$resultado): void {
        foreach ($atletas as $atleta) {
            $inicial = inicial_da_linha($atleta, $competencia);
            $calculo = calcular_mensalidade((int) $atleta['valor_centavos'], (int) $atleta['pagador_id'], $contagens, $faixas, $extras, $inicial);
            $existe = q1(
                'SELECT id, situacao FROM cobrancas WHERE aluno_id = ? AND competencia = ?',
                [(int) $atleta['aluno_id'], $competencia]
            );
            if ($existe === null) {
                exec_sql(
                    'INSERT INTO cobrancas (
                        aluno_id, pagador_id, competencia, plano_id, valor_cheio_centavos,
                        desconto_inicial, desconto_familia, desconto_extra, valor_final_centavos, situacao
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, \'aberta\')',
                    [
                        (int) $atleta['aluno_id'],
                        (int) $atleta['pagador_id'],
                        $competencia,
                        (int) $atleta['plano_id'],
                        (int) $atleta['valor_centavos'],
                        $calculo['inicial'],
                        $calculo['familia'],
                        $calculo['extra'],
                        $calculo['final'],
                    ]
                );
                $resultado['novas']++;
                continue;
            }
            if ($existe['situacao'] === 'paga') {
                $resultado['pagas']++;
                continue;
            }
            exec_sql(
                'UPDATE cobrancas
                 SET pagador_id = ?, plano_id = ?, valor_cheio_centavos = ?, desconto_inicial = ?,
                     desconto_familia = ?, desconto_extra = ?, valor_final_centavos = ?
                 WHERE id = ?',
                [
                    (int) $atleta['pagador_id'],
                    (int) $atleta['plano_id'],
                    (int) $atleta['valor_centavos'],
                    $calculo['inicial'],
                    $calculo['familia'],
                    $calculo['extra'],
                    $calculo['final'],
                    (int) $existe['id'],
                ]
            );
            $resultado['atualizadas']++;
        }
    });
    return $resultado;
}

function cobrancas_do_mes(string $competencia): array
{
    return q(
        'SELECT c.id, c.aluno_id, c.pagador_id, c.valor_cheio_centavos, c.desconto_inicial,
                c.desconto_familia, c.desconto_extra, c.valor_final_centavos, c.situacao, c.pago_em,
                a.nome AS aluno_nome, u.nome AS pagador_nome, p.nome AS plano_nome
         FROM cobrancas c
         JOIN alunos a ON a.id = c.aluno_id
         JOIN responsaveis r ON r.id = c.pagador_id
         JOIN usuarios u ON u.id = r.usuario_id
         JOIN planos p ON p.id = c.plano_id
         WHERE c.competencia = ?
         ORDER BY u.nome, a.nome',
        [$competencia]
    );
}

function definir_situacao_cobranca(int $id, string $situacao): string
{
    $cobranca = q1('SELECT id, competencia FROM cobrancas WHERE id = ?', [$id]);
    if ($cobranca === null) {
        throw new ValidacaoException('Cobrança não encontrada.');
    }
    if ($situacao === 'paga') {
        exec_sql(
            "UPDATE cobrancas SET situacao = 'paga', pago_em = ? WHERE id = ?",
            [hoje(), $id]
        );
    } elseif ($situacao === 'aberta') {
        exec_sql(
            "UPDATE cobrancas SET situacao = 'aberta', pago_em = NULL WHERE id = ?",
            [$id]
        );
    } else {
        throw new ValidacaoException('Situação inválida.');
    }
    return (string) $cobranca['competencia'];
}

function atletas_do_usuario_pagamento(int $usuarioId): array
{
    $ids = [];
    $aluno = q1('SELECT id FROM alunos WHERE usuario_id = ?', [$usuarioId]);
    if ($aluno !== null) {
        $ids[] = (int) $aluno['id'];
    }
    $pagos = q(
        'SELECT ca.aluno_id
         FROM contas_aluno ca
         JOIN responsaveis r ON r.id = ca.pagador_id
         WHERE r.usuario_id = ?',
        [$usuarioId]
    );
    foreach ($pagos as $pago) {
        $ids[] = (int) $pago['aluno_id'];
    }
    $ids = array_values(array_unique($ids));
    if ($ids === []) {
        return [];
    }
    $marcas = implode(',', array_fill(0, count($ids), '?'));
    return q(
        "SELECT a.id, a.nome,
                EXISTS (
                    SELECT 1 FROM matriculas m
                    WHERE m.aluno_id = a.id AND m.situacao = 'ativa'
                ) AS matriculado
         FROM alunos a
         WHERE a.id IN ({$marcas})
         ORDER BY a.nome",
        $ids
    );
}

function painel_pagamentos(int $usuarioId): array
{
    $atletas = atletas_do_usuario_pagamento($usuarioId);
    if ($atletas === []) {
        return ['atletas' => [], 'alertas' => [], 'tem_pagavel' => false];
    }
    $ids = array_map(static fn (array $atleta): int => (int) $atleta['id'], $atletas);
    $marcas = implode(',', array_fill(0, count($ids), '?'));
    $cobrancas = q(
        "SELECT c.aluno_id, c.competencia, c.valor_cheio_centavos, c.desconto_inicial,
                c.desconto_familia, c.desconto_extra, c.valor_final_centavos, c.situacao, c.pago_em,
                p.nome AS plano_nome
         FROM cobrancas c
         JOIN planos p ON p.id = c.plano_id
         WHERE c.aluno_id IN ({$marcas})
         ORDER BY c.competencia",
        $ids
    );
    $porAluno = [];
    foreach ($cobrancas as $cobranca) {
        $porAluno[(int) $cobranca['aluno_id']][substr((string) $cobranca['competencia'], 0, 10)] = $cobranca;
    }
    $faixas = faixas_desconto();
    $contagens = atletas_ativos_por_pagador();
    $extras = extras_por_pagador();
    $atual = mes_atual();
    $proximo = somar_meses($atual, 1);
    $grupos = [];
    $alertas = [];
    $temPagavel = false;
    foreach ($atletas as $atleta) {
        $alunoId = (int) $atleta['id'];
        $conta = conta_do_aluno($alunoId);
        $doAluno = $porAluno[$alunoId] ?? [];
        $fim = somar_meses($atual, 5);
        $inicio = $atual;
        foreach ($doAluno as $competencia => $cobranca) {
            if ($competencia < $inicio) {
                $inicio = $competencia;
            }
            if ($competencia > $fim) {
                $fim = $competencia;
            }
        }
        $meses = [];
        $cursor = $inicio;
        while ($cursor <= $fim) {
            if (isset($doAluno[$cursor])) {
                $cobranca = $doAluno[$cursor];
                $paga = $cobranca['situacao'] === 'paga';
                $meses[] = [
                    'chave' => $alunoId . '|' . substr($cursor, 0, 7),
                    'competencia' => $cursor,
                    'plano_nome' => (string) $cobranca['plano_nome'],
                    'cheio' => (int) $cobranca['valor_cheio_centavos'],
                    'inicial' => number_format((float) $cobranca['desconto_inicial'], 2, '.', ''),
                    'familia' => number_format((float) $cobranca['desconto_familia'], 2, '.', ''),
                    'extra' => number_format((float) $cobranca['desconto_extra'], 2, '.', ''),
                    'final' => (int) $cobranca['valor_final_centavos'],
                    'situacao' => $paga ? 'paga' : 'aberta',
                    'pago_em' => $cobranca['pago_em'],
                    'pagavel' => !$paga,
                    'prevista' => false,
                ];
                $temPagavel = $temPagavel || !$paga;
            } elseif ($cursor >= $atual && $conta !== null && (int) $atleta['matriculado'] === 1) {
                $calculo = calcular_mensalidade(
                    (int) $conta['valor_centavos'],
                    (int) $conta['pagador_id'],
                    $contagens,
                    $faixas,
                    $extras,
                    inicial_da_linha($conta, $cursor)
                );
                $meses[] = [
                    'chave' => $alunoId . '|' . substr($cursor, 0, 7),
                    'competencia' => $cursor,
                    'plano_nome' => (string) $conta['plano_nome'],
                    'cheio' => (int) $conta['valor_centavos'],
                    'inicial' => $calculo['inicial'],
                    'familia' => $calculo['familia'],
                    'extra' => $calculo['extra'],
                    'final' => $calculo['final'],
                    'situacao' => 'prevista',
                    'pago_em' => null,
                    'pagavel' => true,
                    'prevista' => true,
                ];
                $temPagavel = true;
            }
            $cursor = somar_meses($cursor, 1);
        }
        if ($conta !== null && !empty($conta['desconto_desde']) && !empty($conta['desconto_meses'])) {
            $vigente = inicial_da_linha($conta, $atual);
            if ((float) $vigente > 0) {
                $acaba = fim_desconto_inicial((string) $conta['desconto_desde'], (int) $conta['desconto_meses']);
                if ($acaba === $atual || $acaba === $proximo) {
                    $alertas[] = [
                        'nome' => (string) $atleta['nome'],
                        'percentual' => $vigente,
                        'acaba_em' => $acaba,
                        'mes_que_vem' => $acaba === $proximo,
                    ];
                }
            }
        }
        $grupos[] = [
            'id' => $alunoId,
            'nome' => (string) $atleta['nome'],
            'meses' => $meses,
        ];
    }
    return ['atletas' => $grupos, 'alertas' => $alertas, 'tem_pagavel' => $temPagavel];
}

function preparar_pagamento(int $usuarioId, mixed $ate): array
{
    if (!is_array($ate)) {
        throw new ValidacaoException('Escolha até qual mês pagar.');
    }
    $painel = painel_pagamentos($usuarioId);
    $porAtleta = [];
    foreach ($painel['atletas'] as $atleta) {
        $pagaveis = [];
        foreach ($atleta['meses'] as $mes) {
            if ($mes['pagavel']) {
                $mes['aluno_nome'] = $atleta['nome'];
                $pagaveis[] = $mes;
            }
        }
        $porAtleta[(int) $atleta['id']] = $pagaveis;
    }
    $escolhidas = [];
    foreach ($ate as $alunoId => $chave) {
        $chave = trim((string) $chave);
        if ($chave === '') {
            continue;
        }
        $alunoId = (int) $alunoId;
        if (!isset($porAtleta[$alunoId])) {
            throw new ValidacaoException('Essa parcela não está disponível.');
        }
        $corte = null;
        foreach ($porAtleta[$alunoId] as $indice => $mes) {
            if ($mes['chave'] === $chave) {
                $corte = $indice;
                break;
            }
        }
        if ($corte === null) {
            throw new ValidacaoException('Essa parcela não está disponível.');
        }
        for ($i = 0; $i <= $corte; $i++) {
            $escolhidas[] = $porAtleta[$alunoId][$i];
        }
    }
    if ($escolhidas === []) {
        throw new ValidacaoException('Escolha até qual mês pagar.');
    }
    $total = 0;
    foreach ($escolhidas as $parcela) {
        $total += (int) $parcela['final'];
    }
    return ['parcelas' => $escolhidas, 'total' => $total];
}
