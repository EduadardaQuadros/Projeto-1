<?php
$contaAtual = is_array($conta ?? null) ? $conta : null;
$planoSelecionado = (string) ($contaAtual['plano_id'] ?? '');
$pagadorSelecionado = (string) ($contaAtual['pagador_id'] ?? '');
$alunoFixo = is_array($aluno_fixo ?? null) ? $aluno_fixo : null;
$opcoesAlunos = $alunos_opcoes ?? [];
$opcoesPlanos = $planos ?? [];
$opcoesPagadores = $pagadores ?? [];
$mostrarProprio = !empty($permitir_proprio);
$filtrarPagador = !empty($filtrar_pagador);
$rotuloPagador = (string) ($rotulo_pagador ?? 'Quem paga');
?>
<form method="post" action="<?= e(url($acao)) ?>" class="form">
    <?= csrf_field() ?>
    <?php if ($alunoFixo !== null): ?>
        <input type="hidden" name="aluno_id" value="<?= (int) $alunoFixo['id'] ?>">
    <?php else: ?>
        <label for="conta_aluno_id">Aluno matriculado</label>
        <select id="conta_aluno_id" name="aluno_id" required>
            <option value="">Selecione</option>
            <?php foreach ($opcoesAlunos as $opcao): ?>
                <option value="<?= (int) $opcao['id'] ?>" data-menor="<?= !empty($opcao['menor']) ? '1' : '0' ?>" data-pagadores="<?= e(implode(',', $opcao['pagadores'] ?? [])) ?>">
                    <?= e($opcao['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <label for="plano_id">Plano</label>
    <select id="plano_id" name="plano_id" required>
        <option value="">Selecione</option>
        <?php foreach ($opcoesPlanos as $plano): ?>
            <option value="<?= (int) $plano['id'] ?>"<?= $planoSelecionado === (string) $plano['id'] ? ' selected' : '' ?>>
                <?= e($plano['nome']) ?> · <?= e(formatar_centavos((int) $plano['valor_centavos'])) ?><?= (int) $plano['ativo'] ? '' : ' · inativo' ?>
            </option>
        <?php endforeach; ?>
    </select>
    <label for="pagador_id"><?= e($rotuloPagador) ?></label>
    <select id="pagador_id" name="pagador_id"<?= $filtrarPagador ? '' : '' ?>>
        <option value="">Selecione</option>
        <?php foreach ($opcoesPagadores as $pagador): ?>
            <option value="<?= (int) $pagador['id'] ?>"<?= $pagadorSelecionado === (string) $pagador['id'] ? ' selected' : '' ?>>
                <?= e($pagador['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="aviso" id="aviso-pagador" hidden>Esta criança ainda não tem responsável vinculado.</p>
    <?php if ($mostrarProprio): ?>
        <label class="check" for="pagar_proprio">
            <input id="pagar_proprio" name="pagar_proprio" type="checkbox" value="1">
            <span>Este aluno também paga. Filhos e familiares já vinculados entram na mesma conta.</span>
        </label>
    <?php endif; ?>
    <button class="btn" type="submit">Salvar mensalidade</button>
</form>
