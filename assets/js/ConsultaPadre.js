document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.SoloLetrasMayus').forEach(function (Input) {
        Input.addEventListener('input', function () {
            var Pos = Input.selectionStart;
            Input.value = Input.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]/g, '').toUpperCase();
            try { Input.setSelectionRange(Pos, Pos); } catch (Error) {}
        });
    });


    var FechaInicio = document.querySelector('input[name="FechaInicio"]');
    var FechaFin = document.querySelector('input[name="FechaFin"]');
    var BotonesRango = document.querySelectorAll('.ConsultaQuickRanges button[data-range]');

    function FormatoFecha(Fecha) {
        var Y = Fecha.getFullYear();
        var M = String(Fecha.getMonth() + 1).padStart(2, '0');
        var D = String(Fecha.getDate()).padStart(2, '0');
        return Y + '-' + M + '-' + D;
    }

    BotonesRango.forEach(function (Boton) {
        Boton.addEventListener('click', function () {
            if (!FechaInicio || !FechaFin) { return; }
            var Hoy = new Date();
            var Inicio = new Date(Hoy.getFullYear(), Hoy.getMonth(), Hoy.getDate());
            var Fin = new Date(Hoy.getFullYear(), Hoy.getMonth(), Hoy.getDate());
            var Rango = Boton.getAttribute('data-range');
            if (Rango === 'semana') { Inicio.setDate(Fin.getDate() - 6); }
            if (Rango === 'mes') { Inicio = new Date(Fin.getFullYear(), Fin.getMonth(), 1); }
            FechaInicio.value = FormatoFecha(Inicio);
            FechaFin.value = FormatoFecha(Fin);
        });
    });
});
