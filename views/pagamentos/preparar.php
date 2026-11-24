<h1>Pagamento</h1>
<p class="estado">Estas parcelas ficam prontas para o programa de pagamento. Nada é cobrado agora.</p>
<section class="bloco">
    <ul class="parcelas">
        <?php foreach ($preparo['parcelas'] as $parcela): ?>
            <li>
                <strong><?= e($parcela['aluno_nome']) ?></strong>
                <p><?= e(formatar_mes($parcela['competencia'])) ?> · <?= e($parcela['plano_nome']) ?></p>
                <?php if ((float) $parcela['inicial'] > 0 || (float) $parcela['familia'] > 0 || (float) $parcela['extra'] > 0): ?>
                    <p class="estado">
                        <?php if ((float) $parcela['inicial'] > 0): ?>
                            Inicial <?= e(formatar_percentual($parcela['inicial'])) ?>
                        <?php endif; ?>
                        <?php if ((float) $parcela['familia'] > 0): ?>
                            · família <?= e(formatar_percentual($parcela['familia'])) ?>
                        <?php endif; ?>
                        <?php if ((float) $parcela['extra'] > 0): ?>
                            · extra <?= e(formatar_percentual($parcela['extra'])) ?>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
                <p><strong><?= e(formatar_centavos((int) $parcela['final'])) ?></strong></p>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="total-conta">
        <span>Total</span>
        <strong><?= e(formatar_centavos((int) $preparo['total'])) ?></strong>
    </div>
</section>
<section class="bloco pagamento-base">
    <h2>Programa de pagamento</h2>
    <p>Aqui entra o programa de pagamento. A mensalidade continua em aberto até o pagamento ser confirmado.</p>
</section>
<p><a href="<?= e(url('/pagamentos')) ?>">Voltar</a></p>
