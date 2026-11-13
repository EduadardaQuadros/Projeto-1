<h1>Trocar senha</h1>
<?php if (!empty($erro)): ?>
    <p class="flash flash-erro" role="alert"><?= e($erro) ?></p>
<?php endif; ?>
<form method="post" action="<?= e(url('/senha')) ?>" class="form bloco">
    <?= csrf_field() ?>
    <label for="senha_atual">Senha atual</label>
    <input id="senha_atual" name="senha_atual" type="password" autocomplete="current-password" required>
    <label for="senha">Nova senha</label>
    <input id="senha" name="senha" type="password" autocomplete="new-password" required minlength="6">
    <label for="senha_confirmacao">Confirmar nova senha</label>
    <input id="senha_confirmacao" name="senha_confirmacao" type="password" autocomplete="new-password" required minlength="6">
    <button class="btn" type="submit">Salvar senha</button>
</form>
