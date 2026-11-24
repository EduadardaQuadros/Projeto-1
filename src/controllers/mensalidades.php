<?php

declare(strict_types=1);

function mensalidades_contas(): void
{
    require_papel('equipe');
    render('mensalidades/contas', [
        'titulo' => 'Mensalidades',
        'grupos' => grupos_contas(),
        'pendencias' => alunos_sem_conta(),
        'avisos' => avisos_desconto_inicial(),
    ]);
}

function planos_listar(): void
{
    require_papel('equipe');
    render('mensalidades/planos', [
        'titulo' => 'Planos',
        'planos' => planos_todos(),
        'avisos' => avisos_desconto_inicial(),
    ]);
}

function plano_novo(): void
{
    require_papel('equipe');
    render('mensalidades/plano_form', formulario_plano(null, null, []));
}

function plano_criar(): void
{
    require_papel('equipe');
    try {
        criar_plano();
        flash('ok', 'Plano cadastrado.');
        redirect('/mensalidades/planos');
    } catch (ValidacaoException $e) {
        render('mensalidades/plano_form', formulario_plano(null, $e->getMessage(), $_POST));
    }
}

function plano_editar(string $id): void
{
    require_papel('equipe');
    render('mensalidades/plano_form', formulario_plano(buscar_plano((int) $id), null, []));
}

function plano_salvar(string $id): void
{
    require_papel('equipe');
    $planoId = (int) $id;
    try {
        atualizar_plano($planoId);
        flash('ok', 'Plano atualizado.');
        redirect('/mensalidades/planos');
    } catch (ValidacaoException $e) {
        render('mensalidades/plano_form', formulario_plano(buscar_plano($planoId), $e->getMessage(), $_POST));
    }
}

function plano_ativo(string $id): void
{
    require_papel('equipe');
    definir_plano_ativo((int) $id, campo('ativo') === '1' ? 1 : 0);
    flash('ok', 'Plano atualizado.');
    redirect('/mensalidades/planos');
}

function plano_excluir(string $id): void
{
    require_papel('equipe');
    try {
        excluir_plano((int) $id);
        flash('ok', 'Plano excluído.');
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
    }
    redirect('/mensalidades/planos');
}

function descontos_ver(): void
{
    require_papel('equipe');
    render('mensalidades/descontos', [
        'titulo' => 'Descontos',
        'faixas' => faixas_desconto(),
    ]);
}

function desconto_criar(): void
{
    require_papel('equipe');
    try {
        criar_faixa();
        flash('ok', 'Faixa cadastrada.');
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
    }
    redirect('/mensalidades/descontos');
}

function desconto_salvar(string $id): void
{
    require_papel('equipe');
    try {
        atualizar_faixa((int) $id);
        flash('ok', 'Faixa atualizada.');
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
    }
    redirect('/mensalidades/descontos');
}

function desconto_excluir(string $id): void
{
    require_papel('equipe');
    excluir_faixa((int) $id);
    flash('ok', 'Faixa removida.');
    redirect('/mensalidades/descontos');
}

function pagador_desconto(string $id): void
{
    require_papel('equipe');
    try {
        salvar_desconto_extra((int) $id);
        flash('ok', 'Desconto da conta atualizado.');
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
    }
    redirect('/mensalidades');
}

function mensalidades_mes(): void
{
    require_papel('equipe');
    $competencia = competencia_pedida();
    render('mensalidades/mes', dados_mes($competencia));
}

function mensalidades_gerar(): void
{
    require_papel('equipe');
    try {
        $competencia = competencia_de_texto(campo('competencia'));
        $resultado = gerar_cobrancas($competencia);
        flash('ok', mensagem_geracao($resultado));
        ir_para_mes($competencia);
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
        redirect('/mensalidades/mes');
    }
}

function cobranca_situacao(string $id): void
{
    require_papel('equipe');
    try {
        $competencia = definir_situacao_cobranca((int) $id, campo('situacao'));
        flash('ok', campo('situacao') === 'paga' ? 'Mensalidade marcada como paga.' : 'Mensalidade voltou para em aberto.');
        ir_para_mes($competencia);
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
        $competencia = campo('competencia');
        if ($competencia !== '') {
            try {
                ir_para_mes(competencia_de_texto($competencia));
            } catch (ValidacaoException) {
                redirect('/mensalidades/mes');
            }
        }
        redirect('/mensalidades/mes');
    }
}

function aluno_salvar_conta(string $id): void
{
    require_papel('equipe');
    $alunoId = (int) $id;
    try {
        $moveu = salvar_conta_aluno($alunoId, isset($_POST['pagar_proprio']));
        flash('ok', mensagem_conta($moveu));
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
    }
    redirect('/alunos/' . $alunoId);
}

function turma_salvar_conta(string $id): void
{
    require_papel('equipe');
    $turmaId = (int) $id;
    try {
        $moveu = salvar_conta_da_turma($turmaId, isset($_POST['pagar_proprio']));
        flash('ok', mensagem_conta($moveu));
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
    }
    redirect('/turmas/' . $turmaId);
}

function responsavel_virar_aluno(string $id): void
{
    require_papel('equipe');
    $responsavelId = (int) $id;
    try {
        $alunoId = criar_aluno_do_responsavel($responsavelId);
        flash('ok', 'Cadastro de aluno criado. Defina o plano e o pagador.');
        redirect('/alunos/' . $alunoId);
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
        redirect('/responsaveis/' . $responsavelId);
    }
}

function responsavel_salvar_familia(string $id): void
{
    require_papel('equipe');
    $responsavelId = (int) $id;
    try {
        $salvos = salvar_mensalidades_familia($responsavelId);
        flash('ok', $salvos === 1 ? 'Mensalidade da família salva.' : 'Mensalidades da família salvas.');
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
    }
    redirect('/responsaveis/' . $responsavelId);
}

function formulario_plano(?array $plano, ?string $erro, array $old): array
{
    return [
        'titulo' => $plano === null ? 'Novo plano' : 'Editar plano',
        'plano' => $plano,
        'erro' => $erro,
        'old' => $old,
    ];
}

function buscar_plano(int $id): array
{
    $plano = q1(
        'SELECT id, nome, treinos_semana, valor_centavos, desconto_inicial, desconto_meses, ativo FROM planos WHERE id = ?',
        [$id]
    );
    if ($plano === null) {
        nao_encontrado('Plano não encontrado.');
    }
    return $plano;
}

function competencia_pedida(): string
{
    $valor = $_GET['competencia'] ?? '';
    if (!is_string($valor) || trim($valor) === '') {
        return mes_atual();
    }
    try {
        return competencia_de_texto($valor);
    } catch (ValidacaoException) {
        return mes_atual();
    }
}

function dados_mes(string $competencia): array
{
    $cobrancas = cobrancas_do_mes($competencia);
    $grupos = [];
    $totais = ['aberta' => 0, 'paga' => 0];
    foreach ($cobrancas as $cobranca) {
        $pagadorId = (int) $cobranca['pagador_id'];
        if (!isset($grupos[$pagadorId])) {
            $grupos[$pagadorId] = [
                'pagador_nome' => $cobranca['pagador_nome'],
                'total' => 0,
                'itens' => [],
            ];
        }
        $grupos[$pagadorId]['itens'][] = $cobranca;
        $grupos[$pagadorId]['total'] += (int) $cobranca['valor_final_centavos'];
        $totais[$cobranca['situacao']] += (int) $cobranca['valor_final_centavos'];
    }
    return [
        'titulo' => 'Mês',
        'competencia' => $competencia,
        'mes' => substr($competencia, 0, 7),
        'rotulo' => formatar_mes($competencia),
        'grupos' => array_values($grupos),
        'totais' => $totais,
        'pendencias' => alunos_sem_conta(),
    ];
}

function mensagem_geracao(array $resultado): string
{
    return 'Cobranças prontas. '
        . (int) $resultado['novas'] . ' novas, '
        . (int) $resultado['atualizadas'] . ' atualizadas. '
        . (int) $resultado['pagas'] . ' já pagas ficaram como estavam.';
}

function mensagem_conta(int $moveu): string
{
    if ($moveu === 1) {
        return 'Mensalidade salva. 1 filho com plano entrou na mesma conta.';
    }
    if ($moveu > 1) {
        return 'Mensalidade salva. ' . $moveu . ' filhos com plano entraram na mesma conta.';
    }
    return 'Mensalidade salva.';
}

function ir_para_mes(string $competencia): never
{
    header('Location: ' . url('/mensalidades/mes') . '?competencia=' . rawurlencode(substr($competencia, 0, 7)));
    exit;
}
