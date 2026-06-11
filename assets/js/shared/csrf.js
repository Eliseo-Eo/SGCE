/* SGCE 1.0.140 - Módulo compartido: csrf.js */
document.addEventListener('DOMContentLoaded', function () {
    var Marcador = document.querySelector('[data-sgce-csrf-token]');
    if (!Marcador) { return; }
    var Token = Marcador.getAttribute('data-sgce-csrf-token') || '';
    if (!Token) { return; }
    document.querySelectorAll('form[method]').forEach(function (Formulario) {
        var Metodo = (Formulario.getAttribute('method') || '').toLowerCase();
        if (Metodo !== 'post' || Formulario.querySelector('input[name="CsrfToken"]')) { return; }
        var Input = document.createElement('input');
        Input.type = 'hidden';
        Input.name = 'CsrfToken';
        Input.value = Token;
        Formulario.appendChild(Input);
    });
});
