<?php

declare(strict_types=1);

function pagamentos_ver(): void
{
    $usuario = exigir_area_pagamentos();
    $painel = painel_pagamentos((int) $usuario['id']);
    render('pagamentos/ver', [
        'titulo' => 'Pagamentos',
        'atletas' => $painel['atletas'],
        'alertas' => $painel['alertas'],
        'tem_pagavel' => $painel['tem_pagavel'],
    ]);
}

function pagamentos_preparar(): void
{
    $usuario = exigir_area_pagamentos();
    try {
        $preparo = preparar_pagamento((int) $usuario['id'], $_POST['ate'] ?? []);
    } catch (ValidacaoException $e) {
        flash('erro', $e->getMessage());
        redirect('/pagamentos');
    }
    render('pagamentos/preparar', [
        'titulo' => 'Pagamento',
        'preparo' => $preparo,
    ]);
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
