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

        const Totales = {
            A: 0,
            F: 0,
            R: 0,
            J: 0
        };

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
    document.querySelectorAll('input:not([type="password"]):not([type="file"]):not([type="hidden"]), textarea').forEach(function(Control){
        if (Control.placeholder) {
            Control.placeholder = Control.placeholder.toUpperCase();
        }
    });

    document.querySelectorAll('select option').forEach(function(Opcion){
        Opcion.textContent = (Opcion.textContent || '').toUpperCase();
    });

});
