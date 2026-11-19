<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#12355b">
    <title><?= e($titulo) ?> · Escola de Vôlei</title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
</head>
<body class="<?= !empty($publico) ? 'publico' : 'app' ?>">
<?php if (!empty($publico)): ?>
    <main class="miolo miolo-publico">
        <?= $conteudo ?>
    </main>
<?php else: ?>
    <header class="topo">
        <a class="marca-topo" href="<?= e(url('/')) ?>">Escola de Vôlei</a>
        <?php if (!empty($usuario)): ?>
            <div class="topo-acoes">
                <p class="quem">
                    <strong><?= e($usuario['nome']) ?></strong>
                    <span><?= e(papel_label($usuario['papel'])) ?></span>
                </p>
                <?php if ($usuario['papel'] === 'equipe'): ?>
                    <a class="link-topo" href="<?= e(url('/senha')) ?>">Senha</a>
                <?php endif; ?>
                <form method="post" action="<?= e(url('/sair')) ?>">
                    <?= csrf_field() ?>
                    <button class="link-topo botao-link" type="submit">Sair</button>
                </form>
            </div>
        <?php endif; ?>
    </header>
    <main class="miolo">
        <?php if (!empty($flash) && is_array($flash)): ?>
            <p class="flash flash-<?= ($flash['tipo'] ?? '') === 'ok' ? 'ok' : 'erro' ?>" role="status"><?= e($flash['msg'] ?? '') ?></p>
        <?php endif; ?>
        <?= $conteudo ?>
    </main>
    <?php if (!empty($usuario)): ?>
        <nav class="nav" aria-label="Principal">
            <?php
            $itens = $usuario['papel'] === 'equipe'
                ? [['/', 'Início'], ['/turmas', 'Turmas'], ['/alunos', 'Alunos'], ['/professores', 'Professores'], ['/responsaveis', 'Responsáveis']]
                : [['/', 'Início'], ['/senha', 'Senha']];
            foreach ($itens as [$href, $rotulo]):
            ?>
                <a href="<?= e(url($href)) ?>"<?= nav_ativo($href) ? ' aria-current="page"' : '' ?>><?= e($rotulo) ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
<script src="<?= e(url('/assets/js/app.js')) ?>?v=<?= (int) @filemtime(__DIR__ . '/../assets/js/app.js') ?>"></script>
</body>
</html>
