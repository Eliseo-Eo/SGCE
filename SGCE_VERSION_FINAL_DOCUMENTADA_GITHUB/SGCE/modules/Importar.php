<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

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
    global $UserSession;
    $_SESSION['Mensaje'] = $Mensaje;
    if ($EsError) {
        $_SESSION['MensajeTipo'] = 'danger';
    }
    header("Location: Admin.php?Tab=" . urlencode(SgceTabAdminPermitida($Tab, $UserSession)));
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

function InfoServidorSubidasImportacion() {
    $UploadTmp = trim((string)ini_get('upload_tmp_dir'));
    $SysTmp = function_exists('sys_get_temp_dir') ? trim((string)sys_get_temp_dir()) : '';
    $TmpDetectado = $UploadTmp !== '' ? $UploadTmp : $SysTmp;
    $Existe = $TmpDetectado !== '' && is_dir($TmpDetectado) ? 'sí' : 'no';
    $Escribible = $TmpDetectado !== '' && is_writable($TmpDetectado) ? 'sí' : 'no';

    return 'Temporal detectado: ' . ($TmpDetectado !== '' ? $TmpDetectado : 'sin definir') .
        ' | Existe: ' . $Existe .
        ' | Escribible: ' . $Escribible .
        ' | upload_tmp_dir: ' . ($UploadTmp !== '' ? $UploadTmp : 'usa temporal del sistema') .
        ' | upload_max_filesize: ' . (string)ini_get('upload_max_filesize') .
        ' | post_max_size: ' . (string)ini_get('post_max_size');
}

function MensajeErrorSubidaImportacion($CodigoError) {
    $CodigoError = (int)$CodigoError;
    $InfoServidor = InfoServidorSubidasImportacion();
    $MapaErrores = [
        UPLOAD_ERR_INI_SIZE => 'El archivo supera el tamaño máximo permitido por el servidor. Sube un archivo más pequeño o aumenta upload_max_filesize/post_max_size. ' . $InfoServidor,
        UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo permitido por el formulario.',
        UPLOAD_ERR_PARTIAL => 'El archivo se subió incompleto. Intenta subirlo nuevamente.',
        UPLOAD_ERR_NO_FILE => 'No se recibió ningún archivo. Selecciona nuevamente el CSV o Excel y vuelve a importar.',
        UPLOAD_ERR_NO_TMP_DIR => 'El archivo no llegó a SGCE porque PHP no tiene una carpeta temporal válida para recibir subidas. Corrige /tmp o upload_tmp_dir en el servidor. ' . $InfoServidor,
        UPLOAD_ERR_CANT_WRITE => 'PHP recibió el archivo, pero no pudo escribirlo en la carpeta temporal del servidor. Revisa permisos de la carpeta temporal. ' . $InfoServidor,
        UPLOAD_ERR_EXTENSION => 'Una extensión de PHP bloqueó la subida del archivo.',
    ];

    if ($CodigoError !== UPLOAD_ERR_OK) {
        error_log('SGCE importación: error de subida código ' . $CodigoError . ' | ' . $InfoServidor);
    }

    return $MapaErrores[$CodigoError] ?? 'Error al subir el archivo. Código de subida: ' . $CodigoError . '. ' . $InfoServidor;
}

function ValidarArchivoImportacionSubido($Archivo) {
    if (!isset($Archivo) || !is_array($Archivo)) {
        return MensajeErrorSubidaImportacion(UPLOAD_ERR_NO_FILE);
    }

    $CodigoError = (int)($Archivo['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($CodigoError !== UPLOAD_ERR_OK) {
        return MensajeErrorSubidaImportacion($CodigoError);
    }

    if (!is_uploaded_file($Archivo['tmp_name'] ?? '')) {
        return 'No se pudo validar el archivo temporal. Selecciona nuevamente el CSV o Excel e intenta otra vez.';
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

function DetectarDelimitadorCsv($RutaArchivo) {
    $Linea = (string)@file_get_contents($RutaArchivo, false, null, 0, 4096);
    $Conteos = [
        ',' => substr_count($Linea, ','),
        ';' => substr_count($Linea, ';'),
        "	" => substr_count($Linea, "	"),
    ];
    arsort($Conteos);
    $Delimitador = (string)array_key_first($Conteos);
    return ($Conteos[$Delimitador] ?? 0) > 0 ? $Delimitador : ',';
}

function LeerFilasCsv($RutaArchivo) {
    $Handle = fopen($RutaArchivo, 'r');
    if (!$Handle) {
        throw new RuntimeException('No se pudo leer el archivo CSV.');
    }

    $Delimitador = DetectarDelimitadorCsv($RutaArchivo);
    BomStrip($Handle);
    $Filas = [];
    while (($Data = fgetcsv($Handle, 8000, $Delimitador)) !== false) {
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
        $Stmt = $Pdo->prepare("SELECT Id, Username, Rol, Activo FROM Usuarios WHERE Username IN ($Placeholders)");
        $Stmt->execute($Chunk);
        foreach ($Stmt->fetchAll() as $Usuario) {
            $Existentes[(string)$Usuario['Username']] = [
                'Id' => (int)$Usuario['Id'],
                'Rol' => (string)$Usuario['Rol'],
                'Activo' => (int)$Usuario['Activo'],
            ];
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

$Tab = SgceTabAdminPermitida($_POST['Tab'] ?? 'maestros', $UserSession);

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
    $Reactivados = 0;
    $Duplicados = 0;
    $Invalidos = 0;
    $Saltados = 0;

    $CheckGrupo = $Pdo->prepare("SELECT COUNT(*) FROM Grupos WHERE Id = ? AND Activo = 1");
    $CheckGrupo->execute([$GrupoId]);

    if ((int)$CheckGrupo->fetchColumn() <= 0) {
        RedirectAdminImportar('alumnos', 'El grupo seleccionado no existe.', true);
    }

    $Check = $Pdo->prepare("SELECT Id, Activo FROM Alumnos WHERE NombreCompleto = ? AND GrupoId = ? LIMIT 1");
    $StmtReactivar = $Pdo->prepare("UPDATE Alumnos SET Activo = 1 WHERE Id = ?");
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
            $AlumnoExistente = $Check->fetch();
            if ($AlumnoExistente) {
                if ((int)$AlumnoExistente['Activo'] === 1) {
                    $Duplicados++;
                    continue;
                }

                $StmtReactivar->execute([(int)$AlumnoExistente['Id']]);
                $Reactivados++;
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
    if ($Reactivados > 0) { $Mensaje .= " ($Reactivados alumnos reactivados)"; }
    if ($Duplicados > 0) { $Mensaje .= " ($Duplicados duplicados omitidos)"; }
    if ($Invalidos > 0) { $Mensaje .= " ($Invalidos registros inválidos omitidos)"; }
    if ($Saltados > 0) { $Mensaje .= " ($Saltados encabezados omitidos)"; }

    RedirectAdminImportar('alumnos', $Mensaje);
}

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
    $Reactivados = 0;
    $Duplicados = 0;
    $Invalidos = 0;
    $Saltados = 0;

    $Check = $Pdo->prepare("SELECT Id, Activo FROM Grupos WHERE Grado = ? AND Grupo = ? AND Turno = ? LIMIT 1");
    $StmtReactivar = $Pdo->prepare("UPDATE Grupos SET Activo = 1 WHERE Id = ?");
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
            $GrupoExistente = $Check->fetch();
            if ($GrupoExistente) {
                if ((int)$GrupoExistente['Activo'] === 1) {
                    $Duplicados++;
                    continue;
                }

                $StmtReactivar->execute([(int)$GrupoExistente['Id']]);
                $Reactivados++;
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
    if ($Reactivados > 0) { $Mensaje .= " ($Reactivados grupos reactivados)"; }
    if ($Duplicados > 0) { $Mensaje .= " ($Duplicados duplicados omitidos)"; }
    if ($Invalidos > 0) { $Mensaje .= " ($Invalidos registros inválidos omitidos)"; }
    if ($Saltados > 0) { $Mensaje .= " ($Saltados encabezados omitidos)"; }

    RedirectAdminImportar('grupos', $Mensaje);
}

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
    $Reactivados = 0;
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

        if (!preg_match('/^[a-zA-Z0-9._@-]{3,80}$/', $User)) {
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
        $StmtReactivar = $Pdo->prepare("UPDATE Usuarios SET Password = ?, NombreCompleto = ?, Rol = 'maestro', Activo = 1, SessionToken = NULL, SessionTokenExpira = NULL WHERE Id = ? AND Rol = 'maestro'");
        $Stmt = $Pdo->prepare("INSERT INTO Usuarios (Username, Password, NombreCompleto, Rol, Activo) VALUES (?, ?, ?, 'maestro', 1)");
        $HashCache = [];

        $Pdo->beginTransaction();

        foreach ($Pendientes as $Docente) {
            $UsuarioExistente = $Existentes[$Docente['Username']] ?? null;
            $PasswordHash = HashPasswordImportacion($HashCache, $Docente['Password']);

            if ($UsuarioExistente) {
                if ((string)$UsuarioExistente['Rol'] !== 'maestro' || (int)$UsuarioExistente['Activo'] === 1) {
                    $Duplicados++;
                    continue;
                }

                $StmtReactivar->execute([
                    $PasswordHash,
                    $Docente['Nombre'],
                    (int)$UsuarioExistente['Id'],
                ]);
                SgcePrepararCarpetaDocentePlaneaciones((int)$UsuarioExistente['Id'], $Docente['Username']);
                $Reactivados++;
                continue;
            }

            $Stmt->execute([
                $Docente['Username'],
                $PasswordHash,
                $Docente['Nombre'],
            ]);
            SgcePrepararCarpetaDocentePlaneaciones((int)$Pdo->lastInsertId(), $Docente['Username']);
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
    if ($Reactivados > 0) { $Mensaje .= " ($Reactivados docentes reactivados)"; }
    if ($Duplicados > 0) { $Mensaje .= " ($Duplicados usuarios duplicados omitidos)"; }
    if ($Invalidos > 0) { $Mensaje .= " ($Invalidos registros inválidos omitidos)"; }
    if ($Saltados > 0) { $Mensaje .= " ($Saltados encabezados omitidos)"; }

    RedirectAdminImportar('maestros', $Mensaje);
}

RedirectAdminImportar($Tab, 'Operación de importación no reconocida.', true);
?>
