window.SgceAdminCore = (function(){
    function Inicializar(Root) {
        Root = Root || document;
        if (window.SgceAdminEditModals) { window.SgceAdminEditModals.DecorarModalesEdicion(Root); }
        if (window.SgceAdminInputs) { window.SgceAdminInputs.Inicializar(Root); }
    }
    return {Inicializar:Inicializar};
})();
document.addEventListener('DOMContentLoaded', function(){ window.SgceAdminCore.Inicializar(document); });
