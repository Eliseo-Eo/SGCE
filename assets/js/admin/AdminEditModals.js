window.SgceAdminEditModals = (function(){
    function DecorarModalesEdicion(Root) {
        (Root || document).querySelectorAll('.modal[id^="EM"], .modal[id^="EG"], .modal[id^="EAl"], .modal[id^="EAsg"]').forEach(function(Modal){
            const Content = Modal.querySelector('.modal-content');
            const Body = Modal.querySelector('.modal-body');
            const Title = Body ? Body.querySelector('h5, h6') : null;
            if(!Content || !Body || !Title || Body.dataset.editDecorated === '1') return;
            let Titulo = (Title.textContent || 'MODIFICAR REGISTRO').trim().toUpperCase();
            let Subtitulo = 'REVISA LOS DATOS ANTES DE GUARDAR';
            if(Modal.id.indexOf('EMat') === 0) Subtitulo = 'ACTUALIZAR MATERIA DEL GRUPO';
            else if(Modal.id.indexOf('EM') === 0) Subtitulo = 'ACTUALIZAR INFORMACIÓN DEL DOCENTE';
            if(Modal.id.indexOf('EG') === 0) Subtitulo = 'ACTUALIZAR INFORMACIÓN DEL GRUPO';
            if(Modal.id.indexOf('EAl') === 0) Subtitulo = 'ACTUALIZAR INFORMACIÓN DEL ALUMNO';
            if(Modal.id.indexOf('EAsg') === 0) Subtitulo = 'ACTUALIZAR ASIGNACIÓN ACADÉMICA';
            Content.classList.add('EditModalContent');
            Body.classList.add('EditModalBody');
            Body.dataset.editDecorated = '1';
            const Header = document.createElement('div');
            Header.className = 'EditModalHeader';
            Header.innerHTML = '<div class="EditIcon"><i class="fa-solid fa-pen-to-square"></i></div>' + '<h4 class="fw-bold mb-1">' + Titulo + '</h4>' + '<p class="mb-0 opacity-75">' + Subtitulo + '</p>';
            const Info = document.createElement('div');
            Info.className = 'EditInfoBox';
            Info.innerHTML = '<i class="fa-solid fa-circle-info me-1"></i> LOS CAMBIOS SE GUARDARÁN AL CONFIRMAR.';
            Title.remove();
            Content.insertBefore(Header, Content.firstChild);
            Body.insertBefore(Info, Body.firstChild);
            const SubmitBtn = Body.querySelector('button[type="submit"], button:not([type])');
            if(SubmitBtn){
                SubmitBtn.className = 'BtnSaveEdit';
                SubmitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> GUARDAR CAMBIOS';
                const BtnRow = document.createElement('div'); BtnRow.className = 'row g-2 mt-2';
                const ColCancel = document.createElement('div'); ColCancel.className = 'col-12 col-sm-5';
                ColCancel.innerHTML = '<button type="button" class="BtnCancelEdit" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> CANCELAR</button>';
                const ColSave = document.createElement('div'); ColSave.className = 'col-12 col-sm-7';
                SubmitBtn.parentNode.insertBefore(BtnRow, SubmitBtn); BtnRow.appendChild(ColCancel); BtnRow.appendChild(ColSave); ColSave.appendChild(SubmitBtn);
            }
        });
    }
    return {DecorarModalesEdicion:DecorarModalesEdicion};
})();
