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
            }
            if (PlaneacionesAyuda) {
                PlaneacionesAyuda.classList.toggle('SgceMuted', !Habilitado);
            }
        };
        if (UsaPlaneacionesSwitch) {
            UsaPlaneacionesSwitch.addEventListener('change', ActualizarPlaneaciones);
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

        FormConfiguracion.addEventListener('submit', function (Evento) {
            ActualizarProgramasEducativos();
            ActualizarPlaneaciones();
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
