/* SGCE - Módulo compartido: confirm-modal.js */
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
        var EsLogout = TipoConfirmacion === 'logout' || TipoConfirmacion === 'salir' || TipoConfirmacion === 'cerrar-sesion';
        var TiposPeligrosos = ['danger', 'delete', 'remove', 'trash', 'eliminar', 'borrar', 'destroy'];
        var EsPeligroso = TiposPeligrosos.indexOf(TipoConfirmacion) !== -1;
        Modal.classList.toggle('SgceConfirmModalLogout', EsLogout);
        Modal.classList.toggle('SgceConfirmModalDanger', EsPeligroso && !EsLogout);
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
            document.body.classList.add('SgceConfirmBackdropHelper');
            ModalElemento.addEventListener('hidden.bs.modal', function(){
                document.body.classList.remove('SgceConfirmBackdropHelper');
            }, { once: true });
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
        if (!Formulario || !Formulario.matches || !Formulario.matches('form')) {
            return;
        }
        if (Formulario.dataset.sgceConfirmado === '1') {
            return;
        }

        var SubmitterActual = Evento.submitter || document.activeElement || null;
        if (SubmitterActual && SubmitterActual.dataset && SubmitterActual.dataset.sgceSkipConfirm === '1') {
            return;
        }

        var ElementoConfirmacion = null;
        if (SubmitterActual && SubmitterActual.closest) {
            var BotonConfirmacion = SubmitterActual.closest('[data-sgce-confirm]');
            if (BotonConfirmacion && BotonConfirmacion.form === Formulario) {
                ElementoConfirmacion = BotonConfirmacion;
            }
        }
        if (!ElementoConfirmacion && Formulario.matches('form[data-sgce-confirm]')) {
            ElementoConfirmacion = Formulario;
        }
        if (!ElementoConfirmacion) {
            return;
        }

        if (!FormularioConfirmacionValido(Formulario)) {
            Evento.preventDefault();
            return;
        }

        Evento.preventDefault();
        FormularioPendiente = Formulario;
        EnlacePendiente = null;
        BotonSubmitPendiente = SubmitterActual;
        AbrirConfirmacion(ElementoConfirmacion);
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
