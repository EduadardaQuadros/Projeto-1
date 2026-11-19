(function () {
    var campo = document.querySelector('#data_nascimento');
    var aviso = document.querySelector('#aviso-menor');
    var blocoResponsavel = document.querySelector('#bloco-responsavel');
    if (campo && (aviso || blocoResponsavel)) {
    var atualizar = function () {
        var menor = false;
        if (campo.value) {
            var nasc = new Date(campo.value + 'T00:00:00');
            if (!Number.isNaN(nasc.getTime())) {
                var hoje = new Date();
                var anos = hoje.getFullYear() - nasc.getFullYear();
                var mes = hoje.getMonth() - nasc.getMonth();
                if (mes < 0 || (mes === 0 && hoje.getDate() < nasc.getDate())) {
                    anos -= 1;
                }
                menor = anos < 18;
            }
        }
        if (aviso) {
            aviso.hidden = !menor;
        }
        if (blocoResponsavel) {
            blocoResponsavel.hidden = !menor;
        }
    };
    campo.addEventListener('change', atualizar);
    campo.addEventListener('input', atualizar);
    atualizar();
    }
    var botaoCopiar = document.querySelector('#copiar-convite');
    var campoLink = document.querySelector('#link-convite');
    var avisoCopiado = document.querySelector('#aviso-copiado');
    var copiarTexto = function (texto) {
        var area = document.createElement('textarea');
        area.value = texto;
        area.setAttribute('readonly', '');
        area.style.position = 'fixed';
        area.style.top = '0';
        area.style.left = '0';
        area.style.width = '2em';
        area.style.height = '2em';
        area.style.padding = '0';
        area.style.border = 'none';
        area.style.outline = 'none';
        area.style.boxShadow = 'none';
        area.style.background = 'transparent';
        document.body.appendChild(area);
        area.focus();
        area.select();
        area.setSelectionRange(0, area.value.length);
        var ok = false;
        try {
            ok = document.execCommand('copy');
        } catch (erro) {
            ok = false;
        }
        document.body.removeChild(area);
        return ok;
    };
    if (botaoCopiar && campoLink) {
        botaoCopiar.addEventListener('click', function () {
            var texto = campoLink.value;
            var mostrar = function () {
                if (avisoCopiado) {
                    avisoCopiado.hidden = false;
                }
            };
            var copiou = copiarTexto(texto);
            if (copiou) {
                mostrar();
            }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(texto).then(mostrar).catch(function () {
                    if (!copiou && copiarTexto(texto)) {
                        mostrar();
                    }
                });
            }
        });
    }
})();
