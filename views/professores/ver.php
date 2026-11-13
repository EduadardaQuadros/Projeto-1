<h1><?= e($professor['nome']) ?></h1>
<div class="linha-acoes">
    <a class="btn btn-secundario btn-linha" href="<?= e(url('/professores/' . $professor['id'] . '/editar')) ?>">Editar</a>
</div>
<section class="bloco">
    <dl class="ficha">
        <div><dt>Telefone</dt><dd><?= e(formatar_telefone($professor['telefone']) ?: '—') ?></dd></div>
        <div><dt>CPF</dt><dd><?= e(formatar_cpf($professor['cpf']) ?: '—') ?></dd></div>
        <div><dt>E-mail</dt><dd><?= e($professor['email']) ?></dd></div>
    </dl>
</section>
<section class="bloco">
    <h2>Turmas</h2>
    <?php if ($turmas === []): ?>
        <p class="vazio">Nenhuma turma vinculada.</p>
    <?php else: ?>
        <ul class="itens">
            <?php foreach ($turmas as $turma): ?>
                <li>
                    <span>
                        <a href="<?= e(url('/turmas/' . $turma['id'])) ?>"><?= e($turma['nome']) ?></a>
                        · <?= e(NIVEIS[$turma['nivel']] ?? '') ?>
                        <?php if (!(int) $turma['ativa']): ?><span class="selo selo-inativo">Inativa</span><?php endif; ?>
                    </span>
                    <form method="post" action="<?= e(url('/professores/' . $professor['id'] . '/turmas/' . $turma['id'] . '/remover')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn-perigo" type="submit">Tirar</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if ($turmas_disponiveis !== []): ?>
        <form method="post" action="<?= e(url('/professores/' . $professor['id'] . '/turmas')) ?>" class="form">
            <?= csrf_field() ?>
            <label for="turma_id">Vincular turma</label>
            <select id="turma_id" name="turma_id" required>
                <option value="">Selecione</option>
                <?php foreach ($turmas_disponiveis as $turma): ?>
                    <option value="<?= (int) $turma['id'] ?>"><?= e($turma['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn" type="submit">Vincular</button>
        </form>
    <?php endif; ?>
</section>
<?php parte('acesso', [
    'ativo' => $professor['ativo'],
    'acao_senha' => '/professores/' . $professor['id'] . '/senha',
    'acao_ativo' => '/professores/' . $professor['id'] . '/ativo',
]); ?>
