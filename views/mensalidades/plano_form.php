<?php
$registro = is_array($plano) ? $plano : [];
$nome = old($old, 'nome', (string) ($registro['nome'] ?? ''));
$treinos = old($old, 'treinos_semana', (string) ($registro['treinos_semana'] ?? '1'));
$valorPadrao = isset($registro['valor_centavos']) ? texto_centavos((int) $registro['valor_centavos']) : '';
$valor = old($old, 'valor', $valorPadrao);
$descontoPadrao = isset($registro['desconto_inicial']) && $registro['desconto_inicial'] !== null
    ? rtrim(rtrim(number_format((float) $registro['desconto_inicial'], 2, ',', ''), '0'), ',')
    : '';
$desconto = old($old, 'desconto_inicial', $descontoPadrao);
$meses = old($old, 'desconto_meses', (string) ($registro['desconto_meses'] ?? ''));
$ativo = $old !== [] ? isset($old['ativo']) : ($plano === null || (int) ($registro['ativo'] ?? 1) === 1);
$acao = $plano === null ? url('/mensalidades/planos') : url('/mensalidades/planos/' . $plano['id']);
?>
<h1><?= $plano === null ? 'Novo plano' : 'Editar plano' ?></h1>
<?php if (!empty($erro)): ?>
    <p class="flash flash-erro" role="alert"><?= e($erro) ?></p>
<?php endif; ?>
<form method="post" action="<?= e($acao) ?>" class="form bloco">
    <?= csrf_field() ?>
    <label for="nome">Nome</label>
    <input id="nome" name="nome" required maxlength="80" value="<?= e($nome) ?>">
    <label for="treinos_semana">Treinos por semana</label>
    <input id="treinos_semana" name="treinos_semana" type="number" min="1" max="7" required value="<?= e($treinos) ?>">
    <label for="valor">Valor</label>
    <input id="valor" name="valor" inputmode="decimal" required placeholder="220,00" value="<?= e($valor) ?>">
    <label for="desconto_inicial">Desconto inicial</label>
    <input id="desconto_inicial" name="desconto_inicial" inputmode="decimal" placeholder="Opcional, em %" value="<?= e($desconto) ?>">
    <label for="desconto_meses">Meses com esse desconto</label>
    <input id="desconto_meses" name="desconto_meses" type="number" min="1" max="36" placeholder="Ex.: 3" value="<?= e($meses) ?>">
    <p class="estado">Enquanto o prazo não acaba, o desconto sai do valor do plano. Depois a pessoa paga o valor normal.</p>
    <label class="check" for="ativo">
        <input id="ativo" name="ativo" type="checkbox" value="1"<?= $ativo ? ' checked' : '' ?>>
        <span>Plano ativo</span>
    </label>
    <button class="btn" type="submit">Salvar</button>
</form>
<p><a href="<?= e(url('/mensalidades/planos')) ?>">Cancelar</a></p>
