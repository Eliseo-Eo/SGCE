document.addEventListener('DOMContentLoaded', function(){
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
        if (window.SgceAdminCore) window.SgceAdminCore.DecorarModalesEdicion(document);
        if (window.SgceAdminCore) window.SgceAdminCore.InicializarSoloLetras(Region || document);
        if (window.SgceAdminCore) window.SgceAdminCore.InicializarEntradasGenerales(Region || document);
        if (window.SgceInicializarSearchableSelects) window.SgceInicializarSearchableSelects(document);
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
        const Nombre = (Control.name || '').toLowerCase();
        const Minimo = Number.parseInt(Control.dataset.sgceMinLength || (Nombre === 'buscarasignaciones' ? '1' : '2'), 10) || 2;
        return Valor.length === 0 || Valor.length >= Minimo;
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
});
