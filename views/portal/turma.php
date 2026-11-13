<h1><?= e($turma['nome']) ?></h1>
<p class="estado"><?= e(NIVEIS[$turma['nivel']] ?? '') ?><?= $turma['local'] ? ' · ' . e($turma['local']) : '' ?></p>
<section class="bloco">
    <h2>Horários</h2>
    <?php if ($horarios === []): ?>
        <p class="vazio">Sem horário cadastrado.</p>
    <?php else: ?>
        <ul class="itens">
            <?php foreach ($horarios as $horario): ?>
                <li>
                    <span><?= e(DIAS[(int) $horario['dia_semana']] ?? '') ?> · <?= e(formatar_hora($horario['hora_inicio'])) ?>–<?= e(formatar_hora($horario['hora_fim'])) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<?php if ($modo === 'professor'): ?>
    <section class="bloco">
        <h2>Alunos</h2>
        <?php if ($alunos === []): ?>
            <p class="vazio">Nenhum aluno matriculado.</p>
        <?php else: ?>
            <ul class="itens">
                <?php foreach ($alunos as $aluno): ?>
                    <li>
                        <span>
                            <?= e($aluno['nome']) ?>
                            <?php if (eh_menor($aluno['data_nascimento'])): ?><span class="selo">Menor</span><?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php else: ?>
    <section class="bloco">
        <h2>Professores</h2>
        <?php if ($professores === []): ?>
            <p class="vazio">Nenhum professor vinculado.</p>
        <?php else: ?>
            <ul class="itens">
                <?php foreach ($professores as $professor): ?>
                    <li><span><?= e($professor['nome']) ?></span></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php endif; ?>
