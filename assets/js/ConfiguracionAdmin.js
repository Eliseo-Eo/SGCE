document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.InputUpper').forEach(function (Input) {
        Input.addEventListener('input', function () {
            var Posicion = Input.selectionStart;
            Input.value = (Input.value || '').toUpperCase();
            try { Input.setSelectionRange(Posicion, Posicion); } catch (Error) {}
        });
    });

    document.querySelectorAll('.InputDigits').forEach(function (Input) {
        Input.addEventListener('input', function () {
            Input.value = (Input.value || '').replace(/\D/g, '').slice(0, 15);
        });
    });

    var Color = document.getElementById('ColorInstitucional');
    var Texto = document.getElementById('ColorInstitucionalTexto');
    if (Color && Texto) {
        var Actualizar = function () {
            var Valor = window.SgceAplicarTemaColor ? window.SgceAplicarTemaColor(Color.value) : (Color.value || '#97051E').toUpperCase();
            Texto.textContent = Valor;
        };
        Color.addEventListener('input', Actualizar);
        Actualizar();
    }


    var FormConfiguracion = document.querySelector('form[action="ConfiguracionAdmin.php"], .SgceConfigForm, form');
    if (FormConfiguracion) {
        var UsaPlaneacionesSwitch = FormConfiguracion.querySelector('input[name="UsaPlaneaciones"]');
        var TipoPlaneacionSelect = FormConfiguracion.querySelector('select[name="TipoPlaneacion"]');
        var PlaneacionesCantidadInput = FormConfiguracion.querySelector('input[name="PlaneacionesCantidad"]');
        var PlaneacionesAyuda = FormConfiguracion.querySelector('.SgcePlaneacionesHelp');
        var PeriodosModoSelect = FormConfiguracion.querySelector('select[name="PeriodosModo"]');
        var PeriodosPersonalizadosTextarea = FormConfiguracion.querySelector('textarea[name="PeriodosPersonalizados"]');
        var PeriodosPersonalizadosAyuda = FormConfiguracion.querySelector('.SgcePeriodosPersonalizadosHelp');
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
        if (PeriodosModoSelect && PeriodosPersonalizadosTextarea) {
            PeriodosModoSelect.addEventListener('change', ActualizarPeriodosPersonalizados);
            ActualizarPeriodosPersonalizados();
            window.setTimeout(ActualizarPeriodosPersonalizados, 60);
            window.addEventListener('pageshow', ActualizarPeriodosPersonalizados);
        }
        var ActualizarPlaneaciones = function () {
            if (!UsaPlaneacionesSwitch) { return; }
            var Habilitado = UsaPlaneacionesSwitch.checked;
            if (TipoPlaneacionSelect) {
                TipoPlaneacionSelect.disabled = !Habilitado;
                TipoPlaneacionSelect.required = Habilitado;
            }
            if (PlaneacionesCantidadInput) {
                PlaneacionesCantidadInput.disabled = !Habilitado;
                PlaneacionesCantidadInput.required = Habilitado;
                if (!Habilitado) { PlaneacionesCantidadInput.setCustomValidity(''); }
                else {
                    var TipoPlaneacion = TipoPlaneacionSelect ? String(TipoPlaneacionSelect.value || 'CICLO').toUpperCase() : 'CICLO';
                    var PeriodosInput = FormConfiguracion.querySelector('input[name="PeriodosCantidad"]');
                    var CantidadPeriodos = PeriodosInput ? parseInt(PeriodosInput.value || '0', 10) : 0;
                    if (TipoPlaneacion === 'CICLO') {
                        PlaneacionesCantidadInput.placeholder = 'Ej. 1';
                        PlaneacionesCantidadInput.required = false;
                        PlaneacionesCantidadInput.setCustomValidity('');
                    } else if (TipoPlaneacion === 'PERIODO') {
                        PlaneacionesCantidadInput.placeholder = 'Ej. 3';
                        PlaneacionesCantidadInput.required = true;
                    } else {
                        PlaneacionesCantidadInput.placeholder = 'Ej. 1';
                        PlaneacionesCantidadInput.required = true;
                    }
                }
            }
            if (PlaneacionesAyuda) {
                var TipoAyuda = TipoPlaneacionSelect ? String(TipoPlaneacionSelect.value || 'CICLO').toUpperCase() : 'CICLO';
                var Ayudas = {
                    CICLO: 'Se solicitará una planeación por materia durante todo el ciclo escolar.',
                    PERIODO: 'Se solicitará una planeación por cada periodo de evaluación configurado.',
                    UNIDAD: 'Útil cuando cada materia trabaja por unidades, bloques, temas o proyectos.',
                    SEMANA: 'Útil para escuelas que piden planeaciones semanales.'
                };
                if (Habilitado) { PlaneacionesAyuda.textContent = Ayudas[TipoAyuda] || 'Se usa para validar entregas por materia.'; }
                PlaneacionesAyuda.classList.toggle('SgceMuted', !Habilitado);
            }
        };
        if (UsaPlaneacionesSwitch) {
            UsaPlaneacionesSwitch.addEventListener('change', ActualizarPlaneaciones);
            if (TipoPlaneacionSelect) { TipoPlaneacionSelect.addEventListener('change', ActualizarPlaneaciones); }
            var PeriodosCantidadPlaneacion = FormConfiguracion.querySelector('input[name="PeriodosCantidad"]');
            if (PeriodosCantidadPlaneacion) { PeriodosCantidadPlaneacion.addEventListener('input', function () {
                if (TipoPlaneacionSelect && String(TipoPlaneacionSelect.value || '').toUpperCase() === 'PERIODO') { ActualizarPlaneaciones(); }
            }); }
            ActualizarPlaneaciones();
        }


        var UsaProgramasSwitch = FormConfiguracion.querySelector('input[name="UsaProgramas"]');
        var NivelEducativoSelect = FormConfiguracion.querySelector('select[name="NivelEducativo"]');
        var ProgramasTextarea = FormConfiguracion.querySelector('textarea[name="ProgramasIniciales"]');
        var ProgramasAyuda = FormConfiguracion.querySelector('.SgceProgramasHelp');
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
        if (UsaProgramasSwitch && ProgramasTextarea) {
            UsaProgramasSwitch.addEventListener('change', ActualizarProgramasEducativos);
            if (NivelEducativoSelect) { NivelEducativoSelect.addEventListener('change', ActualizarProgramasEducativos); }
            ActualizarProgramasEducativos();
        }


        var MatriculaAutomaticaSwitch = FormConfiguracion.querySelector('input[type="checkbox"][name="MatriculaAutomatica"]');
        var MatriculaPrefijoInput = FormConfiguracion.querySelector('input[name="MatriculaPrefijo"]');
        var MatriculaEjemploInput = document.getElementById('SgceConfigMatriculaEjemplo');
        var MatriculaAyuda = FormConfiguracion.querySelector('.SgceMatriculaHelp');
        var ActualizarMatriculaAutomatica = function () {
            if (!MatriculaPrefijoInput || !MatriculaEjemploInput) { return; }
            var Habilitada = !MatriculaAutomaticaSwitch || MatriculaAutomaticaSwitch.checked === true;
            var Prefijo = String(MatriculaPrefijoInput.value || 'SGCE').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 12) || 'SGCE';
            if (!MatriculaPrefijoInput.disabled) { MatriculaPrefijoInput.value = Prefijo; }
            MatriculaPrefijoInput.disabled = !Habilitada;
            MatriculaPrefijoInput.required = Habilitada;
            MatriculaEjemploInput.disabled = !Habilitada;
            MatriculaEjemploInput.value = Habilitada ? (Prefijo + '-' + new Date().getFullYear() + '-000001') : 'No aplica';
            if (!Habilitada) { MatriculaPrefijoInput.setCustomValidity(''); }
            if (MatriculaAyuda) { MatriculaAyuda.classList.toggle('SgceMuted', !Habilitada); }
        };
        if (MatriculaPrefijoInput && MatriculaEjemploInput) {
            MatriculaPrefijoInput.addEventListener('input', ActualizarMatriculaAutomatica);
            if (MatriculaAutomaticaSwitch) { MatriculaAutomaticaSwitch.addEventListener('change', ActualizarMatriculaAutomatica); }
            ActualizarMatriculaAutomatica();
        }

        FormConfiguracion.addEventListener('submit', function (Evento) {
            ActualizarPeriodosPersonalizados();
            ActualizarProgramasEducativos();
            ActualizarPlaneaciones();
            ActualizarMatriculaAutomatica();
            if (MatriculaAutomaticaSwitch && MatriculaAutomaticaSwitch.checked && MatriculaPrefijoInput && !/^[A-Z0-9]{2,12}$/.test(MatriculaPrefijoInput.value.trim().toUpperCase())) {
                Evento.preventDefault();
                MatriculaPrefijoInput.disabled = false;
                MatriculaPrefijoInput.focus();
                MatriculaPrefijoInput.setCustomValidity('El prefijo de matrícula debe usar solo letras y números, de 2 a 12 caracteres.');
                MatriculaPrefijoInput.reportValidity();
                window.setTimeout(function () { MatriculaPrefijoInput.setCustomValidity(''); }, 1200);
                return;
            }

            if (UsaProgramasSwitch && ProgramasTextarea) {
                var Nivel = NivelEducativoSelect ? String(NivelEducativoSelect.value || '').toUpperCase() : '';
                var RequiereProgramas = NivelesConProgramasObligatorios.indexOf(Nivel) !== -1;
                var Habilitado = UsaProgramasSwitch.checked === true || RequiereProgramas;
                if (Habilitado && !ProgramasTextarea.value.trim()) {
                    Evento.preventDefault();
                    ProgramasTextarea.focus();
                    ProgramasTextarea.setCustomValidity('Captura al menos un programa educativo o desmarca esta opción.');
                    ProgramasTextarea.reportValidity();
                    window.setTimeout(function () { ProgramasTextarea.setCustomValidity(''); }, 1200);
                    return;
                }
            }
        });
    }

});
