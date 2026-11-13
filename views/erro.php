<h1><?= e($titulo) ?></h1>
<p><?= e($mensagem) ?></p>
<p><a class="btn btn-linha" href="<?= e(url(!empty($usuario) ? '/' : '/login')) ?>">Voltar</a></p>
