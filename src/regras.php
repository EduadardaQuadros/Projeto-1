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
        exigir_parentesco($parentesco);
        if (q1('SELECT id FROM responsaveis WHERE id = ?', [$responsavelId]) === null) {
            throw new ValidacaoException('Responsável não encontrado.');
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
    $vinculos = q1('SELECT COUNT(*) AS n FROM aluno_responsavel WHERE aluno_id = ?', [$id]);
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
    exigir_parentesco($parentesco);
    if (q1('SELECT id FROM alunos WHERE id = ?', [$alunoId]) === null) {
        throw new ValidacaoException('Aluno não encontrado.');
    }
    $responsavel = q1(
        'SELECT r.id FROM responsaveis r JOIN usuarios u ON u.id = r.usuario_id WHERE r.id = ? AND u.ativo = 1',
        [$responsavelId]
    );
    if ($responsavel === null) {
        throw new ValidacaoException('Responsável não encontrado.');
    }
    if (q1('SELECT 1 AS ok FROM aluno_responsavel WHERE aluno_id = ? AND responsavel_id = ?', [$alunoId, $responsavelId])) {
        throw new ValidacaoException('Este responsável já está vinculado ao aluno.');
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
    $total = q1('SELECT COUNT(*) AS n FROM aluno_responsavel WHERE aluno_id = ?', [$alunoId]);
    if (eh_menor($aluno['data_nascimento']) && (int) $total['n'] <= 1) {
        throw new ValidacaoException('Aluno menor de idade precisa ter pelo menos um responsável.');
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
