window.SgceCerrarAlertaInstalador = function (Boton) {
    var Alerta = Boton ? Boton.closest('.SgceInstallerAlert, .alert') : null;
    if (!Alerta) { return; }
    Alerta.classList.add('SgceAlertLeaving');
    window.setTimeout(function () { Alerta.remove(); }, 180);
};

document.addEventListener('DOMContentLoaded', function () {
    function MostrarMensaje(Form, Texto) {
        var MensajeAnterior = document.querySelector('.SgceInstallerClientAlert');
        if (MensajeAnterior) { MensajeAnterior.remove(); }

        var Alerta = document.createElement('div');
        Alerta.className = 'alert alert-danger SgceInstallerAlert SgceInstallerClientAlert border-0 shadow-sm rounded-4 mt-4 fw-semibold';
        Alerta.setAttribute('role', 'alert');
        Alerta.innerHTML = `<div class="SgceInstallerAlertBody"><i class="fa-solid fa-circle-xmark me-2"></i><span></span></div><button type="button" class="SgceInstallerAlertClose" aria-label="Cerrar mensaje" data-sgce-dismiss onclick="var A=this.closest('.SgceInstallerAlert,.alert');if(A){A.classList.add('SgceAlertLeaving');setTimeout(function(){A.remove();},180);}"><i class="fa-solid fa-xmark"></i></button>`;
        Alerta.querySelector('span').textContent = Texto;

        var Contenedor = document.querySelector('.SgceModuleWrap');
        var Tarjeta = Form.closest('.SgcePanel');
        if (Contenedor && Tarjeta) { Contenedor.insertBefore(Alerta, Tarjeta); }
        else if (Contenedor) { Contenedor.prepend(Alerta); }
        Alerta.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function CerrarAlerta(Alerta) {
        if (!Alerta) { return; }
        Alerta.classList.add('SgceAlertLeaving');
        window.setTimeout(function () { Alerta.remove(); }, 180);
    }

    document.addEventListener('click', function (Evento) {
        var Boton = Evento.target.closest('[data-sgce-dismiss]');
        if (!Boton) { return; }
        Evento.preventDefault();
        CerrarAlerta(Boton.closest('.SgceInstallerAlert, .alert'));
    });

    document.querySelectorAll('.InputUpper').forEach(function (Input) {
        Input.addEventListener('input', function () {
            var Posicion = Input.selectionStart;
            Input.value = Input.value.toUpperCase();
            try { Input.setSelectionRange(Posicion, Posicion); } catch (Error) {}
        });
    });

    document.querySelectorAll('.InputDigits').forEach(function (Input) {
        Input.addEventListener('input', function () {
            Input.value = Input.value.replace(/\D/g, '').slice(0, 15);
        });
    });

    document.querySelectorAll('.SgceInstallerPage form').forEach(function (Form) {
        Form.addEventListener('submit', function (Evento) {
            var Telefono = Form.querySelector('input[name="TelefonoEscuela"]');
            var Correo = Form.querySelector('input[name="CorreoEscuela"]');
            var UsuarioAdmin = Form.querySelector('input[name="AdminUsuario"]');
            var NombreEscuela = Form.querySelector('input[name="NombreEscuela"]');
            var NombreAdmin = Form.querySelector('input[name="AdminNombre"]');
            var Cct = Form.querySelector('input[name="ClaveCentroTrabajo"]');
            var Confirmacion = Form.querySelector('input[name="ConfirmarInstalacion"]');
            var FechaInicio = Form.querySelector('input[name="FechaInicio"]');
            var FechaFin = Form.querySelector('input[name="FechaFin"]');


            if (NombreEscuela && NombreEscuela.value.trim().length < 3) {
                Evento.preventDefault();
                MostrarMensaje(Form, 'Escribe el nombre oficial de la escuela.');
                NombreEscuela.focus();
                return;
            }

            if (Cct && Cct.value.trim() !== '' && !/^[A-Z0-9-]{3,30}$/.test(Cct.value.trim().toUpperCase())) {
                Evento.preventDefault();
                MostrarMensaje(Form, 'La CCT / clave solo debe usar letras, números o guion, de 3 a 30 caracteres.');
                Cct.focus();
                return;
            }

            if (NombreAdmin && !/^[A-ZÁÉÍÓÚÜÑ .'-]{3,120}$/i.test(NombreAdmin.value.trim())) {
                Evento.preventDefault();
                MostrarMensaje(Form, 'Escribe el nombre del administrador. Solo debe contener letras y espacios.');
                NombreAdmin.focus();
                return;
            }

            if (Telefono && Telefono.value.trim() !== '' && !/^\d{7,15}$/.test(Telefono.value.trim())) {
                Evento.preventDefault();
                MostrarMensaje(Form, 'El teléfono debe contener solo números, mínimo 7 y máximo 15 dígitos.');
                Telefono.focus();
                return;
            }

            if (Correo && Correo.value.trim() !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(Correo.value.trim())) {
                Evento.preventDefault();
                MostrarMensaje(Form, 'El correo institucional debe tener formato válido, por ejemplo direccion@escuela.com.');
                Correo.focus();
                return;
            }

            if (UsuarioAdmin && !/^[A-Za-z0-9._@-]{3,80}$/.test(UsuarioAdmin.value.trim())) {
                Evento.preventDefault();
                MostrarMensaje(Form, 'El usuario administrador debe tener mínimo 3 caracteres. Solo acepta letras, números, punto, guion, guion bajo o @.');
                UsuarioAdmin.focus();
                return;
            }

            if (FechaInicio && FechaFin && FechaInicio.value && FechaFin.value && FechaInicio.value >= FechaFin.value) {
                Evento.preventDefault();
                MostrarMensaje(Form, 'La fecha de inicio del ciclo escolar debe ser menor que la fecha de fin.');
                FechaInicio.focus();
                return;
            }

            if (!Confirmacion || Confirmacion.value.trim() !== 'INSTALAR SGCE') {
                Evento.preventDefault();
                MostrarMensaje(Form, 'Para continuar escribe exactamente: INSTALAR SGCE');
                if (Confirmacion) { Confirmacion.focus(); }
            }
        });
    });
});
