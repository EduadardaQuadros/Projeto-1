<h1><?= e($rotulo) ?></h1>
<?php parte('menu_mensalidades'); ?>
<form method="get" action="<?= e(url('/mensalidades/mes')) ?>" class="form bloco">
    <label for="competencia">Mês</label>
    <input id="competencia" name="competencia" type="month" required value="<?= e($mes) ?>">
    <button class="btn btn-secundario" type="submit">Ver mês</button>
</form>
<form method="post" action="<?= e(url('/mensalidades/mes')) ?>" class="bloco">
    <?= csrf_field() ?>
    <input type="hidden" name="competencia" value="<?= e($mes) ?>">
    <button class="btn" type="submit">Gerar cobranças</button>
</form>
<p class="estado">Cobrança em aberto é recalculada. Cobrança paga permanece com o valor da época.</p>

<?php if ($pendencias !== []): ?>
    <section class="bloco">
        <h2>Fora deste mês</h2>
        <ul class="itens">
            <?php foreach ($pendencias as $aluno): ?>
                <li><a href="<?= e(url('/alunos/' . $aluno['id'])) ?>"><?= e($aluno['nome']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<?php if ($grupos === []): ?>
    <p class="vazio">Nenhuma cobrança neste mês.</p>
<?php else: ?>
    <section class="bloco">
        <dl class="ficha">
            <div><dt>Em aberto</dt><dd><?= e(formatar_centavos((int) $totais['aberta'])) ?></dd></div>
            <div><dt>Pago</dt><dd><?= e(formatar_centavos((int) $totais['paga'])) ?></dd></div>
        </dl>
    </section>
    <?php foreach ($grupos as $grupo): ?>
        <section class="bloco">
            <h2><?= e($grupo['pagador_nome']) ?></h2>
            <ul class="itens">
                <?php foreach ($grupo['itens'] as $item): ?>
                    <li>
                        <span>
                            <a href="<?= e(url('/alunos/' . $item['aluno_id'])) ?>"><?= e($item['aluno_nome']) ?></a>
                            <small>
                                <?= e($item['plano_nome']) ?>
                                <?php if ((float) $item['desconto_inicial'] > 0): ?>
                                    · inicial <?= e(formatar_percentual($item['desconto_inicial'])) ?>
                                <?php endif; ?>
                                · <?= e(formatar_percentual($item['desconto_familia'])) ?> família
                                <?php if ((float) $item['desconto_extra'] > 0): ?>
                                    · extra <?= e(formatar_percentual($item['desconto_extra'])) ?> aplicado
                                <?php endif; ?>
                            </small>
                            <?php if ((float) $item['desconto_extra'] > 0): ?>
                                <span class="selo">Extra aplicado</span>
                            <?php endif; ?>
                            <?php if ($item['situacao'] === 'paga'): ?>
                                <span class="selo">Paga<?= $item['pago_em'] ? ' em ' . e(formatar_data((string) $item['pago_em'])) : '' ?></span>
                            <?php else: ?>
                                <span class="selo selo-inativo">Em aberto</span>
                            <?php endif; ?>
                        </span>
                        <span>
                            <strong><?= e(formatar_centavos((int) $item['valor_final_centavos'])) ?></strong>
                            <form method="post" action="<?= e(url('/cobrancas/' . $item['id'] . '/situacao')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="competencia" value="<?= e($mes) ?>">
                                <?php if ($item['situacao'] === 'paga'): ?>
                                    <input type="hidden" name="situacao" value="aberta">
                                    <button class="btn-perigo" type="submit">Voltar para em aberto</button>
                                <?php else: ?>
                                    <input type="hidden" name="situacao" value="paga">
                                    <button class="btn btn-secundario btn-linha" type="submit">Marcar como paga</button>
                                <?php endif; ?>
                            </form>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="total-conta">
                <span>Total</span>
                <strong><?= e(formatar_centavos((int) $grupo['total'])) ?></strong>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>
