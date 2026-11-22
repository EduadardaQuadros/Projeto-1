<h1>Planos</h1>
<?php parte('menu_mensalidades'); ?>
<div class="linha-acoes">
    <a class="btn btn-linha" href="<?= e(url('/mensalidades/planos/novo')) ?>">Novo plano</a>
</div>
<?php if ($planos === []): ?>
    <p class="vazio">Nenhum plano cadastrado.</p>
<?php else: ?>
    <div class="lista">
        <?php foreach ($planos as $plano): ?>
            <article class="cartao">
                <strong><?= e($plano['nome']) ?></strong>
                <span><?= (int) $plano['treinos_semana'] ?> <?= (int) $plano['treinos_semana'] === 1 ? 'treino por semana' : 'treinos por semana' ?></span>
                <span><?= e(formatar_centavos((int) $plano['valor_centavos'])) ?></span>
                <?php if ($plano['desconto_inicial'] !== null && $plano['desconto_meses'] !== null): ?>
                    <span>Desconto inicial <?= e(formatar_percentual($plano['desconto_inicial'])) ?> por <?= (int) $plano['desconto_meses'] ?> <?= (int) $plano['desconto_meses'] === 1 ? 'mês' : 'meses' ?></span>
                <?php endif; ?>
                <?php
                $acabando = false;
                foreach ($avisos as $aviso) {
                    if ((int) $aviso['plano_id'] === (int) $plano['id']) {
                        $acabando = true;
                        break;
                    }
                }
                ?>
                <?php if ($acabando): ?>
                    <span class="selo">Desconto acabando</span>
                <?php endif; ?>
                <?php if (!(int) $plano['ativo']): ?>
                    <span class="selo selo-inativo">Inativo</span>
                <?php endif; ?>
                <div class="linha-acoes">
                    <a class="btn btn-secundario btn-linha" href="<?= e(url('/mensalidades/planos/' . $plano['id'] . '/editar')) ?>">Editar</a>
                    <form method="post" action="<?= e(url('/mensalidades/planos/' . $plano['id'] . '/ativo')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="ativo" value="<?= (int) $plano['ativo'] ? '0' : '1' ?>">
                        <button class="btn-perigo" type="submit"><?= (int) $plano['ativo'] ? 'Desativar' : 'Reativar' ?></button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
