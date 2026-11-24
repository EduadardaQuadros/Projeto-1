<h1>Início</h1>
<p><a class="btn btn-linha" href="<?= e(url('/pagamentos')) ?>">Pagamentos</a></p>
<section class="bloco">
    <h2>Minhas turmas</h2>
    <?php if ($turmas === []): ?>
        <p class="vazio">Você não tem matrícula ativa.</p>
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
</section>
<section class="bloco">
    <h2>Meus filhos</h2>
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
</section>
