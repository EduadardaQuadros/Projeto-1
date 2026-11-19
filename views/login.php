<div class="cartao-login">
    <div class="marca-login">
        <span class="bola" aria-hidden="true"></span>
        <h1>Escola de Vôlei</h1>
        <p>Turmas, horários e matrículas</p>
    </div>
    <?php if (!empty($erro)): ?>
        <p class="flash flash-erro" role="alert"><?= e($erro) ?></p>
    <?php endif; ?>
    <form method="post" action="<?= e(url('/login')) ?>" class="form">
        <?= csrf_field() ?>
        <?php if (!empty($depois)): ?>
            <input type="hidden" name="depois" value="<?= e($depois) ?>">
        <?php endif; ?>
        <label for="email">E-mail</label>
        <input id="email" name="email" type="email" inputmode="email" autocomplete="username" required maxlength="180" value="<?= e($email ?? '') ?>">
        <label for="senha">Senha</label>
        <input id="senha" name="senha" type="password" autocomplete="current-password" required>
        <button class="btn" type="submit">Entrar</button>
    </form>
    <?php if (!empty($dica_inicial)): ?>
        <p class="dica">Primeiro acesso da equipe<br><strong>admin@escola.local</strong><br>Senha: <strong>volei123</strong></p>
    <?php endif; ?>
</div>
