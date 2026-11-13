<h1>Turmas</h1>
<div class="linha-acoes">
    <a class="btn btn-linha" href="<?= e(url('/turmas/nova')) ?>">Nova turma</a>
</div>
<?php if ($turmas === []): ?>
    <p class="vazio">Nenhuma turma cadastrada.</p>
<?php else: ?>
    <div class="lista">
        <?php foreach ($turmas as $turma): ?>
            <a class="cartao cartao-link" href="<?= e(url('/turmas/' . $turma['id'])) ?>">
                <strong><?= e($turma['nome']) ?></strong>
                <span><?= e(NIVEIS[$turma['nivel']] ?? '') ?><?= $turma['local'] ? ' · ' . e($turma['local']) : '' ?></span>
                <?php foreach (formatar_grade($turma['grade']) as $linha): ?>
                    <span><?= e($linha) ?></span>
                <?php endforeach; ?>
                <span><?= (int) $turma['total_alunos'] ?> alunos<?php if ($turma['capacidade'] !== null): ?> / <?= (int) $turma['capacidade'] ?><?php endif; ?> · <?= (int) $turma['total_professores'] ?> professores</span>
                <?php if (!(int) $turma['ativa']): ?>
                    <span class="selo selo-inativo">Inativa</span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
