window.SgceInicializarSearchableSelects = (function(){
    window.SgceSearchableSelectInstances = window.SgceSearchableSelectInstances || [];

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

            function DebeUsarPanelFlotante() {
                return Select.dataset.sgceSearchableFixed === '1' || document.body.classList.contains('SgceReportsPage');
            }

            function ResetPanelFlotante() {
                if (!DebeUsarPanelFlotante()) return;
                Panel.classList.remove('SgceSearchableSelectPanelPortal');
                Panel.style.position = '';
                Panel.style.left = '';
                Panel.style.top = '';
                Panel.style.right = '';
                Panel.style.width = '';
                Panel.style.zIndex = '';
                Panel.style.maxHeight = '';
                Panel.style.display = '';
                Panel.style.pointerEvents = '';
                List.style.maxHeight = '';
                if (Panel.parentNode !== Wrapper) { Wrapper.appendChild(Panel); }
            }

            function PosicionarPanelFlotante() {
                if (!DebeUsarPanelFlotante() || !Wrapper.classList.contains('is-open')) return;
                if (Panel.parentNode !== document.body) { document.body.appendChild(Panel); }
                Panel.classList.add('SgceSearchableSelectPanelPortal');
                Panel.style.display = 'block';
                Panel.style.pointerEvents = 'auto';
                const Rect = Button.getBoundingClientRect();
                const Margen = 12;
                const Separacion = 6;
                const AltoDisponibleAbajo = Math.max(0, window.innerHeight - Rect.bottom - Margen - Separacion);
                const AltoDisponibleArriba = Math.max(0, Rect.top - Margen - Separacion);
                const ForzarAbajo = Select.dataset.sgceSearchableDirection === 'down' || document.body.classList.contains('SgceReportsPage');
                const AltoBase = ForzarAbajo
                    ? AltoDisponibleAbajo
                    : Math.max(AltoDisponibleAbajo, Math.min(260, AltoDisponibleArriba));
                const AltoDeseado = Math.min(280, Math.max(130, AltoBase));
                const AbrirArriba = !ForzarAbajo && AltoDisponibleAbajo < 170 && AltoDisponibleArriba > AltoDisponibleAbajo;
                const Top = AbrirArriba ? Math.max(Margen, Rect.top - AltoDeseado - Separacion) : Math.min(window.innerHeight - Margen, Rect.bottom + Separacion);
                const Left = Math.max(Margen, Math.min(Rect.left, window.innerWidth - Rect.width - Margen));

                Panel.dataset.sgceDirection = AbrirArriba ? 'up' : 'down';
                Panel.style.position = 'fixed';
                Panel.style.left = Left + 'px';
                Panel.style.top = Top + 'px';
                Panel.style.right = 'auto';
                Panel.style.width = Math.max(180, Rect.width) + 'px';
                Panel.style.zIndex = '2147483000';
                Panel.style.maxHeight = AltoDeseado + 'px';
                List.style.maxHeight = Math.max(115, AltoDeseado - 58) + 'px';
            }

            function Cerrar() {
                Wrapper.classList.remove('is-open');
                Button.setAttribute('aria-expanded', 'false');
                Search.value = '';
                RenderOpciones('');
                ResetPanelFlotante();
            }

            window.SgceSearchableSelectInstances.push({
                wrapper: Wrapper,
                panel: Panel,
                button: Button,
                select: Select,
                close: Cerrar
            });

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
                (window.SgceSearchableSelectInstances || []).forEach(function(Instancia){
                    if (Instancia && Instancia.wrapper !== Wrapper && typeof Instancia.close === 'function') {
                        Instancia.close();
                    }
                });
                if (Abierto) { Cerrar(); return; }
                Wrapper.classList.add('is-open');
                Button.setAttribute('aria-expanded', 'true');
                RenderOpciones('');
                setTimeout(function(){
                    PosicionarPanelFlotante();
                    Search.focus({preventScroll:true});
                }, 0);
            });

            Search.addEventListener('input', function(){
                RenderOpciones(Search.value);
                PosicionarPanelFlotante();
            });
            Search.addEventListener('keydown', function(Evento){
                if (Evento.key === 'Escape') { Evento.preventDefault(); Cerrar(); }
            });
            Select.addEventListener('change', ActualizarEtiqueta);
            document.addEventListener('click', function(Evento){
                if (!Wrapper.contains(Evento.target) && !Panel.contains(Evento.target) && Evento.target !== Select) Cerrar();
            });
            window.addEventListener('resize', PosicionarPanelFlotante);
            window.addEventListener('scroll', PosicionarPanelFlotante, true);

            ActualizarEtiqueta();
            RenderOpciones('');
        });
    }
    return InicializarSearchableSelects;
})();

document.addEventListener('DOMContentLoaded', function(){ window.SgceInicializarSearchableSelects(document); });
