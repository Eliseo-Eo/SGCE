(function(){
    function PrepararModal(Modal){
        if(!Modal || !Modal.classList || !Modal.classList.contains('modal')){return;}
        if(Modal.parentElement !== document.body){
            document.body.appendChild(Modal);
        }
        var Dialog = Modal.querySelector('.modal-dialog');
        if(Dialog && !Dialog.classList.contains('modal-dialog-centered')){
            Dialog.classList.add('modal-dialog-centered');
        }
    }
    document.addEventListener('show.bs.modal', function(Evento){
        PrepararModal(Evento.target);
    }, true);
})();

document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.modal[id^="EM"], .modal[id^="EG"], .modal[id^="EAl"], .modal[id^="EAsg"]').forEach(function(Modal){
        const Content = Modal.querySelector('.modal-content');
        const Body = Modal.querySelector('.modal-body');
        const Form = Modal.querySelector('form');
        const Title = Body ? Body.querySelector('h5, h6') : null;

        if(!Content || !Body || !Title || Body.dataset.editDecorated === '1') return;

        let Titulo = (Title.textContent || 'MODIFICAR REGISTRO').trim().toUpperCase();
        let Subtitulo = 'REVISA LOS DATOS ANTES DE GUARDAR';

        if(Modal.id.indexOf('EM') === 0) Subtitulo = 'ACTUALIZAR INFORMACIÓN DEL DOCENTE';
        if(Modal.id.indexOf('EG') === 0) Subtitulo = 'ACTUALIZAR INFORMACIÓN DEL GRUPO';
        if(Modal.id.indexOf('EAl') === 0) Subtitulo = 'ACTUALIZAR INFORMACIÓN DEL ALUMNO';
        if(Modal.id.indexOf('EAsg') === 0) Subtitulo = 'ACTUALIZAR ASIGNACIÓN ACADÉMICA';

        Content.classList.add('EditModalContent');
        Body.classList.add('EditModalBody');
        Body.dataset.editDecorated = '1';

        const Header = document.createElement('div');
        Header.className = 'EditModalHeader';
        Header.innerHTML = '<div class="EditIcon"><i class="fa-solid fa-pen-to-square"></i></div>' +
                           '<h4 class="fw-bold mb-1">' + Titulo + '</h4>' +
                           '<p class="mb-0 opacity-75">' + Subtitulo + '</p>';

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

            const BtnRow = document.createElement('div');
            BtnRow.className = 'row g-2 mt-2';

            const ColCancel = document.createElement('div');
            ColCancel.className = 'col-12 col-sm-5';
            ColCancel.innerHTML = '<button type="button" class="BtnCancelEdit" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> CANCELAR</button>';

            const ColSave = document.createElement('div');
            ColSave.className = 'col-12 col-sm-7';

            SubmitBtn.parentNode.insertBefore(BtnRow, SubmitBtn);
            BtnRow.appendChild(ColCancel);
            BtnRow.appendChild(ColSave);
            ColSave.appendChild(SubmitBtn);
        }
    });
    function NormalizarInputNombre(El) {
        let Val = El.value || '';
        Val = Val.toUpperCase();
        Val = Val.replace(/[^A-ZÁÉÍÓÚÜÑ\s]/g, '');
        Val = Val.replace(/\s+/g, ' ');
        El.value = Val;
    }

    document.querySelectorAll('.SoloLetrasMayus').forEach(function(El){
        El.addEventListener('input', function(){ NormalizarInputNombre(El); });
        El.addEventListener('blur', function(){ NormalizarInputNombre(El); });
    });
    function SetupSearchPagination(InputId, TableId, PagerId, RowsPerPage) {

        const Input = document.getElementById(InputId);
        const Table = document.getElementById(TableId);
        const Pager = document.getElementById(PagerId);

        if(!Table || !Table.tBodies.length) return;

        let CurrentPage = 1;

        function GetRows() {
            return Array.from(Table.tBodies[0].rows);
        }

        function RenderPager(TotalPages) {
            if(!Pager) return;
            Pager.innerHTML = '';
            if (TotalPages <= 1) return;

            const CreateBtn = function(Label, Page, Disabled, Active) {
                const Btn = document.createElement('button');
                Btn.type = 'button';
                Btn.className = 'btn btn-sm mx-1 ' + (Active ? 'btn-guinda' : 'btn-outline-secondary');
                Btn.textContent = Label;
                Btn.disabled = !!Disabled;
                Btn.addEventListener('click', function(){
                    CurrentPage = Page;
                    Apply();
                });
                return Btn;
            };

            Pager.appendChild(CreateBtn('«', 1, CurrentPage === 1, false));
            Pager.appendChild(CreateBtn('‹', Math.max(1, CurrentPage - 1), CurrentPage === 1, false));

            let Start = Math.max(1, CurrentPage - 2);
            let End = Math.min(TotalPages, Start + 4);
            Start = Math.max(1, End - 4);

            for (let P = Start; P <= End; P++) {
                Pager.appendChild(CreateBtn(String(P), P, false, P === CurrentPage));
            }

            Pager.appendChild(CreateBtn('›', Math.min(TotalPages, CurrentPage + 1), CurrentPage === TotalPages, false));
            Pager.appendChild(CreateBtn('»', TotalPages, CurrentPage === TotalPages, false));
        }

        function Apply() {
            const Filter = (Input ? (Input.value || '').toLowerCase() : '');
            const Rows = GetRows();

            let Matched = [];

            Rows.forEach(function(Row){
                const Cells = Array.from(Row.getElementsByClassName('searchable'));
                const Text = Cells.map(C => (C.innerText || '').toLowerCase()).join(' ');
                const Match = (Filter === '') ? true : (Text.indexOf(Filter) > -1);
                Row.dataset.match = Match ? '1' : '0';
                if (Match) Matched.push(Row);
            });

            const TotalPages = Math.max(1, Math.ceil(Matched.length / RowsPerPage));
            if (CurrentPage > TotalPages) CurrentPage = TotalPages;

            Rows.forEach(function(Row){ Row.style.display = 'none'; });

            const StartIndex = (CurrentPage - 1) * RowsPerPage;
            const EndIndex = StartIndex + RowsPerPage;

            Matched.slice(StartIndex, EndIndex).forEach(function(Row){
                Row.style.display = '';
            });

            RenderPager(TotalPages);
        }

        if (Input) {
            Input.addEventListener('keyup', function(){
                CurrentPage = 1;
                Apply();
            });
        }

        Apply();
    }

    
    SetupSearchPagination('SearchMaestros', 'TableMaestros', 'PagerMaestros', 7);
    SetupSearchPagination('SearchGrupos',   'TableGrupos',   'PagerGrupos',   7);
    SetupSearchPagination('SearchAlumnos',  'TableAlumnos',  'PagerAlumnos',  7);
    SetupSearchPagination('SearchExpedientes','TableExpedientes','PagerExpedientes',7);
    SetupSearchPagination('SearchAsig',     'TableAsig',     'PagerAsig',     7);
    SetupSearchPagination('SearchBitacora','TableBitacora','PagerBitacora',7);
    let FormularioEliminarPendiente = null;
    let BotonEliminarPendiente = null;
    const ModalEliminarElemento = document.getElementById('ModalConfirmarEliminar');
    const TextoTipoEliminar = document.getElementById('DeleteModalTipo');
    const TextoMensajeEliminar = document.getElementById('DeleteModalMensaje');
    const BtnConfirmarEliminar = document.getElementById('BtnConfirmarEliminar');

    if (ModalEliminarElemento && BtnConfirmarEliminar) {
        const ModalEliminar = new bootstrap.Modal(ModalEliminarElemento);

        document.querySelectorAll('form[data-confirm-delete]').forEach(function(Formulario){
            Formulario.addEventListener('submit', function(Evento){
                if (Formulario.dataset.confirmado === '1') {
                    return true;
                }

                Evento.preventDefault();
                FormularioEliminarPendiente = Formulario;
                BotonEliminarPendiente = Evento.submitter || null;

                if (TextoTipoEliminar) {
                    TextoTipoEliminar.textContent = Formulario.dataset.confirmDelete || 'REGISTRO';
                }

                if (TextoMensajeEliminar) {
                    TextoMensajeEliminar.textContent = Formulario.dataset.confirmMessage || '¿DESEAS ELIMINAR ESTE REGISTRO?';
                }

                BtnConfirmarEliminar.innerHTML = '<i class="fa-solid fa-trash"></i> SÍ, ELIMINAR';
                BtnConfirmarEliminar.disabled = false;
                ModalEliminar.show();
            });
        });

        BtnConfirmarEliminar.addEventListener('click', function(){
            if (!FormularioEliminarPendiente) {
                return;
            }

            BtnConfirmarEliminar.disabled = true;
            BtnConfirmarEliminar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ELIMINANDO...';
            FormularioEliminarPendiente.dataset.confirmado = '1';

            if (BotonEliminarPendiente && BotonEliminarPendiente.name) {
                const CampoAccion = document.createElement('input');
                CampoAccion.type = 'hidden';
                CampoAccion.name = BotonEliminarPendiente.name;
                CampoAccion.value = BotonEliminarPendiente.value;
                FormularioEliminarPendiente.appendChild(CampoAccion);
            }

            FormularioEliminarPendiente.submit();
        });
    }
    const TabButtons = document.querySelectorAll('button[data-bs-toggle="tab"]');
    TabButtons.forEach(function(Btn){
        Btn.addEventListener('shown.bs.tab', function (Event) {
            const Target = Event.target.getAttribute('data-bs-target');
            if(!Target) return;

            const Tab = Target.replace('#','');
            const Url = new URL(window.location.href);
            Url.searchParams.set('Tab', Tab);
            history.replaceState({}, '', Url.toString());
        });
    });
    function DebeRespetarMinusculas(Control) {
        const Nombre = (Control.getAttribute('name') || '').toLowerCase();
        const Id = (Control.getAttribute('id') || '').toLowerCase();
        const Tipo = (Control.getAttribute('type') || '').toLowerCase();

        return Tipo === 'password'
            || Nombre === 'user'
            || Nombre === 'username'
            || Nombre === 'pass'
            || Nombre === 'password'
            || Id.includes('search');
    }

    document.querySelectorAll('input:not([type="file"]):not([type="hidden"]), textarea').forEach(function(Control){
        if (Control.placeholder) {
            Control.placeholder = Control.placeholder.toUpperCase();
        }

        if (!DebeRespetarMinusculas(Control)) {
            Control.addEventListener('input', function(){
                Control.value = (Control.value || '').toUpperCase();
            });
        }
    });

    document.querySelectorAll('.InputDigits').forEach(function(Control){
        Control.addEventListener('input', function(){
            Control.value = (Control.value || '').replace(/[^0-9]/g, '');
        });
    });

    document.querySelectorAll('.InputUpperAscii').forEach(function(Control){
        Control.addEventListener('input', function(){
            Control.value = (Control.value || '').toUpperCase().replace(/[^A-Z]/g, '');
        });
    });

    document.querySelectorAll('select option').forEach(function(Opcion){
        Opcion.textContent = (Opcion.textContent || '').toUpperCase();
    });

});


(function(){
    function AjustarContenedoresTablas(){
        var Config={
            TableMaestros:{Rows:7,Height:452},
            TableGrupos:{Rows:7,Height:452},
            TableAlumnos:{Rows:7,Height:452},
            TableAsig:{Rows:7,Height:452},
            TableBitacora:{Rows:7,Height:452},
            TableExpedientes:{Rows:7,Height:452}
        };
        Object.keys(Config).forEach(function(Id){
            var Tabla=document.getElementById(Id);
            if(!Tabla){return;}
            var Wrap=Tabla.closest('.table-responsive');
            if(!Wrap){return;}
            Wrap.classList.add('SgceTableFixedSpace');
            Wrap.style.setProperty('min-height',Config[Id].Height+'px','important');
            Wrap.style.setProperty('max-height','none','important');
            Wrap.style.setProperty('overflow-x','auto','important');
            Wrap.style.setProperty('overflow-y','visible','important');
            Wrap.style.setProperty('border-radius','16px','important');
        });
    }
    document.addEventListener('DOMContentLoaded',AjustarContenedoresTablas);
})();