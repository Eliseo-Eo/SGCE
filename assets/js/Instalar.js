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



    function RenderCheckSummary(Checks) {
        var Panel = document.getElementById('SgceInstallerCheckPanel');
        if (!Panel) { return; }
        var Summary = Panel.querySelector('.SgceInstallerCheckSummary');
        if (!Summary) { return; }
        var Totales = { ok: 0, warning: 0, error: 0 };
        Checks.forEach(function (Check) {
            var Estado = Check.estado || 'warning';
            if (!Object.prototype.hasOwnProperty.call(Totales, Estado)) { Estado = 'warning'; }
            Totales[Estado] += 1;
        });
        Summary.innerHTML = '<span class="SgceCheckOk">OK: ' + Totales.ok + '</span><span class="SgceCheckWarning">Avisos: ' + Totales.warning + '</span><span class="SgceCheckError">Errores: ' + Totales.error + '</span>';
    }

    function RenderChecks(Contenedor, Checks) {
        if (!Contenedor) { return; }
        Contenedor.innerHTML = '';
        Contenedor.classList.remove('IsPreloaded');
        RenderCheckSummary(Checks);
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

    var BotonDetalles = document.getElementById('SgceInstallerDetailsBtn');
    var BotonVerificar = document.getElementById('SgceInstallerVerifyBtn');
    var ResultadosVerificar = document.getElementById('SgceInstallerCheckResults');
    var FormInstalador = document.getElementById('SgceInstallerForm');

    function ActualizarEstadoDetalles(Expandir) {
        if (!ResultadosVerificar || !BotonDetalles) { return; }
        ResultadosVerificar.hidden = !Expandir;
        BotonDetalles.setAttribute('aria-expanded', Expandir ? 'true' : 'false');
        BotonDetalles.innerHTML = Expandir ? '<i class="fa-solid fa-eye-slash"></i> Ocultar detalles' : '<i class="fa-solid fa-list-check"></i> Ver detalles';
    }

    if (BotonDetalles && ResultadosVerificar) {
        ActualizarEstadoDetalles(false);
        BotonDetalles.addEventListener('click', function () {
            ActualizarEstadoDetalles(ResultadosVerificar.hidden === true);
        });
    }
    if (BotonVerificar && ResultadosVerificar && FormInstalador) {
        BotonVerificar.addEventListener('click', function () {
            BotonVerificar.disabled = true;
            BotonVerificar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verificando...';
            var Datos = new FormData(FormInstalador);
            fetch('Instalar.php?VerificarServidor=1', { method: 'POST', body: Datos, credentials: 'same-origin' })
                .then(function (Respuesta) { return Respuesta.json(); })
                .then(function (DatosRespuesta) { RenderChecks(ResultadosVerificar, DatosRespuesta.checks || []); ActualizarEstadoDetalles(true); })
                .catch(function () { MostrarMensaje(FormInstalador, 'No fue posible ejecutar la verificación del servidor. Revisa la conexión y vuelve a intentar.'); })
                .finally(function () {
                    BotonVerificar.disabled = false;
                    BotonVerificar.innerHTML = '<i class="fa-solid fa-shield-check"></i> Verificar servidor y MySQL';
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

    var CampoMatriculaAutomatica = document.querySelector('input[type="checkbox"][name="MatriculaAutomatica"]');
    var CampoPrefijoMatricula = document.querySelector('input[name="MatriculaPrefijo"]');
    var CampoEjemploMatricula = document.getElementById('SgceInstallerMatriculaEjemplo');
    var AyudaMatricula = document.querySelector('.SgceMatriculaHelp');
    var ActualizarEjemploMatricula = function () {
        if (!CampoPrefijoMatricula || !CampoEjemploMatricula) { return; }
        var MatriculaHabilitada = !CampoMatriculaAutomatica || CampoMatriculaAutomatica.checked === true;
        var Prefijo = String(CampoPrefijoMatricula.value || 'SGCE').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 12) || 'SGCE';
        if (!CampoPrefijoMatricula.disabled) { CampoPrefijoMatricula.value = Prefijo; }
        CampoPrefijoMatricula.disabled = !MatriculaHabilitada;
        CampoPrefijoMatricula.required = MatriculaHabilitada;
        CampoEjemploMatricula.disabled = !MatriculaHabilitada;
        CampoEjemploMatricula.value = MatriculaHabilitada ? (Prefijo + '-' + new Date().getFullYear() + '-000001') : 'No aplica';
        if (!MatriculaHabilitada) { CampoPrefijoMatricula.setCustomValidity(''); }
        if (AyudaMatricula) { AyudaMatricula.classList.toggle('SgceMuted', !MatriculaHabilitada); }
    };
    if (CampoPrefijoMatricula && CampoEjemploMatricula) {
        CampoPrefijoMatricula.addEventListener('input', ActualizarEjemploMatricula);
        if (CampoMatriculaAutomatica) { CampoMatriculaAutomatica.addEventListener('change', ActualizarEjemploMatricula); }
        ActualizarEjemploMatricula();
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


    var FormPlaneaciones = document.getElementById('SgceInstallerForm');
    if (FormPlaneaciones) {
        var UsaPlaneacionesSwitch = FormPlaneaciones.querySelector('input[type="checkbox"][name="UsaPlaneaciones"]');
        var TipoPlaneacionSelect = FormPlaneaciones.querySelector('select[name="TipoPlaneacion"]');
        var PlaneacionesCantidadInput = FormPlaneaciones.querySelector('input[name="PlaneacionesCantidad"]');
        var PlaneacionesAyuda = FormPlaneaciones.querySelector('.SgcePlaneacionesHelp');
        var UsaProgramasSwitch = FormPlaneaciones.querySelector('input[type="checkbox"][name="UsaProgramas"]');
        var NivelEducativoSelect = FormPlaneaciones.querySelector('select[name="NivelEducativo"]');
        var ProgramasTextarea = FormPlaneaciones.querySelector('textarea[name="ProgramasIniciales"]');
        var ProgramasAyuda = FormPlaneaciones.querySelector('.SgceProgramasHelp');
        var PeriodosModoSelect = FormPlaneaciones.querySelector('select[name="PeriodosModo"]');
        var PeriodosPersonalizadosTextarea = FormPlaneaciones.querySelector('textarea[name="PeriodosPersonalizados"]');
        var PeriodosPersonalizadosAyuda = FormPlaneaciones.querySelector('.SgcePeriodosPersonalizadosHelp');
        var NivelesConProgramasObligatorios = ['UNIVERSIDAD', 'MAESTRIA', 'DOCTORADO'];
        var ActualizarProgramasEducativos = function () {
            if (!UsaProgramasSwitch || !ProgramasTextarea) { return; }
            var Nivel = NivelEducativoSelect ? String(NivelEducativoSelect.value || '').toUpperCase() : '';
            var RequiereProgramas = NivelesConProgramasObligatorios.indexOf(Nivel) !== -1;
            if (RequiereProgramas) {
                UsaProgramasSwitch.checked = true;
                UsaProgramasSwitch.disabled = true;
            } else {
                UsaProgramasSwitch.disabled = false;
            }
            var Habilitado = UsaProgramasSwitch.checked === true || RequiereProgramas;
            ProgramasTextarea.disabled = !Habilitado;
            ProgramasTextarea.required = Habilitado;
            ProgramasTextarea.classList.toggle('SgceCampoBloqueado', !Habilitado);
            if (!Habilitado) { ProgramasTextarea.setCustomValidity(''); }
            if (ProgramasAyuda) { ProgramasAyuda.classList.toggle('SgceMuted', !Habilitado); }
        };
        var ActualizarPeriodosPersonalizados = function () {
            if (!PeriodosModoSelect || !PeriodosPersonalizadosTextarea) { return; }
            var Personalizado = String(PeriodosModoSelect.value || 'AUTOMATICO').toUpperCase() === 'PERSONALIZADO';
            PeriodosPersonalizadosTextarea.disabled = !Personalizado;
            PeriodosPersonalizadosTextarea.required = Personalizado;
            PeriodosPersonalizadosTextarea.classList.toggle('SgceCampoBloqueado', !Personalizado);
            if (!Personalizado) {
                PeriodosPersonalizadosTextarea.value = '';
                PeriodosPersonalizadosTextarea.setCustomValidity('');
            }
            if (PeriodosPersonalizadosAyuda) { PeriodosPersonalizadosAyuda.classList.toggle('SgceMuted', !Personalizado); }
        };
        var ActualizarPlaneaciones = function () {
            if (!UsaPlaneacionesSwitch) { return; }
            var Habilitado = UsaPlaneacionesSwitch.checked === true;
            [TipoPlaneacionSelect, PlaneacionesCantidadInput].forEach(function (Campo) {
                if (!Campo) { return; }
                Campo.disabled = !Habilitado;
                Campo.required = Habilitado;
                Campo.classList.toggle('SgceCampoBloqueado', !Habilitado);
                if (!Habilitado) { Campo.setCustomValidity(''); }
            });
            if (!Habilitado) {
                if (PlaneacionesCantidadInput) { PlaneacionesCantidadInput.value = ''; }
            } else {
                var TipoPlaneacion = TipoPlaneacionSelect ? String(TipoPlaneacionSelect.value || 'CICLO').toUpperCase() : 'CICLO';
                if (PlaneacionesCantidadInput) {
                    if (TipoPlaneacion === 'PERIODO') {
                        PlaneacionesCantidadInput.placeholder = 'Ej. 3';
                    } else if (TipoPlaneacion === 'CICLO') {
                        PlaneacionesCantidadInput.placeholder = 'Ej. 6';
                    } else {
                        PlaneacionesCantidadInput.placeholder = 'Ej. 1';
                    }
                    PlaneacionesCantidadInput.required = true;
                    PlaneacionesCantidadInput.setCustomValidity('');
                }
                if (PlaneacionesAyuda) {
                    var AyudasPlaneacion = {
                        CICLO: 'Se solicitará la cantidad configurada de planeaciones por materia durante el ciclo escolar.',
                        PERIODO: 'Se solicitará una planeación por cada periodo de evaluación configurado.',
                        UNIDAD: 'Útil cuando cada materia trabaja por unidades, bloques, temas o proyectos.',
                        SEMANA: 'Útil para escuelas que piden planeaciones semanales.'
                    };
                    PlaneacionesAyuda.textContent = AyudasPlaneacion[TipoPlaneacion] || 'Se usa para el control de entregas por materia.';
                }
            }
            if (PlaneacionesAyuda) {
                PlaneacionesAyuda.classList.toggle('SgceMuted', !Habilitado);
            }
        };
        if (PeriodosModoSelect && PeriodosPersonalizadosTextarea) {
            PeriodosModoSelect.addEventListener('change', ActualizarPeriodosPersonalizados);
            ActualizarPeriodosPersonalizados();
            window.setTimeout(ActualizarPeriodosPersonalizados, 60);
            window.addEventListener('pageshow', ActualizarPeriodosPersonalizados);
        }
        if (UsaPlaneacionesSwitch) {
            ['change', 'input', 'click'].forEach(function (EventoNombre) {
                UsaPlaneacionesSwitch.addEventListener(EventoNombre, function () {
                    window.setTimeout(ActualizarPlaneaciones, 0);
                });
            });
            if (TipoPlaneacionSelect) { TipoPlaneacionSelect.addEventListener('change', ActualizarPlaneaciones); }
            var PeriodosCantidadPlaneacion = FormPlaneaciones.querySelector('input[name="PeriodosCantidad"]');
            if (PeriodosCantidadPlaneacion) { PeriodosCantidadPlaneacion.addEventListener('input', function () {
                if (TipoPlaneacionSelect && String(TipoPlaneacionSelect.value || '').toUpperCase() === 'PERIODO') { ActualizarPlaneaciones(); }
            }); }
            ActualizarPlaneaciones();
            window.setTimeout(ActualizarPlaneaciones, 60);
            window.addEventListener('pageshow', ActualizarPlaneaciones);
        }
        if (UsaProgramasSwitch && ProgramasTextarea) {
            ['change', 'input', 'click'].forEach(function (EventoNombre) {
                UsaProgramasSwitch.addEventListener(EventoNombre, function () {
                    window.setTimeout(ActualizarProgramasEducativos, 0);
                });
            });
            if (NivelEducativoSelect) { NivelEducativoSelect.addEventListener('change', ActualizarProgramasEducativos); }
            ActualizarProgramasEducativos();
            window.setTimeout(ActualizarProgramasEducativos, 60);
            window.addEventListener('pageshow', ActualizarProgramasEducativos);
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
            var UsaPlaneaciones = Form.querySelector('input[type="checkbox"][name="UsaPlaneaciones"]');
            var PeriodosCantidad = Form.querySelector('input[name="PeriodosCantidad"]');
            var UsaProgramas = Form.querySelector('input[type="checkbox"][name="UsaProgramas"]');
            var ProgramasIniciales = Form.querySelector('textarea[name="ProgramasIniciales"]');
            var NivelEducativo = Form.querySelector('select[name="NivelEducativo"]');
            var TurnosDisponibles = Form.querySelector('textarea[name="TurnosDisponibles"]');
            var CalificacionMinima = Form.querySelector('input[name="CalificacionMinima"]');
            var CalificacionMaxima = Form.querySelector('input[name="CalificacionMaxima"]');
            var CalificacionAprobatoria = Form.querySelector('input[name="CalificacionAprobatoria"]');
            var MatriculaAutomatica = Form.querySelector('input[type="checkbox"][name="MatriculaAutomatica"]');
            var MatriculaPrefijo = Form.querySelector('input[name="MatriculaPrefijo"]');
            var PeriodosModo = Form.querySelector('select[name="PeriodosModo"]');
            var PeriodosPersonalizados = Form.querySelector('textarea[name="PeriodosPersonalizados"]');

            if (PeriodosModo && PeriodosPersonalizados && String(PeriodosModo.value || 'AUTOMATICO').toUpperCase() !== 'PERSONALIZADO') {
                PeriodosPersonalizados.disabled = true;
                PeriodosPersonalizados.required = false;
                PeriodosPersonalizados.value = '';
                PeriodosPersonalizados.setCustomValidity('');
            }

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

            if (PeriodosCantidad) {
                var CantidadPeriodos = parseInt(PeriodosCantidad.value || '0', 10);
                if (!CantidadPeriodos || CantidadPeriodos < 1 || CantidadPeriodos > 12) {
                    Evento.preventDefault();
                    MostrarMensaje(Form, 'La cantidad de periodos de evaluación debe estar entre 1 y 12.');
                    PeriodosCantidad.focus();
                    return;
                }
            }

            if (TurnosDisponibles) {
                var Turnos = TurnosDisponibles.value.split(/[\n,;]+/).map(function (Turno) { return Turno.trim(); }).filter(Boolean);
                if (Turnos.length === 0) {
                    Evento.preventDefault();
                    MostrarMensaje(Form, 'Captura al menos un turno disponible. Ejemplo: MATUTINO.');
                    TurnosDisponibles.focus();
                    return;
                }
            }

            if (CalificacionMinima && CalificacionMaxima && CalificacionAprobatoria) {
                var MinCal = parseFloat(CalificacionMinima.value);
                var MaxCal = parseFloat(CalificacionMaxima.value);
                var AprobCal = parseFloat(CalificacionAprobatoria.value);
                if (isNaN(MinCal) || isNaN(MaxCal) || isNaN(AprobCal) || MinCal < 0 || MaxCal > 100 || MinCal >= MaxCal || AprobCal < MinCal || AprobCal > MaxCal) {
                    Evento.preventDefault();
                    MostrarMensaje(Form, 'Revisa la escala de calificaciones: la mínima debe ser menor que la máxima y la aprobatoria debe estar dentro del rango.');
                    CalificacionMinima.focus();
                    return;
                }
            }

            if (MatriculaAutomatica && MatriculaAutomatica.checked && MatriculaPrefijo && !/^[A-Z0-9]{2,12}$/.test(MatriculaPrefijo.value.trim().toUpperCase())) {
                Evento.preventDefault();
                MostrarMensaje(Form, 'El prefijo de matrícula debe usar solo letras y números, de 2 a 12 caracteres.');
                MatriculaPrefijo.disabled = false;
                MatriculaPrefijo.focus();
                return;
            }

            if (UsaProgramas && ProgramasIniciales) {
                var NivelActual = NivelEducativo ? String(NivelEducativo.value || '').toUpperCase() : '';
                var RequiereProgramas = ['UNIVERSIDAD', 'MAESTRIA', 'DOCTORADO'].indexOf(NivelActual) !== -1;
                var ProgramasHabilitados = UsaProgramas.checked === true || RequiereProgramas;
                if (ProgramasHabilitados && !ProgramasIniciales.value.trim()) {
                    Evento.preventDefault();
                    MostrarMensaje(Form, 'Captura al menos un programa educativo o desmarca Usa programas educativos.');
                    ProgramasIniciales.focus();
                    return;
                }
            }

            if (PlaneacionesCantidad && UsaPlaneaciones && UsaPlaneaciones.checked) {
                var ValorPlaneaciones = String(PlaneacionesCantidad.value || '').trim();
                var Cantidad = parseInt(ValorPlaneaciones || '0', 10);
                if (ValorPlaneaciones === '' || !Cantidad || Cantidad < 1 || Cantidad > 12) {
                    Evento.preventDefault();
                    MostrarMensaje(Form, 'La cantidad de planeaciones debe estar entre 1 y 12.');
                    PlaneacionesCantidad.focus();
                    return;
                }
                var TipoPlaneacionActual = Form.querySelector('select[name="TipoPlaneacion"]');
                if (TipoPlaneacionActual && String(TipoPlaneacionActual.value || '').toUpperCase() === 'PERIODO' && PeriodosCantidad) {
                    var CantidadPeriodosPlaneacion = parseInt(PeriodosCantidad.value || '0', 10);
                    if (CantidadPeriodosPlaneacion > 0 && Cantidad > CantidadPeriodosPlaneacion) {
                        Evento.preventDefault();
                        MostrarMensaje(Form, 'Por periodo no puede superar la cantidad de periodos de evaluación.');
                        PlaneacionesCantidad.focus();
                        return;
                    }
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
