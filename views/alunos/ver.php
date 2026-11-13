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
    <h2>Responsáveis</h2>
    <?php if ($responsaveis === []): ?>
        <p class="vazio">Nenhum responsável vinculado.</p>
    <?php else: ?>
        <ul class="itens">
            <?php foreach ($responsaveis as $responsavel): ?>
                <li>
                    <span>
                        <a href="<?= e(url('/responsaveis/' . $responsavel['id'])) ?>"><?= e($responsavel['nome']) ?></a>
                        · <?= e(PARENTESCOS[$responsavel['parentesco']] ?? '') ?>
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
            <label for="responsavel_id">Vincular responsável</label>
            <select id="responsavel_id" name="responsavel_id" required>
                <option value="">Selecione</option>
                <?php foreach ($responsaveis_disponiveis as $responsavel): ?>
                    <option value="<?= (int) $responsavel['id'] ?>"><?= e($responsavel['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="parentesco">Parentesco</label>
            <select id="parentesco" name="parentesco" required>
                <option value="">Selecione</option>
                <?php foreach (PARENTESCOS as $valor => $rotulo): ?>
                    <option value="<?= e($valor) ?>"><?= e($rotulo) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn" type="submit">Vincular</button>
        </form>
    <?php else: ?>
        <p><a href="<?= e(url('/responsaveis/novo')) ?>">Cadastrar responsável</a></p>
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

<?php parte('acesso', [
    'ativo' => $aluno['ativo'],
    'acao_senha' => '/alunos/' . $aluno['id'] . '/senha',
    'acao_ativo' => '/alunos/' . $aluno['id'] . '/ativo',
]); ?>
