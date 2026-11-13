<section class="bloco">
    <h2>Acesso</h2>
    <p class="estado"><?= (int) $ativo ? 'Login ativo' : 'Login inativo' ?></p>
    <form method="post" action="<?= e(url($acao_ativo)) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="ativo" value="<?= (int) $ativo ? '0' : '1' ?>">
        <button class="btn btn-secundario" type="submit"><?= (int) $ativo ? 'Desativar acesso' : 'Reativar acesso' ?></button>
    </form>
    <form method="post" action="<?= e(url($acao_senha)) ?>" class="form">
        <?= csrf_field() ?>
        <label for="senha">Nova senha</label>
        <input id="senha" name="senha" type="password" autocomplete="new-password" required minlength="6">
        <label for="senha_confirmacao">Confirmar senha</label>
        <input id="senha_confirmacao" name="senha_confirmacao" type="password" autocomplete="new-password" required minlength="6">
        <button class="btn" type="submit">Redefinir senha</button>
    </form>
</section>
