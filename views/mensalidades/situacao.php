<h1>Situação dos pagamentos</h1>
<?php parte('menu_mensalidades'); ?>
<p class="estado">O prazo de cada mês acaba no último dia. Quem pagou nos últimos 7 dias aparece no aviso de cima.</p>
<?php parte('avisos_pagamento', ['avisos_pagamento' => $avisos]); ?>

<section class="bloco">
    <h2>Pagaram</h2>
    <?php if ($pagaram === []): ?>
        <p class="vazio">Ninguém pagou neste mês.</p>
    <?php else: ?>
        <ul class="itens">
            <?php foreach ($pagaram as $item): ?>
                <li>
                    <span>
                        <strong><?= e($item['aluno_nome']) ?></strong>
                        <small><?= e(formatar_mes($item['competencia'])) ?> · quem paga <?= e($item['pagador_nome']) ?></small>
                        <span class="selo">Paga<?= $item['pago_em'] ? ' em ' . e(formatar_data((string) $item['pago_em'])) : '' ?></span>
                    </span>
                    <strong><?= e(formatar_centavos((int) $item['valor'])) ?></strong>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="bloco">
    <h2>Não pagaram</h2>
    <?php if ($nao === []): ?>
        <p class="vazio">Não há mensalidade em aberto até este mês.</p>
    <?php else: ?>
        <ul class="itens">
            <?php foreach ($nao as $item): ?>
                <li>
                    <span>
                        <strong><?= e($item['aluno_nome']) ?></strong>
                        <small><?= e(formatar_mes($item['competencia'])) ?> · quem paga <?= e($item['pagador_nome']) ?></small>
                        <?php if ($item['prazo'] === 'atrasada'): ?>
                            <span class="selo selo-atraso">Atrasada</span>
                        <?php elseif ($item['prazo'] === 'prazo'): ?>
                            <span class="selo">Prazo acaba em <?= e(formatar_data(fim_do_mes($item['competencia']))) ?></span>
                        <?php else: ?>
                            <span class="selo selo-inativo">Em aberto</span>
                        <?php endif; ?>
                    </span>
                    <strong><?= e(formatar_centavos((int) $item['valor'])) ?></strong>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
