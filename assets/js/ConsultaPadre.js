document.addEventListener('DOMContentLoaded', function(){
    function NormalizarNombre(El){
        let Valor = El.value || '';
        Valor = Valor.toUpperCase();
        Valor = Valor.replace(/[^A-ZÁÉÍÓÚÜÑ\s]/g, '');
        Valor = Valor.replace(/\s+/g, ' ');
        El.value = Valor;
    }
    document.querySelectorAll('.SoloMayus').forEach(function(El){ El.addEventListener('input', function(){ El.value = (El.value || '').toUpperCase(); }); });
        document.querySelectorAll('.SoloLetrasMayus').forEach(function(El){
        El.addEventListener('input', function(){ NormalizarNombre(El); });
        El.addEventListener('blur', function(){ NormalizarNombre(El); });
    });
});