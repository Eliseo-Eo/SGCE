document.addEventListener('DOMContentLoaded', function(){
    function Mayusculas(valor){ return (valor || '').toString().toLocaleUpperCase('es-MX'); }
    function ActivarMayusculas(Root){
        (Root || document).querySelectorAll('.ConductaMayuscula').forEach(function(Control){
            if (Control.dataset.conductaMayusculaBound === '1') return;
            Control.dataset.conductaMayusculaBound = '1';
            Control.style.textTransform = 'uppercase';
            Control.addEventListener('input', function(){
                const Inicio = Control.selectionStart;
                const Fin = Control.selectionEnd;
                Control.value = Mayusculas(Control.value);
                try { Control.setSelectionRange(Inicio, Fin); } catch (E) {}
            });
            Control.value = Mayusculas(Control.value);
        });
    }

    ActivarMayusculas(document);

    if (window.SgceInicializarSearchableSelects) {
        window.SgceInicializarSearchableSelects(document);
    }

    const RevisionModalEl = document.getElementById('ConductaRevisionModal');
    if (!RevisionModalEl || !window.bootstrap) return;

    const RevisionModal = new bootstrap.Modal(RevisionModalEl);
    const Campos = {
        Id: document.getElementById('ConductaRevisionId'),
        Alumno: document.getElementById('ConductaRevisionAlumno'),
        Fecha: document.getElementById('ConductaRevisionFecha'),
        Grupo: document.getElementById('ConductaRevisionGrupo'),
        Materia: document.getElementById('ConductaRevisionMateria'),
        Reporta: document.getElementById('ConductaRevisionReporta'),
        Severidad: document.getElementById('ConductaRevisionSeveridad'),
        Motivo: document.getElementById('ConductaRevisionMotivo'),
        Estado: document.getElementById('ConductaRevisionEstado'),
        Visible: document.getElementById('ConductaRevisionVisiblePadre'),
        Detalle: document.getElementById('ConductaRevisionDetalle'),
        Accion: document.getElementById('ConductaRevisionAccion')
    };

    function Texto(V){ return (V || '').toString(); }
    function SetText(El, Valor){ if (El) El.textContent = Texto(Valor) || '-'; }
    function ClaseSeveridad(Severidad){
        const S = Texto(Severidad).toUpperCase();
        if (S === 'GRAVE') return 'bg-danger';
        if (S === 'MEDIA') return 'bg-warning text-dark';
        return 'bg-success';
    }

    document.querySelectorAll('.ConductaReviewBtn[data-conducta-revision]').forEach(function(Boton){
        Boton.addEventListener('click', function(){
            let Datos = {};
            try { Datos = JSON.parse(Boton.dataset.conductaRevision || '{}'); } catch (E) { Datos = {}; }
            if (Campos.Id) Campos.Id.value = Texto(Datos.Id || '0');
            SetText(Campos.Alumno, Datos.Alumno || 'Alumno');
            SetText(Campos.Fecha, Datos.Fecha);
            SetText(Campos.Grupo, Datos.Grupo);
            SetText(Campos.Materia, [Datos.Materia, Datos.Origen].filter(Boolean).join(' · '));
            SetText(Campos.Reporta, Datos.Reporta || 'Sistema');
            SetText(Campos.Motivo, Datos.MotivoCorto || 'Sin motivo');
            if (Campos.Severidad) {
                Campos.Severidad.textContent = Datos.SeveridadTexto || Datos.Severidad || 'Severidad';
                Campos.Severidad.className = 'badge rounded-pill mb-2 ' + ClaseSeveridad(Datos.Severidad);
            }
            if (Campos.Estado) Campos.Estado.value = Datos.Estado || 'PENDIENTE';
            if (Campos.Visible) Campos.Visible.checked = parseInt(Datos.VisiblePadre || 0, 10) === 1;
            if (Campos.Detalle) Campos.Detalle.value = Mayusculas(Datos.Detalle || '');
            if (Campos.Accion) Campos.Accion.value = Mayusculas(Datos.AccionTomada || '');
            RevisionModal.show();
        });
    });
});
