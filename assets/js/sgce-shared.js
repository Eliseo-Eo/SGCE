
// =====================================================
// TEMA INSTITUCIONAL DINÁMICO
// Aplica en vista previa el mismo cálculo de tonos que usa PHP al guardar.
// =====================================================
window.SgceAjustarColorHex = function (ColorHex, Porcentaje) {
    var Color = String(ColorHex || '#97051E').replace('#', '').trim();
    if (!/^[0-9A-Fa-f]{6}$/.test(Color)) { Color = '97051E'; }
    var Rojo = parseInt(Color.substring(0, 2), 16);
    var Verde = parseInt(Color.substring(2, 4), 16);
    var Azul = parseInt(Color.substring(4, 6), 16);
    var Limite = Math.max(-100, Math.min(100, parseInt(Porcentaje, 10) || 0));
    var Objetivo = Limite >= 0 ? 255 : 0;
    var Factor = Math.abs(Limite) / 100;
    function Ajustar(Canal) {
        var Resultado = Math.round(Canal + (Objetivo - Canal) * Factor);
        return Math.max(0, Math.min(255, Resultado));
    }
    function Hex(Canal) {
        return Canal.toString(16).toUpperCase().padStart(2, '0');
    }
    return '#' + Hex(Ajustar(Rojo)) + Hex(Ajustar(Verde)) + Hex(Ajustar(Azul));
};

window.SgceAplicarTemaColor = function (ColorHex) {
    var Valor = String(ColorHex || '#97051E').trim().toUpperCase();
    if (!/^#[0-9A-F]{6}$/.test(Valor)) { Valor = '#97051E'; }
    var Color = Valor.replace('#', '');
    var Rojo = parseInt(Color.substring(0, 2), 16);
    var Verde = parseInt(Color.substring(2, 4), 16);
    var Azul = parseInt(Color.substring(4, 6), 16);
    var Raiz = document.documentElement;
    Raiz.style.setProperty('--SgceGuinda', Valor);
    Raiz.style.setProperty('--SgceGuindaRGB', Rojo + ',' + Verde + ',' + Azul);
    Raiz.style.setProperty('--SgceGuindaOscuro', window.SgceAjustarColorHex(Valor, -22));
    Raiz.style.setProperty('--SgceGuindaProfundo', window.SgceAjustarColorHex(Valor, -48));
    Raiz.style.setProperty('--SgceGuindaSuave', window.SgceAjustarColorHex(Valor, 84));
    Raiz.style.setProperty('--SgceGuindaClaro', window.SgceAjustarColorHex(Valor, 32));
    Raiz.style.setProperty('--SgceSombraGuinda', '0 18px 42px rgba(' + Rojo + ',' + Verde + ',' + Azul + ',.22)');
    return Valor;
};

// ===== BLOQUE COMPARTIDO EXTRAÍDO =====
document.addEventListener('DOMContentLoaded', function() {

    function OcultarNotificacion(Alerta) {
        if (!Alerta || Alerta.dataset.Ocultando === '1') {
            return;
        }

        Alerta.dataset.Ocultando = '1';
        Alerta.style.transition = 'opacity .45s ease, transform .45s ease, max-height .45s ease, margin .45s ease, padding .45s ease';
        Alerta.style.opacity = '0';
        Alerta.style.transform = 'translateY(-12px)';
        Alerta.style.maxHeight = '0';
        Alerta.style.marginTop = '0';
        Alerta.style.marginBottom = '0';
        Alerta.style.paddingTop = '0';
        Alerta.style.paddingBottom = '0';

        setTimeout(function() {
            Alerta.remove();
        }, 500);
    }

    function PrepararNotificacion(Alerta) {
        if (!Alerta || Alerta.dataset.NotificacionPreparada === '1') {
            return;
        }

        Alerta.dataset.NotificacionPreparada = '1';
        Alerta.classList.add('alert-dismissible', 'fade', 'show');
        Alerta.style.position = 'relative';

        let BotonCerrar = Alerta.querySelector('.btn-close');

        if (!BotonCerrar) {
            BotonCerrar = document.createElement('button');
            BotonCerrar.type = 'button';
            BotonCerrar.className = 'btn-close';
            BotonCerrar.setAttribute('aria-label', 'CERRAR');
            Alerta.appendChild(BotonCerrar);
        }

        BotonCerrar.addEventListener('click', function(Evento) {
            Evento.preventDefault();
            OcultarNotificacion(Alerta);
        });

        function ProgramarAutoCierre() {
            if (!Alerta.classList.contains('d-none') && Alerta.dataset.AutoCierreProgramado !== '1') {
                Alerta.dataset.AutoCierreProgramado = '1';

                setTimeout(function() {
                    OcultarNotificacion(Alerta);
                }, 4500);
            }
        }

        ProgramarAutoCierre();

        const Observador = new MutationObserver(function() {
            ProgramarAutoCierre();
        });

        Observador.observe(Alerta, {
            attributes: true,
            attributeFilter: ['class', 'style']
        });
    }

    document.querySelectorAll('.alert').forEach(function(Alerta) {
        PrepararNotificacion(Alerta);
    });

    const ObservadorBody = new MutationObserver(function(Mutaciones) {
        Mutaciones.forEach(function(Mutacion) {
            Mutacion.addedNodes.forEach(function(Nodo) {
                if (Nodo.nodeType !== 1) {
                    return;
                }

                if (Nodo.classList && Nodo.classList.contains('alert')) {
                    PrepararNotificacion(Nodo);
                }

                if (Nodo.querySelectorAll) {
                    Nodo.querySelectorAll('.alert').forEach(function(Alerta) {
                        PrepararNotificacion(Alerta);
                    });
                }
            });
        });
    });

    ObservadorBody.observe(document.body, {
        childList: true,
        subtree: true
    });

});


// ===== BLOQUE COMPARTIDO EXTRAÍDO =====
// =====================================================
// EFECTOS VISUALES LIGEROS
// Agrego una clase al cargar la página y preparo animaciones suaves.
// No afecta la lógica del sistema, solo mejora la experiencia visual.
// =====================================================
document.addEventListener('DOMContentLoaded', function(){
    document.body.classList.add('PageFadeIn');

    const Elementos = document.querySelectorAll('.card, .card-custom, .MainCard, .StatsCard, .CardClase, .alert, .TopBar, .TopHeader');

    if ('IntersectionObserver' in window) {
        const Observador = new IntersectionObserver(function(Entradas){
            Entradas.forEach(function(Entrada){
                if (Entrada.isIntersecting) {
                    Entrada.target.style.animationPlayState = 'running';
                    Observador.unobserve(Entrada.target);
                }
            });
        }, { threshold:0.08 });

        Elementos.forEach(function(Elemento, Indice){
            Elemento.style.animationDelay = Math.min(Indice * 0.035, 0.35) + 's';
            Observador.observe(Elemento);
        });
    }
});


// ===== BLOQUE COMPARTIDO EXTRAÍDO =====
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.modal').forEach(function(Modal){
        if (Modal.parentElement !== document.body) {
            document.body.appendChild(Modal);
        }
    });
});
