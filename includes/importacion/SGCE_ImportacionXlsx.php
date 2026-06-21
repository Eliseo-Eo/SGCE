<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

function ColumnaExcelAIndice($ReferenciaCelda) {
    if (!preg_match('/^([A-Z]+)/i', (string)$ReferenciaCelda, $Match)) {
        return null;
    }

    $Letras = strtoupper($Match[1]);
    $Indice = 0;
    for ($I = 0; $I < strlen($Letras); $I++) {
        $Indice = ($Indice * 26) + (ord($Letras[$I]) - 64);
    }
    return $Indice - 1;
}

function SgceXmlExcelSeguro($Xml) {
    $Xml = (string)$Xml;
    if ($Xml === '') { return ''; }
    $Xml = preg_replace('/^\xEF\xBB\xBF/', '', $Xml);
    $Xml = ltrim($Xml);
    return $Xml;
}

function SgceAbrirXlsxImportacionSeguro(string $RutaArchivo): ZipArchive {
    if (!is_file($RutaArchivo) || !is_readable($RutaArchivo)) {
        throw new RuntimeException('No se pudo leer el archivo XLSX.');
    }

    $Zip = new ZipArchive();
    if ($Zip->open($RutaArchivo) !== true) {
        throw new RuntimeException('No se pudo abrir el archivo Excel. Revisa que sea .xlsx real y no .xls renombrado.');
    }

    $MaxEntradas = 200;
    $MaxTotalDescomprimido = 60 * 1024 * 1024;
    $MaxEntradaDescomprimida = 15 * 1024 * 1024;
    $TotalDescomprimido = 0;

    try {
        if ($Zip->numFiles <= 0 || $Zip->numFiles > $MaxEntradas) {
            throw new RuntimeException('El XLSX contiene una cantidad inválida de archivos internos.');
        }

        for ($I = 0; $I < $Zip->numFiles; $I++) {
            $Nombre = (string)$Zip->getNameIndex($I);
            $Stat = $Zip->statIndex($I);
            $Size = is_array($Stat) ? (int)($Stat['size'] ?? 0) : 0;
            $CompSize = max(1, is_array($Stat) ? (int)($Stat['comp_size'] ?? 1) : 1);
            $TotalDescomprimido += max(0, $Size);

            if ($Nombre === '' || strpos($Nombre, "\0") !== false || str_starts_with($Nombre, '/') || str_contains($Nombre, '../')) {
                throw new RuntimeException('El XLSX contiene rutas internas no permitidas.');
            }
            if ($Size > $MaxEntradaDescomprimida || $TotalDescomprimido > $MaxTotalDescomprimido) {
                throw new RuntimeException('El XLSX excede el tamaño interno permitido.');
            }
            if ($Size > 0 && ($Size / $CompSize) > 120) {
                throw new RuntimeException('El XLSX tiene una proporción de compresión sospechosa.');
            }
        }
    } catch (Throwable $E) {
        $Zip->close();
        throw $E;
    }

    return $Zip;
}

function SgceExtraerAtributoXmlImportacion($Tag, $Atributo) {
    $Atributo = preg_quote((string)$Atributo, '/');
    if (preg_match('/(?:^|\s)' . $Atributo . '=["\']([^"\']*)["\']/i', (string)$Tag, $Match)) {
        return html_entity_decode((string)$Match[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
    return '';
}

function SgceRutaExcelNormalizada($Target) {
    $Target = str_replace('\\', '/', (string)$Target);
    if ($Target === '') { return ''; }
    if (strpos($Target, '/') === 0) {
        $Ruta = ltrim($Target, '/');
    } elseif (strpos($Target, 'xl/') === 0) {
        $Ruta = $Target;
    } else {
        $Ruta = 'xl/' . ltrim($Target, '/');
    }
    $Ruta = preg_replace('#/+#', '/', $Ruta);
    return $Ruta;
}

function SgceConcatenarContenidoEtiquetaXmlImportacion($Xml, $Etiqueta) {
    $Etiqueta = preg_quote((string)$Etiqueta, '/');
    $Texto = '';
    preg_match_all('/<(?:[A-Za-z0-9_]+:)?' . $Etiqueta . '\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?' . $Etiqueta . '>/is', (string)$Xml, $Matches);
    foreach ($Matches[1] ?? [] as $Contenido) {
        $Texto .= html_entity_decode((string)$Contenido, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
    return $Texto;
}

function SgcePrimerContenidoEtiquetaXmlImportacion($Xml, $Etiqueta) {
    $Etiqueta = preg_quote((string)$Etiqueta, '/');
    if (preg_match('/<(?:[A-Za-z0-9_]+:)?' . $Etiqueta . '\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?' . $Etiqueta . '>/is', (string)$Xml, $Match)) {
        return html_entity_decode((string)$Match[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
    return '';
}

function SharedStringsExcel($Zip) {
    $Xml = $Zip->getFromName('xl/sharedStrings.xml');
    if ($Xml === false || trim((string)$Xml) === '') {
        return [];
    }

    $Xml = SgceXmlExcelSeguro($Xml);
    $Textos = [];

    preg_match_all('/<(?:[A-Za-z0-9_]+:)?si\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?si>/is', $Xml, $SiMatches);
    foreach ($SiMatches[1] ?? [] as $SiXml) {
        $Texto = SgceConcatenarContenidoEtiquetaXmlImportacion($SiXml, 't');
        if ($Texto === '') {
            $Texto = html_entity_decode(trim(strip_tags((string)$SiXml)), ENT_QUOTES | ENT_XML1, 'UTF-8');
        }
        $Textos[] = $Texto;
    }

    return $Textos;
}

function SgceNormalizarClaveHojaImportacion($Valor) {
    $Valor = SgceNormalizarMayusculas($Valor);
    $Valor = strtr($Valor, [
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        '_' => ' ', '-' => ' ', '.' => ' ', '/' => ' ', '\\' => ' '
    ]);
    $Valor = preg_replace('/[^A-Z0-9 ]/u', ' ', $Valor);
    $Valor = preg_replace('/\s+/u', ' ', trim((string)$Valor));
    return $Valor;
}

function SgceHojasExcel($Zip) {
    $XmlWorkbookOriginal = $Zip->getFromName('xl/workbook.xml');
    $XmlRelacionesOriginal = $Zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($XmlWorkbookOriginal === false || $XmlRelacionesOriginal === false) {
        return [];
    }

    $XmlWorkbook = SgceXmlExcelSeguro($XmlWorkbookOriginal);
    $XmlRelaciones = SgceXmlExcelSeguro($XmlRelacionesOriginal);

    $MapaRelaciones = [];
    preg_match_all('/<Relationship\b[^>]*\/?>/i', $XmlRelaciones, $RelMatches);
    foreach ($RelMatches[0] ?? [] as $TagRelacion) {
        $Id = SgceExtraerAtributoXmlImportacion($TagRelacion, 'Id');
        $Target = SgceExtraerAtributoXmlImportacion($TagRelacion, 'Target');
        $Tipo = SgceExtraerAtributoXmlImportacion($TagRelacion, 'Type');

        if ($Id === '' || $Target === '') { continue; }
        if ($Tipo !== '' && stripos($Tipo, '/worksheet') === false) { continue; }

        $Ruta = SgceRutaExcelNormalizada($Target);
        if ($Ruta !== '') {
            $MapaRelaciones[$Id] = $Ruta;
        }
    }

    if (!$MapaRelaciones) {
        return [];
    }

    $Hojas = [];
    preg_match_all('/<(?:[A-Za-z0-9_]+:)?sheet\b[^>]*\/?>/i', $XmlWorkbook, $SheetMatches);
    foreach ($SheetMatches[0] ?? [] as $TagHoja) {
        $Nombre = SgceExtraerAtributoXmlImportacion($TagHoja, 'name');
        $IdRelacion = SgceExtraerAtributoXmlImportacion($TagHoja, 'r:id');
        if ($IdRelacion === '') {
            $IdRelacion = SgceExtraerAtributoXmlImportacion($TagHoja, 'id');
        }

        $Ruta = $MapaRelaciones[$IdRelacion] ?? '';
        if ($Nombre !== '' && $Ruta !== '') {
            $Hojas[] = [
                'Nombre' => $Nombre,
                'Clave' => SgceNormalizarClaveHojaImportacion($Nombre),
                'Ruta' => $Ruta,
            ];
        }
    }

    return $Hojas;
}

function SgceResolverHojaExcelImportacion($Hojas, $Preferidas = []) {
    if (!$Hojas) { return ''; }
    if (count($Hojas) === 1) { return (string)$Hojas[0]['Ruta']; }

    $PreferidasNormalizadas = [];
    foreach ($Preferidas as $Preferida) {
        $Clave = SgceNormalizarClaveHojaImportacion($Preferida);
        if ($Clave !== '') { $PreferidasNormalizadas[] = $Clave; }
    }

    foreach ($PreferidasNormalizadas as $Preferida) {
        foreach ($Hojas as $Hoja) {
            if ((string)$Hoja['Clave'] === $Preferida) { return (string)$Hoja['Ruta']; }
        }
    }

    foreach ($PreferidasNormalizadas as $Preferida) {
        foreach ($Hojas as $Hoja) {
            $ClaveHoja = (string)$Hoja['Clave'];
            if ($ClaveHoja === '') { continue; }
            if (strpos($ClaveHoja, $Preferida) === 0 || strpos($Preferida, $ClaveHoja) === 0) {
                return (string)$Hoja['Ruta'];
            }
        }
    }

    foreach ($PreferidasNormalizadas as $Preferida) {
        foreach ($Hojas as $Hoja) {
            $ClaveHoja = (string)$Hoja['Clave'];
            if ($ClaveHoja !== '' && strpos($ClaveHoja, $Preferida) !== false) {
                return (string)$Hoja['Ruta'];
            }
        }
    }

    $NombresDisponibles = implode(', ', array_map(static fn($Hoja) => (string)$Hoja['Nombre'], $Hojas));
    $NombresEsperados = $Preferidas ? implode(', ', $Preferidas) : 'la pestaña correspondiente';
    throw new RuntimeException('El Excel tiene varias pestañas, pero no se encontró la pestaña esperada: ' . $NombresEsperados . '. Pestañas disponibles: ' . $NombresDisponibles . '.');
}

function LeerFilasXlsx($RutaArchivo, $NombresHojaPreferidos = []) {
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('El servidor requiere la extensión PHP zip para leer archivos Excel .xlsx. También puedes usar CSV.');
    }

    $Zip = SgceAbrirXlsxImportacionSeguro((string)$RutaArchivo);

    $SharedStrings = SharedStringsExcel($Zip);
    $HojasExcel = SgceHojasExcel($Zip);
    $NombreHoja = SgceResolverHojaExcelImportacion($HojasExcel, $NombresHojaPreferidos);
    if ($NombreHoja === '') {
        $Zip->close();
        throw new RuntimeException('El archivo Excel no contiene hojas válidas o SGCE no pudo leer el índice interno del libro. Guarda el archivo como .xlsx normal e intenta de nuevo.');
    }

    $XmlHoja = $Zip->getFromName($NombreHoja);
    $Zip->close();
    if ($XmlHoja === false || trim((string)$XmlHoja) === '') {
        throw new RuntimeException('No se pudo leer la hoja seleccionada del Excel.');
    }

    $XmlHoja = SgceXmlExcelSeguro($XmlHoja);
    if (stripos($XmlHoja, '<worksheet') === false && !preg_match('/<[^>]*:worksheet\b/i', $XmlHoja)) {
        throw new RuntimeException('La hoja de Excel no tiene formato válido. Hoja detectada: ' . $NombreHoja . '.');
    }

    $MaxFilasImportacion = 10000;
    $MaxColumnasImportacion = 80;
    $Filas = [];
    preg_match_all('/<(?:[A-Za-z0-9_]+:)?row\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?row>/is', $XmlHoja, $RowMatches);
    foreach ($RowMatches[1] ?? [] as $ContenidoFila) {
        $Fila = [];
        $IndiceAutomatico = 0;

        preg_match_all('/<(?:[A-Za-z0-9_]+:)?c\b([^>]*)>(.*?)<\/(?:[A-Za-z0-9_]+:)?c>/is', $ContenidoFila, $CellMatches, PREG_SET_ORDER);
        foreach ($CellMatches as $CeldaMatch) {
            $AtributosCelda = (string)($CeldaMatch[1] ?? '');
            $ContenidoCelda = (string)($CeldaMatch[2] ?? '');
            $Referencia = SgceExtraerAtributoXmlImportacion($AtributosCelda, 'r');
            $Tipo = SgceExtraerAtributoXmlImportacion($AtributosCelda, 't');
            $Indice = ColumnaExcelAIndice($Referencia);
            if ($Indice === null) { $Indice = $IndiceAutomatico; }
            $IndiceAutomatico = max($IndiceAutomatico, $Indice + 1);

            if ($Tipo === 'inlineStr') {
                $Valor = SgceConcatenarContenidoEtiquetaXmlImportacion($ContenidoCelda, 't');
            } else {
                $Valor = SgcePrimerContenidoEtiquetaXmlImportacion($ContenidoCelda, 'v');
                if ($Tipo === 's') {
                    $Valor = $SharedStrings[(int)$Valor] ?? '';
                } elseif ($Tipo === 'b') {
                    $Valor = $Valor === '1' ? 'SI' : 'NO';
                } elseif ($Valor === '' && stripos($ContenidoCelda, '<is') !== false) {
                    $Valor = SgceConcatenarContenidoEtiquetaXmlImportacion($ContenidoCelda, 't');
                }
            }

            $Fila[$Indice] = trim((string)$Valor);
        }

        if ($Fila) {
            ksort($Fila);
            $Maximo = max(array_keys($Fila));
            if ($Maximo >= $MaxColumnasImportacion) {
                throw new RuntimeException('El XLSX supera el máximo de columnas permitido para importación.');
            }
            $Normalizada = [];
            for ($I = 0; $I <= $Maximo; $I++) {
                $Normalizada[] = $Fila[$I] ?? '';
            }
            if (count(array_filter($Normalizada, static fn($Valor) => trim((string)$Valor) !== '')) > 0) {
                $Filas[] = $Normalizada;
                if (count($Filas) > $MaxFilasImportacion) {
                    throw new RuntimeException('El XLSX supera el máximo de filas permitido para importación.');
                }
            }
        }
    }

    return $Filas;
}

