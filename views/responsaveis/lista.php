<h1>Responsáveis</h1>
<div class="linha-acoes">
    <a class="btn btn-linha" href="<?= e(url('/responsaveis/novo')) ?>">Novo responsável</a>
</div>
<?php if ($responsaveis === []): ?>
    <p class="vazio">Nenhum responsável cadastrado.</p>
<?php else: ?>
    <div class="lista">
        <?php foreach ($responsaveis as $responsavel): ?>
            <a class="cartao cartao-link" href="<?= e(url('/responsaveis/' . $responsavel['id'])) ?>">
                <strong><?= e($responsavel['nome']) ?></strong>
                <span><?= (int) $responsavel['total_alunos'] ?> alunos</span>
                <?php if (!(int) $responsavel['ativo']): ?>
                    <span class="selo selo-inativo">Acesso inativo</span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
