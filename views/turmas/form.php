<?php
$registro = is_array($turma) ? $turma : [];
$nome = old($old, 'nome', (string) ($registro['nome'] ?? ''));
$nivel = old($old, 'nivel', (string) ($registro['nivel'] ?? ''));
$local = old($old, 'local', (string) ($registro['local'] ?? ''));
$capacidade = old($old, 'capacidade', isset($registro['capacidade']) ? (string) $registro['capacidade'] : '');
$ativa = $old !== [] ? isset($old['ativa']) : (int) ($registro['ativa'] ?? 1) === 1;
$acao = $turma === null ? url('/turmas') : url('/turmas/' . $turma['id']);
?>
<h1><?= $turma === null ? 'Nova turma' : 'Editar turma' ?></h1>
<?php if (!empty($erro)): ?>
    <p class="flash flash-erro" role="alert"><?= e($erro) ?></p>
<?php endif; ?>
<form method="post" action="<?= e($acao) ?>" class="form bloco">
    <?= csrf_field() ?>
    <label for="nome">Nome</label>
    <input id="nome" name="nome" required maxlength="120" value="<?= e($nome) ?>">
    <label for="nivel">Nível</label>
    <select id="nivel" name="nivel" required>
        <option value="">Selecione</option>
        <?php foreach (NIVEIS as $valor => $rotulo): ?>
            <option value="<?= e($valor) ?>"<?= $nivel === $valor ? ' selected' : '' ?>><?= e($rotulo) ?></option>
        <?php endforeach; ?>
    </select>
    <label for="local">Local</label>
    <input id="local" name="local" maxlength="120" value="<?= e((string) $local) ?>">
    <label for="capacidade">Capacidade</label>
    <input id="capacidade" name="capacidade" inputmode="numeric" maxlength="3" value="<?= e($capacidade) ?>" placeholder="Em branco se não houver limite">
    <label class="check" for="ativa">
        <input id="ativa" name="ativa" type="checkbox" value="1"<?= $ativa ? ' checked' : '' ?>>
        Turma ativa
    </label>
    <button class="btn" type="submit">Salvar</button>
</form>
<p><a href="<?= e($turma === null ? url('/turmas') : url('/turmas/' . $turma['id'])) ?>">Cancelar</a></p>
