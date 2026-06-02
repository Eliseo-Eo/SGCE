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
        Alerta.innerHTML = `<div class="SgceInstallerAlertBody"><i class="fa-solid fa-circle-xmark me-2"></i><span></span></div><button type="button" class="SgceInstallerAlertClose" aria-label="Cerrar mensaje" data-sgce-dismiss><i class="fa-solid fa-xmark"></i></button>`;
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



    function RenderChecks(Contenedor, Checks) {
        if (!Contenedor) { return; }
        Contenedor.innerHTML = '';
        Checks.forEach(function (Check) {
            var Item = document.createElement('div');
            Item.className = 'SgceInstallerCheckItem SgceInstallerCheck' + (Check.estado || 'warning').toUpperCase();
            var Icon = Check.estado === 'ok' ? 'fa-circle-check' : (Check.estado === 'error' ? 'fa-circle-xmark' : 'fa-triangle-exclamation');
            Item.innerHTML = '<i class="fa-solid ' + Icon + '"></i><div><strong></strong><p></p></div>';
            Item.querySelector('strong').textContent = Check.titulo || 'Verificación';
            Item.querySelector('p').textContent = Check.detalle || '';
            Contenedor.appendChild(Item);
        });
    }

    var BotonVerificar = document.getElementById('SgceInstallerVerifyBtn');
    var ResultadosVerificar = document.getElementById('SgceInstallerCheckResults');
    var FormInstalador = document.getElementById('SgceInstallerForm');
    if (BotonVerificar && ResultadosVerificar && FormInstalador) {
        BotonVerificar.addEventListener('click', function () {
            BotonVerificar.disabled = true;
            BotonVerificar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verificando...';
            var Datos = new FormData(FormInstalador);
            fetch('Instalar.php?VerificarServidor=1', { method: 'POST', body: Datos, credentials: 'same-origin' })
                .then(function (Respuesta) { return Respuesta.json(); })
                .then(function (DatosRespuesta) { RenderChecks(ResultadosVerificar, DatosRespuesta.checks || []); })
                .catch(function () { MostrarMensaje(FormInstalador, 'No fue posible ejecutar la verificación del servidor. Revisa la conexión y vuelve a intentar.'); })
                .finally(function () {
                    BotonVerificar.disabled = false;
                    BotonVerificar.innerHTML = '<i class="fa-solid fa-shield-check"></i> Verificar servidor';
                });
        });
    }

    var Color = document.getElementById('ColorInstitucional');
    var TextoColor = document.getElementById('ColorInstitucionalTexto');
    if (Color && TextoColor) {
        var ActualizarColor = function () {
            var Valor = window.SgceAplicarTemaColor ? window.SgceAplicarTemaColor(Color.value) : (Color.value || '#97051E').toUpperCase();
            TextoColor.textContent = Valor;
        };
        Color.addEventListener('input', ActualizarColor);
        ActualizarColor();
    }



    var FormInstaladorPassword = document.getElementById('SgceInstallerForm');
    if (FormInstaladorPassword) {
        var PasswordAdminLive = FormInstaladorPassword.querySelector('input[name="AdminPassword"]');
        var PasswordAdminConfirmLive = FormInstaladorPassword.querySelector('input[name="AdminPasswordConfirm"]');
        var ValidarPasswordConfirmLive = function () {
            if (!PasswordAdminLive || !PasswordAdminConfirmLive) { return; }
            if (PasswordAdminConfirmLive.value && PasswordAdminLive.value !== PasswordAdminConfirmLive.value) {
                PasswordAdminConfirmLive.setCustomValidity('Las contraseñas no coinciden.');
            } else {
                PasswordAdminConfirmLive.setCustomValidity('');
            }
        };
        if (PasswordAdminLive && PasswordAdminConfirmLive) {
            PasswordAdminLive.addEventListener('input', ValidarPasswordConfirmLive);
            PasswordAdminConfirmLive.addEventListener('input', ValidarPasswordConfirmLive);
        }
    }

    document.querySelectorAll('.SgceInstallerPage form').forEach(function (Form) {
        Form.addEventListener('submit', function (Evento) {
            var Telefono = Form.querySelector('input[name="TelefonoEscuela"]');
            var Correo = Form.querySelector('input[name="CorreoEscuela"]');
            var UsuarioAdmin = Form.querySelector('input[name="AdminUsuario"]');
            var PasswordAdmin = Form.querySelector('input[name="AdminPassword"]');
            var PasswordAdminConfirm = Form.querySelector('input[name="AdminPasswordConfirm"]');
            var NombreEscuela = Form.querySelector('input[name="NombreEscuela"]');
            var NombreAdmin = Form.querySelector('input[name="AdminNombre"]');
            var Cct = Form.querySelector('input[name="ClaveCentroTrabajo"]');
            var Confirmacion = Form.querySelector('input[name="ConfirmarInstalacion"]');
            var FechaInicio = Form.querySelector('input[name="FechaInicio"]');
            var FechaFin = Form.querySelector('input[name="FechaFin"]');
            var ColorInstitucional = Form.querySelector('input[name="ColorInstitucional"]');
            var PlaneacionesCantidad = Form.querySelector('input[name="PlaneacionesCantidad"]');


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

            if (ColorInstitucional && !/^#[0-9A-Fa-f]{6}$/.test(ColorInstitucional.value.trim())) {
                Evento.preventDefault();
                MostrarMensaje(Form, 'Selecciona un color institucional válido.');
                ColorInstitucional.focus();
                return;
            }

            if (UsuarioAdmin && !/^[A-Za-z0-9._@-]{3,80}$/.test(UsuarioAdmin.value.trim())) {
                Evento.preventDefault();
                MostrarMensaje(Form, 'El usuario administrador debe tener mínimo 3 caracteres. Solo acepta letras, números, punto, guion, guion bajo o @.');
                UsuarioAdmin.focus();
                return;
            }

            if (PasswordAdmin && PasswordAdminConfirm && PasswordAdmin.value !== PasswordAdminConfirm.value) {
                Evento.preventDefault();
                MostrarMensaje(Form, 'Las contraseñas del administrador no coinciden. Revisa ambos campos e intenta nuevamente.');
                PasswordAdminConfirm.focus();
                return;
            }

            if (FechaInicio && FechaFin && FechaInicio.value && FechaFin.value && FechaInicio.value >= FechaFin.value) {
                Evento.preventDefault();
                MostrarMensaje(Form, 'La fecha de inicio del ciclo escolar debe ser menor que la fecha de fin.');
                FechaInicio.focus();
                return;
            }

            if (PlaneacionesCantidad) {
                var Cantidad = parseInt(PlaneacionesCantidad.value || '0', 10);
                if (!Cantidad || Cantidad < 1 || Cantidad > 12) {
                    Evento.preventDefault();
                    MostrarMensaje(Form, 'La cantidad de planeaciones debe estar entre 1 y 12.');
                    PlaneacionesCantidad.focus();
                    return;
                }
            }

            if (!Confirmacion || Confirmacion.value.trim() !== 'INSTALAR SGCE') {
                Evento.preventDefault();
                MostrarMensaje(Form, 'Para continuar escribe exactamente: INSTALAR SGCE');
                if (Confirmacion) { Confirmacion.focus(); }
            }
        });
    });
});
