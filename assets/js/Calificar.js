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

    function PintarCalificacion(Input) {
        const Valor = parseFloat(Input.value);

        Input.classList.remove(
            'border-success',
            'border-warning',
            'border-danger'
        );

        if (Input.value === '' || Number.isNaN(Valor)) {
            return;
        }

        if (Valor >= 8) {
            Input.classList.add('border-success');
        } else if (Valor >= 6) {
            Input.classList.add('border-warning');
        } else {
            Input.classList.add('border-danger');
        }
    }

    Inputs.forEach(function(Input) {
        PintarCalificacion(Input);

        Input.addEventListener('input', function() {
            PintarCalificacion(this);
        });
    });

    const FormCalificaciones = document.getElementById('FormCalificaciones');

    if (FormCalificaciones) {
        FormCalificaciones.addEventListener('submit', function(Evento) {
            const Alerta = document.getElementById('JsAlert');
            let TieneCalificacionInvalida = false;

            Inputs.forEach(function(Input) {
                const ValorTexto = String(Input.value || '').trim();

                if (ValorTexto === '') {
                    return;
                }

                const ValorNumero = parseFloat(ValorTexto);

                if (Number.isNaN(ValorNumero) || ValorNumero < 5 || ValorNumero > 10) {
                    TieneCalificacionInvalida = true;
                }
            });

            if (TieneCalificacionInvalida) {
                Evento.preventDefault();

                if (Alerta) {
                    Alerta.innerHTML = `
                        <i class="fa-solid fa-circle-exclamation me-2"></i>
                        <strong>Calificación inválida:</strong>
                        Usa valores de 5 a 10 o deja el campo vacío para borrar la calificación.
                    `;

                    Alerta.classList.remove('d-none');
                }

                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        });
    }

    document.querySelectorAll('input:not([type="password"]):not([type="file"]):not([type="hidden"]), textarea').forEach(function(Control) {
        if (Control.placeholder) {
            Control.placeholder = Control.placeholder.toUpperCase();
        }
    });

    document.querySelectorAll('select option').forEach(function(Opcion) {
        Opcion.textContent = (Opcion.textContent || '').toUpperCase();
    });

    const SelectorPeriodo = document.getElementById('PeriodoIdSelect');

    if (SelectorPeriodo) {
        SelectorPeriodo.addEventListener('change', function() {
            const AsignacionId = SelectorPeriodo.getAttribute('data-asignacion-id') || '';
            window.location.href = 'Calificar.php?AsignacionId=' + encodeURIComponent(AsignacionId) + '&PeriodoId=' + encodeURIComponent(SelectorPeriodo.value);
        });
    }

});
