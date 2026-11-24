<h1>Meus filhos</h1>
<p><a class="btn btn-linha" href="<?= e(url('/pagamentos')) ?>">Pagamentos</a></p>
<?php if ($filhos === []): ?>
    <p class="vazio">Nenhum aluno vinculado a este responsável.</p>
<?php else: ?>
    <div class="lista">
        <?php foreach ($filhos as $filho): ?>
            <a class="cartao cartao-link" href="<?= e(url('/filhos/' . $filho['id'])) ?>">
                <strong><?= e($filho['nome']) ?></strong>
                <span><?= (int) idade($filho['data_nascimento']) ?> anos · <?= e(rotulo_parentesco((string) $filho['parentesco'])) ?></span>
                <?php if (eh_menor($filho['data_nascimento'])): ?>
                    <span class="selo">Menor</span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
