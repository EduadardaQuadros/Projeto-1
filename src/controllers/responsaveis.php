<?php

declare(strict_types=1);

function responsaveis_listar(): void
{
    require_papel('equipe');
    $responsaveis = q(
        'SELECT r.id, u.nome, u.email, u.ativo, r.cpf,
            (SELECT COUNT(*) FROM aluno_responsavel ar WHERE ar.responsavel_id = r.id) AS total_alunos
         FROM responsaveis r
         JOIN usuarios u ON u.id = r.usuario_id
         ORDER BY u.nome'
    );
    render('responsaveis/lista', [
        'titulo' => 'Responsáveis',
        'responsaveis' => $responsaveis,
    ]);
}

function responsavel_novo(): void
{
    require_papel('equipe');
    render('responsaveis/form', [
        'titulo' => 'Novo responsável',
        'responsavel' => null,
        'erro' => null,
        'old' => [],
    ]);
}

function responsavel_criar(): void
{
    require_papel('equipe');
    try {
        $id = criar_responsavel();
        flash('ok', 'Responsável cadastrado.');
        redirect('/responsaveis/' . $id);
    } catch (ValidacaoException $e) {
        render('responsaveis/form', [
            'titulo' => 'Novo responsável',
            'responsavel' => null,
            'erro' => $e->getMessage(),
            'old' => $_POST,
        ]);
    }
}

function responsavel_ver(string $id): void
{
    require_papel('equipe');
    $responsavel = buscar_responsavel((int) $id);
    render('responsaveis/ver', [
        'titulo' => $responsavel['nome'],
        'responsavel' => $responsavel,
        'alunos' => q(
            'SELECT a.id, a.nome, a.data_nascimento, ar.parentesco
             FROM aluno_responsavel ar
             JOIN alunos a ON a.id = ar.aluno_id
             WHERE ar.responsavel_id = ?
             ORDER BY a.nome',
            [$responsavel['id']]
        ),
        'alunos_disponiveis' => q(
            'SELECT a.id, a.nome
             FROM alunos a
             WHERE a.id NOT IN (SELECT aluno_id FROM aluno_responsavel WHERE responsavel_id = ?)
             ORDER BY a.nome',
            [$responsavel['id']]
        ),
    ]);
}

function responsavel_editar(string $id): void
{
    require_papel('equipe');
    render('responsaveis/form', [
        'titulo' => 'Editar responsável',
        'responsavel' => buscar_responsavel((int) $id),
        'erro' => null,
        'old' => [],
    ]);
}

function responsavel_salvar(string $id): void
{
    require_papel('equipe');
    $responsavelId = (int) $id;
    try {
        atualizar_responsavel($responsavelId);
        flash('ok', 'Responsável atualizado.');
        redirect('/responsaveis/' . $responsavelId);
    } catch (ValidacaoException $e) {
        $responsavel = q1(
            'SELECT r.*, u.nome, u.email, u.ativo
             FROM responsaveis r
             JOIN usuarios u ON u.id = r.usuario_id
             WHERE r.id = ?',
            [$responsavelId]
        );
        if ($responsavel === null) {
            nao_encontrado('Responsável não encontrado.');
        }
        render('responsaveis/form', [
            'titulo' => 'Editar responsável',
            'responsavel' => $responsavel,
            'erro' => $e->getMessage(),
            'old' => $_POST,
        ]);
    }
}

function responsavel_vincular_aluno(string $id): void
{
    require_papel('equipe');
    $responsavelId = (int) $id;
    try {
        vincular_responsavel(campo_int('aluno_id'), $responsavelId, campo('parentesco'));
        flash('ok', 'Aluno vinculado.');
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
    }
    redirect('/responsaveis/' . $responsavelId);
}

function responsavel_desvincular_aluno(string $responsavelId, string $alunoId): void
{
    require_papel('equipe');
    try {
        desvincular_responsavel((int) $alunoId, (int) $responsavelId);
        flash('ok', 'Aluno desvinculado.');
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
    }
    redirect('/responsaveis/' . (int) $responsavelId);
}

function responsavel_senha(string $id): void
{
    require_papel('equipe');
    $responsavel = buscar_responsavel((int) $id);
    try {
        redefinir_senha((int) $responsavel['usuario_id']);
        flash('ok', 'Senha do responsável redefinida.');
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
    }
    redirect('/responsaveis/' . (int) $responsavel['id']);
}

function responsavel_ativo(string $id): void
{
    require_papel('equipe');
    $responsavel = buscar_responsavel((int) $id);
    definir_ativo((int) $responsavel['usuario_id'], campo('ativo') === '1' ? 1 : 0);
    flash('ok', 'Acesso do responsável atualizado.');
    redirect('/responsaveis/' . (int) $responsavel['id']);
}

function buscar_responsavel(int $id): array
{
    $responsavel = q1(
        'SELECT r.*, u.nome, u.email, u.ativo, u.id AS usuario_id
         FROM responsaveis r
         JOIN usuarios u ON u.id = r.usuario_id
         WHERE r.id = ?',
        [$id]
    );
    if ($responsavel === null) {
        nao_encontrado('Responsável não encontrado.');
    }
    return $responsavel;
}
