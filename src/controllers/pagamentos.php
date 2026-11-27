<?php

declare(strict_types=1);

function pagamentos_ver(): void
{
    $usuario = exigir_area_pagamentos();
    sincronizar_pagamentos_pendentes((int) $usuario['id']);
    $painel = painel_pagamentos((int) $usuario['id']);
    render('pagamentos/ver', [
        'titulo' => 'Pagamentos',
        'atletas' => $painel['atletas'],
        'avisos' => montar_avisos_painel($painel),
        'tem_pagavel' => $painel['tem_pagavel'],
    ]);
}

function pagamentos_preparar(): void
{
    $usuario = exigir_area_pagamentos();
    $ate = $_POST['ate'] ?? [];
    try {
        if (campo('cpf') !== '') {
            guardar_cpf_pagador((int) $usuario['id'], campo('cpf'));
        }
        if (campo('telefone') !== '') {
            guardar_telefone_pagador((int) $usuario['id'], campo('telefone'));
        }
        $fatura = cobrar_no_asaas((int) $usuario['id'], $ate);
    } catch (PagadorIncompletoException $e) {
        $pagador = dados_pagador((int) $usuario['id']);
        render('pagamentos/cpf', [
            'titulo' => 'Dados de quem paga',
            'ate' => $e->ate,
            'pedir_cpf' => $pagador['cpf'] === null,
            'pedir_telefone' => $pagador['telefone'] === null,
        ]);
        return;
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
        redirect('/pagamentos');
    }
    header('Location: ' . $fatura);
    exit;
}

function pagamentos_webhook(): void
{
    $corpo = file_get_contents('php://input');
    processar_webhook_asaas(is_string($corpo) ? $corpo : '');
}

function exigir_area_pagamentos(): array
{
    $usuario = require_login();
    if ($usuario['papel'] === 'equipe' || $usuario['papel'] === 'professor') {
        recusar('Você não pode abrir esta página.');
    }
    $usuarioId = (int) $usuario['id'];
    if (perfil_aluno($usuarioId) === null && perfil_responsavel($usuarioId) === null) {
        recusar('Você não pode abrir esta página.');
    }
    return $usuario;
}
