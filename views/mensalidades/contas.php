<h1>Mensalidades</h1>
<?php parte('menu_mensalidades'); ?>
<p class="estado">Cada atleta tem um plano e um adulto que paga. O desconto de família sai da quantidade de atletas ativos nessa conta.</p>
<?php if ($avisos !== []): ?>
    <section class="bloco">
        <h2>Desconto inicial acabando</h2>
        <ul class="itens">
            <?php foreach ($avisos as $aviso): ?>
                <li>
                    <span>
                        <a href="<?= e(url('/alunos/' . $aviso['aluno_id'])) ?>"><?= e($aviso['aluno_nome']) ?></a>
                        <small><?= e($aviso['plano_nome']) ?> · <?= e(formatar_percentual($aviso['percentual'])) ?> acaba em <?= e(formatar_mes($aviso['acaba_em'])) ?>. No mês seguinte o valor volta ao normal.</small>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<?php if ($pendencias !== []): ?>
    <section class="bloco">
        <h2>Sem plano ou pagador</h2>
        <p class="aviso">Estas matrículas ativas ainda não entram na cobrança do mês.</p>
        <ul class="itens">
            <?php foreach ($pendencias as $aluno): ?>
                <li>
                    <a href="<?= e(url('/alunos/' . $aluno['id'])) ?>"><?= e($aluno['nome']) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<?php if ($grupos === []): ?>
    <p class="vazio">Nenhuma conta com matrícula ativa.</p>
<?php endif; ?>

<?php foreach ($grupos as $grupo): ?>
    <section class="bloco">
        <h2><?= e($grupo['pagador_nome']) ?></h2>
        <p class="estado"><?= (int) $grupo['atletas'] ?> <?= (int) $grupo['atletas'] === 1 ? 'atleta' : 'atletas' ?> · desconto de família <?= e(formatar_percentual($grupo['familia'])) ?></p>
        <?php if ($grupo['extra'] !== null && (float) $grupo['extra'] > 0): ?>
            <p class="aviso">Desconto extra de <?= e(formatar_percentual($grupo['extra'])) ?> aplicado em cada mensalidade desta conta.</p>
        <?php endif; ?>
        <ul class="itens">
            <?php foreach ($grupo['itens'] as $item): ?>
                <li>
                    <span>
                        <a href="<?= e(url('/alunos/' . $item['aluno_id'])) ?>"><?= e($item['aluno_nome']) ?></a>
                        <small>
                            <?= e($item['plano_nome']) ?> · cheio <?= e(formatar_centavos($item['cheio'])) ?>
                            <?php if ((float) $item['inicial'] > 0): ?>
                                · inicial <?= e(formatar_percentual($item['inicial'])) ?>
                            <?php endif; ?>
                            <?php if ((float) $item['familia'] > 0): ?>
                                · família <?= e(formatar_percentual($item['familia'])) ?>
                            <?php endif; ?>
                            <?php if ((float) $item['extra'] > 0): ?>
                                · extra <?= e(formatar_percentual($item['extra'])) ?> aplicado, de <?= e(formatar_centavos((int) $item['apos_familia'])) ?> para <?= e(formatar_centavos((int) $item['final'])) ?>
                            <?php endif; ?>
                        </small>
                    </span>
                    <strong><?= e(formatar_centavos($item['final'])) ?></strong>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="total-conta">
            <span>Total da conta</span>
            <strong><?= e(formatar_centavos((int) $grupo['total'])) ?></strong>
        </div>
        <form method="post" action="<?= e(url('/mensalidades/pagadores/' . $grupo['pagador_id'] . '/desconto')) ?>" class="form">
            <?= csrf_field() ?>
            <label for="extra-<?= (int) $grupo['pagador_id'] ?>">Desconto extra desta conta</label>
            <input id="extra-<?= (int) $grupo['pagador_id'] ?>" name="desconto_extra" inputmode="decimal" placeholder="Opcional, em %" value="<?= e($grupo['extra'] !== null ? rtrim(rtrim(number_format((float) $grupo['extra'], 2, ',', ''), '0'), ',') : '') ?>">
            <button class="btn btn-secundario" type="submit">Salvar desconto</button>
        </form>
    </section>
<?php endforeach; ?>
