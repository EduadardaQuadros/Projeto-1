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
    <h2>Aula e pagamento</h2>
    <?php if ($aluno_perfil === null): ?>
        <form method="post" action="<?= e(url('/responsaveis/' . $responsavel['id'] . '/aluno')) ?>" class="form">
            <?= csrf_field() ?>
            <p class="estado">Esta pessoa pode treinar com o mesmo login e pagar junto com os filhos.</p>
            <label for="data_nascimento">Data de nascimento</label>
            <input id="data_nascimento" name="data_nascimento" type="date" required>
            <button class="btn" type="submit">Este responsável também faz aula</button>
        </form>
    <?php else: ?>
        <p><a href="<?= e(url('/alunos/' . $aluno_perfil['id'])) ?>">Abrir o cadastro de aluno</a></p>
    <?php endif; ?>
    <?php if ($pagos === []): ?>
        <p class="vazio">Não paga a mensalidade de nenhum aluno.</p>
    <?php else: ?>
        <ul class="itens">
            <?php foreach ($pagos as $pago): ?>
                <li>
                    <span>
                        <a href="<?= e(url('/alunos/' . $pago['id'])) ?>"><?= e($pago['nome']) ?></a>
                        <small><?= e($pago['plano_nome']) ?></small>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<?php if (is_array($familia)): ?>
<section class="bloco">
    <h2>Mensalidades da família</h2>
    <p class="estado">Opcional. Escolha o plano de cada pessoa para cobrar tudo nesta conta. Quem ficar em branco não muda.</p>
    <?php if ($familia['extra'] !== null && (float) $familia['extra'] > 0): ?>
        <p class="aviso">Desconto extra de <?= e(formatar_percentual($familia['extra'])) ?> aplicado em cada mensalidade desta conta.</p>
    <?php endif; ?>
    <form method="post" action="<?= e(url('/responsaveis/' . $responsavel['id'] . '/mensalidades')) ?>" class="form">
        <?= csrf_field() ?>
        <?php foreach ($familia['pessoas'] as $pessoa): ?>
            <fieldset class="familia-pessoa">
                <legend>
                    <?= e($pessoa['nome']) ?>
                    <?php if ($pessoa['proprio']): ?>
                        · esta pessoa
                    <?php else: ?>
                        · <?= e(rotulo_parentesco((string) $pessoa['parentesco'])) ?>
                    <?php endif; ?>
                    <?php if ($pessoa['menor']): ?><span class="selo">Menor</span><?php endif; ?>
                </legend>
                <?php if (!$pessoa['matriculado']): ?>
                    <p class="estado">Ainda não está em uma turma, então a mensalidade não entra no mês.</p>
                <?php else: ?>
                    <?php if ($pessoa['plano_nome']): ?>
                        <p class="estado">Plano atual: <?= e($pessoa['plano_nome']) ?></p>
                    <?php endif; ?>
                    <label for="plano-familia-<?= (int) $pessoa['id'] ?>">Plano</label>
                    <select id="plano-familia-<?= (int) $pessoa['id'] ?>" name="plano[<?= (int) $pessoa['id'] ?>]">
                        <option value="">Não alterar</option>
                        <?php foreach ($planos as $plano): ?>
                            <?php if (!(int) $plano['ativo'] && (int) $plano['id'] !== (int) $pessoa['plano_id']) { continue; } ?>
                            <option value="<?= (int) $plano['id'] ?>">
                                <?= e($plano['nome']) ?> · <?= e(formatar_centavos((int) $plano['valor_centavos'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </fieldset>
        <?php endforeach; ?>
        <button class="btn" type="submit">Salvar mensalidades da família</button>
    </form>
</section>
<?php endif; ?>
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
                        · <?= e(rotulo_parentesco((string) $aluno['parentesco'])) ?>
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
                    <option value="<?= (int) $aluno['id'] ?>"><?= e($aluno['nome']) ?> · <?= eh_menor($aluno['data_nascimento']) ? 'menor' : 'adulto' ?></option>
                <?php endforeach; ?>
            </select>
            <p class="estado">Menor de idade recebe mãe, pai ou outro. Adulto recebe familiar, para um adulto pagar a mensalidade de outro.</p>
            <label for="parentesco">Parentesco</label>
            <select id="parentesco" name="parentesco" required>
                <option value="">Selecione</option>
                <?php foreach (PARENTESCOS as $valor => $rotulo): ?>
                    <option value="<?= e($valor) ?>"><?= e($rotulo) ?></option>
                <?php endforeach; ?>
                <option value="familiar">Familiar</option>
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
