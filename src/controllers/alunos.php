<?php

declare(strict_types=1);

function alunos_listar(): void
{
    require_papel('equipe');
    $alunos = q(
        'SELECT a.id, a.nome, a.data_nascimento, a.cpf, u.email, u.ativo
         FROM alunos a
         JOIN usuarios u ON u.id = a.usuario_id
         ORDER BY a.nome'
    );
    render('alunos/lista', [
        'titulo' => 'Alunos',
        'alunos' => $alunos,
    ]);
}

function aluno_novo(): void
{
    require_papel('equipe');
    render('alunos/form', formulario_aluno(null, null, []));
}

function aluno_criar(): void
{
    require_papel('equipe');
    try {
        $id = criar_aluno();
        flash('ok', 'Aluno cadastrado.');
        redirect('/alunos/' . $id);
    } catch (ValidacaoException $e) {
        render('alunos/form', formulario_aluno(null, $e->getMessage(), $_POST));
    }
}

function aluno_ver(string $id): void
{
    require_papel('equipe');
    $aluno = buscar_aluno((int) $id);
    render('alunos/ver', dados_aluno($aluno));
}

function aluno_editar(string $id): void
{
    require_papel('equipe');
    $aluno = buscar_aluno((int) $id);
    render('alunos/form', formulario_aluno($aluno, null, []));
}

function aluno_salvar(string $id): void
{
    require_papel('equipe');
    $alunoId = (int) $id;
    try {
        atualizar_aluno($alunoId);
        flash('ok', 'Aluno atualizado.');
        redirect('/alunos/' . $alunoId);
    } catch (ValidacaoException $e) {
        $aluno = q1(
            'SELECT a.*, u.email, u.ativo FROM alunos a JOIN usuarios u ON u.id = a.usuario_id WHERE a.id = ?',
            [$alunoId]
        );
        if ($aluno === null) {
            nao_encontrado('Aluno não encontrado.');
        }
        render('alunos/form', formulario_aluno($aluno, $e->getMessage(), $_POST));
    }
}

function aluno_vincular_responsavel(string $id): void
{
    require_papel('equipe');
    $alunoId = (int) $id;
    try {
        vincular_responsavel($alunoId, campo_int('responsavel_id'), campo('parentesco'));
        flash('ok', campo('parentesco') === 'familiar' ? 'Familiar vinculado.' : 'Responsável vinculado.');
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
    }
    redirect('/alunos/' . $alunoId);
}

function aluno_desvincular_responsavel(string $alunoId, string $responsavelId): void
{
    require_papel('equipe');
    try {
        $aluno = buscar_aluno((int) $alunoId);
        desvincular_responsavel((int) $alunoId, (int) $responsavelId);
        flash('ok', eh_menor($aluno['data_nascimento']) ? 'Responsável desvinculado.' : 'Familiar desvinculado.');
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
    }
    redirect('/alunos/' . (int) $alunoId);
}

function aluno_matricular(string $id): void
{
    require_papel('equipe');
    $alunoId = (int) $id;
    try {
        matricular($alunoId, campo_int('turma_id'), campo('data_inicio'));
        flash('ok', 'Aluno matriculado.');
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
    }
    redirect('/alunos/' . $alunoId);
}

function aluno_senha(string $id): void
{
    require_papel('equipe');
    $aluno = buscar_aluno((int) $id);
    try {
        redefinir_senha((int) $aluno['usuario_id']);
        flash('ok', 'Senha do aluno redefinida.');
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
    }
    redirect('/alunos/' . (int) $aluno['id']);
}

function aluno_ativo(string $id): void
{
    require_papel('equipe');
    $aluno = buscar_aluno((int) $id);
    definir_ativo((int) $aluno['usuario_id'], campo('ativo') === '1' ? 1 : 0);
    flash('ok', 'Acesso do aluno atualizado.');
    redirect('/alunos/' . (int) $aluno['id']);
}

function formulario_aluno(?array $aluno, ?string $erro, array $old): array
{
    return [
        'titulo' => $aluno === null ? 'Novo aluno' : 'Editar aluno',
        'aluno' => $aluno,
        'erro' => $erro,
        'old' => $old,
        'responsaveis' => q(
            'SELECT r.id, u.nome
             FROM responsaveis r
             JOIN usuarios u ON u.id = r.usuario_id
             WHERE u.ativo = 1
             ORDER BY u.nome'
        ),
    ];
}

function buscar_aluno(int $id): array
{
    $aluno = q1(
        'SELECT a.*, u.email, u.ativo, u.id AS usuario_id
         FROM alunos a
         JOIN usuarios u ON u.id = a.usuario_id
         WHERE a.id = ?',
        [$id]
    );
    if ($aluno === null) {
        nao_encontrado('Aluno não encontrado.');
    }
    return $aluno;
}

function dados_aluno(array $aluno): array
{
    $id = (int) $aluno['id'];
    $conta = conta_do_aluno($id);
    $menor = eh_menor($aluno['data_nascimento']);
    return [
        'titulo' => $aluno['nome'],
        'aluno' => $aluno,
        'responsaveis' => q(
            'SELECT r.id, u.nome, u.ativo, ar.parentesco
             FROM aluno_responsavel ar
             JOIN responsaveis r ON r.id = ar.responsavel_id
             JOIN usuarios u ON u.id = r.usuario_id
             WHERE ar.aluno_id = ?
             ORDER BY u.nome',
            [$id]
        ),
        'responsaveis_disponiveis' => q(
            'SELECT r.id, u.nome
             FROM responsaveis r
             JOIN usuarios u ON u.id = r.usuario_id
             WHERE u.ativo = 1
               AND r.id NOT IN (SELECT responsavel_id FROM aluno_responsavel WHERE aluno_id = ?)
             ORDER BY u.nome',
            [$id]
        ),
        'matriculas' => q(
            "SELECT m.id, m.data_inicio, t.id AS turma_id, t.nome, t.ativa, p.nome AS plano_nome
             FROM matriculas m
             JOIN turmas t ON t.id = m.turma_id
             LEFT JOIN contas_aluno ca ON ca.aluno_id = m.aluno_id
             LEFT JOIN planos p ON p.id = ca.plano_id
             WHERE m.aluno_id = ? AND m.situacao = 'ativa'
             ORDER BY t.nome",
            [$id]
        ),
        'turmas_disponiveis' => q(
            "SELECT t.id, t.nome
             FROM turmas t
             WHERE t.ativa = 1
               AND t.id NOT IN (
                 SELECT turma_id FROM matriculas WHERE aluno_id = ? AND situacao = 'ativa'
               )
             ORDER BY t.nome",
            [$id]
        ),
        'conta' => $conta,
        'resumo' => resumo_conta_aluno($id),
        'planos' => planos_para_escolha($conta === null ? null : (int) $conta['plano_id']),
        'pagadores' => pagadores_do_aluno($id, $menor),
        'menor' => $menor,
    ];
}
