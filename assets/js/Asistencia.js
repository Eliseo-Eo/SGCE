document.addEventListener("DOMContentLoaded", function() {

    // ALERTA SUCCESS AUTO HIDE

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

    // COLORES DINÁMICOS EN SELECT

    const Selects = document.querySelectorAll('.EstadoSelect');

    function AplicarColor(Select){

        Select.classList.remove(
            'border-success',
            'border-danger',
            'border-warning',
            'border-primary'
        );

        switch(Select.value){

            case 'A':

                Select.classList.add('border-success');

            break;

            case 'F':

                Select.classList.add('border-danger');

            break;

            case 'R':

                Select.classList.add('border-warning');

            break;

            case 'J':

                Select.classList.add('border-primary');

            break;

        }

    }

    Selects.forEach(function(Select){

        AplicarColor(Select);

        Select.addEventListener('change', function(){

            AplicarColor(this);

        });

    });

});



    // ============================================================
    // HOMOLOGAR TEXTOS POR DEFECTO EN MAYÚSCULAS
    // ------------------------------------------------------------
    // Aquí dejo en mayúsculas los placeholders y el texto visible de
    // las opciones de los select. No modifico los valores internos de
    // los option para no romper validaciones como Matutino/Vespertino.
    // Tampoco toco passwords ni archivos.
    // ============================================================
    document.querySelectorAll('input:not([type="password"]):not([type="file"]):not([type="hidden"]), textarea').forEach(function(Control){
        if (Control.placeholder) {
            Control.placeholder = Control.placeholder.toUpperCase();
        }
    });

    document.querySelectorAll('select option').forEach(function(Opcion){
        Opcion.textContent = (Opcion.textContent || '').toUpperCase();
    });