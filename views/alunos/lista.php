<h1>Alunos</h1>
<div class="linha-acoes">
    <a class="btn btn-linha" href="<?= e(url('/alunos/novo')) ?>">Novo aluno</a>
</div>
<?php if ($alunos === []): ?>
    <p class="vazio">Nenhum aluno cadastrado.</p>
<?php else: ?>
    <div class="lista">
        <?php foreach ($alunos as $aluno): ?>
            <a class="cartao cartao-link" href="<?= e(url('/alunos/' . $aluno['id'])) ?>">
                <strong><?= e($aluno['nome']) ?></strong>
                <span><?= (int) idade($aluno['data_nascimento']) ?> anos</span>
                <?php if (eh_menor($aluno['data_nascimento'])): ?>
                    <span class="selo">Menor</span>
                <?php endif; ?>
                <?php if (!(int) $aluno['ativo']): ?>
                    <span class="selo selo-inativo">Acesso inativo</span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
