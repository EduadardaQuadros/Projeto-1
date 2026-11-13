<?php

declare(strict_types=1);

function professores_listar(): void
{
    require_papel('equipe');
    $professores = q(
        'SELECT p.id, p.nome, p.cpf, u.email, u.ativo,
            (SELECT COUNT(*) FROM turma_professor tp WHERE tp.professor_id = p.id) AS total_turmas
         FROM professores p
         JOIN usuarios u ON u.id = p.usuario_id
         ORDER BY p.nome'
    );
    render('professores/lista', [
        'titulo' => 'Professores',
        'professores' => $professores,
    ]);
}

function professor_novo(): void
{
    require_papel('equipe');
    render('professores/form', [
        'titulo' => 'Novo professor',
        'professor' => null,
        'erro' => null,
        'old' => [],
    ]);
}

function professor_criar(): void
{
    require_papel('equipe');
    try {
        $id = criar_professor();
        flash('ok', 'Professor cadastrado.');
        redirect('/professores/' . $id);
    } catch (ValidacaoException $e) {
        render('professores/form', [
            'titulo' => 'Novo professor',
            'professor' => null,
            'erro' => $e->getMessage(),
            'old' => $_POST,
        ]);
    }
}

function professor_ver(string $id): void
{
    require_papel('equipe');
    $professor = buscar_professor((int) $id);
    render('professores/ver', [
        'titulo' => $professor['nome'],
        'professor' => $professor,
        'turmas' => q(
            'SELECT t.id, t.nome, t.ativa, t.nivel
             FROM turma_professor tp
             JOIN turmas t ON t.id = tp.turma_id
             WHERE tp.professor_id = ?
             ORDER BY t.nome',
            [$professor['id']]
        ),
        'turmas_disponiveis' => q(
            'SELECT t.id, t.nome
             FROM turmas t
             WHERE t.id NOT IN (SELECT turma_id FROM turma_professor WHERE professor_id = ?)
             ORDER BY t.ativa DESC, t.nome',
            [$professor['id']]
        ),
    ]);
}

function professor_editar(string $id): void
{
    require_papel('equipe');
    render('professores/form', [
        'titulo' => 'Editar professor',
        'professor' => buscar_professor((int) $id),
        'erro' => null,
        'old' => [],
    ]);
}

function professor_salvar(string $id): void
{
    require_papel('equipe');
    $professorId = (int) $id;
    try {
        atualizar_professor($professorId);
        flash('ok', 'Professor atualizado.');
        redirect('/professores/' . $professorId);
    } catch (ValidacaoException $e) {
        $professor = q1(
            'SELECT p.*, u.email, u.ativo FROM professores p JOIN usuarios u ON u.id = p.usuario_id WHERE p.id = ?',
            [$professorId]
        );
        if ($professor === null) {
            nao_encontrado('Professor não encontrado.');
        }
        render('professores/form', [
            'titulo' => 'Editar professor',
            'professor' => $professor,
            'erro' => $e->getMessage(),
            'old' => $_POST,
        ]);
    }
}

function professor_vincular_turma(string $id): void
{
    require_papel('equipe');
    $professorId = (int) $id;
    try {
        vincular_professor(campo_int('turma_id'), $professorId);
        flash('ok', 'Turma vinculada.');
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
    }
    redirect('/professores/' . $professorId);
}

function professor_desvincular_turma(string $professorId, string $turmaId): void
{
    require_papel('equipe');
    desvincular_professor((int) $turmaId, (int) $professorId);
    flash('ok', 'Turma desvinculada.');
    redirect('/professores/' . (int) $professorId);
}

function professor_senha(string $id): void
{
    require_papel('equipe');
    $professor = buscar_professor((int) $id);
    try {
        redefinir_senha((int) $professor['usuario_id']);
        flash('ok', 'Senha do professor redefinida.');
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
    }
    redirect('/professores/' . (int) $professor['id']);
}

function professor_ativo(string $id): void
{
    require_papel('equipe');
    $professor = buscar_professor((int) $id);
    definir_ativo((int) $professor['usuario_id'], campo('ativo') === '1' ? 1 : 0);
    flash('ok', 'Acesso do professor atualizado.');
    redirect('/professores/' . (int) $professor['id']);
}

function buscar_professor(int $id): array
{
    $professor = q1(
        'SELECT p.*, u.email, u.ativo, u.id AS usuario_id
         FROM professores p
         JOIN usuarios u ON u.id = p.usuario_id
         WHERE p.id = ?',
        [$id]
    );
    if ($professor === null) {
        nao_encontrado('Professor não encontrado.');
    }
    return $professor;
}
