<h1>Painel</h1>
<div class="lista">
    <a class="cartao cartao-link" href="<?= e(url('/turmas')) ?>">
        <span class="numero"><?= (int) $contagens['turmas'] ?></span>
        <span>Turmas ativas</span>
    </a>
    <a class="cartao cartao-link" href="<?= e(url('/professores')) ?>">
        <span class="numero"><?= (int) $contagens['professores'] ?></span>
        <span>Professores</span>
    </a>
    <a class="cartao cartao-link" href="<?= e(url('/alunos')) ?>">
        <span class="numero"><?= (int) $contagens['alunos'] ?></span>
        <span>Alunos</span>
    </a>
    <a class="cartao cartao-link" href="<?= e(url('/responsaveis')) ?>">
        <span class="numero"><?= (int) $contagens['responsaveis'] ?></span>
        <span>Responsáveis</span>
    </a>
    <a class="cartao cartao-link" href="<?= e(url('/mensalidades')) ?>">
        <span class="numero"><?= (int) $contagens['sem_conta'] ?></span>
        <span>Atletas sem mensalidade</span>
    </a>
</div>
<?php if ($avisos_pagamento !== []): ?>
    <section class="bloco depois-painel">
        <h2>Pagamentos</h2>
        <p><a href="<?= e(url('/mensalidades/situacao')) ?>">Ver quem pagou e quem não pagou</a></p>
        <?php parte('avisos_pagamento', ['avisos_pagamento' => $avisos_pagamento]); ?>
    </section>
<?php else: ?>
    <p class="depois-painel"><a class="btn btn-linha" href="<?= e(url('/mensalidades/situacao')) ?>">Situação dos pagamentos</a></p>
<?php endif; ?>
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
