<h1><?= e($aluno['nome']) ?></h1>
<div class="linha-acoes">
    <a class="btn btn-secundario btn-linha" href="<?= e(url('/alunos/' . $aluno['id'] . '/editar')) ?>">Editar</a>
    <?php if (eh_menor($aluno['data_nascimento'])): ?>
        <span class="selo">Menor</span>
    <?php endif; ?>
</div>
<section class="bloco">
    <dl class="ficha">
        <div><dt>Nascimento</dt><dd><?= e(formatar_data($aluno['data_nascimento'])) ?> · <?= (int) idade($aluno['data_nascimento']) ?> anos</dd></div>
        <div><dt>Telefone</dt><dd><?= e(formatar_telefone($aluno['telefone']) ?: '—') ?></dd></div>
        <div><dt>CPF</dt><dd><?= e(formatar_cpf($aluno['cpf']) ?: '—') ?></dd></div>
        <div><dt>E-mail</dt><dd><?= e($aluno['email']) ?></dd></div>
    </dl>
</section>

<section class="bloco">
    <h2><?= $menor ? 'Responsáveis' : 'Familiares' ?></h2>
    <?php if (!$menor): ?>
        <p class="estado">Um familiar é outro adulto que pode pagar a mensalidade. Adulto não recebe responsável.</p>
    <?php endif; ?>
    <?php if ($responsaveis === []): ?>
        <p class="vazio"><?= $menor ? 'Nenhum responsável vinculado.' : 'Nenhum familiar vinculado.' ?></p>
    <?php else: ?>
        <ul class="itens">
            <?php foreach ($responsaveis as $responsavel): ?>
                <li>
                    <span>
                        <a href="<?= e(url('/responsaveis/' . $responsavel['id'])) ?>"><?= e($responsavel['nome']) ?></a>
                        · <?= e(rotulo_parentesco((string) $responsavel['parentesco'])) ?>
                    </span>
                    <form method="post" action="<?= e(url('/alunos/' . $aluno['id'] . '/responsaveis/' . $responsavel['id'] . '/remover')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn-perigo" type="submit">Tirar</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if ($responsaveis_disponiveis !== []): ?>
        <form method="post" action="<?= e(url('/alunos/' . $aluno['id'] . '/responsaveis')) ?>" class="form">
            <?= csrf_field() ?>
            <label for="responsavel_id"><?= $menor ? 'Vincular responsável' : 'Vincular familiar' ?></label>
            <select id="responsavel_id" name="responsavel_id" required>
                <option value="">Selecione</option>
                <?php foreach ($responsaveis_disponiveis as $responsavel): ?>
                    <option value="<?= (int) $responsavel['id'] ?>"><?= e($responsavel['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($menor): ?>
                <label for="parentesco">Parentesco</label>
                <select id="parentesco" name="parentesco" required>
                    <option value="">Selecione</option>
                    <?php foreach (PARENTESCOS as $valor => $rotulo): ?>
                        <option value="<?= e($valor) ?>"><?= e($rotulo) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input type="hidden" name="parentesco" value="familiar">
            <?php endif; ?>
            <button class="btn" type="submit">Vincular</button>
        </form>
    <?php else: ?>
        <p><a href="<?= e(url('/responsaveis/novo')) ?>"><?= $menor ? 'Cadastrar responsável' : 'Cadastrar familiar' ?></a></p>
    <?php endif; ?>
</section>

<section class="bloco">
    <h2>Turmas</h2>
    <?php if ($matriculas === []): ?>
        <p class="vazio">Nenhuma matrícula ativa.</p>
    <?php else: ?>
        <ul class="itens">
            <?php foreach ($matriculas as $matricula): ?>
                <li>
                    <span>
                        <a href="<?= e(url('/turmas/' . $matricula['turma_id'])) ?>"><?= e($matricula['nome']) ?></a>
                        <small><?= $matricula['plano_nome'] ? e($matricula['plano_nome']) : 'Sem mensalidade' ?></small>
                        <?php if (!(int) $matricula['ativa']): ?><span class="selo selo-inativo">Inativa</span><?php endif; ?>
                    </span>
                    <form method="post" action="<?= e(url('/matriculas/' . $matricula['id'] . '/encerrar')) ?>" onsubmit="return confirm('Encerrar esta matrícula?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="voltar" value="<?= e('/alunos/' . $aluno['id']) ?>">
                        <button class="btn-perigo" type="submit">Encerrar</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if ($turmas_disponiveis !== []): ?>
        <form method="post" action="<?= e(url('/alunos/' . $aluno['id'] . '/matriculas')) ?>" class="form">
            <?= csrf_field() ?>
            <label for="turma_id">Matricular em</label>
            <select id="turma_id" name="turma_id" required>
                <option value="">Selecione</option>
                <?php foreach ($turmas_disponiveis as $turma): ?>
                    <option value="<?= (int) $turma['id'] ?>"><?= e($turma['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="data_inicio">Início</label>
            <input id="data_inicio" name="data_inicio" type="date" required value="<?= e(hoje()) ?>">
            <button class="btn" type="submit">Matricular</button>
        </form>
    <?php endif; ?>
</section>

<section class="bloco" id="mensalidade">
    <h2>Mensalidade</h2>
    <?php if ($resumo === null): ?>
        <p class="aviso">Sem plano ou sem pagador, esta pessoa não entra na cobrança do mês.</p>
    <?php else: ?>
        <?php if ($matriculas === []): ?>
            <p class="estado">Sem matrícula ativa, este valor não entra na cobrança do mês.</p>
        <?php endif; ?>
        <dl class="ficha">
            <div><dt>Plano</dt><dd><?= e($resumo['plano_nome']) ?></dd></div>
            <div><dt>Quem paga</dt><dd><?= e($resumo['pagador_nome']) ?></dd></div>
            <div><dt>Valor</dt><dd><?= e(formatar_centavos((int) $resumo['valor_centavos'])) ?></dd></div>
            <?php if ((float) $resumo['inicial'] > 0): ?>
                <div><dt>Desconto inicial</dt><dd><?= e(formatar_percentual($resumo['inicial'])) ?> até <?= e(formatar_mes((string) $resumo['inicial_ate'])) ?>. Depois o valor volta ao normal.</dd></div>
            <?php endif; ?>
            <div><dt>Família</dt><dd><?= e(formatar_percentual($resumo['familia'])) ?> · <?= (int) $resumo['atletas'] ?> <?= (int) $resumo['atletas'] === 1 ? 'atleta' : 'atletas' ?></dd></div>
            <div><dt>Desconto extra</dt><dd><?php if ((float) $resumo['extra'] > 0): ?><?= e(formatar_percentual($resumo['extra'])) ?> aplicado, de <?= e(formatar_centavos((int) $resumo['apos_familia'])) ?> para <?= e(formatar_centavos((int) $resumo['final'])) ?><?php else: ?>Nenhum<?php endif; ?></dd></div>
            <div><dt>A pagar</dt><dd><?= e(formatar_centavos((int) $resumo['final'])) ?></dd></div>
        </dl>
    <?php endif; ?>
    <?php if ($menor && $pagadores === []): ?>
        <p class="aviso">Vincule um responsável antes de definir a mensalidade. Para uma criança, quem paga só pode ser um responsável.</p>
    <?php elseif ($planos === []): ?>
        <p><a href="<?= e(url('/mensalidades/planos/novo')) ?>">Cadastrar um plano</a></p>
    <?php else: ?>
        <?php parte('conta_form', [
            'acao' => '/alunos/' . $aluno['id'] . '/conta',
            'planos' => $planos,
            'pagadores' => $pagadores,
            'conta' => $conta,
            'aluno_fixo' => $aluno,
            'permitir_proprio' => !$menor,
            'rotulo_pagador' => $menor ? 'Responsável que paga' : 'Familiar que paga',
        ]); ?>
    <?php endif; ?>
</section>

<?php parte('acesso', [
    'ativo' => $aluno['ativo'],
    'acao_senha' => '/alunos/' . $aluno['id'] . '/senha',
    'acao_ativo' => '/alunos/' . $aluno['id'] . '/ativo',
]); ?>
