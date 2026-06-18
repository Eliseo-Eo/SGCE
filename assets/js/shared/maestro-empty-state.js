/* SGCE 1.0.185 - Módulo compartido: maestro-empty-state.js */
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('[data-maestro-empty-close="true"]').forEach(function(Boton){
        if (Boton.dataset.sgceMaestroCloseReady === '1') { return; }
        Boton.dataset.sgceMaestroCloseReady = '1';
        Boton.addEventListener('click', function(Evento){
            Evento.preventDefault();
            var Aviso = Boton.closest('.MaestroEmptyState');
            if (Aviso) {
                Aviso.classList.add('MaestroEmptyStateOculto');
                window.setTimeout(function(){ Aviso.remove(); }, 260);
            }
        });
    });
});
