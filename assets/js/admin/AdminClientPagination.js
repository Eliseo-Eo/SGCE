document.addEventListener('DOMContentLoaded', function(){
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
            return window.SgceAdminUtils.NormalizarBusqueda(Text);
        }

        function RowFilterValue(Row, Key) {
            if (!Key) return '';
            if (Object.prototype.hasOwnProperty.call(Row.dataset, Key)) {
                return window.SgceAdminUtils.NormalizarBusqueda(Row.dataset[Key] || '');
            }
            return window.SgceAdminUtils.NormalizarBusqueda(Row.getAttribute('data-' + Key) || '');
        }

        function RowMatchesSelectFilters(Row) {
            return SelectFilters.every(function(Control){
                const Value = window.SgceAdminUtils.NormalizarBusqueda(Control.value || '');
                if (Value === '') return true;
                const Key = (Control.dataset.sgceFilterKey || '').trim();
                return RowFilterValue(Row, Key) === Value;
            });
        }

        function AnySelectFilterActive() {
            return SelectFilters.some(function(Control){
                return window.SgceAdminUtils.NormalizarBusqueda(Control.value || '') !== '';
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
            const Filter = window.SgceAdminUtils.NormalizarBusqueda(Input ? Input.value : '');
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
                if (Input) window.SgceAdminUtils.RestablecerControlFiltro(Input);
                SelectFilters.forEach(function(Control){ window.SgceAdminUtils.RestablecerControlFiltro(Control); });
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
});
