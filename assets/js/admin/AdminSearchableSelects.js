window.SgceInicializarSearchableSelects = (function(){
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
    return InicializarSearchableSelects;
})();

document.addEventListener('DOMContentLoaded', function(){ window.SgceInicializarSearchableSelects(document); });
