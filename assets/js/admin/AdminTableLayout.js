(function(){
    function AjustarContenedoresTablas(){
        var Config={
            TableMaestros:{Height:452},
            TableGrupos:{Height:452},
            TableAlumnos:{Height:452},
            TableAsig:{Height:452},
            TableBitacora:{Height:452},
            TableExpedientes:{Height:452}
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
