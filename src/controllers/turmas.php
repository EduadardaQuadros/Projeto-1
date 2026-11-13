<?php

declare(strict_types=1);

function turmas_listar(): void
{
    require_papel('equipe');
    $grade = grade_sql('t.id');
    $turmas = q(
        "SELECT t.id, t.nome, t.nivel, t.local, t.capacidade, t.ativa,
            (SELECT COUNT(*) FROM matriculas m WHERE m.turma_id = t.id AND m.situacao = 'ativa') AS total_alunos,
            (SELECT COUNT(*) FROM turma_professor tp WHERE tp.turma_id = t.id) AS total_professores,
            {$grade} AS grade
         FROM turmas t
         ORDER BY t.ativa DESC, t.nome"
    );
    render('turmas/lista', [
        'titulo' => 'Turmas',
        'turmas' => $turmas,
    ]);
}

function turma_nova(): void
{
    require_papel('equipe');
    render('turmas/form', [
        'titulo' => 'Nova turma',
        'turma' => null,
        'erro' => null,
        'old' => [],
    ]);
}

function turma_criar(): void
{
    require_papel('equipe');
    try {
        $id = criar_turma();
        flash('ok', 'Turma cadastrada.');
        redirect('/turmas/' . $id);
    } catch (ValidacaoException $e) {
        render('turmas/form', [
            'titulo' => 'Nova turma',
            'turma' => null,
            'erro' => $e->getMessage(),
            'old' => $_POST,
        ]);
    }
}

function turma_ver(string $id): void
{
    require_papel('equipe');
    $turma = buscar_turma((int) $id);
    $professores = q(
        'SELECT p.id, p.nome, u.ativo
         FROM turma_professor tp
         JOIN professores p ON p.id = tp.professor_id
         JOIN usuarios u ON u.id = p.usuario_id
         WHERE tp.turma_id = ?
         ORDER BY p.nome',
        [$turma['id']]
    );
    $disponiveisProf = q(
        'SELECT p.id, p.nome
         FROM professores p
         JOIN usuarios u ON u.id = p.usuario_id
         WHERE u.ativo = 1
           AND p.id NOT IN (SELECT professor_id FROM turma_professor WHERE turma_id = ?)
         ORDER BY p.nome',
        [$turma['id']]
    );
    $matriculas = q(
        "SELECT m.id, m.data_inicio, a.id AS aluno_id, a.nome, a.data_nascimento
         FROM matriculas m
         JOIN alunos a ON a.id = m.aluno_id
         WHERE m.turma_id = ? AND m.situacao = 'ativa'
         ORDER BY a.nome",
        [$turma['id']]
    );
    $encerradas = q(
        "SELECT m.id, m.data_inicio, m.data_fim, a.nome
         FROM matriculas m
         JOIN alunos a ON a.id = m.aluno_id
         WHERE m.turma_id = ? AND m.situacao = 'encerrada'
         ORDER BY m.data_fim DESC, a.nome",
        [$turma['id']]
    );
    $disponiveisAlunos = q(
        "SELECT a.id, a.nome
         FROM alunos a
         JOIN usuarios u ON u.id = a.usuario_id
         WHERE u.ativo = 1
           AND a.id NOT IN (
             SELECT aluno_id FROM matriculas WHERE turma_id = ? AND situacao = 'ativa'
           )
         ORDER BY a.nome",
        [$turma['id']]
    );
    render('turmas/ver', [
        'titulo' => $turma['nome'],
        'turma' => $turma,
        'horarios' => horarios_da_turma((int) $turma['id']),
        'professores' => $professores,
        'professores_disponiveis' => $disponiveisProf,
        'matriculas' => $matriculas,
        'encerradas' => $encerradas,
        'alunos_disponiveis' => $disponiveisAlunos,
    ]);
}

function turma_editar(string $id): void
{
    require_papel('equipe');
    $turma = buscar_turma((int) $id);
    render('turmas/form', [
        'titulo' => 'Editar turma',
        'turma' => $turma,
        'erro' => null,
        'old' => [],
    ]);
}

function turma_salvar(string $id): void
{
    require_papel('equipe');
    $turmaId = (int) $id;
    try {
        atualizar_turma($turmaId);
        flash('ok', 'Turma atualizada.');
        redirect('/turmas/' . $turmaId);
    } catch (ValidacaoException $e) {
        $turma = q1('SELECT * FROM turmas WHERE id = ?', [$turmaId]);
        if ($turma === null) {
            nao_encontrado('Turma não encontrada.');
        }
        render('turmas/form', [
            'titulo' => 'Editar turma',
            'turma' => $turma,
            'erro' => $e->getMessage(),
            'old' => $_POST,
        ]);
    }
}

function horario_criar(string $turmaId): void
{
    require_papel('equipe');
    $id = (int) $turmaId;
    try {
        adicionar_horario($id);
        flash('ok', 'Horário adicionado.');
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
    }
    redirect('/turmas/' . $id);
}

function horario_excluir(string $id): void
{
    require_papel('equipe');
    try {
        $turmaId = excluir_horario((int) $id);
        flash('ok', 'Horário removido.');
        redirect('/turmas/' . $turmaId);
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
        redirect('/turmas');
    }
}

function turma_vincular_professor(string $turmaId): void
{
    require_papel('equipe');
    $id = (int) $turmaId;
    try {
        vincular_professor($id, campo_int('professor_id'));
        flash('ok', 'Professor vinculado.');
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
    }
    redirect('/turmas/' . $id);
}

function turma_desvincular_professor(string $turmaId, string $professorId): void
{
    require_papel('equipe');
    desvincular_professor((int) $turmaId, (int) $professorId);
    flash('ok', 'Professor desvinculado.');
    redirect('/turmas/' . (int) $turmaId);
}

function turma_matricular(string $turmaId): void
{
    require_papel('equipe');
    $id = (int) $turmaId;
    try {
        matricular(campo_int('aluno_id'), $id, campo('data_inicio'));
        flash('ok', 'Aluno matriculado.');
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
    }
    redirect('/turmas/' . $id);
}

function matricula_encerrar(string $id): void
{
    require_papel('equipe');
    $voltar = destino_interno('/turmas');
    try {
        encerrar_matricula((int) $id);
        flash('ok', 'Matrícula encerrada.');
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
    }
    redirect($voltar);
}

function buscar_turma(int $id): array
{
    $turma = q1('SELECT * FROM turmas WHERE id = ?', [$id]);
    if ($turma === null) {
        nao_encontrado('Turma não encontrada.');
    }
    return $turma;
}

function horarios_da_turma(int $turmaId): array
{
    return q(
        'SELECT id, dia_semana, hora_inicio, hora_fim FROM horarios WHERE turma_id = ? ORDER BY dia_semana, hora_inicio',
        [$turmaId]
    );
}
