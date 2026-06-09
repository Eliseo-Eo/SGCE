document.addEventListener("DOMContentLoaded", function() {
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
    }

    DecorarModalesEdicion(document);

    function NormalizarInputNombre(El) {
        let Val = El.value || '';
        Val = Val.toUpperCase();
        Val = Val.replace(/[^A-ZÁÉÍÓÚÜÑ\s]/g, '');
        Val = Val.replace(/\s+/g, ' ');
        El.value = Val;
    }

    function InicializarSoloLetras(Root) {
        (Root || document).querySelectorAll('.SoloLetrasMayus').forEach(function(El){
            if (El.dataset.sgceSoloLetrasBound === '1') return;
            El.dataset.sgceSoloLetrasBound = '1';
            El.addEventListener('input', function(){ NormalizarInputNombre(El); });
            El.addEventListener('blur', function(){ NormalizarInputNombre(El); });
        });
    }

    InicializarSoloLetras(document);
    function NormalizarBusqueda(Texto) {
        let Valor = (Texto || '').toString();
        try {
            Valor = Valor.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        } catch (Error) {
            // Compatibilidad con navegadores antiguos.
        }
        Valor = Valor.toLowerCase().trim();
        Valor = Valor
            .replace(/\bprimer(o)?\b|\b1ro\b|\b1er\b|\b1°\b/g, '1')
            .replace(/\bsegundo\b|\b2do\b|\b2°\b/g, '2')
            .replace(/\btercer(o)?\b|\b3ro\b|\b3°\b/g, '3')
            .replace(/\s+/g, ' ')
            .trim();
        return Valor;
    }

    function SetupSearchPagination(InputId, TableId, PagerId, RowsPerPage) {

        const Input = document.getElementById(InputId);
        const Table = document.getElementById(TableId);
        const Pager = document.getElementById(PagerId);

        if(!Table || !Table.tBodies.length) return;
        if (Table.dataset && Table.dataset.sgceServerPaged === '1') return;

        let CurrentPage = 1;
        const TBody = Table.tBodies[0];
        const ColSpan = Table.tHead && Table.tHead.rows.length ? Table.tHead.rows[0].cells.length : 1;
        const SelectFilters = Array.from(document.querySelectorAll('[data-sgce-filter-table=\"' + TableId + '\"]'));
        const ClearButtons = Array.from(document.querySelectorAll('[data-sgce-clear-filters=\"' + TableId + '\"]'));
        let EmptyRow = null;

        function GetDataRows() {
            return Array.from(TBody.rows).filter(function(Row){
                return !Row.classList.contains('SgceNoResultsRow') && Row.querySelector('.searchable');
            });
        }

        function GetStaticRows() {
            return Array.from(TBody.rows).filter(function(Row){
                return !Row.classList.contains('SgceNoResultsRow') && !Row.querySelector('.searchable');
            });
        }

        function RowText(Row) {
            const Cells = Array.from(Row.getElementsByClassName('searchable'));
            const Text = Cells.length ? Cells.map(C => C.innerText || '').join(' ') : (Row.innerText || '');
            return NormalizarBusqueda(Text);
        }

        function RowFilterValue(Row, Key) {
            if (!Key) return '';
            if (Object.prototype.hasOwnProperty.call(Row.dataset, Key)) {
                return NormalizarBusqueda(Row.dataset[Key] || '');
            }
            return NormalizarBusqueda(Row.getAttribute('data-' + Key) || '');
        }

        function RowMatchesSelectFilters(Row) {
            return SelectFilters.every(function(Control){
                const Value = NormalizarBusqueda(Control.value || '');
                if (Value === '') return true;
                const Key = (Control.dataset.sgceFilterKey || '').trim();
                return RowFilterValue(Row, Key) === Value;
            });
        }

        function AnySelectFilterActive() {
            return SelectFilters.some(function(Control){
                return NormalizarBusqueda(Control.value || '') !== '';
            });
        }

        function CrearBoton(Label, Page, Disabled, Active) {
            const Li = document.createElement('li');
            Li.className = 'page-item' + (Disabled ? ' disabled' : '') + (Active ? ' active' : '');

            const Btn = document.createElement('button');
            Btn.type = 'button';
            Btn.className = 'page-link';
            Btn.textContent = Label;
            Btn.disabled = !!Disabled;
            Btn.addEventListener('click', function(){
                if (Disabled || Active) return;
                CurrentPage = Page;
                Apply();
            });

            Li.appendChild(Btn);
            return Li;
        }

        function RenderPager(TotalMatched, TotalRows, TotalPages, StartIndex, EndIndex, FilterActive) {
            if(!Pager) return;
            Pager.innerHTML = '';
            Pager.classList.add('SgceClientPager');

            const Count = document.createElement('div');
            Count.className = 'SgcePagerInfo SgceClientPagerCount';

            if (TotalRows <= 0) {
                Count.textContent = 'Mostrando 0 de 0 registro(s).';
                Pager.appendChild(Count);
                return;
            }

            if (TotalMatched <= 0) {
                Count.textContent = FilterActive ? 'Sin coincidencias en ' + TotalRows + ' registro(s).' : 'Mostrando 0 de ' + TotalRows + ' registro(s).';
                Pager.appendChild(Count);
                return;
            }

            Count.textContent = 'Mostrando ' + (StartIndex + 1) + '-' + EndIndex + ' de ' + TotalMatched + ' registro(s)' + (FilterActive ? ' filtrado(s) de ' + TotalRows + '.' : '.');

            const Ul = document.createElement('ul');
            Ul.className = 'pagination pagination-sm justify-content-center flex-wrap gap-1 mb-0';

            if (TotalPages > 1) {
                Ul.appendChild(CrearBoton('«', 1, CurrentPage === 1, false));
                Ul.appendChild(CrearBoton('‹', Math.max(1, CurrentPage - 1), CurrentPage === 1, false));

                let Start = Math.max(1, CurrentPage - 2);
                let End = Math.min(TotalPages, CurrentPage + 2);

                if (CurrentPage <= 3) {
                    End = Math.min(TotalPages, 5);
                }
                if (CurrentPage >= TotalPages - 2) {
                    Start = Math.max(1, TotalPages - 4);
                }

                if (Start > 1) {
                    Ul.appendChild(CrearBoton('1', 1, false, CurrentPage === 1));
                    if (Start > 2) {
                        const Dots = document.createElement('li');
                        Dots.className = 'page-item disabled SgcePagerDots';
                        Dots.innerHTML = '<span class="page-link">…</span>';
                        Ul.appendChild(Dots);
                    }
                }

                for (let P = Start; P <= End; P++) {
                    Ul.appendChild(CrearBoton(String(P), P, false, P === CurrentPage));
                }

                if (End < TotalPages) {
                    if (End < TotalPages - 1) {
                        const Dots = document.createElement('li');
                        Dots.className = 'page-item disabled SgcePagerDots';
                        Dots.innerHTML = '<span class="page-link">…</span>';
                        Ul.appendChild(Dots);
                    }
                    Ul.appendChild(CrearBoton(String(TotalPages), TotalPages, false, CurrentPage === TotalPages));
                }

                Ul.appendChild(CrearBoton('›', Math.min(TotalPages, CurrentPage + 1), CurrentPage === TotalPages, false));
                Ul.appendChild(CrearBoton('»', TotalPages, CurrentPage === TotalPages, false));
                Pager.appendChild(Ul);
            }

            Pager.appendChild(Count);
        }

        function MostrarFilaSinResultados(FilterActive, TotalRows) {
            if (!EmptyRow) {
                EmptyRow = document.createElement('tr');
                EmptyRow.className = 'SgceNoResultsRow';
                const Td = document.createElement('td');
                Td.colSpan = ColSpan;
                Td.className = 'text-center text-muted fw-bold py-4';
                EmptyRow.appendChild(Td);
            }
            EmptyRow.cells[0].innerHTML = FilterActive
                ? '<i class="fa-solid fa-magnifying-glass me-1"></i> No se encontraron coincidencias en los ' + TotalRows + ' registro(s).'
                : '<i class="fa-solid fa-inbox me-1"></i> No hay registros para mostrar.';
            if (!EmptyRow.parentNode) {
                TBody.appendChild(EmptyRow);
            }
            EmptyRow.style.display = '';
        }

        function OcultarFilaSinResultados() {
            if (EmptyRow) EmptyRow.style.display = 'none';
        }

        function Apply() {
            const Filter = NormalizarBusqueda(Input ? Input.value : '');
            const FilterActive = Filter !== '' || AnySelectFilterActive();
            const DataRows = GetDataRows();
            const StaticRows = GetStaticRows();

            StaticRows.forEach(function(Row){ Row.style.display = DataRows.length ? 'none' : ''; });

            if (DataRows.length === 0) {
                OcultarFilaSinResultados();
                RenderPager(0, 0, 1, 0, 0, FilterActive);
                return;
            }

            const Tokens = Filter.split(' ').filter(Boolean);
            let Matched = [];

            DataRows.forEach(function(Row){
                const Text = RowText(Row);
                const SearchMatch = Tokens.length === 0 ? true : Tokens.every(function(Token){ return Text.indexOf(Token) > -1; });
                const SelectMatch = RowMatchesSelectFilters(Row);
                const Match = SearchMatch && SelectMatch;
                Row.dataset.match = Match ? '1' : '0';
                if (Match) Matched.push(Row);
            });

            const TotalRows = DataRows.length;
            const TotalPages = Math.max(1, Math.ceil(Matched.length / RowsPerPage));
            if (CurrentPage > TotalPages) CurrentPage = TotalPages;
            if (CurrentPage < 1) CurrentPage = 1;

            DataRows.forEach(function(Row){ Row.style.display = 'none'; });

            if (Matched.length === 0) {
                MostrarFilaSinResultados(FilterActive, TotalRows);
                RenderPager(0, TotalRows, 1, 0, 0, FilterActive);
                return;
            }

            OcultarFilaSinResultados();

            const StartIndex = (CurrentPage - 1) * RowsPerPage;
            const EndIndex = Math.min(StartIndex + RowsPerPage, Matched.length);

            Matched.slice(StartIndex, EndIndex).forEach(function(Row){
                Row.style.display = '';
            });

            RenderPager(Matched.length, TotalRows, TotalPages, StartIndex, EndIndex, FilterActive);
        }

        if (Input) {
            Input.addEventListener('input', function(){
                CurrentPage = 1;
                Apply();
            });
            Input.addEventListener('keyup', function(){
                CurrentPage = 1;
                Apply();
            });
        }

        SelectFilters.forEach(function(Control){
            Control.addEventListener('change', function(){
                CurrentPage = 1;
                Apply();
            });
        });

        ClearButtons.forEach(function(Btn){
            Btn.addEventListener('click', function(Evento){
                if (Evento) Evento.preventDefault();
                if (Input) SgceRestablecerControlFiltro(Input);
                SelectFilters.forEach(function(Control){ SgceRestablecerControlFiltro(Control); });
                CurrentPage = 1;
                Apply();
            });
        });

        Apply();
    }

    
    SetupSearchPagination('SearchMaestros', 'TableMaestros', 'PagerMaestros', 7);
    SetupSearchPagination('SearchGrupos',   'TableGrupos',   'PagerGrupos',   7);
    SetupSearchPagination('SearchAlumnos',  'TableAlumnos',  'PagerAlumnos',  7);
    SetupSearchPagination('SearchMaterias', 'TableMaterias', 'PagerMaterias', 7);
    SetupSearchPagination('SearchExpedientes','TableExpedientes','PagerExpedientes',7);
    SetupSearchPagination('SearchAsig',     'TableAsig',     'PagerAsig',     7);
    SetupSearchPagination('SearchBitacora','TableBitacora','PagerBitacora',7);

    let SgceServerFilterAbort = null;

    function SgceServerRegion(Elemento) {
        if (!Elemento) return null;
        return Elemento.closest('.AlumnosTableCard, .MateriasTableCard, .AsignacionesTableCard, .SgceBitacoraCard');
    }

    function SgceServerTab(Form) {
        const TabControl = Form ? Form.querySelector('input[name="Tab"]') : null;
        return TabControl ? (TabControl.value || '').toLowerCase() : '';
    }

    function SgceAdminEndpointPorTab(Tab) {
        const Mapa = {
            alumnos: 'api/admin/alumnos.php',
            materias: 'api/admin/materias.php',
            asignaciones: 'api/admin/asignaciones.php',
            bitacora: 'api/admin/bitacora.php'
        };
        return Mapa[Tab] || '';
    }

    function SgceBuscarControlPorNombre(Root, Nombre) {
        if (!Root || !Nombre) return null;
        const Controles = Root.querySelectorAll('input, select, textarea');
        for (let I = 0; I < Controles.length; I++) {
            if (Controles[I].name === Nombre) return Controles[I];
        }
        return null;
    }

    function SgceActualizarSearchableSelectVisual(Select) {
        if (!Select) return;
        const Wrapper = Select.nextElementSibling && Select.nextElementSibling.classList && Select.nextElementSibling.classList.contains('SgceSearchableSelectWrap')
            ? Select.nextElementSibling
            : null;
        if (!Wrapper) return;

        const Texto = Wrapper.querySelector('.SgceSearchableSelectText');
        const Input = Wrapper.querySelector('.SgceSearchableSelectInput');
        const Opcion = Select.options[Select.selectedIndex] || Select.options[0] || null;

        if (Texto) Texto.textContent = Opcion ? (Opcion.textContent || '').trim() : 'Seleccionar...';
        if (Input) Input.value = '';
        Wrapper.classList.remove('is-open');
        Wrapper.classList.toggle('SgceSearchableSelectEmpty', Select.value === '');
        const Button = Wrapper.querySelector('.SgceSearchableSelectButton');
        if (Button) Button.setAttribute('aria-expanded', 'false');
    }

    function SgceRestablecerControlFiltro(Control) {
        if (!Control) return;

        const Tag = (Control.tagName || '').toUpperCase();
        const Tipo = (Control.type || '').toLowerCase();
        const Nombre = (Control.name || '').toLowerCase();

        if (Tipo === 'hidden') {
            if (Nombre.indexOf('pag') === 0) Control.value = '1';
            return;
        }

        if (Tag === 'SELECT') {
            let IndiceVacio = -1;
            Array.from(Control.options || []).some(function(Opcion, Indice){
                if ((Opcion.value || '') === '') {
                    IndiceVacio = Indice;
                    return true;
                }
                return false;
            });

            if (IndiceVacio >= 0) {
                Control.selectedIndex = IndiceVacio;
                Control.value = '';
            } else if (Control.options && Control.options.length) {
                Control.selectedIndex = 0;
                Control.value = Control.options[0].value;
            } else {
                Control.selectedIndex = -1;
                Control.value = '';
            }

            SgceActualizarSearchableSelectVisual(Control);
            return;
        }

        if (Tipo === 'checkbox' || Tipo === 'radio') {
            Control.checked = false;
            return;
        }

        Control.value = '';
    }

    function SgceLimpiarFormularioServidor(Form) {
        if (!Form) return;

        Form.querySelectorAll('input, select, textarea').forEach(function(Control){
            SgceRestablecerControlFiltro(Control);
        });
    }

    function SgceUrlDesdeFormulario(Form, BaseUrl) {
        const Url = new URL(BaseUrl || Form.getAttribute('action') || window.location.href, window.location.href);
        Url.search = '';
        const Datos = new FormData(Form);
        Datos.forEach(function(Valor, Clave){
            Url.searchParams.set(Clave, Valor);
        });
        return Url;
    }


    function InicializarSearchableSelects(Root) {
        (Root || document).querySelectorAll('select[data-sgce-searchable-select="1"]').forEach(function(Select){
            if (Select.dataset.sgceSearchableBound === '1') return;
            Select.dataset.sgceSearchableBound = '1';
            Select.classList.add('SgceNativeSelectHidden');

            const Wrapper = document.createElement('div');
            Wrapper.className = 'SgceSearchableSelectWrap';
            const Button = document.createElement('button');
            Button.type = 'button';
            Button.className = 'SgceSearchableSelectButton';
            Button.setAttribute('aria-haspopup', 'listbox');
            Button.setAttribute('aria-expanded', 'false');
            const ButtonText = document.createElement('span');
            ButtonText.className = 'SgceSearchableSelectText';
            const Icon = document.createElement('i');
            Icon.className = 'fa-solid fa-chevron-down';
            Button.appendChild(ButtonText);
            Button.appendChild(Icon);

            const Panel = document.createElement('div');
            Panel.className = 'SgceSearchableSelectPanel';
            const Search = document.createElement('input');
            Search.type = 'text';
            Search.className = 'SgceSearchableSelectInput';
            Search.placeholder = Select.dataset.sgceSearchPlaceholder || 'Buscar...';
            Search.setAttribute('autocomplete', 'off');
            const List = document.createElement('div');
            List.className = 'SgceSearchableSelectList';
            Panel.appendChild(Search);
            Panel.appendChild(List);
            Wrapper.appendChild(Button);
            Wrapper.appendChild(Panel);
            Select.insertAdjacentElement('afterend', Wrapper);

            function NormalizarSelect(Texto) {
                let Valor = (Texto || '').toString();
                try { Valor = Valor.normalize('NFD').replace(/[\u0300-\u036f]/g, ''); } catch (Error) {}
                return Valor.toLowerCase().replace(/\s+/g, ' ').trim();
            }

            function OpcionActualTexto() {
                const Opcion = Select.options[Select.selectedIndex];
                return Opcion ? (Opcion.textContent || '').trim() : '';
            }

            function ActualizarEtiqueta() {
                const Texto = OpcionActualTexto() || (Select.options[0] ? Select.options[0].textContent.trim() : 'Seleccionar...');
                ButtonText.textContent = Texto;
                if (Select.value === '') Wrapper.classList.add('SgceSearchableSelectEmpty');
                else Wrapper.classList.remove('SgceSearchableSelectEmpty');
            }

            function Cerrar() {
                Wrapper.classList.remove('is-open');
                Button.setAttribute('aria-expanded', 'false');
                Search.value = '';
                RenderOpciones('');
            }

            function Elegir(Value) {
                Select.value = Value;
                Select.dispatchEvent(new Event('change', {bubbles:true}));
                ActualizarEtiqueta();
                Cerrar();
                Button.focus({preventScroll:true});
            }

            function RenderOpciones(Filtro) {
                const Busqueda = NormalizarSelect(Filtro);
                List.innerHTML = '';
                let Coincidencias = 0;
                Array.from(Select.options).forEach(function(Opcion){
                    const Texto = (Opcion.textContent || '').trim();
                    const Value = Opcion.value;
                    if (Busqueda && NormalizarSelect(Texto).indexOf(Busqueda) === -1) return;
                    const Item = document.createElement('button');
                    Item.type = 'button';
                    Item.className = 'SgceSearchableSelectOption';
                    if (Value === Select.value) Item.classList.add('is-selected');
                    Item.textContent = Texto || 'Seleccionar...';
                    Item.addEventListener('click', function(){ Elegir(Value); });
                    List.appendChild(Item);
                    Coincidencias++;
                });
                if (Coincidencias === 0) {
                    const Empty = document.createElement('div');
                    Empty.className = 'SgceSearchableSelectNoResults';
                    Empty.textContent = 'Sin coincidencias';
                    List.appendChild(Empty);
                }
            }

            Button.addEventListener('click', function(){
                const Abierto = Wrapper.classList.contains('is-open');
                document.querySelectorAll('.SgceSearchableSelectWrap.is-open').forEach(function(AbiertoWrap){
                    if (AbiertoWrap !== Wrapper) AbiertoWrap.classList.remove('is-open');
                });
                if (Abierto) { Cerrar(); return; }
                Wrapper.classList.add('is-open');
                Button.setAttribute('aria-expanded', 'true');
                RenderOpciones('');
                setTimeout(function(){ Search.focus({preventScroll:true}); }, 0);
            });

            Search.addEventListener('input', function(){ RenderOpciones(Search.value); });
            Search.addEventListener('keydown', function(Evento){
                if (Evento.key === 'Escape') { Evento.preventDefault(); Cerrar(); }
            });
            Select.addEventListener('change', ActualizarEtiqueta);
            document.addEventListener('click', function(Evento){
                if (!Wrapper.contains(Evento.target) && Evento.target !== Select) Cerrar();
            });

            ActualizarEtiqueta();
            RenderOpciones('');
        });
    }

    function SgceReiniciarComponentesParciales(Region) {
        DecorarModalesEdicion(document);
        InicializarSoloLetras(Region || document);
        InicializarEntradasGenerales(Region || document);
        InicializarSearchableSelects(document);
        InicializarServerFilters(Region || document);
    }

    function SgceAplicarRespuestaParcial(Json, RegionActual, AdminUrl, FocusInfo) {
        if (!Json || Json.ok !== true || !RegionActual) {
            window.location.href = AdminUrl.toString();
            return;
        }

        const Tbody = RegionActual.querySelector('tbody[data-sgce-partial-tbody]');
        const Pager = RegionActual.querySelector('[data-sgce-partial-pager]');
        const Count = RegionActual.querySelector('.SgceCountPill');
        const Tab = Json.tab || SgceServerTab(RegionActual.querySelector('form[data-sgce-server-filter="1"]'));
        const Modals = Tab ? document.querySelector('[data-sgce-partial-modals="' + Tab + '"]') : null;

        RegionActual.classList.add('SgceServerFilterUpdating');
        window.requestAnimationFrame(function(){
            if (Tbody) Tbody.innerHTML = Json.tbody || '';
            if (Pager) Pager.innerHTML = Json.pager || '';
            if (Count && Json.count) Count.outerHTML = Json.count;
            if (Modals && typeof Json.modals === 'string') Modals.innerHTML = Json.modals;

            history.replaceState({}, '', Json.url || AdminUrl.toString());
            SgceReiniciarComponentesParciales(RegionActual);

            const FormActual = RegionActual.querySelector('form[data-sgce-server-filter="1"]');
            if (FormActual && FormActual.dataset.sgceClearPending === '1') {
                SgceLimpiarFormularioServidor(FormActual);
                delete FormActual.dataset.sgceClearPending;
            }

            if (FocusInfo && FocusInfo.Nombre) {
                const NuevoControl = SgceBuscarControlPorNombre(RegionActual, FocusInfo.Nombre);
                if (NuevoControl) {
                    NuevoControl.focus({preventScroll: true});
                    if (NuevoControl.setSelectionRange && typeof FocusInfo.Cursor === 'number') {
                        const Largo = (NuevoControl.value || '').length;
                        const Pos = Math.min(FocusInfo.Cursor, Largo);
                        NuevoControl.setSelectionRange(Pos, Pos);
                    }
                }
            }
            setTimeout(function(){ RegionActual.classList.remove('SgceServerFilterUpdating'); }, 120);
        });
    }

    function SgceEnviarFiltroServidor(Form, ControlOrigen, UrlForzada) {
        const Region = SgceServerRegion(Form || ControlOrigen);
        if (!Region || !Form) return;

        const Tab = SgceServerTab(Form);
        const Endpoint = SgceAdminEndpointPorTab(Tab);
        const AdminUrl = UrlForzada ? new URL(UrlForzada, window.location.href) : SgceUrlDesdeFormulario(Form, 'Admin.php');
        const ApiUrl = Endpoint ? (UrlForzada ? new URL(Endpoint, window.location.href) : SgceUrlDesdeFormulario(Form, Endpoint)) : AdminUrl;

        if (UrlForzada && Endpoint) {
            const Limpia = new URL(UrlForzada, window.location.href);
            ApiUrl.search = '';
            Limpia.searchParams.forEach(function(Valor, Clave){ ApiUrl.searchParams.set(Clave, Valor); });
            ApiUrl.searchParams.set('Tab', Tab);
        }

        const FocusInfo = ControlOrigen ? {
            Nombre: ControlOrigen.name || '',
            Cursor: typeof ControlOrigen.selectionStart === 'number' ? ControlOrigen.selectionStart : null
        } : null;

        if (!Endpoint) {
            window.location.href = AdminUrl.toString();
            return;
        }

        if (SgceServerFilterAbort) {
            SgceServerFilterAbort.abort();
        }
        SgceServerFilterAbort = new AbortController();
        Region.classList.add('SgceServerFilterLoading');

        fetch(ApiUrl.toString(), {
            method: 'GET',
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'},
            signal: SgceServerFilterAbort.signal
        })
        .then(function(Respuesta){
            if (!Respuesta.ok) throw new Error('HTTP ' + Respuesta.status);
            return Respuesta.json();
        })
        .then(function(Json){
            SgceAplicarRespuestaParcial(Json, Region, AdminUrl, FocusInfo);
        })
        .catch(function(Error){
            if (Error && Error.name === 'AbortError') return;
            window.location.href = AdminUrl.toString();
        })
        .finally(function(){
            Region.classList.remove('SgceServerFilterLoading');
        });
    }


    function SgceBusquedaServidorLista(Control) {
        if (!Control || (Control.type || '').toLowerCase() !== 'text') return true;
        const Valor = (Control.value || '').trim();
        return Valor.length === 0 || Valor.length >= 2;
    }

    function InicializarServerFilters(Root) {
        (Root || document).querySelectorAll('form[data-sgce-server-filter="1"]').forEach(function(Form){
            if (Form.dataset.sgceServerFilterBound === '1') return;
            Form.dataset.sgceServerFilterBound = '1';
            let Timer = null;

            const Enviar = function(ControlOrigen){
                if (Timer) clearTimeout(Timer);
                if (ControlOrigen && !SgceBusquedaServidorLista(ControlOrigen)) return;
                SgceEnviarFiltroServidor(Form, ControlOrigen || null, null);
            };

            Form.addEventListener('submit', function(Evento){
                Evento.preventDefault();
                Enviar(document.activeElement && Form.contains(document.activeElement) ? document.activeElement : null);
            });

            Form.querySelectorAll('select, input[type="date"]').forEach(function(Control){
                Control.addEventListener('change', function(){ Enviar(Control); });
            });
            Form.querySelectorAll('input[type="text"]').forEach(function(Control){
                Control.addEventListener('input', function(){
                    if (Timer) clearTimeout(Timer);
                    if (!SgceBusquedaServidorLista(Control)) return;
                    Timer = setTimeout(function(){ SgceEnviarFiltroServidor(Form, Control, null); }, 450);
                });
                Control.addEventListener('keydown', function(Evento){
                    if (Evento.key === 'Enter') {
                        Evento.preventDefault();
                        Enviar(Control);
                    }
                });
            });
        });

        (Root || document).querySelectorAll('.SgceClearFiltersBtn').forEach(function(Enlace){
            if (Enlace.dataset.sgceClearAjaxBound === '1') return;
            Enlace.dataset.sgceClearAjaxBound = '1';
            Enlace.addEventListener('click', function(Evento){
                const Region = SgceServerRegion(Enlace);
                if (!Region) return;
                Evento.preventDefault();
                const Form = Region.querySelector('form[data-sgce-server-filter="1"]');
                if (Form) {
                    Form.dataset.sgceClearPending = '1';
                    SgceLimpiarFormularioServidor(Form);
                }
                SgceEnviarFiltroServidor(Form, null, Enlace.href);
            });
        });

        (Root || document).querySelectorAll('.SgcePagerServer a.page-link').forEach(function(Enlace){
            if (Enlace.dataset.sgcePagerAjaxBound === '1') return;
            Enlace.dataset.sgcePagerAjaxBound = '1';
            Enlace.addEventListener('click', function(Evento){
                const Region = SgceServerRegion(Enlace);
                if (!Region) return;
                Evento.preventDefault();
                const Form = Region.querySelector('form[data-sgce-server-filter="1"]');
                SgceEnviarFiltroServidor(Form, null, Enlace.href);
            });
        });
    }

    InicializarServerFilters(document);

    let FormularioEliminarPendiente = null;
    let BotonEliminarPendiente = null;
    const ModalEliminarElemento = document.getElementById('ModalConfirmarEliminar');
    const TextoTipoEliminar = document.getElementById('DeleteModalTipo');
    const TextoMensajeEliminar = document.getElementById('DeleteModalMensaje');
    const BtnConfirmarEliminar = document.getElementById('BtnConfirmarEliminar');

    if (ModalEliminarElemento && BtnConfirmarEliminar) {
        const ModalEliminar = new bootstrap.Modal(ModalEliminarElemento);

        document.addEventListener('submit', function(Evento){
            const Formulario = Evento.target && Evento.target.closest ? Evento.target.closest('form[data-confirm-delete]') : null;
            if (!Formulario) return;
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

        if (Control.classList && Control.classList.contains('SgceSearchableSelectInput')) return true;

        return Tipo === 'password'
            || Nombre === 'user'
            || Nombre === 'username'
            || Nombre === 'pass'
            || Nombre === 'password'
            || Id.includes('search');
    }

    function InicializarEntradasGenerales(Root) {
        (Root || document).querySelectorAll('input:not([type="file"]):not([type="hidden"]), textarea').forEach(function(Control){
            if (Control.placeholder) {
                Control.placeholder = Control.placeholder.toUpperCase();
            }

            if (!DebeRespetarMinusculas(Control) && Control.dataset.sgceUpperBound !== '1') {
                Control.dataset.sgceUpperBound = '1';
                Control.addEventListener('input', function(){
                    Control.value = (Control.value || '').toUpperCase();
                });
            }
        });

        (Root || document).querySelectorAll('.InputDigits').forEach(function(Control){
            if (Control.dataset.sgceDigitsBound === '1') return;
            Control.dataset.sgceDigitsBound = '1';
            Control.addEventListener('input', function(){
                Control.value = (Control.value || '').replace(/[^0-9]/g, '');
            });
        });

        (Root || document).querySelectorAll('.InputUpperAscii').forEach(function(Control){
            if (Control.dataset.sgceAsciiBound === '1') return;
            Control.dataset.sgceAsciiBound = '1';
            Control.addEventListener('input', function(){
                Control.value = (Control.value || '').toUpperCase().replace(/[^A-Z]/g, '');
            });
        });

        (Root || document).querySelectorAll('select option').forEach(function(Opcion){
            Opcion.textContent = (Opcion.textContent || '').toUpperCase();
        });
    }

    InicializarEntradasGenerales(document);
    InicializarSearchableSelects(document);

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