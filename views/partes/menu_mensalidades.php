<?php
$atualMenu = request_path();
$itensMenu = [
    ['/mensalidades', 'Contas'],
    ['/mensalidades/planos', 'Planos'],
    ['/mensalidades/descontos', 'Descontos'],
    ['/mensalidades/mes', 'Mês'],
];
?>
<div class="linha-acoes">
    <?php foreach ($itensMenu as [$href, $rotulo]): ?>
        <?php $marcado = $href === '/mensalidades' ? $atualMenu === '/mensalidades' : str_starts_with($atualMenu, $href); ?>
        <a class="btn btn-secundario btn-linha" href="<?= e(url($href)) ?>"<?= $marcado ? ' aria-current="page"' : '' ?>><?= e($rotulo) ?></a>
    <?php endforeach; ?>
</div>
