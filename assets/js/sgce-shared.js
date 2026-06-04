window.SgceAjustarColorHex = function (ColorHex, Porcentaje) {
    var Color = String(ColorHex || '#97051E').replace('#', '').trim();
    if (!/^[0-9A-Fa-f]{6}$/.test(Color)) { Color = '97051E'; }
    var Rojo = parseInt(Color.substring(0, 2), 16);
    var Verde = parseInt(Color.substring(2, 4), 16);
    var Azul = parseInt(Color.substring(4, 6), 16);
    var Limite = Math.max(-100, Math.min(100, parseInt(Porcentaje, 10) || 0));
    var Objetivo = Limite >= 0 ? 255 : 0;
    var Factor = Math.abs(Limite) / 100;
    function Ajustar(Canal) {
        var Resultado = Math.round(Canal + (Objetivo - Canal) * Factor);
        return Math.max(0, Math.min(255, Resultado));
    }
    function Hex(Canal) {
        return Canal.toString(16).toUpperCase().padStart(2, '0');
    }
    return '#' + Hex(Ajustar(Rojo)) + Hex(Ajustar(Verde)) + Hex(Ajustar(Azul));
};

window.SgceAplicarTemaColor = function (ColorHex) {
    var Valor = String(ColorHex || '#97051E').trim().toUpperCase();
    if (!/^#[0-9A-F]{6}$/.test(Valor)) { Valor = '#97051E'; }
    var Color = Valor.replace('#', '');
    var Rojo = parseInt(Color.substring(0, 2), 16);
    var Verde = parseInt(Color.substring(2, 4), 16);
    var Azul = parseInt(Color.substring(4, 6), 16);
    var Raiz = document.documentElement;
    Raiz.style.setProperty('--SgceGuinda', Valor);
    Raiz.style.setProperty('--SgceGuindaRGB', Rojo + ',' + Verde + ',' + Azul);
    Raiz.style.setProperty('--SgceGuindaOscuro', window.SgceAjustarColorHex(Valor, -22));
    Raiz.style.setProperty('--SgceGuindaProfundo', window.SgceAjustarColorHex(Valor, -48));
    Raiz.style.setProperty('--SgceGuindaSuave', window.SgceAjustarColorHex(Valor, 84));
    Raiz.style.setProperty('--SgceGuindaClaro', window.SgceAjustarColorHex(Valor, 32));
    Raiz.style.setProperty('--SgceSombraGuinda', '0 18px 42px rgba(' + Rojo + ',' + Verde + ',' + Azul + ',.22)');
    return Valor;
};
document.addEventListener('DOMContentLoaded', function() {

    function OcultarNotificacion(Alerta) {
        if (!Alerta || Alerta.dataset.Ocultando === '1') {
            return;
        }

        Alerta.dataset.Ocultando = '1';
        Alerta.style.transition = 'opacity .45s ease, transform .45s ease, max-height .45s ease, margin .45s ease, padding .45s ease';
        Alerta.style.opacity = '0';
        Alerta.style.transform = 'translateY(-12px)';
        Alerta.style.maxHeight = '0';
        Alerta.style.marginTop = '0';
        Alerta.style.marginBottom = '0';
        Alerta.style.paddingTop = '0';
        Alerta.style.paddingBottom = '0';

        setTimeout(function() {
            Alerta.remove();
        }, 500);
    }

    function PrepararNotificacion(Alerta) {
        if (!Alerta || Alerta.dataset.NotificacionPreparada === '1') {
            return;
        }

        Alerta.dataset.NotificacionPreparada = '1';
        Alerta.classList.add('alert-dismissible', 'fade', 'show');
        Alerta.style.position = 'relative';

        let BotonesCerrar = Alerta.querySelectorAll('.btn-close, [data-bs-dismiss="alert"], [data-sgce-dismiss], .SgceInstallerAlertClose');

        if (BotonesCerrar.length === 0) {
            const BotonCerrar = document.createElement('button');
            BotonCerrar.type = 'button';
            BotonCerrar.className = 'btn-close';
            BotonCerrar.setAttribute('aria-label', 'Cerrar mensaje');
            Alerta.appendChild(BotonCerrar);
            BotonesCerrar = Alerta.querySelectorAll('.btn-close');
        }

        BotonesCerrar.forEach(function(BotonCerrar) {
            if (BotonCerrar.dataset.SgceCloseReady === '1') {
                return;
            }
            BotonCerrar.dataset.SgceCloseReady = '1';
            BotonCerrar.addEventListener('click', function(Evento) {
                Evento.preventDefault();
                OcultarNotificacion(Alerta);
            });
        });

        function ProgramarAutoCierre() {
            if (!Alerta.classList.contains('d-none') && Alerta.dataset.AutoCierreProgramado !== '1') {
                Alerta.dataset.AutoCierreProgramado = '1';

                setTimeout(function() {
                    OcultarNotificacion(Alerta);
                }, 4500);
            }
        }

        ProgramarAutoCierre();

        const Observador = new MutationObserver(function() {
            ProgramarAutoCierre();
        });

        Observador.observe(Alerta, {
            attributes: true,
            attributeFilter: ['class', 'style']
        });
    }

    document.querySelectorAll('.alert').forEach(function(Alerta) {
        PrepararNotificacion(Alerta);
    });

    const ObservadorBody = new MutationObserver(function(Mutaciones) {
        Mutaciones.forEach(function(Mutacion) {
            Mutacion.addedNodes.forEach(function(Nodo) {
                if (Nodo.nodeType !== 1) {
                    return;
                }

                if (Nodo.classList && Nodo.classList.contains('alert')) {
                    PrepararNotificacion(Nodo);
                }

                if (Nodo.querySelectorAll) {
                    Nodo.querySelectorAll('.alert').forEach(function(Alerta) {
                        PrepararNotificacion(Alerta);
                    });
                }
            });
        });
    });

    ObservadorBody.observe(document.body, {
        childList: true,
        subtree: true
    });

});
document.addEventListener('show.bs.modal', function(Evento){
    var Modal = Evento.target;
    if (Modal && Modal.classList && Modal.classList.contains('modal') && Modal.parentElement !== document.body) {
        document.body.appendChild(Modal);
    }
}, true);
document.addEventListener('DOMContentLoaded', function(){
    var ModalActual = null;
    var FormularioPendiente = null;
    var EnlacePendiente = null;
    var BotonSubmitPendiente = null;

    function TextoSeguro(Valor, Predeterminado) {
        Valor = String(Valor || '').trim();
        return Valor !== '' ? Valor : Predeterminado;
    }

    function CrearModalConfirmacion() {
        var Existente = document.getElementById('SgceConfirmModalGlobal');
        if (Existente) {
            return Existente;
        }

        var Modal = document.createElement('div');
        Modal.className = 'modal fade SgceConfirmModal';
        Modal.id = 'SgceConfirmModalGlobal';
        Modal.tabIndex = -1;
        Modal.setAttribute('aria-hidden', 'true');
        Modal.innerHTML = '' +
            '<div class="modal-dialog modal-dialog-centered SgceConfirmDialog">' +
                '<div class="modal-content SgceConfirmContent">' +
                    '<div class="SgceConfirmHeader">' +
                        '<div class="SgceConfirmIcon"><i class="fa-solid fa-triangle-exclamation"></i></div>' +
                        '<h4 id="SgceConfirmTitle">CONFIRMAR ACCIÓN</h4>' +
                        '<p id="SgceConfirmSubtitle">REVISIÓN NECESARIA</p>' +
                    '</div>' +
                    '<div class="SgceConfirmBody">' +
                        '<p class="SgceConfirmMessage" id="SgceConfirmMessage">¿DESEAS CONTINUAR?</p>' +
                        '<div class="SgceConfirmDetail"><i class="fa-solid fa-circle-info"></i><span id="SgceConfirmDetail">Revisa la información antes de confirmar.</span></div>' +
                        '<div class="SgceConfirmActions">' +
                            '<button type="button" class="SgceConfirmCancel" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> CANCELAR</button>' +
                            '<button type="button" class="SgceConfirmAccept" id="SgceConfirmAccept"><i class="fa-solid fa-check"></i> CONFIRMAR</button>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
        document.body.appendChild(Modal);
        return Modal;
    }

    function ConfigurarModalDesde(Elemento) {
        var Modal = CrearModalConfirmacion();
        var TipoConfirmacion = TextoSeguro(Elemento.dataset.sgceConfirm, 'normal').toLowerCase();
        Modal.classList.toggle('SgceConfirmModalDanger', TipoConfirmacion === 'danger');
        var Icono = TextoSeguro(Elemento.dataset.sgceConfirmIcon, Elemento.dataset.sgceConfirm === 'logout' ? 'fa-right-from-bracket' : 'fa-file-import');
        var Titulo = TextoSeguro(Elemento.dataset.sgceConfirmTitle, 'CONFIRMAR ACCIÓN');
        var Subtitulo = TextoSeguro(Elemento.dataset.sgceConfirmSubtitle, Elemento.dataset.sgceConfirm === 'logout' ? 'SALIDA DEL SISTEMA' : 'IMPORTACIÓN DE DATOS');
        var Mensaje = TextoSeguro(Elemento.dataset.sgceConfirmMessage, '¿DESEAS CONTINUAR?');
        var Detalle = TextoSeguro(Elemento.dataset.sgceConfirmDetail, 'Revisa la información antes de confirmar.');
        var Boton = TextoSeguro(Elemento.dataset.sgceConfirmButton, 'SÍ, CONTINUAR');

        Modal.querySelector('.SgceConfirmIcon i').className = 'fa-solid ' + Icono;
        Modal.querySelector('#SgceConfirmTitle').textContent = Titulo;
        Modal.querySelector('#SgceConfirmSubtitle').textContent = Subtitulo;
        Modal.querySelector('#SgceConfirmMessage').textContent = Mensaje;
        Modal.querySelector('#SgceConfirmDetail').textContent = Detalle;

        var BotonAceptar = Modal.querySelector('#SgceConfirmAccept');
        BotonAceptar.disabled = false;
        BotonAceptar.innerHTML = '<i class="fa-solid fa-check"></i> ' + Boton;
        BotonAceptar.dataset.loadingText = TextoSeguro(Elemento.dataset.sgceConfirmLoading, 'PROCESANDO...');

        return Modal;
    }

    function AbrirConfirmacion(Elemento) {
        var ModalElemento = ConfigurarModalDesde(Elemento);

        if (window.bootstrap && bootstrap.Modal) {
            ModalActual = bootstrap.Modal.getOrCreateInstance(ModalElemento, { backdrop: 'static', keyboard: true });
            ModalActual.show();
        } else {
            var Mensaje = TextoSeguro(Elemento.dataset.sgceConfirmMessage, '¿DESEAS CONTINUAR?');
            if (window.confirm(Mensaje)) {
                ConfirmarAccion();
            }
        }
    }

    function AgregarSubmitter(Formulario, Boton) {
        if (!Boton || !Boton.name) {
            return;
        }
        var YaExiste = Formulario.querySelector('input[type="hidden"][data-sgce-submit-clone="1"][name="' + Boton.name + '"]');
        if (YaExiste) {
            YaExiste.value = Boton.value;
            return;
        }
        var Campo = document.createElement('input');
        Campo.type = 'hidden';
        Campo.name = Boton.name;
        Campo.value = Boton.value;
        Campo.dataset.sgceSubmitClone = '1';
        Formulario.appendChild(Campo);
    }

    function FormularioConfirmacionValido(Formulario) {
        if (!Formulario) {
            return false;
        }

        if (typeof Formulario.checkValidity === 'function' && !Formulario.checkValidity()) {
            if (typeof Formulario.reportValidity === 'function') {
                Formulario.reportValidity();
            }
            return false;
        }

        var CamposArchivo = Formulario.querySelectorAll('input[type="file"][required]');
        for (var I = 0; I < CamposArchivo.length; I++) {
            var CampoArchivo = CamposArchivo[I];
            if (!CampoArchivo.files || CampoArchivo.files.length === 0) {
                CampoArchivo.focus();
                if (typeof CampoArchivo.reportValidity === 'function') {
                    CampoArchivo.reportValidity();
                }
                return false;
            }
        }

        return true;
    }

    function EnviarFormularioConfirmado() {
        if (!FormularioPendiente) {
            return;
        }

        FormularioPendiente.dataset.sgceConfirmado = '1';
        AgregarSubmitter(FormularioPendiente, BotonSubmitPendiente);

        if (ModalActual && typeof ModalActual.hide === 'function') {
            try { ModalActual.hide(); } catch (ErrorModal) {}
        }

        if (FormularioPendiente.querySelector('input[type="file"]')) {
            FormularioPendiente.setAttribute('enctype', 'multipart/form-data');
            FormularioPendiente.setAttribute('method', 'POST');
        }
        FormularioPendiente.submit();
    }

    function ConfirmarAccion() {
        var ModalElemento = document.getElementById('SgceConfirmModalGlobal');
        var BotonAceptar = ModalElemento ? ModalElemento.querySelector('#SgceConfirmAccept') : null;
        if (BotonAceptar) {
            BotonAceptar.disabled = true;
            BotonAceptar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + TextoSeguro(BotonAceptar.dataset.loadingText, 'PROCESANDO...');
        }

        if (FormularioPendiente) {
            EnviarFormularioConfirmado();
            return;
        }

        if (EnlacePendiente) {
            window.location.href = EnlacePendiente.href;
        }
    }

    document.body.addEventListener('submit', function(Evento){
        var Formulario = Evento.target;
        if (!Formulario || !Formulario.matches || !Formulario.matches('form[data-sgce-confirm]')) {
            return;
        }
        if (Formulario.dataset.sgceConfirmado === '1') {
            return;
        }

        var SubmitterActual = Evento.submitter || document.activeElement || null;
        if (SubmitterActual && SubmitterActual.dataset && SubmitterActual.dataset.sgceSkipConfirm === '1') {
            return;
        }

        if (!FormularioConfirmacionValido(Formulario)) {
            Evento.preventDefault();
            return;
        }

        Evento.preventDefault();
        FormularioPendiente = Formulario;
        EnlacePendiente = null;
        BotonSubmitPendiente = Evento.submitter || document.activeElement || null;
        AbrirConfirmacion(Formulario);
    }, true);

    document.body.addEventListener('click', function(Evento){
        var Enlace = Evento.target.closest ? Evento.target.closest('a[data-sgce-confirm]') : null;
        if (!Enlace) {
            return;
        }
        Evento.preventDefault();
        EnlacePendiente = Enlace;
        FormularioPendiente = null;
        BotonSubmitPendiente = null;
        AbrirConfirmacion(Enlace);
    });

    document.body.addEventListener('click', function(Evento){
        var Boton = Evento.target.closest ? Evento.target.closest('#SgceConfirmAccept') : null;
        if (!Boton) {
            return;
        }
        Evento.preventDefault();
        ConfirmarAccion();
    });
});
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('[data-maestro-empty-close="true"]').forEach(function(Boton){
        if (Boton.dataset.sgceMaestroCloseReady === '1') { return; }
        Boton.dataset.sgceMaestroCloseReady = '1';
        Boton.addEventListener('click', function(Evento){
            Evento.preventDefault();
            var Aviso = Boton.closest('.MaestroEmptyState');
            if (Aviso) {
                Aviso.classList.add('MaestroEmptyStateOculto');
                window.setTimeout(function(){ Aviso.remove(); }, 260);
            }
        });
    });
});
document.addEventListener('DOMContentLoaded', function () {
    var Marcador = document.querySelector('[data-sgce-csrf-token]');
    if (!Marcador) { return; }
    var Token = Marcador.getAttribute('data-sgce-csrf-token') || '';
    if (!Token) { return; }
    document.querySelectorAll('form[method]').forEach(function (Formulario) {
        var Metodo = (Formulario.getAttribute('method') || '').toLowerCase();
        if (Metodo !== 'post' || Formulario.querySelector('input[name="CsrfToken"]')) { return; }
        var Input = document.createElement('input');
        Input.type = 'hidden';
        Input.name = 'CsrfToken';
        Input.value = Token;
        Formulario.appendChild(Input);
    });
});
