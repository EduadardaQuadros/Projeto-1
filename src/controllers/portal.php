<?php

declare(strict_types=1);

function inicio(): void
{
    $usuario = require_login();
    if ($usuario['papel'] === 'equipe') {
        render('inicio_equipe', [
            'titulo' => 'Início',
            'contagens' => [
                'turmas' => (int) q1('SELECT COUNT(*) AS n FROM turmas WHERE ativa = 1')['n'],
                'professores' => (int) q1('SELECT COUNT(*) AS n FROM professores')['n'],
                'alunos' => (int) q1('SELECT COUNT(*) AS n FROM alunos')['n'],
                'responsaveis' => (int) q1('SELECT COUNT(*) AS n FROM responsaveis')['n'],
            ],
        ]);
        return;
    }
    if ($usuario['papel'] === 'responsavel') {
        $filhos = q(
            'SELECT a.id, a.nome, a.data_nascimento, ar.parentesco
             FROM alunos a
             JOIN aluno_responsavel ar ON ar.aluno_id = a.id
             JOIN responsaveis r ON r.id = ar.responsavel_id
             WHERE r.usuario_id = ?
             ORDER BY a.nome',
            [(int) $usuario['id']]
        );
        render('inicio_responsavel', [
            'titulo' => 'Meus filhos',
            'filhos' => $filhos,
        ]);
        return;
    }
    render('inicio_turmas', [
        'titulo' => 'Minhas turmas',
        'turmas' => turmas_do_usuario($usuario),
        'papel' => $usuario['papel'],
    ]);
}

function filho_ver(string $id): void
{
    $usuario = require_papel('responsavel');
    $filho = q1(
        'SELECT a.id, a.nome, a.data_nascimento
         FROM alunos a
         JOIN aluno_responsavel ar ON ar.aluno_id = a.id
         JOIN responsaveis r ON r.id = ar.responsavel_id
         WHERE a.id = ? AND r.usuario_id = ?',
        [(int) $id, (int) $usuario['id']]
    );
    if ($filho === null) {
        recusar('Você não pode ver este aluno.');
    }
    $turmas = q(
        "SELECT t.id, t.nome, t.nivel, t.local
         FROM turmas t
         JOIN matriculas m ON m.turma_id = t.id AND m.situacao = 'ativa'
         WHERE m.aluno_id = ? AND t.ativa = 1
         ORDER BY t.nome",
        [(int) $filho['id']]
    );
    $ids = array_map(static fn (array $turma): int => (int) $turma['id'], $turmas);
    $horarios = agrupar_por_turma(buscar_horarios($ids));
    $professores = agrupar_nomes($ids);
    render('portal/filho', [
        'titulo' => $filho['nome'],
        'filho' => $filho,
        'turmas' => $turmas,
        'horarios' => $horarios,
        'professores' => $professores,
    ]);
}

function minha_turma(string $id): void
{
    $usuario = require_login();
    $turmaId = (int) $id;
    if ($usuario['papel'] === 'professor') {
        $turma = q1(
            'SELECT t.id, t.nome, t.nivel, t.local
             FROM turmas t
             JOIN turma_professor tp ON tp.turma_id = t.id
             JOIN professores p ON p.id = tp.professor_id
             WHERE t.id = ? AND p.usuario_id = ? AND t.ativa = 1',
            [$turmaId, (int) $usuario['id']]
        );
        if ($turma === null) {
            recusar('Você não pode ver esta turma.');
        }
        $alunos = q(
            "SELECT a.nome, a.data_nascimento
             FROM matriculas m
             JOIN alunos a ON a.id = m.aluno_id
             WHERE m.turma_id = ? AND m.situacao = 'ativa'
             ORDER BY a.nome",
            [$turmaId]
        );
        render('portal/turma', [
            'titulo' => $turma['nome'],
            'turma' => $turma,
            'horarios' => horarios_da_turma($turmaId),
            'alunos' => $alunos,
            'professores' => [],
            'modo' => 'professor',
        ]);
        return;
    }
    if ($usuario['papel'] === 'aluno') {
        $turma = q1(
            "SELECT t.id, t.nome, t.nivel, t.local
             FROM turmas t
             JOIN matriculas m ON m.turma_id = t.id AND m.situacao = 'ativa'
             JOIN alunos a ON a.id = m.aluno_id
             WHERE t.id = ? AND a.usuario_id = ? AND t.ativa = 1",
            [$turmaId, (int) $usuario['id']]
        );
        if ($turma === null) {
            recusar('Você não pode ver esta turma.');
        }
        render('portal/turma', [
            'titulo' => $turma['nome'],
            'turma' => $turma,
            'horarios' => horarios_da_turma($turmaId),
            'alunos' => [],
            'professores' => q(
                'SELECT p.nome
                 FROM turma_professor tp
                 JOIN professores p ON p.id = tp.professor_id
                 WHERE tp.turma_id = ?
                 ORDER BY p.nome',
                [$turmaId]
            ),
            'modo' => 'aluno',
        ]);
        return;
    }
    recusar('Você não pode ver esta turma.');
}

function turmas_do_usuario(array $usuario): array
{
    $grade = grade_sql('t.id');
    if ($usuario['papel'] === 'professor') {
        return q(
            "SELECT t.id, t.nome, t.nivel, t.local, {$grade} AS grade
             FROM turmas t
             JOIN turma_professor tp ON tp.turma_id = t.id
             JOIN professores p ON p.id = tp.professor_id
             WHERE p.usuario_id = ? AND t.ativa = 1
             ORDER BY t.nome",
            [(int) $usuario['id']]
        );
    }
    return q(
        "SELECT t.id, t.nome, t.nivel, t.local, {$grade} AS grade
         FROM turmas t
         JOIN matriculas m ON m.turma_id = t.id AND m.situacao = 'ativa'
         JOIN alunos a ON a.id = m.aluno_id
         WHERE a.usuario_id = ? AND t.ativa = 1
         ORDER BY t.nome",
        [(int) $usuario['id']]
    );
}

function buscar_horarios(array $ids): array
{
    if ($ids === []) {
        return [];
    }
    $marcas = implode(',', array_fill(0, count($ids), '?'));
    return q(
        "SELECT turma_id, dia_semana, hora_inicio, hora_fim FROM horarios WHERE turma_id IN ({$marcas}) ORDER BY dia_semana, hora_inicio",
        $ids
    );
}

function agrupar_por_turma(array $linhas): array
{
    $grupos = [];
    foreach ($linhas as $linha) {
        $grupos[(int) $linha['turma_id']][] = $linha;
    }
    return $grupos;
}

function agrupar_nomes(array $ids): array
{
    if ($ids === []) {
        return [];
    }
    $marcas = implode(',', array_fill(0, count($ids), '?'));
    $linhas = q(
        "SELECT tp.turma_id, p.nome
         FROM turma_professor tp
         JOIN professores p ON p.id = tp.professor_id
         WHERE tp.turma_id IN ({$marcas})
         ORDER BY p.nome",
        $ids
    );
    $grupos = [];
    foreach ($linhas as $linha) {
        $grupos[(int) $linha['turma_id']][] = $linha['nome'];
    }
    return $grupos;
}
