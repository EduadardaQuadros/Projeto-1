<h1>Professores</h1>
<div class="linha-acoes">
    <a class="btn btn-linha" href="<?= e(url('/professores/novo')) ?>">Novo professor</a>
</div>
<?php if ($professores === []): ?>
    <p class="vazio">Nenhum professor cadastrado.</p>
<?php else: ?>
    <div class="lista">
        <?php foreach ($professores as $professor): ?>
            <a class="cartao cartao-link" href="<?= e(url('/professores/' . $professor['id'])) ?>">
                <strong><?= e($professor['nome']) ?></strong>
                <span><?= (int) $professor['total_turmas'] ?> turmas</span>
                <?php if (!(int) $professor['ativo']): ?>
                    <span class="selo selo-inativo">Acesso inativo</span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
