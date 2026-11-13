<?php
$registro = is_array($responsavel) ? $responsavel : [];
$nome = old($old, 'nome', (string) ($registro['nome'] ?? ''));
$telefone = old($old, 'telefone', formatar_telefone(isset($registro['telefone']) ? (string) $registro['telefone'] : ''));
$cpf = old($old, 'cpf', formatar_cpf(isset($registro['cpf']) ? (string) $registro['cpf'] : ''));
$email = old($old, 'email', (string) ($registro['email'] ?? ''));
$acao = $responsavel === null ? url('/responsaveis') : url('/responsaveis/' . $responsavel['id']);
?>
<h1><?= $responsavel === null ? 'Novo responsável' : 'Editar responsável' ?></h1>
<?php if (!empty($erro)): ?>
    <p class="flash flash-erro" role="alert"><?= e($erro) ?></p>
<?php endif; ?>
<form method="post" action="<?= e($acao) ?>" class="form bloco">
    <?= csrf_field() ?>
    <label for="nome">Nome</label>
    <input id="nome" name="nome" required maxlength="120" value="<?= e($nome) ?>">
    <label for="telefone">Telefone</label>
    <input id="telefone" name="telefone" type="tel" inputmode="tel" maxlength="20" value="<?= e($telefone) ?>">
    <label for="cpf">CPF</label>
    <input id="cpf" name="cpf" inputmode="numeric" maxlength="14" value="<?= e($cpf) ?>" placeholder="Opcional">
    <label for="email">E-mail de acesso</label>
    <input id="email" name="email" type="email" inputmode="email" autocomplete="off" required maxlength="180" value="<?= e($email) ?>">
    <?php if ($responsavel === null): ?>
        <label for="senha">Senha inicial</label>
        <input id="senha" name="senha" type="password" autocomplete="new-password" required minlength="6">
        <label for="senha_confirmacao">Confirmar senha</label>
        <input id="senha_confirmacao" name="senha_confirmacao" type="password" autocomplete="new-password" required minlength="6">
    <?php endif; ?>
    <button class="btn" type="submit">Salvar</button>
</form>
<p><a href="<?= e($responsavel === null ? url('/responsaveis') : url('/responsaveis/' . $responsavel['id'])) ?>">Cancelar</a></p>
