<h1><?= e($filho['nome']) ?></h1>
<p class="estado"><?= (int) idade($filho['data_nascimento']) ?> anos<?php if (eh_menor($filho['data_nascimento'])): ?> · menor de idade<?php endif; ?></p>
<?php if ($turmas === []): ?>
    <p class="vazio">Nenhuma turma ativa no momento.</p>
<?php else: ?>
    <?php foreach ($turmas as $turma): ?>
        <section class="bloco">
            <h2><?= e($turma['nome']) ?></h2>
            <p class="estado"><?= e(NIVEIS[$turma['nivel']] ?? '') ?><?= $turma['local'] ? ' · ' . e($turma['local']) : '' ?></p>
            <?php $nomes = $professores[(int) $turma['id']] ?? []; ?>
            <p><?= $nomes === [] ? 'Sem professor vinculado.' : e(implode(', ', $nomes)) ?></p>
            <?php $grade = $horarios[(int) $turma['id']] ?? []; ?>
            <?php if ($grade === []): ?>
                <p class="vazio">Sem horário cadastrado.</p>
            <?php else: ?>
                <ul class="itens">
                    <?php foreach ($grade as $horario): ?>
                        <li>
                            <span><?= e(DIAS[(int) $horario['dia_semana']] ?? '') ?> · <?= e(formatar_hora($horario['hora_inicio'])) ?>–<?= e(formatar_hora($horario['hora_fim'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
<?php endif; ?>
