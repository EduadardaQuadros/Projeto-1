(function () {
    var campo = document.querySelector('#data_nascimento');
    var aviso = document.querySelector('#aviso-menor');
    var blocoResponsavel = document.querySelector('#bloco-responsavel');
    var blocoFamiliar = document.querySelector('#bloco-familiar');
    var definirBloco = function (bloco, ativo) {
        if (!bloco) {
            return;
        }
        bloco.hidden = !ativo;
        var campos = bloco.querySelectorAll('input, select, textarea');
        Array.prototype.forEach.call(campos, function (item) {
            item.disabled = !ativo;
        });
    };
    if (campo && (aviso || blocoResponsavel || blocoFamiliar)) {
    var atualizar = function () {
        var menor = false;
        var temData = false;
        if (campo.value) {
            var nasc = new Date(campo.value + 'T00:00:00');
            if (!Number.isNaN(nasc.getTime())) {
                temData = true;
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
        definirBloco(blocoResponsavel, menor);
        definirBloco(blocoFamiliar, temData && !menor);
    };
    campo.addEventListener('change', atualizar);
    campo.addEventListener('input', atualizar);
    atualizar();
    }
    var alunoConta = document.querySelector('#conta_aluno_id');
    var pagadorConta = document.querySelector('#pagador_id');
    var avisoPagador = document.querySelector('#aviso-pagador');
    var pagarProprio = document.querySelector('#pagar_proprio');
    if (alunoConta && pagadorConta && alunoConta.tagName === 'SELECT') {
        var filtrarPagador = function () {
            var option = alunoConta.options[alunoConta.selectedIndex];
            var ids = option && option.getAttribute('data-pagadores') ? option.getAttribute('data-pagadores').split(',').filter(Boolean) : [];
            var menor = option && option.getAttribute('data-menor') === '1';
            var escolhido = !!option && option.value !== '';
            Array.prototype.forEach.call(pagadorConta.options, function (opcao, indice) {
                if (indice === 0) {
                    return;
                }
                var oculto = escolhido && ids.indexOf(opcao.value) === -1;
                opcao.hidden = oculto;
                opcao.disabled = oculto;
            });
            if (pagadorConta.selectedOptions[0] && pagadorConta.selectedOptions[0].disabled) {
                pagadorConta.value = '';
            }
            if (pagarProprio) {
                pagarProprio.closest('label').hidden = !escolhido || menor;
                if (menor) {
                    pagarProprio.checked = false;
                }
            }
            if (avisoPagador) {
                avisoPagador.hidden = !(escolhido && menor && ids.length === 0);
            }
        };
        alunoConta.addEventListener('change', filtrarPagador);
        filtrarPagador();
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
    var prazos = document.querySelectorAll('.ate-pagamento');
    var totalPagamento = document.querySelector('#total-pagamento');
    if (prazos.length && totalPagamento) {
        var textoCentavos = function (centavos) {
            var inteiro = Math.floor(centavos / 100);
            var fracao = String(centavos % 100).padStart(2, '0');
            var texto = String(inteiro).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return 'R$ ' + texto + ',' + fracao;
        };
        var atualizarTotal = function () {
            var soma = 0;
            Array.prototype.forEach.call(prazos, function (campo) {
                var opcao = campo.options[campo.selectedIndex];
                soma += opcao ? (parseInt(opcao.getAttribute('data-centavos'), 10) || 0) : 0;
            });
            totalPagamento.textContent = textoCentavos(soma);
        };
        Array.prototype.forEach.call(prazos, function (campo) {
            campo.addEventListener('change', atualizarTotal);
        });
        atualizarTotal();
    }
})();
