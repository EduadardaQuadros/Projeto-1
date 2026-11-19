<?php
$usuario = $usuario ?? null;
$nascimento = old($old, 'data_nascimento', '');
$menorAviso = $nascimento !== '' && data_valida($nascimento) && eh_menor($nascimento);
?>
<h1><?= e($turma['nome']) ?></h1>
<p class="estado"><?= e(NIVEIS[$turma['nivel']] ?? '') ?><?= $turma['local'] ? ' · ' . e($turma['local']) : '' ?></p>
<?php if ($horarios !== []): ?>
    <ul class="itens bloco">
        <?php foreach ($horarios as $horario): ?>
            <li>
                <span><?= e(DIAS[(int) $horario['dia_semana']] ?? '') ?> · <?= e(formatar_hora($horario['hora_inicio'])) ?>–<?= e(formatar_hora($horario['hora_fim'])) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if (!empty($erro)): ?>
    <p class="flash flash-erro" role="alert"><?= e($erro) ?></p>
<?php endif; ?>

<?php if (!(int) $turma['ativa']): ?>
    <p class="aviso">Esta turma não está recebendo alunos.</p>
<?php elseif ($lotada): ?>
    <p class="aviso">Esta turma já atingiu a capacidade.</p>
<?php elseif ($usuario !== null && $usuario['papel'] === 'aluno' && $ja_matriculado): ?>
    <p class="flash flash-ok">Você já está nesta turma.</p>
    <a class="btn btn-linha" href="<?= e(url('/minhas-turmas/' . $turma['id'])) ?>">Ver turma</a>
<?php elseif ($usuario !== null && $usuario['papel'] === 'aluno'): ?>
    <form method="post" action="<?= e(url('/convite/' . $turma['convite'])) ?>" class="bloco">
        <?= csrf_field() ?>
        <p>Entre nesta turma com a conta de <?= e($usuario['nome']) ?>.</p>
        <button class="btn" type="submit">Entrar na turma</button>
    </form>
<?php elseif ($usuario !== null): ?>
    <section class="bloco">
        <p>Este link é para o aluno se cadastrar. Saia da conta de <?= e(papel_label($usuario['papel'])) ?> para continuar.</p>
        <form method="post" action="<?= e(url('/sair')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="depois" value="<?= e('/convite/' . $turma['convite']) ?>">
            <button class="btn" type="submit">Sair e abrir o cadastro</button>
        </form>
    </section>
<?php else: ?>
    <p><a href="<?= e(url('/login') . '?depois=' . rawurlencode('/convite/' . $turma['convite'])) ?>">Já tenho cadastro</a></p>
    <form method="post" action="<?= e(url('/convite/' . $turma['convite'])) ?>" class="form">
        <?= csrf_field() ?>
        <section class="bloco">
            <h2>Seu cadastro</h2>
            <label for="nome">Nome</label>
            <input id="nome" name="nome" required maxlength="120" value="<?= e(old($old, 'nome')) ?>">
            <label for="data_nascimento">Data de nascimento</label>
            <input id="data_nascimento" name="data_nascimento" type="date" required value="<?= e($nascimento) ?>">
            <p class="aviso" id="aviso-menor"<?= $menorAviso ? '' : ' hidden' ?>>Menor de 18 anos: preencha também os dados do responsável.</p>
            <label for="telefone">Telefone</label>
            <input id="telefone" name="telefone" type="tel" inputmode="tel" maxlength="20" value="<?= e(old($old, 'telefone')) ?>">
            <label for="cpf">CPF</label>
            <input id="cpf" name="cpf" inputmode="numeric" maxlength="14" value="<?= e(old($old, 'cpf')) ?>" placeholder="Opcional">
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" inputmode="email" autocomplete="off" required maxlength="180" value="<?= e(old($old, 'email')) ?>">
            <label for="senha">Senha</label>
            <input id="senha" name="senha" type="password" autocomplete="new-password" required minlength="6">
            <label for="senha_confirmacao">Confirmar senha</label>
            <input id="senha_confirmacao" name="senha_confirmacao" type="password" autocomplete="new-password" required minlength="6">
        </section>
        <section class="bloco" id="bloco-responsavel"<?= $menorAviso ? '' : ' hidden' ?>>
            <h2>Responsável</h2>
            <label for="resp_nome">Nome do responsável</label>
            <input id="resp_nome" name="resp_nome" maxlength="120" value="<?= e(old($old, 'resp_nome')) ?>">
            <label for="parentesco">Parentesco</label>
            <select id="parentesco" name="parentesco">
                <option value="">Selecione</option>
                <?php foreach (PARENTESCOS as $valor => $rotulo): ?>
                    <option value="<?= e($valor) ?>"<?= old($old, 'parentesco') === $valor ? ' selected' : '' ?>><?= e($rotulo) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="resp_telefone">Telefone</label>
            <input id="resp_telefone" name="resp_telefone" type="tel" inputmode="tel" maxlength="20" value="<?= e(old($old, 'resp_telefone')) ?>">
            <label for="resp_cpf">CPF</label>
            <input id="resp_cpf" name="resp_cpf" inputmode="numeric" maxlength="14" value="<?= e(old($old, 'resp_cpf')) ?>" placeholder="Opcional">
            <label for="resp_email">E-mail do responsável</label>
            <input id="resp_email" name="resp_email" type="email" inputmode="email" autocomplete="off" maxlength="180" value="<?= e(old($old, 'resp_email')) ?>">
            <label for="resp_senha">Senha do responsável</label>
            <input id="resp_senha" name="resp_senha" type="password" autocomplete="new-password" minlength="6">
            <label for="resp_senha_confirmacao">Confirmar senha</label>
            <input id="resp_senha_confirmacao" name="resp_senha_confirmacao" type="password" autocomplete="new-password" minlength="6">
        </section>
        <button class="btn" type="submit">Cadastrar e entrar na turma</button>
    </form>
<?php endif; ?>
