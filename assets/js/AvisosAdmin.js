document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.AlertAuto').forEach(function(Alerta) {
        setTimeout(function() {
            if (!Alerta) { return; }
            Alerta.style.transition = 'opacity .4s ease, transform .4s ease';
            Alerta.style.opacity = '0';
            Alerta.style.transform = 'translateY(-10px)';
            setTimeout(function() { Alerta.remove(); }, 450);
        }, 4500);
    });
});

(function(){
    function ForceImportant(Element, Property, Value){
        if(Element){ Element.style.setProperty(Property, Value, 'important'); }
    }
    function CenterAvisoModal(Modal){
        if(!Modal || !Modal.classList.contains('ModalAvisoEstado')){ return; }
        if(Modal.parentElement !== document.body){ document.body.appendChild(Modal); }
        var Dialog = Modal.querySelector('.modal-dialog');
        if(Dialog){
            ForceImportant(Dialog, 'position', 'fixed');
            ForceImportant(Dialog, 'top', '50%');
            ForceImportant(Dialog, 'left', '50%');
            ForceImportant(Dialog, 'transform', 'translate(-50%, -50%)');
            ForceImportant(Dialog, 'margin', '0');
            ForceImportant(Dialog, 'width', 'min(94vw, 520px)');
            ForceImportant(Dialog, 'max-width', 'min(94vw, 520px)');
        }
    }
    document.addEventListener('show.bs.modal', function(Event){ CenterAvisoModal(Event.target); setTimeout(function(){ CenterAvisoModal(Event.target); }, 20); }, true);
    document.addEventListener('shown.bs.modal', function(Event){ CenterAvisoModal(Event.target); setTimeout(function(){ CenterAvisoModal(Event.target); }, 90); }, true);
})();