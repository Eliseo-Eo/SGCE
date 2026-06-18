/* SGCE 1.0.185 - Módulo compartido: theme.js */
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
