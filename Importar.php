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

// Valido la pestaña para regresar al mismo módulo después de importar.
function TabPermitidaImportar($Tab) {
    $Permitidas = ['maestros','grupos','alumnos','asignaciones'];
    return in_array($Tab, $Permitidas, true) ? $Tab : 'maestros';
}

// Redirecciono al administrador con mensaje de éxito o error.
function RedirectAdminImportar($Tab, $Mensaje, $EsError = false) {
    $_SESSION['Mensaje'] = $Mensaje;
    if ($EsError) {
        $_SESSION['MensajeTipo'] = 'danger';
    }
    header("Location: Admin.php?Tab=" . urlencode(TabPermitidaImportar($Tab)));
    exit;
}

// Quito BOM de archivos CSV para evitar caracteres raros al leer la primera columna.
function BomStrip($Handle) {
    if (fgets($Handle, 4) !== "\xEF\xBB\xBF") {
        rewind($Handle);
    }
}

// Normalizo nombres importados: limpio espacios, valido letras y convierto a mayúsculas.
function NormalizarNombreImportar($Valor) {
    $Valor = trim((string)$Valor);
    if ($Valor === '') { return ''; }

    $Valor = preg_replace('/\s+/u', ' ', $Valor);

    if (!preg_match('/^[\p{L}\s]+$/u', $Valor)) {
        return '';
    }

    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($Valor, 'UTF-8');
    }

    return strtoupper($Valor);
}

// Normalizo cualquier texto importado que deba guardarse en mayúsculas.
// No lo aplico a Username ni Password porque esos campos deben respetar lo escrito.
function NormalizarMayusculasImportar($Valor) {
    $Valor = trim((string)$Valor);
    if ($Valor === '') { return ''; }

    $Valor = preg_replace('/\s+/u', ' ', $Valor);

    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($Valor, 'UTF-8');
    }

    return strtoupper($Valor);
}

function TieneCsvValido($NombreArchivo) {
    return strtolower(pathinfo((string)$NombreArchivo, PATHINFO_EXTENSION)) === 'csv';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Admin.php');
    exit;
}

$Tab = TabPermitidaImportar($_POST['Tab'] ?? 'maestros');

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

    if (!isset($_FILES['CsvAlumnos']) || $_FILES['CsvAlumnos']['error'] !== UPLOAD_ERR_OK) {
        RedirectAdminImportar('alumnos', 'Error al subir el archivo de alumnos.', true);
    }

    if (!TieneCsvValido($_FILES['CsvAlumnos']['name'])) {
        RedirectAdminImportar('alumnos', 'Solo se permiten archivos CSV.', true);
    }

    $Handle = fopen($_FILES['CsvAlumnos']['tmp_name'], 'r');

    if (!$Handle) {
        RedirectAdminImportar('alumnos', 'No se pudo leer el archivo.', true);
    }

    BomStrip($Handle);

    $Insertados = 0;
    $Duplicados = 0;
    $Invalidos = 0;

    $CheckGrupo = $Pdo->prepare("SELECT COUNT(*) FROM Grupos WHERE Id = ?");
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

            $Nombre = NormalizarNombreImportar($Data[0]);

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

    if (!isset($_FILES['CsvDocentes']) || $_FILES['CsvDocentes']['error'] !== UPLOAD_ERR_OK) {
        RedirectAdminImportar('maestros', 'Error al subir el archivo de docentes.', true);
    }

    if (!TieneCsvValido($_FILES['CsvDocentes']['name'])) {
        RedirectAdminImportar('maestros', 'Solo se permiten archivos CSV.', true);
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

            $Nombre = NormalizarNombreImportar($Data[0]);
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
