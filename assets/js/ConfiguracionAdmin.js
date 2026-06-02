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
});
