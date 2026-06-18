window.SgceAdminUtils = (function(){
    function NormalizarBusqueda(Texto) {
        let Valor = (Texto || '').toString();
        try { Valor = Valor.normalize('NFD').replace(/[\u0300-\u036f]/g, ''); } catch (Error) {}
        Valor = Valor.toLowerCase().trim();
        return Valor
            .replace(/\bprimer(o)?\b|\b1ro\b|\b1er\b|\b1°\b/g, '1')
            .replace(/\bsegundo\b|\b2do\b|\b2°\b/g, '2')
            .replace(/\btercer(o)?\b|\b3ro\b|\b3°\b/g, '3')
            .replace(/\s+/g, ' ')
            .trim();
    }
    function ActualizarSearchableSelectVisual(Select) {
        if (!Select) return;
        const Wrapper = Select.nextElementSibling && Select.nextElementSibling.classList && Select.nextElementSibling.classList.contains('SgceSearchableSelectWrap') ? Select.nextElementSibling : null;
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
    function RestablecerControlFiltro(Control) {
        if (!Control) return;
        const Tag = (Control.tagName || '').toUpperCase();
        const Tipo = (Control.type || '').toLowerCase();
        const Nombre = (Control.name || '').toLowerCase();
        if (Tipo === 'hidden') { if (Nombre.indexOf('pag') === 0) Control.value = '1'; return; }
        if (Tag === 'SELECT') {
            let IndiceVacio = -1;
            Array.from(Control.options || []).some(function(Opcion, Indice){ if ((Opcion.value || '') === '') { IndiceVacio = Indice; return true; } return false; });
            if (IndiceVacio >= 0) { Control.selectedIndex = IndiceVacio; Control.value = ''; }
            else if (Control.options && Control.options.length) { Control.selectedIndex = 0; Control.value = Control.options[0].value; }
            else { Control.selectedIndex = -1; Control.value = ''; }
            ActualizarSearchableSelectVisual(Control);
            return;
        }
        if (Tipo === 'checkbox' || Tipo === 'radio') { Control.checked = false; return; }
        Control.value = '';
    }
    return {NormalizarBusqueda:NormalizarBusqueda, ActualizarSearchableSelectVisual:ActualizarSearchableSelectVisual, RestablecerControlFiltro:RestablecerControlFiltro};
})();
