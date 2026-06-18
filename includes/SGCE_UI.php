<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }


function SgceVersion(): string {
    return defined('SGCE_VERSION') ? (string)SGCE_VERSION : '1.0.185';
}


function SgceAssetUrl(string $Ruta): string {
    $Ruta = trim($Ruta);
    if ($Ruta === '') { return ''; }
    if (preg_match('/^https?:\/\//i', $Ruta)) { return $Ruta; }
    $Separador = str_contains($Ruta, '?') ? '&' : '?';
    return $Ruta . $Separador . 'v=' . rawurlencode(SgceVersion());
}


function SgceCss(string $Ruta): string {
    $Url = htmlspecialchars(SgceAssetUrl($Ruta), ENT_QUOTES, 'UTF-8');
    return '<link rel="stylesheet" href="' . $Url . '">';
}


function SgceJs(string $Ruta): string {
    $Url = htmlspecialchars(SgceAssetUrl($Ruta), ENT_QUOTES, 'UTF-8');
    return '<script src="' . $Url . '"></script>';
}

function SgceColorInstitucional($Pdo) {
    $Config = SgceObtenerConfiguracion($Pdo);
    return SgceNormalizarColorHex($Config['ColorInstitucional'] ?? '#97051E');
}


function SgceEstilosTema($Pdo) {
    $Base = SgceColorInstitucional($Pdo);
    $Oscuro = SgceColorAjustar($Base, -22);
    $Profundo = SgceColorAjustar($Base, -48);
    $Suave = SgceColorAjustar($Base, 84);
    $Claro = SgceColorAjustar($Base, 32);
    [$R, $G, $B] = SgceColorRgb($Base);
    return '<style id="SgceTemaInstitucional">:root{--SgceGuinda:' . $Base . ';--SgceGuindaRGB:' . $R . ',' . $G . ',' . $B . ';--SgceGuindaOscuro:' . $Oscuro . ';--SgceGuindaProfundo:' . $Profundo . ';--SgceGuindaSuave:' . $Suave . ';--SgceGuindaClaro:' . $Claro . ';--SgceSombraGuinda:0 12px 26px rgba(' . $R . ',' . $G . ',' . $B . ',.14);}</style>';
}


function SgcePageSizeSeguro($Valor, $Default = 50, $Min = 5, $Max = 100) {
    $Valor = (int)$Valor;
    if ($Valor <= 0) { $Valor = (int)$Default; }
    return max((int)$Min, min((int)$Max, $Valor));
}


function SgcePaginaActual($Nombre, $Default = 1) {
    $Valor = isset($_GET[$Nombre]) ? (int)$_GET[$Nombre] : (int)$Default;
    return max(1, $Valor);
}


function SgceLimitOffset($Pagina, $PorPagina) {
    $Pagina = max(1, (int)$Pagina);
    $PorPagina = max(1, min(100, (int)$PorPagina));
    return [($Pagina - 1) * $PorPagina, $PorPagina];
}


function SgcePagerResumenTexto($PaginaActual, $TotalRegistros, $PorPagina, $FilasEnPagina = null) {
    $TotalRegistros = max(0, (int)$TotalRegistros);
    $PorPagina = max(1, (int)$PorPagina);

    if ($TotalRegistros <= 0) {
        return 'Mostrando 0 de 0 registro(s).';
    }

    $TotalPaginas = max(1, (int)ceil($TotalRegistros / $PorPagina));
    $PaginaActual = min(max(1, (int)$PaginaActual), $TotalPaginas);
    $Inicio = (($PaginaActual - 1) * $PorPagina) + 1;

    if ($FilasEnPagina !== null) {
        $FilasEnPagina = max(0, (int)$FilasEnPagina);
        $Fin = min($TotalRegistros, max($Inicio, $Inicio + $FilasEnPagina - 1));
    } else {
        $Fin = min($TotalRegistros, $Inicio + $PorPagina - 1);
    }

    return 'Mostrando ' . $Inicio . '-' . $Fin . ' de ' . $TotalRegistros . ' registro(s).';
}


function SgceRenderPager($NombrePagina, $PaginaActual, $TotalRegistros, $PorPagina, $ParametrosExtra = [], $MostrarResumen = true) {
    $TotalRegistros = max(0, (int)$TotalRegistros);
    $PorPagina = max(1, (int)$PorPagina);
    $TotalPaginas = max(1, (int)ceil($TotalRegistros / $PorPagina));
    $PaginaActual = min(max(1, (int)$PaginaActual), $TotalPaginas);

    if ($TotalPaginas <= 1) {
        return $MostrarResumen ? '<div class="SgcePagerServer"><div class="SgcePagerInfo">' . HGlobal(SgcePagerResumenTexto($PaginaActual, $TotalRegistros, $PorPagina)) . '</div></div>' : '';
    }

    $Base = $_GET;
    foreach ($ParametrosExtra as $K => $V) {
        if ($V === null) { unset($Base[$K]); }
        else { $Base[$K] = $V; }
    }

    $CrearUrl = static function(int $Pagina) use ($Base, $NombrePagina): string {
        $Params = $Base;
        $Params[$NombrePagina] = max(1, $Pagina);
        return '?' . HGlobal(http_build_query($Params));
    };

    $CrearItem = static function(int $Pagina, string $TextoHtml, bool $Disabled = false, bool $Active = false, string $AriaLabel = '') use ($CrearUrl): string {
        $Clase = 'page-item' . ($Disabled ? ' disabled' : '') . ($Active ? ' active' : '');
        $Atributos = $AriaLabel !== '' ? ' aria-label="' . HGlobal($AriaLabel) . '"' : '';
        if ($Disabled || $Active) {
            $Extra = $Active ? ' aria-current="page"' : ' aria-disabled="true" tabindex="-1"';
            return '<li class="' . $Clase . '"><span class="page-link"' . $Atributos . $Extra . '>' . $TextoHtml . '</span></li>';
        }
        return '<li class="' . $Clase . '"><a class="page-link" href="' . $CrearUrl($Pagina) . '"' . $Atributos . '>' . $TextoHtml . '</a></li>';
    };

    $CrearPuntos = static function(): string {
        return '<li class="page-item disabled SgcePagerDots"><span class="page-link" aria-hidden="true">…</span></li>';
    };

    $Inicio = max(1, $PaginaActual - 2);
    $Fin = min($TotalPaginas, $PaginaActual + 2);

    if ($PaginaActual <= 3) {
        $Fin = min($TotalPaginas, 5);
    }
    if ($PaginaActual >= $TotalPaginas - 2) {
        $Inicio = max(1, $TotalPaginas - 4);
    }

    $Html = '<nav class="SgcePagerServer" aria-label="Paginación"><ul class="pagination pagination-sm justify-content-center flex-wrap gap-1 mb-0">';
    $Html .= $CrearItem(1, '&laquo;', $PaginaActual <= 1, false, 'Primera página');
    $Html .= $CrearItem(max(1, $PaginaActual - 1), '&lsaquo;', $PaginaActual <= 1, false, 'Página anterior');

    if ($Inicio > 1) {
        $Html .= $CrearItem(1, '1', false, $PaginaActual === 1, 'Página 1');
        if ($Inicio > 2) { $Html .= $CrearPuntos(); }
    }

    for ($I = $Inicio; $I <= $Fin; $I++) {
        $Html .= $CrearItem($I, (string)$I, false, $I === $PaginaActual, 'Página ' . $I);
    }

    if ($Fin < $TotalPaginas) {
        if ($Fin < $TotalPaginas - 1) { $Html .= $CrearPuntos(); }
        $Html .= $CrearItem($TotalPaginas, (string)$TotalPaginas, false, $PaginaActual === $TotalPaginas, 'Página ' . $TotalPaginas);
    }

    $Html .= $CrearItem(min($TotalPaginas, $PaginaActual + 1), '&rsaquo;', $PaginaActual >= $TotalPaginas, false, 'Página siguiente');
    $Html .= $CrearItem($TotalPaginas, '&raquo;', $PaginaActual >= $TotalPaginas, false, 'Última página');
    $Html .= '</ul>';

    if ($MostrarResumen) {
        $Html .= '<div class="SgcePagerInfo">' . HGlobal(SgcePagerResumenTexto($PaginaActual, $TotalRegistros, $PorPagina)) . '</div>';
    }

    $Html .= '</nav>';
    return $Html;
}

function SgceEtiquetaEtapaPorTipoTexto(string $Tipo): string {
    $Tipo = SgceTipoPeriodizacionValido($Tipo);
    return match ($Tipo) {
        'SEMESTRAL' => 'Semestre',
        'CUATRIMESTRAL' => 'Cuatrimestre',
        'TRIMESTRAL' => 'Trimestre',
        'MODULAR' => 'Módulo',
        default => 'Año',
    };
}


function SgceEtiquetaEtapaActual(PDO $Pdo, int $OfertaId = 0): string {
    if ($OfertaId <= 0) { $Oferta = SgceOfertaActiva($Pdo); }
    else { $Stmt = $Pdo->prepare('SELECT EtiquetaEtapa, TipoPeriodizacion FROM OfertasEducativas WHERE Id = ? LIMIT 1'); $Stmt->execute([$OfertaId]); $Oferta = $Stmt->fetch() ?: []; }
    $Etiqueta = trim((string)($Oferta['EtiquetaEtapa'] ?? ''));
    $TipoOferta = (string)($Oferta['TipoPeriodizacion'] ?? 'ANUAL');
    if ($Etiqueta !== '') {
        $EtiquetaNormalizada = SgceNormalizarMayusculas($Etiqueta);
        if ($EtiquetaNormalizada === 'GRADO' && SgceTipoPeriodizacionValido($TipoOferta) === 'ANUAL') {
            return 'Año';
        }
        $Etiqueta = function_exists('mb_strtolower') ? mb_strtolower($Etiqueta, 'UTF-8') : strtolower($Etiqueta);
        return function_exists('mb_convert_case') ? mb_convert_case($Etiqueta, MB_CASE_TITLE, 'UTF-8') : ucfirst($Etiqueta);
    }
    return SgceEtiquetaEtapaPorTipoTexto($TipoOferta);
}


function SgceEtiquetaEtapaAcademica(int $Orden, string $Tipo): string {
    $Orden = max(1, $Orden);
    $OrdenTexto = $Orden . '°';
    $Tipo = SgceTipoPeriodizacionValido($Tipo);
    if ($Tipo === 'SEMESTRAL') { return $OrdenTexto . ' SEMESTRE'; }
    if ($Tipo === 'CUATRIMESTRAL') { return $OrdenTexto . ' CUATRIMESTRE'; }
    if ($Tipo === 'TRIMESTRAL') { return $OrdenTexto . ' TRIMESTRE'; }
    if ($Tipo === 'MODULAR') { return $OrdenTexto . ' MÓDULO'; }
    return $OrdenTexto . ' AÑO';
}


function SgceEtapaNombreDesdeTextoVisual(string $Nombre, string $TipoFallback = 'ANUAL'): string {
    $Nombre = trim(preg_replace('/\s+/u', ' ', $Nombre));
    if ($Nombre === '') { return ''; }

    $Normal = SgceNormalizarMayusculas($Nombre);
    $TipoPorEtiqueta = [
        'AÑO' => 'ANUAL',
        'ANIO' => 'ANUAL',
        'SEMESTRE' => 'SEMESTRAL',
        'CUATRIMESTRE' => 'CUATRIMESTRAL',
        'TRIMESTRE' => 'TRIMESTRAL',
        'MÓDULO' => 'MODULAR',
        'MODULO' => 'MODULAR',
    ];

    if (preg_match('/^(AÑO|ANIO|SEMESTRE|CUATRIMESTRE|TRIMESTRE|MÓDULO|MODULO)\s+(\d+)/u', $Normal, $M)) {
        return SgceEtiquetaEtapaAcademica((int)$M[2], $TipoPorEtiqueta[$M[1]] ?? $TipoFallback);
    }

    if (preg_match('/^(\d+)\s*°?\s*(AÑO|ANIO|SEMESTRE|CUATRIMESTRE|TRIMESTRE|MÓDULO|MODULO)?/u', $Normal, $M)) {
        $Tipo = !empty($M[2]) ? ($TipoPorEtiqueta[$M[2]] ?? $TipoFallback) : $TipoFallback;
        return SgceEtiquetaEtapaAcademica((int)$M[1], $Tipo);
    }

    return $Nombre;
}


function SgceEtapaNombreVisual(array $Fila, string $TipoFallback = 'ANUAL'): string {
    $Orden = (int)($Fila['EtapaOrden'] ?? $Fila['Orden'] ?? 0);
    $Tipo = (string)($Fila['TipoPeriodizacion'] ?? $TipoFallback);
    if ($Orden > 0) {
        return SgceEtiquetaEtapaAcademica($Orden, $Tipo);
    }
    $Nombre = trim((string)($Fila['EtapaNombre'] ?? $Fila['Nombre'] ?? $Fila['Grado'] ?? ''));
    $NombreVisual = SgceEtapaNombreDesdeTextoVisual($Nombre, $TipoFallback);
    return $NombreVisual !== '' ? $NombreVisual : SgceEtiquetaEtapaAcademica(1, $TipoFallback);
}


function SgceGrupoNombreVisual(array $Fila, string $TipoFallback = 'ANUAL'): string {
    $Etapa = SgceEtapaNombreVisual($Fila, $TipoFallback);
    $Grupo = trim((string)($Fila['Grupo'] ?? ''));
    $Turno = trim((string)($Fila['Turno'] ?? ''));
    return trim($Etapa . ($Grupo !== '' ? ' "' . $Grupo . '"' : '') . ($Turno !== '' ? ' ' . $Turno : ''));
}


function SgceEtiquetaEtapaPorTipo(string $Tipo): string {
    $Tipo = SgceTipoPeriodizacionValido($Tipo);
    if ($Tipo === 'SEMESTRAL') { return 'SEMESTRE'; }
    if ($Tipo === 'CUATRIMESTRAL') { return 'CUATRIMESTRE'; }
    if ($Tipo === 'TRIMESTRAL') { return 'TRIMESTRE'; }
    if ($Tipo === 'MODULAR') { return 'MÓDULO'; }
    return 'AÑO';
}

