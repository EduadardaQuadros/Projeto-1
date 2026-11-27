<h1>Dados de quem paga</h1>
<p class="estado">O Asaas precisa destes dados para abrir o Pix e o cartão de crédito. Eles ficam salvos no cadastro.</p>
<form class="form" method="post" action="<?= e(url('/pagamentos')) ?>">
    <?= csrf_field() ?>
    <?php foreach ($ate as $alunoId => $chave): ?>
        <?php if ((string) $chave !== ''): ?>
            <input type="hidden" name="ate[<?= (int) $alunoId ?>]" value="<?= e((string) $chave) ?>">
        <?php endif; ?>
    <?php endforeach; ?>
    <?php if (!empty($pedir_cpf)): ?>
        <label for="cpf">CPF</label>
        <input id="cpf" name="cpf" inputmode="numeric" autocomplete="off" required>
    <?php endif; ?>
    <?php if (!empty($pedir_telefone)): ?>
        <label for="telefone">Celular</label>
        <input id="telefone" name="telefone" inputmode="tel" autocomplete="tel" required>
    <?php endif; ?>
    <button class="btn" type="submit">Continuar para o pagamento</button>
</form>
<p><a href="<?= e(url('/pagamentos')) ?>">Voltar</a></p>
