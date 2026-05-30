document.addEventListener('DOMContentLoaded', function () {
    var Estados = {
        SUBIDA: {
            Clase: 'EstadoSubida',
            Icono: 'fa-clock',
            Titulo: 'Planeación en revisión',
            Texto: 'La planeación queda marcada como pendiente de análisis o seguimiento.',
            Placeholder: 'Puedes dejar una nota opcional de seguimiento para el docente.'
        },
        APROBADA: {
            Clase: 'EstadoAprobada',
            Icono: 'fa-circle-check',
            Titulo: 'Planeación aprobada',
            Texto: 'La entrega cumple con la revisión y quedará registrada como aprobada.',
            Placeholder: 'Puedes escribir una felicitación, comentario o dejar la nota vacía.'
        },
        DEVUELTA: {
            Clase: 'EstadoDevuelta',
            Icono: 'fa-triangle-exclamation',
            Titulo: 'Planeación devuelta para corrección',
            Texto: 'La entrega será regresada al docente. La nota debe explicar qué corregir.',
            Placeholder: 'Indica con claridad qué fecha, contenido, formato o evidencia debe corregirse.'
        }
    };

    document.querySelectorAll('.PlaneacionReviewModal').forEach(function (Modal) {
        var Select = Modal.querySelector('.PlaneacionReviewSelect');
        var Icon = Modal.querySelector('.PlaneacionReviewPreviewIcon i');
        var Titulo = Modal.querySelector('.PlaneacionReviewPreviewTitle');
        var Texto = Modal.querySelector('.PlaneacionReviewPreviewText');
        var Nota = Modal.querySelector('.PlaneacionReviewNote');
        if (!Select || !Icon || !Titulo || !Texto || !Nota) { return; }

        var AplicarEstado = function () {
            var Actual = Estados[Select.value] || Estados.SUBIDA;
            Modal.classList.remove('EstadoSubida', 'EstadoAprobada', 'EstadoDevuelta', 'EstadoPendiente');
            Modal.classList.add(Actual.Clase);
            Icon.className = 'fa-solid ' + Actual.Icono;
            Titulo.textContent = Actual.Titulo;
            Texto.textContent = Actual.Texto;
            Nota.placeholder = Actual.Placeholder;
            Nota.required = Select.value === 'DEVUELTA';
        };
        Select.addEventListener('change', AplicarEstado);
        AplicarEstado();
    });
});
