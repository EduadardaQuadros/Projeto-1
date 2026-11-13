<h1><?= e($responsavel['nome']) ?></h1>
<div class="linha-acoes">
    <a class="btn btn-secundario btn-linha" href="<?= e(url('/responsaveis/' . $responsavel['id'] . '/editar')) ?>">Editar</a>
</div>
<section class="bloco">
    <dl class="ficha">
        <div><dt>Telefone</dt><dd><?= e(formatar_telefone($responsavel['telefone']) ?: '—') ?></dd></div>
        <div><dt>CPF</dt><dd><?= e(formatar_cpf($responsavel['cpf']) ?: '—') ?></dd></div>
        <div><dt>E-mail</dt><dd><?= e($responsavel['email']) ?></dd></div>
    </dl>
</section>
<section class="bloco">
    <h2>Alunos</h2>
    <?php if ($alunos === []): ?>
        <p class="vazio">Nenhum aluno vinculado.</p>
    <?php else: ?>
        <ul class="itens">
            <?php foreach ($alunos as $aluno): ?>
                <li>
                    <span>
                        <a href="<?= e(url('/alunos/' . $aluno['id'])) ?>"><?= e($aluno['nome']) ?></a>
                        · <?= e(PARENTESCOS[$aluno['parentesco']] ?? '') ?>
                        <?php if (eh_menor($aluno['data_nascimento'])): ?><span class="selo">Menor</span><?php endif; ?>
                    </span>
                    <form method="post" action="<?= e(url('/responsaveis/' . $responsavel['id'] . '/alunos/' . $aluno['id'] . '/remover')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn-perigo" type="submit">Tirar</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if ($alunos_disponiveis !== []): ?>
        <form method="post" action="<?= e(url('/responsaveis/' . $responsavel['id'] . '/alunos')) ?>" class="form">
            <?= csrf_field() ?>
            <label for="aluno_id">Vincular aluno</label>
            <select id="aluno_id" name="aluno_id" required>
                <option value="">Selecione</option>
                <?php foreach ($alunos_disponiveis as $aluno): ?>
                    <option value="<?= (int) $aluno['id'] ?>"><?= e($aluno['nome']) ?></option>
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
    <?php endif; ?>
</section>
<?php parte('acesso', [
    'ativo' => $responsavel['ativo'],
    'acao_senha' => '/responsaveis/' . $responsavel['id'] . '/senha',
    'acao_ativo' => '/responsaveis/' . $responsavel['id'] . '/ativo',
]); ?>
