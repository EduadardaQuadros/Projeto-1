<?php
$registro = is_array($aluno) ? $aluno : [];
$nome = old($old, 'nome', (string) ($registro['nome'] ?? ''));
$nascimento = old($old, 'data_nascimento', isset($registro['data_nascimento']) ? substr((string) $registro['data_nascimento'], 0, 10) : '');
$telefone = old($old, 'telefone', formatar_telefone(isset($registro['telefone']) ? (string) $registro['telefone'] : ''));
$cpf = old($old, 'cpf', formatar_cpf(isset($registro['cpf']) ? (string) $registro['cpf'] : ''));
$email = old($old, 'email', (string) ($registro['email'] ?? ''));
$responsavelId = old($old, 'responsavel_id', '');
$parentesco = old($old, 'parentesco', '');
$acao = $aluno === null ? url('/alunos') : url('/alunos/' . $aluno['id']);
?>
<h1><?= $aluno === null ? 'Novo aluno' : 'Editar aluno' ?></h1>
<?php if (!empty($erro)): ?>
    <p class="flash flash-erro" role="alert"><?= e($erro) ?></p>
<?php endif; ?>
<form method="post" action="<?= e($acao) ?>" class="form bloco">
    <?= csrf_field() ?>
    <label for="nome">Nome</label>
    <input id="nome" name="nome" required maxlength="120" value="<?= e($nome) ?>">
    <label for="data_nascimento">Data de nascimento</label>
    <input id="data_nascimento" name="data_nascimento" type="date" required value="<?= e($nascimento) ?>">
    <p class="aviso" id="aviso-menor" hidden>Menor de 18 anos: escolha um responsável antes de salvar.</p>
    <label for="telefone">Telefone</label>
    <input id="telefone" name="telefone" type="tel" inputmode="tel" maxlength="20" value="<?= e($telefone) ?>">
    <label for="cpf">CPF</label>
    <input id="cpf" name="cpf" inputmode="numeric" maxlength="14" value="<?= e($cpf) ?>" placeholder="Opcional">
    <label for="email">E-mail de acesso</label>
    <input id="email" name="email" type="email" inputmode="email" autocomplete="off" required maxlength="180" value="<?= e($email) ?>">
    <?php if ($aluno === null): ?>
        <label for="senha">Senha inicial</label>
        <input id="senha" name="senha" type="password" autocomplete="new-password" required minlength="6">
        <label for="senha_confirmacao">Confirmar senha</label>
        <input id="senha_confirmacao" name="senha_confirmacao" type="password" autocomplete="new-password" required minlength="6">
        <label for="responsavel_id">Responsável</label>
        <select id="responsavel_id" name="responsavel_id">
            <option value="">Nenhum</option>
            <?php foreach ($responsaveis as $responsavel): ?>
                <option value="<?= (int) $responsavel['id'] ?>"<?= $responsavelId === (string) $responsavel['id'] ? ' selected' : '' ?>><?= e($responsavel['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($responsaveis === []): ?>
            <p class="vazio">Nenhum responsável cadastrado. <a href="<?= e(url('/responsaveis/novo')) ?>">Cadastrar responsável</a></p>
        <?php endif; ?>
        <label for="parentesco">Parentesco</label>
        <select id="parentesco" name="parentesco">
            <option value="">Selecione</option>
            <?php foreach (PARENTESCOS as $valor => $rotulo): ?>
                <option value="<?= e($valor) ?>"<?= $parentesco === $valor ? ' selected' : '' ?>><?= e($rotulo) ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <button class="btn" type="submit">Salvar</button>
</form>
<p><a href="<?= e($aluno === null ? url('/alunos') : url('/alunos/' . $aluno['id'])) ?>">Cancelar</a></p>
