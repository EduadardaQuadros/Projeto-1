<?php

declare(strict_types=1);

require __DIR__ . '/controllers/auth.php';
require __DIR__ . '/controllers/turmas.php';
require __DIR__ . '/controllers/alunos.php';
require __DIR__ . '/controllers/professores.php';
require __DIR__ . '/controllers/responsaveis.php';
require __DIR__ . '/controllers/portal.php';

function despachar(): void
{
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($metodo === 'POST') {
        csrf_check();
    }
    $caminho = request_path();
    $rotas = [
        ['GET', '#^/convite/([a-f0-9]{32})$#', 'convite_ver'],
        ['POST', '#^/convite/([a-f0-9]{32})$#', 'convite_entrar'],
        ['GET', '#^/login$#', 'tela_login'],
        ['POST', '#^/login$#', 'entrar'],
        ['POST', '#^/sair$#', 'sair'],
        ['GET', '#^/senha$#', 'tela_senha'],
        ['POST', '#^/senha$#', 'salvar_senha'],
        ['GET', '#^/$#', 'inicio'],

        ['GET', '#^/turmas$#', 'turmas_listar'],
        ['GET', '#^/turmas/nova$#', 'turma_nova'],
        ['POST', '#^/turmas$#', 'turma_criar'],
        ['GET', '#^/turmas/(\d+)$#', 'turma_ver'],
        ['GET', '#^/turmas/(\d+)/editar$#', 'turma_editar'],
        ['POST', '#^/turmas/(\d+)$#', 'turma_salvar'],
        ['POST', '#^/turmas/(\d+)/horarios$#', 'horario_criar'],
        ['POST', '#^/horarios/(\d+)/excluir$#', 'horario_excluir'],
        ['POST', '#^/turmas/(\d+)/professores$#', 'turma_vincular_professor'],
        ['POST', '#^/turmas/(\d+)/professores/(\d+)/remover$#', 'turma_desvincular_professor'],
        ['POST', '#^/turmas/(\d+)/convite$#', 'turma_renovar_convite'],
        ['POST', '#^/turmas/(\d+)/matriculas$#', 'turma_matricular'],
        ['POST', '#^/matriculas/(\d+)/encerrar$#', 'matricula_encerrar'],

        ['GET', '#^/alunos$#', 'alunos_listar'],
        ['GET', '#^/alunos/novo$#', 'aluno_novo'],
        ['POST', '#^/alunos$#', 'aluno_criar'],
        ['GET', '#^/alunos/(\d+)$#', 'aluno_ver'],
        ['GET', '#^/alunos/(\d+)/editar$#', 'aluno_editar'],
        ['POST', '#^/alunos/(\d+)$#', 'aluno_salvar'],
        ['POST', '#^/alunos/(\d+)/responsaveis$#', 'aluno_vincular_responsavel'],
        ['POST', '#^/alunos/(\d+)/responsaveis/(\d+)/remover$#', 'aluno_desvincular_responsavel'],
        ['POST', '#^/alunos/(\d+)/matriculas$#', 'aluno_matricular'],
        ['POST', '#^/alunos/(\d+)/senha$#', 'aluno_senha'],
        ['POST', '#^/alunos/(\d+)/ativo$#', 'aluno_ativo'],

        ['GET', '#^/professores$#', 'professores_listar'],
        ['GET', '#^/professores/novo$#', 'professor_novo'],
        ['POST', '#^/professores$#', 'professor_criar'],
        ['GET', '#^/professores/(\d+)$#', 'professor_ver'],
        ['GET', '#^/professores/(\d+)/editar$#', 'professor_editar'],
        ['POST', '#^/professores/(\d+)$#', 'professor_salvar'],
        ['POST', '#^/professores/(\d+)/turmas$#', 'professor_vincular_turma'],
        ['POST', '#^/professores/(\d+)/turmas/(\d+)/remover$#', 'professor_desvincular_turma'],
        ['POST', '#^/professores/(\d+)/senha$#', 'professor_senha'],
        ['POST', '#^/professores/(\d+)/ativo$#', 'professor_ativo'],

        ['GET', '#^/responsaveis$#', 'responsaveis_listar'],
        ['GET', '#^/responsaveis/novo$#', 'responsavel_novo'],
        ['POST', '#^/responsaveis$#', 'responsavel_criar'],
        ['GET', '#^/responsaveis/(\d+)$#', 'responsavel_ver'],
        ['GET', '#^/responsaveis/(\d+)/editar$#', 'responsavel_editar'],
        ['POST', '#^/responsaveis/(\d+)$#', 'responsavel_salvar'],
        ['POST', '#^/responsaveis/(\d+)/alunos$#', 'responsavel_vincular_aluno'],
        ['POST', '#^/responsaveis/(\d+)/alunos/(\d+)/remover$#', 'responsavel_desvincular_aluno'],
        ['POST', '#^/responsaveis/(\d+)/senha$#', 'responsavel_senha'],
        ['POST', '#^/responsaveis/(\d+)/ativo$#', 'responsavel_ativo'],

        ['GET', '#^/filhos/(\d+)$#', 'filho_ver'],
        ['GET', '#^/minhas-turmas/(\d+)$#', 'minha_turma'],
    ];

    foreach ($rotas as [$metodoRota, $padrao, $acao]) {
        if ($metodoRota === $metodo && preg_match($padrao, $caminho, $params)) {
            array_shift($params);
            $acao(...$params);
            return;
        }
    }

    http_response_code(404);
    $logado = Auth::usuario() !== null;
    render('erro', [
        'titulo' => 'Página não encontrada',
        'mensagem' => 'Esse endereço não existe.',
    ], !$logado);
}
