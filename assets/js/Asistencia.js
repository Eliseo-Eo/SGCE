document.addEventListener("DOMContentLoaded", function() {
    const Alertas = document.querySelectorAll('.alert-success');

    Alertas.forEach(function(Alerta){
        setTimeout(function(){
            Alerta.style.transition = "all .5s ease";
            Alerta.style.opacity = "0";
            Alerta.style.transform = "translateY(-10px)";
            setTimeout(function(){
                Alerta.remove();
            },500);
        },3000);
    });

    const Selects = document.querySelectorAll('.EstadoSelect');
    const Contadores = {
        A: document.getElementById('ContadorAsistencia'),
        F: document.getElementById('ContadorFalta'),
        R: document.getElementById('ContadorRetardo'),
        J: document.getElementById('ContadorJustificante')
    };

    function AplicarColor(Select){
        Select.classList.remove(
            'border-success',
            'border-danger',
            'border-warning',
            'border-primary',
            'SgceEstadoA',
            'SgceEstadoF',
            'SgceEstadoR',
            'SgceEstadoJ'
        );

        switch(Select.value){
            case 'A':
                Select.classList.add('border-success', 'SgceEstadoA');
            break;
            case 'F':
                Select.classList.add('border-danger', 'SgceEstadoF');
            break;
            case 'R':
                Select.classList.add('border-warning', 'SgceEstadoR');
            break;
            case 'J':
                Select.classList.add('border-primary', 'SgceEstadoJ');
            break;
        }
    }

    function ActualizarContadores(){
        const Totales = { A: 0, F: 0, R: 0, J: 0 };

        Selects.forEach(function(Select){
            if (Object.prototype.hasOwnProperty.call(Totales, Select.value)) {
                Totales[Select.value] += 1;
            }
        });

        Object.keys(Totales).forEach(function(Clave){
            if (Contadores[Clave]) {
                Contadores[Clave].textContent = Totales[Clave];
            }
        });
    }

    Selects.forEach(function(Select){
        AplicarColor(Select);
        Select.addEventListener('change', function(){
            AplicarColor(this);
            ActualizarContadores();
        });
    });

    ActualizarContadores();

    const ModalEl = document.getElementById('ModalConductaPaseLista');
    const Modal = ModalEl && window.bootstrap ? new bootstrap.Modal(ModalEl) : null;
    let FilaConductaActual = null;

    const ModalCampos = {
        Titulo: document.getElementById('ModalConductaPaseListaTitulo'),
        Alumno: document.getElementById('ModalConductaAlumnoNombre'),
        Contexto: document.getElementById('ModalConductaClaseContexto'),
        Aviso: document.getElementById('ModalConductaAviso'),
        Tipo: document.getElementById('ModalConductaTipo'),
        Severidad: document.getElementById('ModalConductaSeveridad'),
        Categoria: document.getElementById('ModalConductaCategoria'),
        Motivo: document.getElementById('ModalConductaMotivo'),
        Detalle: document.getElementById('ModalConductaDetalle'),
        Accion: document.getElementById('ModalConductaAccion'),
        VisiblePadre: document.getElementById('ModalConductaVisiblePadre'),
        Guardar: document.getElementById('ModalConductaGuardar'),
        Quitar: document.getElementById('ModalConductaQuitar'),
        Cancelar: document.getElementById('ModalConductaCancelar')
    };

    function CampoConducta(Fila, Clase){
        return Fila ? Fila.querySelector('.' + Clase) : null;
    }

    function ValorCampo(Fila, Clase){
        const Campo = CampoConducta(Fila, Clase);
        return Campo ? Campo.value : '';
    }

    function AsignarValor(Fila, Clase, Valor){
        const Campo = CampoConducta(Fila, Clase);
        if (Campo) { Campo.value = Valor || ''; }
    }

    function SgceConductaMayusculas(Valor){
        return (Valor || '').toString().toLocaleUpperCase('es-MX').trim();
    }

    function SgceConductaMayusculasLibre(Valor){
        return (Valor || '').toString().toLocaleUpperCase('es-MX');
    }

    function FilaTieneReporte(Fila){
        return ValorCampo(Fila, 'ConductaRegistrar') === '1';
    }

    function FilaBloqueada(Fila){
        const Boton = Fila ? Fila.querySelector('.SgceConductaModalBtn, .ConductaModalBtn') : null;
        return Boton && Boton.dataset.conductaBloqueada === '1';
    }

    function ActualizarFilaConducta(Fila){
        if (!Fila) { return; }
        const Boton = Fila.querySelector('.SgceConductaModalBtn, .ConductaModalBtn');
        const Icono = Fila.querySelector('.SgceConductaBtnIcon');
        const Texto = Fila.querySelector('.SgceConductaBtnText');
        const Estado = Fila.querySelector('.SgceConductaFilaEstado');
        const TieneReporte = FilaTieneReporte(Fila);
        const Bloqueada = FilaBloqueada(Fila);
        const EstadoOriginal = Boton ? (Boton.dataset.conductaEstado || 'PENDIENTE') : 'PENDIENTE';

        if (Boton) {
            Boton.classList.toggle('is-registered', TieneReporte || Bloqueada);
        }
        if (Icono) { Icono.textContent = (TieneReporte || Bloqueada) ? '✅' : '📝'; }
        if (Texto) {
            if (Bloqueada) {
                Texto.textContent = 'Ver reporte';
            } else {
                Texto.textContent = TieneReporte ? 'Editar reporte' : 'Registrar reporte';
            }
        }
        if (Estado) {
            Estado.classList.remove('text-muted', 'text-success', 'fw-bold');
            if (Bloqueada) {
                Estado.classList.add('text-success', 'fw-bold');
                Estado.textContent = 'Conducta: ' + TextoEstadoConducta(EstadoOriginal);
            } else if (TieneReporte) {
                Estado.classList.add('text-success', 'fw-bold');
                Estado.textContent = ValorCampo(Fila, 'ConductaVisiblePadre') === '1' ? 'Conducta: Reporte registrado · Visible al validar' : 'Conducta: Reporte registrado';
            } else {
                Estado.classList.add('text-muted');
                Estado.textContent = 'Conducta normal por defecto.';
            }
        }
    }

    function TextoEstadoConducta(Estado){
        const Mapa = {
            PENDIENTE: 'Pendiente',
            VALIDADO: 'Validado',
            EN_SEGUIMIENTO: 'En seguimiento',
            CERRADO: 'Cerrado',
            CANCELADO: 'Cancelado'
        };
        return Mapa[Estado] || 'Pendiente';
    }

    function AplicarTemaModalConducta(SoloLectura, TieneReporte){
        const ModalContenido = ModalEl ? ModalEl.querySelector('.SgceConductaModalContent') : null;
        if (!ModalContenido) { return; }
        ModalContenido.classList.remove('is-create', 'is-edit', 'is-readonly');
        if (SoloLectura) {
            ModalContenido.classList.add('is-readonly', 'is-edit');
        } else if (TieneReporte) {
            ModalContenido.classList.add('is-edit');
        } else {
            ModalContenido.classList.add('is-create');
        }
    }



    function ActualizarEstadoVisiblePadresModal(){
        const Check = ModalCampos.VisiblePadre;
        if (!Check) { return; }
        const Caja = Check.closest('.SgceConductaPadreBox');
        const Estado = document.getElementById('ModalConductaVisiblePadreEstado');
        const Icono = Caja ? Caja.querySelector('.SgceConductaPadreIcon i') : null;
        const Visible = !!Check.checked;
        if (Caja) {
            Caja.classList.toggle('is-visible', Visible);
            Caja.classList.toggle('is-hidden', !Visible);
            Caja.setAttribute('aria-label', Visible ? 'Visible para padres al validarse' : 'No visible para padres');
        }
        if (Estado) { Estado.textContent = Visible ? 'VISIBLE PARA PADRES' : 'NO VISIBLE PARA PADRES'; }
        if (Icono) {
            Icono.classList.toggle('fa-eye', Visible);
            Icono.classList.toggle('fa-eye-slash', !Visible);
        }
    }

    function ConfigurarModalLectura(SoloLectura, TieneReporte){
        AplicarTemaModalConducta(SoloLectura, TieneReporte);
        ['Tipo', 'Severidad', 'Categoria', 'Motivo', 'Detalle', 'Accion', 'VisiblePadre'].forEach(function(Clave){
            if (ModalCampos[Clave]) { ModalCampos[Clave].disabled = SoloLectura; }
        });
        if (ModalCampos.Guardar) { ModalCampos.Guardar.hidden = SoloLectura; }
        if (ModalCampos.Cancelar) {
            const TextoCancelar = ModalCampos.Cancelar.querySelector('span') || ModalCampos.Cancelar;
            TextoCancelar.textContent = SoloLectura ? 'Cerrar' : 'Cancelar';
        }
        if (ModalCampos.Aviso) {
            const AvisoTexto = ModalCampos.Aviso.querySelector('span') || ModalCampos.Aviso;
            AvisoTexto.textContent = SoloLectura
                ? 'Este reporte ya fue revisado por administración. Se muestra en modo lectura y no modifica el pase de lista.'
                : 'La conducta normal no se captura. Usa este formulario solo cuando exista una incidencia o reconocimiento.';
        }
    }

    function AbrirModalConducta(Boton){
        if (!Modal || !Boton) { console.warn('SGCE: Modal de conducta no disponible.'); return; }
        FilaConductaActual = Boton.closest('.SgceConductaAlumnoRow');
        if (!FilaConductaActual) { return; }

        const SoloLectura = FilaBloqueada(FilaConductaActual);
        const TieneReporteFila = FilaTieneReporte(FilaConductaActual);
        ConfigurarModalLectura(SoloLectura, TieneReporteFila);
        if (ModalCampos.Quitar) { ModalCampos.Quitar.hidden = SoloLectura || !TieneReporteFila; }

        if (ModalCampos.Titulo) {
            ModalCampos.Titulo.textContent = SoloLectura ? 'Ver reporte de conducta' : (TieneReporteFila ? 'Editar reporte de conducta' : 'Registrar reporte de conducta');
        }
        if (ModalCampos.Guardar) {
            const TextoGuardar = ModalCampos.Guardar.querySelector('span') || ModalCampos.Guardar;
            TextoGuardar.textContent = TieneReporteFila ? 'Actualizar en lista' : 'Guardar en lista';
        }
        if (ModalCampos.Alumno) { ModalCampos.Alumno.textContent = Boton.dataset.alumnoNombre || 'Alumno seleccionado'; }
        if (ModalCampos.Contexto) { ModalCampos.Contexto.textContent = Boton.dataset.claseContexto || ''; }

        if (ModalCampos.Tipo) { ModalCampos.Tipo.value = ValorCampo(FilaConductaActual, 'ConductaTipo') || 'REPORTE'; }
        if (ModalCampos.Severidad) { ModalCampos.Severidad.value = ValorCampo(FilaConductaActual, 'ConductaSeveridad') || 'LEVE'; }
        if (ModalCampos.Categoria) { ModalCampos.Categoria.value = ValorCampo(FilaConductaActual, 'ConductaCategoria') || ''; }
        if (ModalCampos.Motivo) {
            ModalCampos.Motivo.value = ValorCampo(FilaConductaActual, 'ConductaMotivo') || '';
            ModalCampos.Motivo.classList.remove('is-invalid');
        }
        if (ModalCampos.Detalle) { ModalCampos.Detalle.value = ValorCampo(FilaConductaActual, 'ConductaDetalle') || ''; }
        if (ModalCampos.Accion) { ModalCampos.Accion.value = ValorCampo(FilaConductaActual, 'ConductaAccion') || ''; }
        if (ModalCampos.VisiblePadre) { ModalCampos.VisiblePadre.checked = ValorCampo(FilaConductaActual, 'ConductaVisiblePadre') === '1'; }
        ActualizarEstadoVisiblePadresModal();

        Modal.show();
        setTimeout(function(){ if (!SoloLectura && ModalCampos.Motivo) { ModalCampos.Motivo.focus(); } }, 180);
    }

    function GuardarModalConducta(){
        if (!FilaConductaActual) { return; }
        const Motivo = ModalCampos.Motivo ? SgceConductaMayusculas(ModalCampos.Motivo.value) : '';
        if (Motivo === '') {
            if (ModalCampos.Motivo) {
                ModalCampos.Motivo.classList.add('is-invalid');
                ModalCampos.Motivo.focus();
            }
            return;
        }

        AsignarValor(FilaConductaActual, 'ConductaRegistrar', '1');
        AsignarValor(FilaConductaActual, 'ConductaTipo', ModalCampos.Tipo ? ModalCampos.Tipo.value : 'REPORTE');
        AsignarValor(FilaConductaActual, 'ConductaSeveridad', ModalCampos.Severidad ? ModalCampos.Severidad.value : 'LEVE');
        AsignarValor(FilaConductaActual, 'ConductaCategoria', ModalCampos.Categoria ? SgceConductaMayusculas(ModalCampos.Categoria.value) : '');
        AsignarValor(FilaConductaActual, 'ConductaMotivo', Motivo);
        AsignarValor(FilaConductaActual, 'ConductaDetalle', ModalCampos.Detalle ? SgceConductaMayusculas(ModalCampos.Detalle.value) : '');
        AsignarValor(FilaConductaActual, 'ConductaAccion', ModalCampos.Accion ? SgceConductaMayusculas(ModalCampos.Accion.value) : '');
        AsignarValor(FilaConductaActual, 'ConductaVisiblePadre', ModalCampos.VisiblePadre && ModalCampos.VisiblePadre.checked ? '1' : '0');
        ActualizarFilaConducta(FilaConductaActual);
        if (Modal) { Modal.hide(); }
    }

    function QuitarModalConducta(){
        if (!FilaConductaActual || FilaBloqueada(FilaConductaActual)) { return; }
        AsignarValor(FilaConductaActual, 'ConductaRegistrar', '0');
        AsignarValor(FilaConductaActual, 'ConductaTipo', 'REPORTE');
        AsignarValor(FilaConductaActual, 'ConductaSeveridad', 'LEVE');
        AsignarValor(FilaConductaActual, 'ConductaCategoria', '');
        AsignarValor(FilaConductaActual, 'ConductaMotivo', '');
        AsignarValor(FilaConductaActual, 'ConductaDetalle', '');
        AsignarValor(FilaConductaActual, 'ConductaAccion', '');
        AsignarValor(FilaConductaActual, 'ConductaVisiblePadre', '0');
        if (ModalCampos.VisiblePadre) { ModalCampos.VisiblePadre.checked = false; }
        ActualizarEstadoVisiblePadresModal();
        ActualizarFilaConducta(FilaConductaActual);
        if (Modal) { Modal.hide(); }
    }

    document.querySelectorAll('.SgceConductaModalBtn, .ConductaModalBtn').forEach(function(Boton){
        Boton.addEventListener('click', function(){ AbrirModalConducta(Boton); });
        const Fila = Boton.closest('.SgceConductaAlumnoRow');
        ActualizarFilaConducta(Fila);
    });

    if (ModalCampos.Guardar) { ModalCampos.Guardar.addEventListener('click', GuardarModalConducta); }
    if (ModalCampos.Quitar) { ModalCampos.Quitar.addEventListener('click', QuitarModalConducta); }
    if (ModalCampos.VisiblePadre) { ModalCampos.VisiblePadre.addEventListener('change', ActualizarEstadoVisiblePadresModal); }

    if (ModalCampos.Motivo) {
        ModalCampos.Motivo.addEventListener('input', function(){
            if (ModalCampos.Motivo.value.trim() !== '') { ModalCampos.Motivo.classList.remove('is-invalid'); }
        });
    }

    document.querySelectorAll('.SgceConductaUppercase').forEach(function(Control){
        Control.addEventListener('input', function(){
            const Inicio = Control.selectionStart;
            const Fin = Control.selectionEnd;
            Control.value = SgceConductaMayusculasLibre(Control.value);
            try { Control.setSelectionRange(Inicio, Fin); } catch (E) {}
        });
        Control.value = SgceConductaMayusculasLibre(Control.value);
    });

    const FormPaseLista = document.getElementById('FormPaseLista');
    if (FormPaseLista) {
        FormPaseLista.addEventListener('submit', function(Evento){
            const FilaIncompleta = Array.from(document.querySelectorAll('.SgceConductaAlumnoRow')).find(function(Fila){
                return FilaTieneReporte(Fila) && ValorCampo(Fila, 'ConductaMotivo').trim() === '';
            });
            if (FilaIncompleta) {
                Evento.preventDefault();
                const Boton = FilaIncompleta.querySelector('.SgceConductaModalBtn, .ConductaModalBtn');
                AbrirModalConducta(Boton);
                if (ModalCampos.Motivo) { ModalCampos.Motivo.classList.add('is-invalid'); }
            }
        });
    }

    document.querySelectorAll('input:not([type="password"]):not([type="file"]):not([type="hidden"]), textarea').forEach(function(Control){
        if (Control.placeholder) {
            Control.placeholder = Control.placeholder.toUpperCase();
        }
    });

    document.querySelectorAll('select option').forEach(function(Opcion){
        Opcion.textContent = (Opcion.textContent || '').toUpperCase();
    });
});
