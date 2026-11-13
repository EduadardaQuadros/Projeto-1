(function () {
    var campo = document.querySelector('#data_nascimento');
    var aviso = document.querySelector('#aviso-menor');
    if (!campo || !aviso) {
        return;
    }
    var atualizar = function () {
        if (!campo.value) {
            aviso.hidden = true;
            return;
        }
        var nasc = new Date(campo.value + 'T00:00:00');
        if (Number.isNaN(nasc.getTime())) {
            aviso.hidden = true;
            return;
        }
        var hoje = new Date();
        var anos = hoje.getFullYear() - nasc.getFullYear();
        var mes = hoje.getMonth() - nasc.getMonth();
        if (mes < 0 || (mes === 0 && hoje.getDate() < nasc.getDate())) {
            anos -= 1;
        }
        aviso.hidden = anos >= 18;
    };
    campo.addEventListener('change', atualizar);
    campo.addEventListener('input', atualizar);
    atualizar();
})();
