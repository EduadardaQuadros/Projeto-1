<h1>Descontos de família</h1>
<?php parte('menu_mensalidades'); ?>
<p class="estado">Uma pessoa sozinha não tem desconto. A faixa usada é a maior quantidade que a conta alcança. O percentual vale para cada atleta.</p>
<?php if ($faixas === []): ?>
    <p class="vazio">Nenhuma faixa cadastrada. Toda conta fica sem desconto de família.</p>
<?php else: ?>
    <div class="lista">
        <?php foreach ($faixas as $faixa): ?>
            <form method="post" action="<?= e(url('/mensalidades/descontos/' . $faixa['id'])) ?>" class="form cartao">
                <?= csrf_field() ?>
                <label for="minimo-<?= (int) $faixa['id'] ?>">A partir de quantos atletas</label>
                <input id="minimo-<?= (int) $faixa['id'] ?>" name="minimo_atletas" type="number" min="2" max="30" required value="<?= (int) $faixa['minimo_atletas'] ?>">
                <label for="percentual-<?= (int) $faixa['id'] ?>">Percentual em cada um</label>
                <input id="percentual-<?= (int) $faixa['id'] ?>" name="percentual" inputmode="decimal" required value="<?= e(rtrim(rtrim(number_format((float) $faixa['percentual'], 2, ',', ''), '0'), ',')) ?>">
                <button class="btn" type="submit">Salvar faixa</button>
            </form>
            <form method="post" action="<?= e(url('/mensalidades/descontos/' . $faixa['id'] . '/excluir')) ?>" onsubmit="return confirm('Remover esta faixa?');">
                <?= csrf_field() ?>
                <button class="btn-perigo" type="submit">Remover</button>
            </form>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<form method="post" action="<?= e(url('/mensalidades/descontos')) ?>" class="form bloco">
    <?= csrf_field() ?>
    <h2>Nova faixa</h2>
    <label for="minimo_atletas">A partir de quantos atletas</label>
    <input id="minimo_atletas" name="minimo_atletas" type="number" min="2" max="30" required>
    <label for="percentual">Percentual em cada um</label>
    <input id="percentual" name="percentual" inputmode="decimal" required placeholder="5">
    <button class="btn" type="submit">Adicionar</button>
</form>
