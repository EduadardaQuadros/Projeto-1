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
</div>
