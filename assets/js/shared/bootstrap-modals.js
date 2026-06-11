/* SGCE 1.0.140 - Módulo compartido: bootstrap-modals.js */
document.addEventListener('show.bs.modal', function(Evento){
    var Modal = Evento.target;
    if (Modal && Modal.classList && Modal.classList.contains('modal') && Modal.parentElement !== document.body) {
        document.body.appendChild(Modal);
    }
}, true);
