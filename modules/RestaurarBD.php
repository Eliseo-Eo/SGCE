<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

require_once dirname(__DIR__) . '/config/Conexion.php';
IniciarSesionSegura();
$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { header('Location: index.php'); exit; }
SgceExigirPermiso($UserSession, 'respaldos', 'Solo el administrador puede entrar a respaldos y restauración.');

$Mensaje = $_SESSION['MensajeRestaurarBD'] ?? '';
$TipoMensaje = $_SESSION['TipoRestaurarBD'] ?? 'info';
unset($_SESSION['MensajeRestaurarBD'], $_SESSION['TipoRestaurarBD']);

function HRest($Texto) { return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }

function InfoServidorSubidasRestaurar() {
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

function MensajeErrorSubidaRest($CodigoError) {
    $CodigoError = (int)$CodigoError;
    $InfoServidor = InfoServidorSubidasRestaurar();
    $MapaErrores = [
        UPLOAD_ERR_INI_SIZE => 'El respaldo supera el tamaño máximo permitido por el servidor. Sube un archivo más pequeño o aumenta upload_max_filesize/post_max_size. ' . $InfoServidor,
        UPLOAD_ERR_FORM_SIZE => 'El respaldo supera el tamaño máximo permitido por el formulario.',
        UPLOAD_ERR_PARTIAL => 'El respaldo se subió incompleto. Intenta subirlo nuevamente.',
        UPLOAD_ERR_NO_FILE => 'No se recibió ningún archivo SQL. Selecciona nuevamente el respaldo y vuelve a importar.',
        UPLOAD_ERR_NO_TMP_DIR => 'El respaldo no llegó a SGCE porque PHP no tiene una carpeta temporal válida para recibir subidas. Corrige /tmp o upload_tmp_dir en el servidor. ' . $InfoServidor,
        UPLOAD_ERR_CANT_WRITE => 'PHP recibió el respaldo, pero no pudo escribirlo en la carpeta temporal del servidor. Revisa permisos de la carpeta temporal. ' . $InfoServidor,
        UPLOAD_ERR_EXTENSION => 'Una extensión de PHP bloqueó la subida del respaldo.',
    ];

    if ($CodigoError !== UPLOAD_ERR_OK) {
        error_log('SGCE restaurar respaldo: error de subida código ' . $CodigoError . ' | ' . $InfoServidor);
    }

    return $MapaErrores[$CodigoError] ?? 'Error al subir el respaldo. Código de subida: ' . $CodigoError . '. ' . $InfoServidor;
}

function RedirectRestaurar($Mensaje, $Tipo = 'success') {
    $_SESSION['MensajeRestaurarBD'] = $Mensaje;
    $_SESSION['TipoRestaurarBD'] = $Tipo;
    header('Location: RestaurarBD.php');
    exit;
}

function QTablaRest($Tabla) { return '`' . str_replace('`','``',$Tabla) . '`'; }

function TablasSistemaRest($Pdo) {
    $Preferidas = ['IntentosSeguridad','BitacoraMovimientos','Planeaciones','Avisos','Asistencias','Calificaciones','PeriodosEvaluacion','CiclosEscolares','Asignaciones','Alumnos','Grupos','Usuarios','ConfiguracionSistema'];
    $Existentes = array_map(function($R){ return $R[0]; }, $Pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM));
    $Tablas = [];
    foreach ($Preferidas as $Tabla) {
        if (in_array($Tabla, $Existentes, true)) { $Tablas[] = $Tabla; }
    }
    foreach ($Existentes as $Tabla) {
        if (!in_array($Tabla, $Tablas, true)) { $Tablas[] = $Tabla; }
    }
    return $Tablas;
}

function VaciarTablasRest($Pdo, $IncluirUsuarios = false, $ConservarCicloPeriodo = true) {
    $Tablas = TablasSistemaRest($Pdo);
    $ConservarEscolar = $ConservarCicloPeriodo
        ? ['Usuarios', 'ConfiguracionSistema', 'CiclosEscolares', 'PeriodosEvaluacion', 'IntentosSeguridad']
        : ['Usuarios', 'ConfiguracionSistema', 'IntentosSeguridad'];

    $Pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach ($Tablas as $Tabla) {
        if (!$IncluirUsuarios && in_array($Tabla, $ConservarEscolar, true)) { continue; }
        $Pdo->exec('DELETE FROM ' . QTablaRest($Tabla));
    }
    $Pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

function GarantizarSesionDespuesRestaurar($Pdo, $UserSession) {
    $Token = $_COOKIE['AuthToken'] ?? '';
    $Token = is_string($Token) ? trim($Token) : '';
    if ($Token !== '' && preg_match('/^[a-f0-9]{64}$/i', $Token)) {
        $Username = (string)($UserSession['Username'] ?? '');
        if ($Username !== '') {
            $Stmt = $Pdo->prepare('UPDATE Usuarios SET SessionToken = ?, SessionTokenExpira = DATE_ADD(NOW(), INTERVAL 1 DAY) WHERE Username = ? AND Activo = 1 LIMIT 1');
            $Stmt->execute([$Token, $Username]);
        }
    }

    $TotalUsuarios = (int)$Pdo->query('SELECT COUNT(*) FROM Usuarios')->fetchColumn();
    if ($TotalUsuarios <= 0) {
        throw new Exception('El respaldo no contiene usuarios. Por seguridad no se permite dejar el sistema sin cuentas de acceso.');
    }
}

function PartirSqlRest($Sql) {
    $Sentencias = [];
    $Actual = '';
    $Len = strlen($Sql);
    $Comilla = null;
    $Escape = false;
    for ($I = 0; $I < $Len; $I++) {
        $Ch = $Sql[$I];
        $Next = ($I + 1 < $Len) ? $Sql[$I + 1] : '';

        if ($Comilla === null && $Ch === '-' && $Next === '-') {
            while ($I < $Len && $Sql[$I] !== "\n") { $I++; }
            continue;
        }
        if ($Comilla === null && $Ch === '#') {
            while ($I < $Len && $Sql[$I] !== "\n") { $I++; }
            continue;
        }
        if ($Comilla === null && $Ch === '/' && $Next === '*') {
            $I += 2;
            while ($I + 1 < $Len && !($Sql[$I] === '*' && $Sql[$I + 1] === '/')) { $I++; }
            $I++;
            continue;
        }

        if ($Comilla !== null) {
            $Actual .= $Ch;
            if ($Escape) { $Escape = false; continue; }
            if ($Ch === '\\') { $Escape = true; continue; }
            if ($Ch === $Comilla) { $Comilla = null; }
            continue;
        }

        if ($Ch === "'" || $Ch === '"' || $Ch === '`') {
            $Comilla = $Ch;
            $Actual .= $Ch;
            continue;
        }

        if ($Ch === ';') {
            $Stmt = trim($Actual);
            if ($Stmt !== '') { $Sentencias[] = $Stmt; }
            $Actual = '';
            continue;
        }

        $Actual .= $Ch;
    }
    $Stmt = trim($Actual);
    if ($Stmt !== '') { $Sentencias[] = $Stmt; }
    return $Sentencias;
}

function SentenciaPermitidaRest($Sql) {
    $Limpia = ltrim($Sql);
    if (preg_match('/^SET\s+/i', $Limpia)) { return true; }
    if (preg_match('/^(INSERT|REPLACE)\s+INTO\s+`?([A-Za-z0-9_]+)`?/i', $Limpia, $M)) {
        $TablasPermitidas = ['Usuarios','Grupos','Alumnos','Asignaciones','CiclosEscolares','PeriodosEvaluacion','Calificaciones','Asistencias','Avisos','Planeaciones','BitacoraMovimientos','IntentosSeguridad','ConfiguracionSistema'];
        return in_array($M[2], $TablasPermitidas, true);
    }
    return false;
}

function ImportarSqlRest($Pdo, $Sql) {
    $Sentencias = PartirSqlRest($Sql);
    $Ejecutadas = 0;
    foreach ($Sentencias as $Sentencia) {
        if (!SentenciaPermitidaRest($Sentencia)) {
            throw new Exception('El archivo no parece ser un respaldo de SOLO DATOS generado por este sistema. Sentencia no permitida: ' . substr($Sentencia, 0, 60));
        }
        $Pdo->exec($Sentencia);
        $Ejecutadas++;
    }
    return $Ejecutadas;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    RequerirCsrfPost();

    if (isset($_POST['VaciarEscolar'])) {
        $Confirmar = trim((string)($_POST['Confirmar'] ?? ''));
        if ($Confirmar !== 'BORRAR DATOS ESCOLARES') {
            RedirectRestaurar('Para borrar debes escribir exactamente: BORRAR DATOS ESCOLARES', 'danger');
        }
        try {
            VaciarTablasRest($Pdo, false, true);
            $Pdo->prepare("INSERT INTO Avisos (Titulo, Mensaje, Publico, Activo) VALUES (?, ?, 'TODOS', 1)")
                ->execute(['SISTEMA REINICIADO', 'LOS DATOS ESCOLARES FUERON BORRADOS. PUEDES CAPTURAR NUEVOS REGISTROS O IMPORTAR UN RESPALDO DE DATOS.']);
            RegistrarBitacora($Pdo, $UserSession, 'VACIAR_DATOS_ESCOLARES', 'BASE_DE_DATOS', null, 'SE BORRARON DATOS ESCOLARES, CONSERVANDO USUARIOS');
            RedirectRestaurar('Datos escolares borrados correctamente. Los usuarios se conservaron.', 'success');
        } catch (Exception $E) {
            $CodigoError = SgceRegistrarErrorTecnico('RESTAURAR_BD_VACIAR_ESCOLAR', $E);
            RedirectRestaurar('No se pudieron borrar los datos escolares. Código de seguimiento: ' . $CodigoError, 'danger');
        }
    }

    if (isset($_POST['ImportarRespaldo'])) {
        $ArchivoSql = $_FILES['ArchivoSql'] ?? null;
        if (!isset($ArchivoSql) || !is_array($ArchivoSql)) {
            RedirectRestaurar(MensajeErrorSubidaRest(UPLOAD_ERR_NO_FILE), 'danger');
        }

        $CodigoErrorSubida = (int)($ArchivoSql['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($CodigoErrorSubida !== UPLOAD_ERR_OK) {
            RedirectRestaurar(MensajeErrorSubidaRest($CodigoErrorSubida), 'danger');
        }

        if (!is_uploaded_file($ArchivoSql['tmp_name'] ?? '')) {
            RedirectRestaurar('No se pudo validar el archivo temporal. Selecciona nuevamente el respaldo SQL e intenta otra vez.', 'danger');
        }

        if ((int)$ArchivoSql['size'] <= 0 || (int)$ArchivoSql['size'] > 80 * 1024 * 1024) {
            RedirectRestaurar('El archivo está vacío o supera el máximo permitido de 80 MB.', 'danger');
        }
        $Nombre = (string)($ArchivoSql['name'] ?? '');
        if (strtolower(pathinfo($Nombre, PATHINFO_EXTENSION)) !== 'sql') {
            RedirectRestaurar('El archivo debe tener extensión .sql.', 'danger');
        }
        if (function_exists('finfo_open')) {
            $Finfo = finfo_open(FILEINFO_MIME_TYPE);
            $Mime = $Finfo ? (string)finfo_file($Finfo, $ArchivoSql['tmp_name']) : '';
            if ($Finfo) { finfo_close($Finfo); }
            $MimesSql = ['text/plain', 'text/x-sql', 'application/sql', 'application/octet-stream'];
            if ($Mime !== '' && !in_array($Mime, $MimesSql, true)) {
                RedirectRestaurar('El archivo no parece ser un respaldo SQL válido.', 'danger');
            }
        }
        $Modo = $_POST['ModoImportacion'] ?? 'fusionar';
        if (!in_array($Modo, ['fusionar','reemplazar_escolar','reemplazar_todo'], true)) {
            $Modo = 'fusionar';
        }
        $Sql = file_get_contents($ArchivoSql['tmp_name']);
        if ($Sql === false || trim($Sql) === '') {
            RedirectRestaurar('No se pudo leer el archivo SQL.', 'danger');
        }
        if (!SgceFirmaRespaldoValida($Sql)) {
            RedirectRestaurar('El archivo no tiene la firma oficial SGCE. Por seguridad solo se importan respaldos generados por este sistema.', 'danger');
        }
        if (preg_match('/\b(DROP\s+DATABASE|CREATE\s+DATABASE|DROP\s+TABLE|CREATE\s+TABLE|ALTER\s+TABLE)\b/i', $Sql)) {
            RedirectRestaurar('Este importador acepta únicamente respaldos de SOLO DATOS generados por “Exportar solo datos”. Si subiste un respaldo completo con estructura, usa install/SGCE.sql o el instalador de forma manual.', 'danger');
        }

        try {
            $Pdo->beginTransaction();

            if ($Modo === 'reemplazar_escolar') {
                VaciarTablasRest($Pdo, false, false);
            } elseif ($Modo === 'reemplazar_todo') {
                VaciarTablasRest($Pdo, true, false);
            }

            $Pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            $Ejecutadas = ImportarSqlRest($Pdo, $Sql);
            GarantizarSesionDespuesRestaurar($Pdo, $UserSession);
            $Pdo->exec('SET FOREIGN_KEY_CHECKS=1');

            if ($Pdo->inTransaction()) {
                $Pdo->commit();
            }

            RegistrarBitacora($Pdo, $UserSession, 'IMPORTAR_RESPALDO_DATOS', 'BASE_DE_DATOS', null, 'MODO: ' . $Modo . ' | SENTENCIAS: ' . $Ejecutadas);
            RedirectRestaurar('Importación terminada correctamente. Sentencias ejecutadas: ' . $Ejecutadas, 'success');
        } catch (Exception $E) {
            if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
            try { $Pdo->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (Exception $Ex) {}
            $CodigoError = SgceRegistrarErrorTecnico('RESTAURAR_BD_IMPORTAR', $E);
            RedirectRestaurar('No se pudo importar el respaldo. Código de seguimiento: ' . $CodigoError, 'danger');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SGCE | Respaldos e Importación</title>
<link rel="icon" type="image/x-icon" href="assets/media/img/favicon.ico">
<link rel="shortcut icon" type="image/x-icon" href="assets/media/img/favicon.ico">
<link rel="apple-touch-icon" href="assets/media/img/favicon.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sgce-base.min.css?v=sgce">
<link rel="stylesheet" href="assets/css/sgce-soft-motion.css?v=sgce">
<?= SgceEstilosTema($Pdo) ?>
<link rel="stylesheet" href="assets/css/respaldos-botones-metalicos.css?v=sgce">
</head>
<body class="SgceRestorePage">
<div class="container py-4 SgceModuleWrap SgceRestoreWrap">
    <div class="Top mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="fw-black mb-1"><span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">🗄️</span> RESPALDOS E IMPORTACIÓN</h1>
            <p class="mb-0 opacity-75">Respalda, restaura o limpia los datos del sistema desde un solo lugar.</p>
        </div>
        <a href="Admin.php?Tab=inicio" class="SgceBtnVolverInicio" title="Volver al inicio" aria-label="Volver al inicio"><i class="fa-solid fa-house"></i><span>Volver al inicio</span></a>
    </div>

    <?php if ($Mensaje !== ''): ?>
        <div class="alert alert-<?= HRest($TipoMensaje) ?> border-0 shadow-sm rounded-4 fw-semibold"><?= HRest($Mensaje) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="Card SgceRestoreCard p-4 h-100">
                <div class="SgceRestoreCardHead"><div class="IconBox"><span class="SgceColorIcon" aria-hidden="true">📤</span></div><div><h4>Exportar respaldos</h4><p>Usa este respaldo para restaurar desde esta misma pantalla. No toca la estructura de la base de datos.</p></div></div>
                <div class="d-grid gap-3">
                    <a id="BtnExportarSoloDatosVerdeMetalico" href="ExportarDatosBD.php" class="ActionBtn BtnRespaldosExportarVerdeMetalico"><span class="SgceColorIcon" aria-hidden="true">📤</span> EXPORTAR SOLO DATOS</a>
                    </div>
                <div class="SgceRestoreInfo"><i class="fa-solid fa-circle-info"></i><span><strong>Recomendado:</strong> este es el respaldo correcto para volver a importar desde el sistema.</span></div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="Card SgceRestoreCard p-4 h-100">
                <div class="SgceRestoreCardHead"><div class="IconBox"><span class="SgceColorIcon" aria-hidden="true">📥</span></div><div><h4>Importar respaldo de datos</h4><p>Sube un .sql generado por “Exportar solo datos”.</p></div></div>
                <form method="POST" enctype="multipart/form-data" data-sgce-confirm="import" data-sgce-confirm-title="CONFIRMAR IMPORTACIÓN" data-sgce-confirm-subtitle="IMPORTAR RESPALDO" data-sgce-confirm-message="¿REALMENTE DESEAS IMPORTAR ESTE RESPALDO?" data-sgce-confirm-detail="Esta acción puede fusionar, reemplazar datos escolares o reemplazar todo según el modo seleccionado. Revisa el archivo SQL y el modo antes de continuar." data-sgce-confirm-button="SÍ, IMPORTAR RESPALDO" data-sgce-confirm-loading="IMPORTANDO RESPALDO..." data-sgce-confirm-icon="fa-database">
                    <?= CampoCsrf() ?>
                    <div class="mb-3">
                        <label class="fw-bold mb-2">Archivo SQL</label>
                        <input type="file" name="ArchivoSql" accept=".sql" class="form-control FormControl" required>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold mb-2">Modo de importación</label>
                        <select name="ModoImportacion" class="form-select FormControl">
                            <option value="fusionar">Fusionar/agregar datos sin borrar primero</option>
                            <option value="reemplazar_escolar">Borrar datos escolares y luego importar, conservando usuarios</option>
                            <option value="reemplazar_todo">Borrar TODO y luego importar, incluyendo usuarios</option>
                        </select>
                    </div>
                    <button id="BtnImportarRespaldoAzulMetalico" class="ActionBtn BtnRespaldosImportarAzulMetalico w-100 border-0" name="ImportarRespaldo" value="1" type="submit"><span class="SgceColorIcon" aria-hidden="true">📥</span> IMPORTAR RESPALDO</button>
                </form>
            </div>
        </div>

        <div class="col-12">
            <div class="Card SgceRestoreCard SgceDangerCard p-4">
                <div class="SgceRestoreCardHead SgceDangerHead"><div class="IconBox"><span class="SgceColorIcon" aria-hidden="true">⚠️</span></div><div><h4>Borrar datos escolares</h4><p>Esto borra grupos, alumnos, asignaciones, asistencias, calificaciones, avisos y bitácora. Conserva usuarios para que no pierdas acceso.</p></div></div>
                <form method="POST" class="row g-3 align-items-end" data-sgce-confirm="danger" data-sgce-confirm-title="CONFIRMAR BORRADO" data-sgce-confirm-subtitle="DATOS ESCOLARES" data-sgce-confirm-message="¿REALMENTE DESEAS BORRAR LOS DATOS ESCOLARES?" data-sgce-confirm-detail="Esta acción eliminará datos escolares y conservará usuarios. Debes escribir la frase solicitada para que el servidor acepte la operación." data-sgce-confirm-button="SÍ, BORRAR DATOS" data-sgce-confirm-loading="BORRANDO DATOS..." data-sgce-confirm-icon="fa-trash-can">
                    <?= CampoCsrf() ?>
                    <div class="col-lg-8">
                        <label class="fw-bold mb-2">Confirmación</label>
                        <input type="text" name="Confirmar" class="form-control FormControl" placeholder="Escribe: BORRAR DATOS ESCOLARES">
                    </div>
                    <div class="col-lg-4">
                        <button id="BtnBorrarDatosRojoFijo" class="ActionBtn BtnRespaldosBorrarRojoFijo w-100 border-0" type="submit" name="VaciarEscolar" value="1"><span class="SgceColorIcon" aria-hidden="true">🗑️</span> BORRAR DATOS</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php ImprimirCsrfScript(); ?>
<script src="assets/js/sgce-shared.js?v=sgce"></script>
</body>
</html>
