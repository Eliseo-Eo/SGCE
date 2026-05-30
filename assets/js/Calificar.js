document.addEventListener("DOMContentLoaded", function() {

    const Alertas = document.querySelectorAll('.alert-success');

    Alertas.forEach(function(Alerta) {

        setTimeout(function() {

            Alerta.style.transition = "all .5s ease";
            Alerta.style.opacity = "0";
            Alerta.style.transform = "translateY(-10px)";

            setTimeout(function() {
                Alerta.remove();
            }, 500);

        }, 3000);

    });

    const Inputs = document.querySelectorAll('.InputNota');

    Inputs.forEach(function(Input) {

        Input.addEventListener('input', function() {

            let Valor = parseFloat(this.value);

            this.classList.remove(
                'border-success',
                'border-warning',
                'border-danger'
            );

            if (this.value === '') {
                return;
            }

            if (Valor >= 8) {

                this.classList.add('border-success');

            } else if (Valor >= 6) {

                this.classList.add('border-warning');

            } else {

                this.classList.add('border-danger');

            }

        });

    });

    document.getElementById('FormCalificaciones')
    .addEventListener('submit', function(E) {

        const Inputs = document.querySelectorAll('.InputNota');

        const Alerta = document.getElementById('JsAlert');

        let HuboCambios = false;

        let TieneMenoresDeCinco = false;

        Inputs.forEach(function(Input) {

            const ValorOriginal = Input.getAttribute('data-original');

            const ValorActual = Input.value;

            if (ValorActual !== ValorOriginal) {

                HuboCambios = true;
            }

            if (
                ValorActual !== '' &&
                parseFloat(ValorActual) < 5
            ) {

                TieneMenoresDeCinco = true;
            }

        });

        if (!HuboCambios) {

            E.preventDefault();

            Alerta.innerHTML = `
                <i class="fa-solid fa-circle-exclamation me-2"></i>
                <strong>Sin Cambios:</strong>
                No Has Modificado Ninguna Calificación.
            `;

            Alerta.classList.remove('d-none');

            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });

            return;
        }

        if (TieneMenoresDeCinco) {

            E.preventDefault();

            Alerta.innerHTML = `
                <i class="fa-solid fa-circle-exclamation me-2"></i>
                <strong>Calificación Inválida:</strong>
                La calificación mínima permitida es 5. Usa valores de 5 a 10 o deja el campo vacío para borrar la calificación.
            `;

            Alerta.classList.remove('d-none');

            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });

            return;
        }

    });

});
    document.querySelectorAll('input:not([type="password"]):not([type="file"]):not([type="hidden"]), textarea').forEach(function(Control){
        if (Control.placeholder) {
            Control.placeholder = Control.placeholder.toUpperCase();
        }
    });

    document.querySelectorAll('select option').forEach(function(Opcion){
        Opcion.textContent = (Opcion.textContent || '').toUpperCase();
    });


document.addEventListener('DOMContentLoaded', function () {
    var SelectorPeriodo = document.getElementById('PeriodoIdSelect');
    if (!SelectorPeriodo) { return; }
    SelectorPeriodo.addEventListener('change', function () {
        var AsignacionId = SelectorPeriodo.getAttribute('data-asignacion-id') || '';
        window.location.href = 'Calificar.php?AsignacionId=' + encodeURIComponent(AsignacionId) + '&PeriodoId=' + encodeURIComponent(SelectorPeriodo.value);
    });
});
