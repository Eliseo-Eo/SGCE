<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

/*
    Procesa importaciones masivas de catálogos escolares desde CSV o Excel (.xlsx).
    Valida archivo, formato, duplicados y datos obligatorios antes de guardar en la base.
*/

require_once dirname(__DIR__) . '/config/Conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$UserSession = VerificarSesionCookie($Pdo);

if (!$UserSession || !SgcePuedeImportarCatalogos($UserSession)) {
    header('Location: index.php');
    exit;
}

RequerirCsrfPost();

function RedirectAdminImportar($Tab, $Mensaje, $EsError = false) {
    $_SESSION['Mensaje'] = $Mensaje;
    if ($EsError) {
        $_SESSION['MensajeTipo'] = 'danger';
    }
    header("Location: Admin.php?Tab=" . urlencode(SgceTabAdminPermitida($Tab)));
    exit;
}

function BomStrip($Handle) {
    if (fgets($Handle, 4) !== "\xEF\xBB\xBF") {
        rewind($Handle);
    }
}

function ExtensionImportacion($NombreArchivo) {
    return strtolower(pathinfo((string)$NombreArchivo, PATHINFO_EXTENSION));
}

function ValidarArchivoImportacionSubido($Archivo) {
    if (!isset($Archivo) || !is_array($Archivo) || ($Archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return 'Error al subir el archivo.';
    }

    if (($Archivo['size'] ?? 0) <= 0) {
        return 'El archivo está vacío.';
    }

    if (($Archivo['size'] ?? 0) > 10 * 1024 * 1024) {
        return 'El archivo no debe pesar más de 10 MB.';
    }

    $Extension = ExtensionImportacion($Archivo['name'] ?? '');
    if (!in_array($Extension, ['csv', 'xlsx'], true)) {
        return 'Solo se permiten archivos CSV o Excel .xlsx.';
    }

    $MimePermitidos = [
        'text/plain',
        'text/csv',
        'application/csv',
        'application/vnd.ms-excel',
        'application/octet-stream',
        'application/zip',
        'application/x-zip',
        'application/x-zip-compressed',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    if (function_exists('finfo_open') && is_uploaded_file($Archivo['tmp_name'] ?? '')) {
        $Finfo = finfo_open(FILEINFO_MIME_TYPE);
        $Mime = $Finfo ? finfo_file($Finfo, $Archivo['tmp_name']) : '';
        if ($Finfo) { finfo_close($Finfo); }
        if ($Mime !== '' && !in_array($Mime, $MimePermitidos, true)) {
            return 'El archivo no parece ser CSV o Excel válido.';
        }
    }

    return '';
}

function LeerFilasCsv($RutaArchivo) {
    $Handle = fopen($RutaArchivo, 'r');
    if (!$Handle) {
        throw new RuntimeException('No se pudo leer el archivo CSV.');
    }

    BomStrip($Handle);
    $Filas = [];
    while (($Data = fgetcsv($Handle, 4000, ',')) !== false) {
        $Filas[] = $Data;
    }
    fclose($Handle);
    return $Filas;
}

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

function TextoNodosExcel($Nodos) {
    $Texto = '';
    foreach ($Nodos ?: [] as $Nodo) {
        $Texto .= (string)$Nodo;
    }
    return $Texto;
}

function SharedStringsExcel($Zip) {
    $Xml = $Zip->getFromName('xl/sharedStrings.xml');
    if ($Xml === false || trim($Xml) === '') {
        return [];
    }

    $Documento = simplexml_load_string($Xml);
    if (!$Documento) {
        return [];
    }

    $Ns = $Documento->getNamespaces(true);
    $MainNs = $Ns[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $Documento->registerXPathNamespace('x', $MainNs);

    $Textos = [];
    foreach ($Documento->xpath('//x:si') ?: [] as $Si) {
        $Si->registerXPathNamespace('x', $MainNs);
        $Textos[] = TextoNodosExcel($Si->xpath('.//x:t'));
    }
    return $Textos;
}

function PrimeraHojaExcel($Zip) {
    $Hojas = [];
    for ($I = 0; $I < $Zip->numFiles; $I++) {
        $Nombre = $Zip->getNameIndex($I);
        if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $Nombre)) {
            $Hojas[] = $Nombre;
        }
    }
    natsort($Hojas);
    return $Hojas ? reset($Hojas) : '';
}

function LeerFilasXlsx($RutaArchivo) {
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('El servidor requiere la extensión PHP zip para leer archivos Excel .xlsx. También puedes usar CSV.');
    }
    if (!function_exists('simplexml_load_string')) {
        throw new RuntimeException('El servidor requiere la extensión PHP SimpleXML para leer archivos Excel .xlsx. También puedes usar CSV.');
    }

    $Zip = new ZipArchive();
    if ($Zip->open($RutaArchivo) !== true) {
        throw new RuntimeException('No se pudo abrir el archivo Excel.');
    }

    $SharedStrings = SharedStringsExcel($Zip);
    $NombreHoja = PrimeraHojaExcel($Zip);
    if ($NombreHoja === '') {
        $Zip->close();
        throw new RuntimeException('El archivo Excel no contiene hojas válidas.');
    }

    $XmlHoja = $Zip->getFromName($NombreHoja);
    $Zip->close();
    if ($XmlHoja === false || trim($XmlHoja) === '') {
        throw new RuntimeException('No se pudo leer la primera hoja del Excel.');
    }

    $Hoja = simplexml_load_string($XmlHoja);
    if (!$Hoja) {
        throw new RuntimeException('La hoja de Excel no tiene formato válido.');
    }

    $Ns = $Hoja->getNamespaces(true);
    $MainNs = $Ns[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $Hoja->registerXPathNamespace('x', $MainNs);

    $Filas = [];
    foreach ($Hoja->xpath('//x:sheetData/x:row') ?: [] as $FilaXml) {
        $FilaXml->registerXPathNamespace('x', $MainNs);
        $Fila = [];
        $IndiceAutomatico = 0;

        foreach ($FilaXml->xpath('x:c') ?: [] as $Celda) {
            $Celda->registerXPathNamespace('x', $MainNs);
            $Atributos = $Celda->attributes();
            $Referencia = (string)($Atributos['r'] ?? '');
            $Tipo = (string)($Atributos['t'] ?? '');
            $Indice = ColumnaExcelAIndice($Referencia);
            if ($Indice === null) { $Indice = $IndiceAutomatico; }
            $IndiceAutomatico = max($IndiceAutomatico, $Indice + 1);

            if ($Tipo === 'inlineStr') {
                $Valor = TextoNodosExcel($Celda->xpath('.//x:is//x:t'));
            } else {
                $V = $Celda->xpath('x:v');
                $Valor = $V ? (string)$V[0] : '';
                if ($Tipo === 's') {
                    $Valor = $SharedStrings[(int)$Valor] ?? '';
                } elseif ($Tipo === 'b') {
                    $Valor = $Valor === '1' ? 'SI' : 'NO';
                }
            }

            $Fila[$Indice] = trim((string)$Valor);
        }

        if ($Fila) {
            ksort($Fila);
            $Maximo = max(array_keys($Fila));
            $Normalizada = [];
            for ($I = 0; $I <= $Maximo; $I++) {
                $Normalizada[] = $Fila[$I] ?? '';
            }
            if (count(array_filter($Normalizada, static fn($Valor) => trim((string)$Valor) !== '')) > 0) {
                $Filas[] = $Normalizada;
            }
        }
    }

    return $Filas;
}

function LeerFilasImportacionSubida($Archivo) {
    $Ruta = $Archivo['tmp_name'] ?? '';
    $Extension = ExtensionImportacion($Archivo['name'] ?? '');

    if ($Extension === 'csv') {
        return LeerFilasCsv($Ruta);
    }
    if ($Extension === 'xlsx') {
        return LeerFilasXlsx($Ruta);
    }

    throw new RuntimeException('Formato de archivo no permitido.');
}

function EsFilaVacia($Data) {
    foreach ($Data as $Valor) {
        if (trim((string)$Valor) !== '') { return false; }
    }
    return true;
}

function EsEncabezadoAlumno($Data) {
    $Primero = SgceNormalizarMayusculas($Data[0] ?? '');
    return in_array($Primero, ['NOMBRE', 'NOMBRE COMPLETO', 'ALUMNO', 'ALUMNOS'], true);
}

function EsEncabezadoDocente($Data) {
    $Primero = SgceNormalizarMayusculas($Data[0] ?? '');
    $Segundo = SgceNormalizarMayusculas($Data[1] ?? '');
    return in_array($Primero, ['NOMBRE', 'NOMBRE COMPLETO', 'DOCENTE', 'MAESTRO'], true)
        || in_array($Segundo, ['USUARIO', 'USERNAME'], true);
}

function EsEncabezadoGrupo($Data) {
    $PrimerCampo = SgceNormalizarMayusculas($Data[0] ?? '');
    $SegundoCampo = SgceNormalizarMayusculas($Data[1] ?? '');
    $TercerCampo = SgceNormalizarMayusculas($Data[2] ?? '');
    return in_array($PrimerCampo, ['GRADO', 'GRADOS'], true)
        || in_array($SegundoCampo, ['GRUPO', 'GRUPOS'], true)
        || in_array($TercerCampo, ['TURNO', 'TURNOS'], true);
}

function UsuariosExistentesPorUsername($Pdo, $Usernames) {
    $Usernames = array_values(array_unique(array_filter(array_map('strval', $Usernames), static fn($Valor) => trim($Valor) !== '')));
    if (!$Usernames) { return []; }

    $Existentes = [];
    foreach (array_chunk($Usernames, 250) as $Chunk) {
        $Placeholders = implode(',', array_fill(0, count($Chunk), '?'));
        $Stmt = $Pdo->prepare("SELECT Username FROM Usuarios WHERE Username IN ($Placeholders)");
        $Stmt->execute($Chunk);
        foreach ($Stmt->fetchAll(PDO::FETCH_COLUMN) as $Username) {
            $Existentes[(string)$Username] = true;
        }
    }

    return $Existentes;
}

function HashPasswordImportacion(&$Cache, $Password) {
    $Password = (string)$Password;
    if (!isset($Cache[$Password])) {
        $Cache[$Password] = SgcePasswordHash($Password);
    }
    return $Cache[$Password];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Admin.php');
    exit;
}

$Tab = SgceTabAdminPermitida($_POST['Tab'] ?? 'maestros');

// =====================================================
// IMPORTAR ALUMNOS
// =====================================================
if (isset($_POST['ImportarAlumnos'])) {

    $GrupoId = intval($_POST['GrupoId'] ?? 0);

    if ($GrupoId <= 0) {
        RedirectAdminImportar('alumnos', 'Por favor, selecciona un grupo válido.', true);
    }

    $ErrorArchivo = ValidarArchivoImportacionSubido($_FILES['CsvAlumnos'] ?? null);
    if ($ErrorArchivo !== '') {
        RedirectAdminImportar('alumnos', $ErrorArchivo, true);
    }

    try {
        $Filas = LeerFilasImportacionSubida($_FILES['CsvAlumnos']);
    } catch (Exception $E) {
        error_log('SGCE importación alumnos: ' . $E->getMessage());
        RedirectAdminImportar('alumnos', $E->getMessage(), true);
    }

    $Insertados = 0;
    $Duplicados = 0;
    $Invalidos = 0;
    $Saltados = 0;

    $CheckGrupo = $Pdo->prepare("SELECT COUNT(*) FROM Grupos WHERE Id = ? AND Activo = 1");
    $CheckGrupo->execute([$GrupoId]);

    if ((int)$CheckGrupo->fetchColumn() <= 0) {
        RedirectAdminImportar('alumnos', 'El grupo seleccionado no existe.', true);
    }

    $Check = $Pdo->prepare("SELECT COUNT(*) FROM Alumnos WHERE NombreCompleto = ? AND GrupoId = ?");
    $Stmt = $Pdo->prepare("INSERT INTO Alumnos (NombreCompleto, GrupoId) VALUES (?, ?)");

    try {
        $Pdo->beginTransaction();

        foreach ($Filas as $Data) {
            if (EsFilaVacia($Data)) { continue; }
            if (EsEncabezadoAlumno($Data)) { $Saltados++; continue; }

            $Nombre = SgceNormalizarNombre($Data[0] ?? '');

            if ($Nombre === '') {
                $Invalidos++;
                continue;
            }

            $Check->execute([$Nombre, $GrupoId]);
            if ((int)$Check->fetchColumn() > 0) {
                $Duplicados++;
                continue;
            }

            $Stmt->execute([$Nombre, $GrupoId]);
            $Insertados++;
        }

        $Pdo->commit();
        RegistrarBitacora($Pdo, $UserSession, 'IMPORTAR_ALUMNOS', 'Alumnos', null, 'ALUMNOS IMPORTADOS: ' . $Insertados);

    } catch (Exception $E) {
        if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
        error_log('SGCE importación alumnos: ' . $E->getMessage());
        RedirectAdminImportar('alumnos', 'Error al importar los alumnos.', true);
    }

    $Mensaje = "Se importaron $Insertados alumnos correctamente.";
    if ($Duplicados > 0) { $Mensaje .= " ($Duplicados duplicados omitidos)"; }
    if ($Invalidos > 0) { $Mensaje .= " ($Invalidos registros inválidos omitidos)"; }
    if ($Saltados > 0) { $Mensaje .= " ($Saltados encabezados omitidos)"; }

    RedirectAdminImportar('alumnos', $Mensaje);
}

// =====================================================
// IMPORTAR GRUPOS
// =====================================================
if (isset($_POST['ImportarGrupos'])) {

    $ErrorArchivo = ValidarArchivoImportacionSubido($_FILES['CsvGrupos'] ?? null);
    if ($ErrorArchivo !== '') {
        RedirectAdminImportar('grupos', $ErrorArchivo, true);
    }

    try {
        $Filas = LeerFilasImportacionSubida($_FILES['CsvGrupos']);
    } catch (Exception $E) {
        error_log('SGCE importación grupos: ' . $E->getMessage());
        RedirectAdminImportar('grupos', $E->getMessage(), true);
    }

    $Insertados = 0;
    $Duplicados = 0;
    $Invalidos = 0;
    $Saltados = 0;

    $Check = $Pdo->prepare("SELECT COUNT(*) FROM Grupos WHERE Grado = ? AND Grupo = ? AND Turno = ?");
    $Stmt = $Pdo->prepare("INSERT INTO Grupos (Grado, Grupo, Turno, Activo) VALUES (?, ?, ?, 1)");

    try {
        $Pdo->beginTransaction();

        foreach ($Filas as $Data) {
            $Data = array_map(static fn($Valor) => trim((string)$Valor), $Data);

            if (EsFilaVacia($Data)) { continue; }

            if (count(array_filter($Data, static fn($Valor) => trim((string)$Valor) !== '')) < 3 && isset($Data[0])) {
                $Partes = preg_split('/[;\s]+/', trim($Data[0]));
                $Data = array_values(array_filter($Partes ?: [], static fn($Valor) => trim((string)$Valor) !== ''));
            }

            if (count($Data) < 3) {
                $Invalidos++;
                continue;
            }

            if (EsEncabezadoGrupo($Data)) {
                $Saltados++;
                continue;
            }

            $Grado = trim($Data[0]);
            $Grupo = SgceNormalizarGrupo($Data[1]);
            $Turno = SgceNormalizarTurno($Data[2]);

            if (!SgceValidarGrado($Grado) || $Grupo === '' || $Turno === '') {
                $Invalidos++;
                continue;
            }

            $Check->execute([$Grado, $Grupo, $Turno]);
            if ((int)$Check->fetchColumn() > 0) {
                $Duplicados++;
                continue;
            }

            $Stmt->execute([$Grado, $Grupo, $Turno]);
            $Insertados++;
        }

        $Pdo->commit();
        RegistrarBitacora($Pdo, $UserSession, 'IMPORTAR_GRUPOS', 'Grupos', null, 'GRUPOS IMPORTADOS: ' . $Insertados);

    } catch (Exception $E) {
        if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
        error_log('SGCE importación grupos: ' . $E->getMessage());
        RedirectAdminImportar('grupos', 'Error al importar los grupos.', true);
    }

    $Mensaje = "Se importaron $Insertados grupos correctamente.";
    if ($Duplicados > 0) { $Mensaje .= " ($Duplicados duplicados omitidos)"; }
    if ($Invalidos > 0) { $Mensaje .= " ($Invalidos registros inválidos omitidos)"; }
    if ($Saltados > 0) { $Mensaje .= " ($Saltados encabezados omitidos)"; }

    RedirectAdminImportar('grupos', $Mensaje);
}

// =====================================================
// IMPORTAR DOCENTES
// =====================================================
if (isset($_POST['ImportarDocentes'])) {

    $ErrorArchivo = ValidarArchivoImportacionSubido($_FILES['CsvDocentes'] ?? null);
    if ($ErrorArchivo !== '') {
        RedirectAdminImportar('maestros', $ErrorArchivo, true);
    }

    try {
        $Filas = LeerFilasImportacionSubida($_FILES['CsvDocentes']);
    } catch (Exception $E) {
        error_log('SGCE importación docentes: ' . $E->getMessage());
        RedirectAdminImportar('maestros', $E->getMessage(), true);
    }

    $Insertados = 0;
    $Duplicados = 0;
    $Invalidos = 0;
    $Saltados = 0;
    $Pendientes = [];
    $UsuariosArchivo = [];

    foreach ($Filas as $Data) {
        if (EsFilaVacia($Data)) { continue; }
        if (EsEncabezadoDocente($Data)) { $Saltados++; continue; }

        if (!isset($Data[0], $Data[1], $Data[2])) {
            $Invalidos++;
            continue;
        }

        $Nombre = SgceNormalizarNombre($Data[0]);
        $User = trim((string)$Data[1]);
        $Pass = trim((string)$Data[2]);

        if ($Nombre === '' || $User === '' || $Pass === '' || SgceValidarPasswordFuerte($Pass) !== true) {
            $Invalidos++;
            continue;
        }

        if (isset($UsuariosArchivo[$User])) {
            $Duplicados++;
            continue;
        }

        $UsuariosArchivo[$User] = true;
        $Pendientes[] = [
            'Nombre' => $Nombre,
            'Username' => $User,
            'Password' => $Pass,
        ];
    }

    try {
        $Existentes = UsuariosExistentesPorUsername($Pdo, array_column($Pendientes, 'Username'));
        $Stmt = $Pdo->prepare("INSERT INTO Usuarios (Username, Password, NombreCompleto, Rol, Activo) VALUES (?, ?, ?, 'maestro', 1)");
        $HashCache = [];

        $Pdo->beginTransaction();

        foreach ($Pendientes as $Docente) {
            if (isset($Existentes[$Docente['Username']])) {
                $Duplicados++;
                continue;
            }

            $Stmt->execute([
                $Docente['Username'],
                HashPasswordImportacion($HashCache, $Docente['Password']),
                $Docente['Nombre'],
            ]);
            $Insertados++;
        }

        $Pdo->commit();
        RegistrarBitacora($Pdo, $UserSession, 'IMPORTAR_DOCENTES', 'Usuarios', null, 'DOCENTES IMPORTADOS: ' . $Insertados);

    } catch (Exception $E) {
        if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
        error_log('SGCE importación docentes: ' . $E->getMessage());
        RedirectAdminImportar('maestros', 'Error al importar los docentes.', true);
    }

    $Mensaje = "Se importaron $Insertados docentes correctamente.";
    if ($Duplicados > 0) { $Mensaje .= " ($Duplicados usuarios duplicados omitidos)"; }
    if ($Invalidos > 0) { $Mensaje .= " ($Invalidos registros inválidos omitidos)"; }
    if ($Saltados > 0) { $Mensaje .= " ($Saltados encabezados omitidos)"; }

    RedirectAdminImportar('maestros', $Mensaje);
}

RedirectAdminImportar($Tab, 'Operación de importación no reconocida.', true);
?>
