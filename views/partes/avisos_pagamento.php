<?php if (!empty($avisos_pagamento)): ?>
    <?php foreach ($avisos_pagamento as $aviso): ?>
        <p class="aviso<?= ($aviso['tipo'] ?? '') === 'pago' ? ' aviso-ok' : '' ?>"><?= e((string) $aviso['texto']) ?></p>
    <?php endforeach; ?>
<?php endif; ?>
