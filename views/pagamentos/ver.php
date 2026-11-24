<h1>Pagamentos</h1>
<?php foreach ($alertas as $alerta): ?>
    <p class="aviso">
        O desconto de <?= e(formatar_percentual($alerta['percentual'])) ?> de <?= e($alerta['nome']) ?>
        acaba <?= $alerta['mes_que_vem'] ? 'mês que vem' : 'neste mês' ?>.
        No mês seguinte o valor volta ao normal.
    </p>
<?php endforeach; ?>

<?php if ($atletas === []): ?>
    <p class="vazio">Não há mensalidade ligada a este login.</p>
<?php else: ?>
    <form method="post" action="<?= e(url('/pagamentos')) ?>">
        <?= csrf_field() ?>
        <?php foreach ($atletas as $atleta): ?>
            <section class="bloco">
                <h2><?= e($atleta['nome']) ?></h2>
                <?php if ($atleta['meses'] === []): ?>
                    <p class="vazio">A equipe ainda não definiu a mensalidade.</p>
                <?php else: ?>
                    <ul class="parcelas">
                        <?php foreach ($atleta['meses'] as $mes): ?>
                            <li>
                                <strong><?= e(formatar_mes($mes['competencia'])) ?></strong>
                                <p class="estado"><?= e($mes['plano_nome']) ?></p>
                                <?php if ((float) $mes['inicial'] > 0 || (float) $mes['familia'] > 0 || (float) $mes['extra'] > 0): ?>
                                    <p class="estado">Cheio <?= e(formatar_centavos((int) $mes['cheio'])) ?>
                                        <?php if ((float) $mes['inicial'] > 0): ?>
                                            · inicial <?= e(formatar_percentual($mes['inicial'])) ?>
                                        <?php endif; ?>
                                        <?php if ((float) $mes['familia'] > 0): ?>
                                            · família <?= e(formatar_percentual($mes['familia'])) ?>
                                        <?php endif; ?>
                                        <?php if ((float) $mes['extra'] > 0): ?>
                                            · extra <?= e(formatar_percentual($mes['extra'])) ?> aplicado
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                <p>
                                    <strong><?= e(formatar_centavos((int) $mes['final'])) ?></strong>
                                    <?php if ($mes['situacao'] === 'paga'): ?>
                                        <span class="selo">Paga<?= $mes['pago_em'] ? ' em ' . e(formatar_data((string) $mes['pago_em'])) : '' ?></span>
                                    <?php elseif ($mes['prevista']): ?>
                                        <span class="selo selo-inativo">Prevista</span>
                                    <?php else: ?>
                                        <span class="selo selo-inativo">Em aberto</span>
                                    <?php endif; ?>
                                </p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php
                    $pagaveis = [];
                    foreach ($atleta['meses'] as $mes) {
                        if ($mes['pagavel']) {
                            $pagaveis[] = $mes;
                        }
                    }
                    ?>
                    <?php if ($pagaveis !== []): ?>
                        <label class="pagar-ate">
                            Pagar até
                            <select class="ate-pagamento" name="ate[<?= (int) $atleta['id'] ?>]">
                                <option value="" data-centavos="0">Não incluir</option>
                                <?php $acumulado = 0; ?>
                                <?php foreach ($pagaveis as $indice => $mes): ?>
                                    <?php $acumulado += (int) $mes['final']; ?>
                                    <option value="<?= e($mes['chave']) ?>" data-centavos="<?= $acumulado ?>">
                                        <?php if ($indice === 0): ?>
                                            <?= count($pagaveis) > 1 ? 'Só ' : '' ?><?= e(formatar_mes($mes['competencia'])) ?>
                                        <?php else: ?>
                                            Até <?= e(formatar_mes($mes['competencia'])) ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <p class="estado">Começa no primeiro mês em aberto. Para adiantar, os meses seguintes entram em ordem.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
        <?php if ($tem_pagavel): ?>
            <section class="bloco pagamento-base">
                <h2>Pagamento</h2>
                <p class="estado">O programa de pagamento entra nesta etapa. Nada é cobrado agora e a mensalidade continua em aberto.</p>
                <div class="total-conta">
                    <span>Total selecionado</span>
                    <strong id="total-pagamento">R$ 0,00</strong>
                </div>
                <button class="btn" type="submit">Continuar para o pagamento</button>
            </section>
        <?php endif; ?>
    </form>
<?php endif; ?>
