<?php

/*
    Archivo: Importar.php
    Descripción: Procesa archivos CSV para importar alumnos y docentes desde el panel administrador.
    Valida formato, datos duplicados y nombres antes de guardarlos en la base de datos.
*/

require 'Conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$UserSession = VerificarSesionCookie($Pdo);

if (!$UserSession || $UserSession['Rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

RequerirCsrfPost();

// Redirecciono al administrador con mensaje de éxito o error.
function RedirectAdminImportar($Tab, $Mensaje, $EsError = false) {
    $_SESSION['Mensaje'] = $Mensaje;
    if ($EsError) {
        $_SESSION['MensajeTipo'] = 'danger';
    }
    header("Location: Admin.php?Tab=" . urlencode(SgceTabAdminPermitida($Tab)));
    exit;
}

// Quito BOM de archivos CSV para evitar caracteres raros al leer la primera columna.
function BomStrip($Handle) {
    if (fgets($Handle, 4) !== "\xEF\xBB\xBF") {
        rewind($Handle);
    }
}

function TieneCsvValido($NombreArchivo) {
    return strtolower(pathinfo((string)$NombreArchivo, PATHINFO_EXTENSION)) === 'csv';
}

function ValidarArchivoCsvSubido($Archivo) {
    if (!isset($Archivo) || !is_array($Archivo) || ($Archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return 'Error al subir el archivo.';
    }

    if (($Archivo['size'] ?? 0) <= 0) {
        return 'El archivo está vacío.';
    }

    if (($Archivo['size'] ?? 0) > 5 * 1024 * 1024) {
        return 'El archivo CSV no debe pesar más de 5 MB.';
    }

    if (!TieneCsvValido($Archivo['name'] ?? '')) {
        return 'Solo se permiten archivos CSV.';
    }

    $MimePermitidos = ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel', 'application/octet-stream'];
    if (function_exists('finfo_open') && is_uploaded_file($Archivo['tmp_name'] ?? '')) {
        $Finfo = finfo_open(FILEINFO_MIME_TYPE);
        $Mime = $Finfo ? finfo_file($Finfo, $Archivo['tmp_name']) : '';
        if ($Finfo) { finfo_close($Finfo); }
        if ($Mime !== '' && !in_array($Mime, $MimePermitidos, true)) {
            return 'El archivo no parece ser un CSV válido.';
        }
    }

    return '';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Admin.php');
    exit;
}

$Tab = SgceTabAdminPermitida($_POST['Tab'] ?? 'maestros');

// =====================================================
// IMPORTAR ALUMNOS
// =====================================================

// =====================================================
// IMPORTAR ALUMNOS DESDE CSV
// =====================================================
if (isset($_POST['ImportarAlumnos'])) {

    $GrupoId = intval($_POST['GrupoId'] ?? 0);

    if ($GrupoId <= 0) {
        RedirectAdminImportar('alumnos', 'Por favor, selecciona un grupo válido.', true);
    }

    $ErrorCsv = ValidarArchivoCsvSubido($_FILES['CsvAlumnos'] ?? null);
    if ($ErrorCsv !== '') {
        RedirectAdminImportar('alumnos', $ErrorCsv, true);
    }

    $Handle = fopen($_FILES['CsvAlumnos']['tmp_name'], 'r');

    if (!$Handle) {
        RedirectAdminImportar('alumnos', 'No se pudo leer el archivo.', true);
    }

    BomStrip($Handle);

    $Insertados = 0;
    $Duplicados = 0;
    $Invalidos = 0;

    $CheckGrupo = $Pdo->prepare("SELECT COUNT(*) FROM Grupos WHERE Id = ? AND Activo = 1");
    $CheckGrupo->execute([$GrupoId]);

    if ((int)$CheckGrupo->fetchColumn() <= 0) {
        fclose($Handle);
        RedirectAdminImportar('alumnos', 'El grupo seleccionado no existe.', true);
    }

    $Check = $Pdo->prepare("
        SELECT COUNT(*)
        FROM Alumnos
        WHERE NombreCompleto = ?
        AND GrupoId = ?
    ");

    $Stmt = $Pdo->prepare("
        INSERT INTO Alumnos (NombreCompleto, GrupoId)
        VALUES (?, ?)
    ");

    try {
        $Pdo->beginTransaction();

        while (($Data = fgetcsv($Handle, 2000, ",")) !== false) {

            if (!isset($Data[0]) || trim($Data[0]) === '') {
                continue;
            }

            $Nombre = SgceNormalizarNombre($Data[0]);

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
        fclose($Handle);
        RedirectAdminImportar('alumnos', 'Error al importar los alumnos.', true);
    }

    fclose($Handle);

    $Mensaje = "Se importaron $Insertados alumnos correctamente.";
    if ($Duplicados > 0) { $Mensaje .= " ($Duplicados duplicados omitidos)"; }
    if ($Invalidos > 0) { $Mensaje .= " ($Invalidos registros inválidos omitidos)"; }

    RedirectAdminImportar('alumnos', $Mensaje);
}

// =====================================================
// IMPORTAR DOCENTES
// =====================================================

// =====================================================
// IMPORTAR DOCENTES DESDE CSV
// =====================================================
if (isset($_POST['ImportarDocentes'])) {

    $ErrorCsv = ValidarArchivoCsvSubido($_FILES['CsvDocentes'] ?? null);
    if ($ErrorCsv !== '') {
        RedirectAdminImportar('maestros', $ErrorCsv, true);
    }

    $Handle = fopen($_FILES['CsvDocentes']['tmp_name'], 'r');

    if (!$Handle) {
        RedirectAdminImportar('maestros', 'No se pudo leer el archivo.', true);
    }

    BomStrip($Handle);

    $Insertados = 0;
    $Duplicados = 0;
    $Invalidos = 0;

    $Check = $Pdo->prepare("
        SELECT COUNT(*)
        FROM Usuarios
        WHERE Username = ?
    ");

    $Stmt = $Pdo->prepare("
        INSERT INTO Usuarios (Username, Password, NombreCompleto, Rol, Activo)
        VALUES (?, ?, ?, 'maestro', 1)
    ");

    try {
        $Pdo->beginTransaction();

        while (($Data = fgetcsv($Handle, 2000, ",")) !== false) {

            if (!isset($Data[0], $Data[1], $Data[2])) {
                $Invalidos++;
                continue;
            }

            $Nombre = SgceNormalizarNombre($Data[0]);
            $User = trim((string)$Data[1]);
            $Pass = trim((string)$Data[2]);

            if ($Nombre === '' || $User === '' || $Pass === '') {
                $Invalidos++;
                continue;
            }

            $Check->execute([$User]);

            if ((int)$Check->fetchColumn() > 0) {
                $Duplicados++;
                continue;
            }

            $Stmt->execute([$User, $Pass, $Nombre]);
            $Insertados++;
        }

        $Pdo->commit();
        RegistrarBitacora($Pdo, $UserSession, 'IMPORTAR_DOCENTES', 'Usuarios', null, 'DOCENTES IMPORTADOS: ' . $Insertados);

    } catch (Exception $E) {
        if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
        fclose($Handle);
        RedirectAdminImportar('maestros', 'Error al importar los docentes.', true);
    }

    fclose($Handle);

    $Mensaje = "Se importaron $Insertados docentes correctamente.";
    if ($Duplicados > 0) { $Mensaje .= " ($Duplicados usuarios duplicados omitidos)"; }
    if ($Invalidos > 0) { $Mensaje .= " ($Invalidos registros inválidos omitidos)"; }

    RedirectAdminImportar('maestros', $Mensaje);
}

RedirectAdminImportar($Tab, 'Operación de importación no reconocida.', true);
?>

