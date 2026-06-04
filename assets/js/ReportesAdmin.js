document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.SgceReportForm').forEach(function (Form) {
        Form.addEventListener('submit', function (Evento) {
            var Inicio = Form.querySelector('input[name="FechaInicio"]');
            var Fin = Form.querySelector('input[name="FechaFin"]');
            if (Inicio && Fin && Inicio.value && Fin.value && Inicio.value > Fin.value) {
                Evento.preventDefault();
                alert('La fecha de inicio no puede ser mayor que la fecha fin.');
                Inicio.focus();
            }
        });
    });
});
