<h1>Minhas turmas</h1>
<?php if ($papel !== 'professor'): ?>
    <p><a class="btn btn-linha" href="<?= e(url('/pagamentos')) ?>">Pagamentos</a></p>
<?php endif; ?>
<?php if ($turmas === []): ?>
    <p class="vazio"><?= $papel === 'professor' ? 'Você ainda não está em nenhuma turma ativa.' : 'Você não tem matrícula ativa.' ?></p>
<?php else: ?>
    <div class="lista">
        <?php foreach ($turmas as $turma): ?>
            <a class="cartao cartao-link" href="<?= e(url('/minhas-turmas/' . $turma['id'])) ?>">
                <strong><?= e($turma['nome']) ?></strong>
                <span><?= e(NIVEIS[$turma['nivel']] ?? '') ?><?= $turma['local'] ? ' · ' . e($turma['local']) : '' ?></span>
                <?php foreach (formatar_grade($turma['grade']) as $linha): ?>
                    <span><?= e($linha) ?></span>
                <?php endforeach; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
