<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/config/Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { header('Location: index.php'); exit; }
SgceExigirPermiso($UserSession, 'configuracion', 'Solo el administrador puede modificar la configuración del sistema.');
RequerirCsrfPost();

function HConfig($Texto) { return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }
function ConfigMayusculas($Texto) {
    $Texto = (string)$Texto;
    if (function_exists('mb_strtoupper')) { return mb_strtoupper($Texto, 'UTF-8'); }
    $Texto = strtr($Texto, [
        'á'=>'Á','é'=>'É','í'=>'Í','ó'=>'Ó','ú'=>'Ú','ü'=>'Ü','ñ'=>'Ñ',
        'à'=>'À','è'=>'È','ì'=>'Ì','ò'=>'Ò','ù'=>'Ù','ä'=>'Ä','ë'=>'Ë','ï'=>'Ï','ö'=>'Ö'
    ]);
    return strtoupper($Texto);
}

function ConfigLongitud($Texto) {
    $Texto = (string)$Texto;
    return function_exists('mb_strlen') ? mb_strlen($Texto, 'UTF-8') : strlen($Texto);
}

function ConfigNormalizar($Texto, $Mayusculas = true) {
    $Texto = trim(preg_replace('/\s+/u', ' ', (string)$Texto));
    return $Mayusculas ? ConfigMayusculas($Texto) : $Texto;
}
function ConfigFechaValida($Fecha) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$Fecha)) { return false; }
    $D = DateTime::createFromFormat('Y-m-d', (string)$Fecha);
    return $D && $D->format('Y-m-d') === $Fecha;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['MigrarGrupoAcademico'])) {
    try {
        $GrupoOrigenId = max(0, (int)($_POST['GrupoOrigenId'] ?? 0));
        $CopiarAsignaciones = !empty($_POST['CopiarAsignaciones']);
        $CicloDestino = SgceCicloActivo($Pdo);
        $CicloDestinoId = (int)($CicloDestino['Id'] ?? 0);
        if ($GrupoOrigenId <= 0 || $CicloDestinoId <= 0) { throw new Exception('Selecciona un grupo origen y asegúrate de tener un ciclo activo destino.'); }
        $Pdo->beginTransaction();
        $R = SgceMigrarGrupoSiguienteCiclo($Pdo, $GrupoOrigenId, $CicloDestinoId, $CopiarAsignaciones);
        RegistrarBitacora($Pdo, $UserSession, 'MIGRAR_GRUPO_CICLO', 'Grupos', $GrupoOrigenId, 'PROMOVIDOS: '.$R['Promovidos'].' | EGRESADOS: '.$R['Egresados'].' | KARDEX: '.($R['KardexCongelados'] ?? 0).' | CONFLICTOS: '.$R['Conflictos']);
        $Pdo->commit();
        $_SESSION['MensajeConfiguracion'] = 'Migración realizada: '.$R['Promovidos'].' alumnos promovidos, '.$R['Egresados'].' egresados, '.$R['Conflictos'].' conflictos omitidos. Kardex congelados: '.($R['KardexCongelados'] ?? 0).'. Asignaciones copiadas: '.($R['AsignacionesCopiadas'] ?? 0).'. Omitidas por docente inactivo: '.($R['AsignacionesOmitidasDocente'] ?? 0).'. Grupos creados: '.(!empty($R['GrupoCreado']) ? '1' : '0').'.';
        $_SESSION['MensajeConfiguracionTipo'] = 'success';
    } catch (Exception $E) {
        if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
        $_SESSION['MensajeConfiguracion'] = $E->getMessage();
        $_SESSION['MensajeConfiguracionTipo'] = 'danger';
    }
    header('Location: ConfiguracionAdmin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['MigrarCicloAcademico'])) {
    try {
        $CicloOrigenId = max(0, (int)($_POST['CicloOrigenId'] ?? 0));
        $CopiarAsignaciones = !empty($_POST['CopiarAsignaciones']);
        $CicloDestino = SgceCicloActivo($Pdo);
        $CicloDestinoId = (int)($CicloDestino['Id'] ?? 0);
        if ($CicloOrigenId <= 0 || $CicloDestinoId <= 0) { throw new Exception('Selecciona un ciclo origen cerrado y asegúrate de tener un ciclo activo destino.'); }
        $Pdo->beginTransaction();
        $R = SgceMigrarCicloCompleto($Pdo, $CicloOrigenId, $CicloDestinoId, $CopiarAsignaciones);
        RegistrarBitacora($Pdo, $UserSession, 'MIGRAR_CICLO_COMPLETO', 'CiclosEscolares', $CicloOrigenId, 'GRUPOS: '.$R['GruposProcesados'].' | PROMOVIDOS: '.$R['Promovidos'].' | EGRESADOS: '.$R['Egresados'].' | KARDEX: '.($R['KardexCongelados'] ?? 0).' | CONFLICTOS: '.$R['Conflictos']);
        $Pdo->commit();
        $_SESSION['MensajeConfiguracion'] = 'Ciclo migrado: '.$R['GruposProcesados'].' grupos procesados, '.$R['Promovidos'].' alumnos promovidos, '.$R['Egresados'].' egresados, '.$R['Conflictos'].' conflictos omitidos, '.($R['KardexCongelados'] ?? 0).' kardex congelados y '.$R['GruposCreados'].' grupos creados en el ciclo activo. Asignaciones copiadas: '.($R['AsignacionesCopiadas'] ?? 0).'. Omitidas por docente inactivo: '.($R['AsignacionesOmitidasDocente'] ?? 0).'.';
        $_SESSION['MensajeConfiguracionTipo'] = 'success';
    } catch (Exception $E) {
        if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
        $_SESSION['MensajeConfiguracion'] = $E->getMessage();
        $_SESSION['MensajeConfiguracionTipo'] = 'danger';
    }
    header('Location: ConfiguracionAdmin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $NombreEscuela = ConfigNormalizar($_POST['NombreEscuela'] ?? '', true);
        $ClaveCentroTrabajo = ConfigNormalizar($_POST['ClaveCentroTrabajo'] ?? '', true);
        $DirectorNombre = ConfigNormalizar($_POST['DirectorNombre'] ?? '', true);
        $MunicipioEstado = ConfigNormalizar($_POST['MunicipioEstado'] ?? '', true);
        $TelefonoEscuela = ConfigNormalizar($_POST['TelefonoEscuela'] ?? '', false);
        $CorreoEscuela = ConfigNormalizar($_POST['CorreoEscuela'] ?? '', false);
        $LemaInstitucional = ConfigNormalizar($_POST['LemaInstitucional'] ?? '', false);
        $ColorInstitucional = SgceNormalizarColorHex($_POST['ColorInstitucional'] ?? '#97051E');

        $CicloNombre = ConfigNormalizar($_POST['CicloNombre'] ?? '', true);
        $FechaInicio = trim((string)($_POST['FechaInicio'] ?? ''));
        $FechaFin = trim((string)($_POST['FechaFin'] ?? ''));
        $PeriodoUno = ConfigNormalizar($_POST['PeriodoUno'] ?? '', true);
        $PeriodoDos = ConfigNormalizar($_POST['PeriodoDos'] ?? '', true);
        $PeriodoTres = ConfigNormalizar($_POST['PeriodoTres'] ?? '', true);
        $PlaneacionesCantidad = max(1, min(12, (int)($_POST['PlaneacionesCantidad'] ?? 1)));
        $NivelEducativo = SgceNivelEducativoValido((string)($_POST['NivelEducativo'] ?? 'SECUNDARIA'));
        $TipoPeriodizacion = SgceTipoPeriodizacionValido((string)($_POST['TipoPeriodizacion'] ?? 'ANUAL'));
        $TotalEtapas = max(1, min(20, (int)($_POST['TotalEtapas'] ?? 3)));
        $UsaCarreras = !empty($_POST['UsaCarreras']) || SgceRequiereCarrerasPorDefecto($NivelEducativo, $TipoPeriodizacion);
        $CarrerasIniciales = ConfigNormalizar($_POST['CarrerasIniciales'] ?? '', true);
        $NombreOfertaAcademica = ConfigNormalizar($_POST['NombreOfertaAcademica'] ?? ($NivelEducativo . ' ' . $TipoPeriodizacion), true);

        if ($NombreEscuela === '' || ConfigLongitud($NombreEscuela) < 3) { throw new Exception('Escribe el nombre oficial de la escuela.'); }
        if ($ClaveCentroTrabajo !== '' && !preg_match('/^[A-Z0-9-]{3,30}$/', $ClaveCentroTrabajo)) { throw new Exception('La CCT / clave solo debe usar letras, números o guion.'); }
        if ($DirectorNombre !== '' && !preg_match('/^[A-ZÁÉÍÓÚÜÑ .\'-]{3,120}$/u', $DirectorNombre)) { throw new Exception('El nombre del director solo debe contener letras y espacios.'); }
        if ($TelefonoEscuela !== '' && !preg_match('/^\d{7,15}$/', $TelefonoEscuela)) { throw new Exception('El teléfono debe contener solo números, mínimo 7 y máximo 15 dígitos.'); }
        if ($CorreoEscuela !== '' && !filter_var($CorreoEscuela, FILTER_VALIDATE_EMAIL)) { throw new Exception('El correo institucional no tiene formato válido.'); }
        if (!preg_match('/^#[0-9A-F]{6}$/', $ColorInstitucional)) { throw new Exception('El color institucional no tiene formato válido.'); }
        if ($CicloNombre === '' || ConfigLongitud($CicloNombre) > 40 || !ConfigFechaValida($FechaInicio) || !ConfigFechaValida($FechaFin) || strtotime($FechaInicio) >= strtotime($FechaFin)) {
            throw new Exception('Revisa el ciclo escolar. Las fechas no son válidas y el nombre del ciclo no debe superar 40 caracteres.');
        }
        if ($PeriodoUno === '' || $PeriodoDos === '' || $PeriodoTres === '') { throw new Exception('Los tres periodos son obligatorios.'); }
        foreach ([$PeriodoUno, $PeriodoDos, $PeriodoTres] as $NombrePeriodoValidar) {
            if (ConfigLongitud($NombrePeriodoValidar) > 80) { throw new Exception('El nombre de cada periodo no debe superar 80 caracteres.'); }
        }
        if (count(array_unique([$PeriodoUno, $PeriodoDos, $PeriodoTres])) !== 3) { throw new Exception('Los periodos no pueden repetirse.'); }
        if ($PlaneacionesCantidad < 1 || $PlaneacionesCantidad > 12) { throw new Exception('La cantidad de planeaciones debe estar entre 1 y 12.'); }
        if ($TotalEtapas < 1 || $TotalEtapas > 20) { throw new Exception('La cantidad de etapas académicas debe estar entre 1 y 20.'); }
        if ($NombreOfertaAcademica === '' || ConfigLongitud($NombreOfertaAcademica) > 140) { throw new Exception('Escribe un nombre válido para la oferta educativa.'); }
        if ($UsaCarreras && $CarrerasIniciales === '' && count(SgceCarrerasListar($Pdo, true)) === 0) { throw new Exception('Si activas carreras/programas, registra al menos una carrera inicial.'); }
        $OfertaActualValidar = SgceOfertaActiva($Pdo);
        if ($OfertaActualValidar) {
            $StmtGruposOferta = $Pdo->prepare('SELECT COUNT(*) FROM Grupos WHERE OfertaId = ?');
            $StmtGruposOferta->execute([(int)$OfertaActualValidar['Id']]);
            $TieneGruposOferta = (int)$StmtGruposOferta->fetchColumn() > 0;
            $CambioEstructura = (string)$OfertaActualValidar['NivelEducativo'] !== $NivelEducativo
                || (string)$OfertaActualValidar['TipoPeriodizacion'] !== $TipoPeriodizacion
                || (int)$OfertaActualValidar['TotalEtapas'] !== $TotalEtapas
                || (int)$OfertaActualValidar['UsaCarreras'] !== ($UsaCarreras ? 1 : 0);
            if ($TieneGruposOferta && $CambioEstructura) {
                throw new Exception('La estructura académica ya tiene grupos vinculados. Por seguridad no se puede cambiar nivel, periodización, etapas o uso de carreras después de crear grupos. Puedes agregar carreras nuevas en el campo de carreras iniciales.');
            }
        }

        SgceCrearTablaConfiguracionSiNoExiste($Pdo);
        CrearTablaBitacoraSiNoExiste($Pdo);
        $Pdo->beginTransaction();
        SgceGuardarConfiguracion($Pdo, [
            'NombreEscuela' => $NombreEscuela,
            'ClaveCentroTrabajo' => $ClaveCentroTrabajo,
            'DirectorNombre' => $DirectorNombre,
            'MunicipioEstado' => $MunicipioEstado,
            'TelefonoEscuela' => $TelefonoEscuela,
            'CorreoEscuela' => $CorreoEscuela,
            'LemaInstitucional' => $LemaInstitucional,
            'ColorInstitucional' => $ColorInstitucional,
            'SistemaNombre' => 'SGCE',
            'PlaneacionesCantidad' => (string)$PlaneacionesCantidad,
            'NivelEducativo' => $NivelEducativo,
            'TipoPeriodizacion' => $TipoPeriodizacion,
            'TotalEtapas' => (string)$TotalEtapas,
            'UsaCarreras' => $UsaCarreras ? '1' : '0',
            'NombreOfertaAcademica' => $NombreOfertaAcademica,
        ]);
        SgceConfigurarMultiescolarInicial($Pdo, $NivelEducativo, $TipoPeriodizacion, $TotalEtapas, $UsaCarreras, $CarrerasIniciales, $NombreOfertaAcademica);

        $CicloActivo = SgceCicloActivo($Pdo);
        $CicloId = (int)($CicloActivo['Id'] ?? 0);
        $NombreActivoActual = ConfigNormalizar($CicloActivo['Nombre'] ?? '', true);
        $EsCambioDeCiclo = ($CicloId <= 0 || $NombreActivoActual !== $CicloNombre);

        if ($EsCambioDeCiclo) {
            $Pdo->prepare('UPDATE CiclosEscolares SET Activo = 0')->execute();
            $StmtExisteCiclo = $Pdo->prepare('SELECT Id FROM CiclosEscolares WHERE Nombre = ? LIMIT 1');
            $StmtExisteCiclo->execute([$CicloNombre]);
            $CicloExistenteId = (int)$StmtExisteCiclo->fetchColumn();
            if ($CicloExistenteId > 0) {
                $StmtCiclo = $Pdo->prepare('UPDATE CiclosEscolares SET FechaInicio = ?, FechaFin = ?, Activo = 1 WHERE Id = ?');
                $StmtCiclo->execute([$FechaInicio, $FechaFin, $CicloExistenteId]);
                $CicloId = $CicloExistenteId;
            } else {
                $StmtCiclo = $Pdo->prepare('INSERT INTO CiclosEscolares (Nombre, FechaInicio, FechaFin, Activo) VALUES (?, ?, ?, 1)');
                $StmtCiclo->execute([$CicloNombre, $FechaInicio, $FechaFin]);
                $CicloId = (int)$Pdo->lastInsertId();
            }
        } else {
            $StmtCiclo = $Pdo->prepare('UPDATE CiclosEscolares SET FechaInicio = ?, FechaFin = ?, Activo = 1 WHERE Id = ?');
            $StmtCiclo->execute([$FechaInicio, $FechaFin, $CicloId]);
        }

        $PeriodosFinales = [1 => $PeriodoUno, 2 => $PeriodoDos, 3 => $PeriodoTres];
        $StmtTemp = $Pdo->prepare('UPDATE PeriodosEvaluacion SET Nombre = ? WHERE CicloId = ? AND Orden = ?');
        foreach ($PeriodosFinales as $OrdenTemp => $NombreFinal) {
            $StmtTemp->execute(['__TEMP_PERIODO_' . $OrdenTemp . '_' . time(), $CicloId, $OrdenTemp]);
        }
        $StmtBuscarPeriodo = $Pdo->prepare('SELECT Id FROM PeriodosEvaluacion WHERE CicloId = ? AND Orden = ? LIMIT 1');
        $StmtActualizarPeriodo = $Pdo->prepare('UPDATE PeriodosEvaluacion SET Nombre = ?, Activo = 1 WHERE Id = ?');
        $StmtInsertarPeriodo = $Pdo->prepare('INSERT INTO PeriodosEvaluacion (CicloId, Nombre, Orden, Activo) VALUES (?, ?, ?, 1)');
        foreach ($PeriodosFinales as $OrdenPeriodo => $NombrePeriodo) {
            $StmtBuscarPeriodo->execute([$CicloId, $OrdenPeriodo]);
            $PeriodoExistenteId = (int)$StmtBuscarPeriodo->fetchColumn();
            if ($PeriodoExistenteId > 0) {
                $StmtActualizarPeriodo->execute([$NombrePeriodo, $PeriodoExistenteId]);
            } else {
                $StmtInsertarPeriodo->execute([$CicloId, $NombrePeriodo, $OrdenPeriodo]);
            }
        }
        $Pdo->prepare('UPDATE PeriodosEvaluacion SET Activo = 0 WHERE CicloId = ? AND Orden NOT BETWEEN 1 AND 3')->execute([$CicloId]);

        RegistrarBitacora($Pdo, $UserSession, 'ACTUALIZAR_CONFIGURACION', 'ConfiguracionSistema', null, 'CONFIGURACIÓN INSTITUCIONAL ACTUALIZADA');
        $Pdo->commit();
        $_SESSION['MensajeConfiguracion'] = 'Configuración guardada correctamente.';
        $_SESSION['MensajeConfiguracionTipo'] = 'success';
    } catch (Exception $E) {
        if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
        $CodigoError = SgceRegistrarErrorTecnico('CONFIGURACION_ADMIN', $E);
        $_SESSION['MensajeConfiguracion'] = 'No se pudo guardar la configuración. Código de seguimiento: ' . $CodigoError;
        $_SESSION['MensajeConfiguracionTipo'] = 'danger';
    }
    header('Location: ConfiguracionAdmin.php');
    exit;
}

$Config = SgceObtenerConfiguracion($Pdo);
$OfertaActivaConfig = SgceOfertaActiva($Pdo);
$NivelEducativoConfig = SgceNivelEducativoValido($Config['NivelEducativo'] ?? ($OfertaActivaConfig['NivelEducativo'] ?? 'SECUNDARIA'));
$TipoPeriodizacionConfig = SgceTipoPeriodizacionValido($Config['TipoPeriodizacion'] ?? ($OfertaActivaConfig['TipoPeriodizacion'] ?? 'ANUAL'));
$TotalEtapasConfig = (int)($Config['TotalEtapas'] ?? ($OfertaActivaConfig['TotalEtapas'] ?? 3));
$UsaCarrerasConfig = !empty($Config['UsaCarreras']) || !empty($OfertaActivaConfig['UsaCarreras']);
$CarrerasConfig = SgceCarrerasListar($Pdo, true);
$EtapasConfig = !empty($OfertaActivaConfig['Id']) ? SgceEtapasAcademicasListar($Pdo, (int)$OfertaActivaConfig['Id'], true) : [];
$CicloActivo = SgceCicloActivo($Pdo);
$CiclosInactivosMigracion = SgceCiclosInactivosConGrupos($Pdo);
$CicloOrigenMigracionId = max(0, (int)($_GET['CicloOrigenId'] ?? ($CiclosInactivosMigracion[0]['Id'] ?? 0)));
$GruposMigracion = $CicloOrigenMigracionId > 0 ? SgceGruposListarPorCiclo($Pdo, $CicloOrigenMigracionId, true) : [];
$Periodos = [];
if (!empty($CicloActivo['Id'])) {
    $StmtPeriodos = $Pdo->prepare('SELECT Orden, Nombre FROM PeriodosEvaluacion WHERE CicloId = ? AND Orden BETWEEN 1 AND 3 ORDER BY Orden ASC');
    $StmtPeriodos->execute([(int)$CicloActivo['Id']]);
    foreach ($StmtPeriodos->fetchAll() as $P) { $Periodos[(int)$P['Orden']] = $P['Nombre']; }
}
$PeriodoUno = $Periodos[1] ?? 'PRIMER PARCIAL';
$PeriodoDos = $Periodos[2] ?? 'SEGUNDO PARCIAL';
$PeriodoTres = $Periodos[3] ?? 'TERCER PARCIAL';
$PlaneacionesCantidad = SgceCantidadPlaneaciones($Pdo);
$Mensaje = $_SESSION['MensajeConfiguracion'] ?? '';
$MensajeTipo = $_SESSION['MensajeConfiguracionTipo'] ?? 'success';
unset($_SESSION['MensajeConfiguracion'], $_SESSION['MensajeConfiguracionTipo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Configuración | SGCE</title>
<link rel="icon" type="image/x-icon" href="assets/media/img/favicon.ico">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sgce-base.min.css?v=sgce">
<link rel="stylesheet" href="assets/css/sgce-soft-motion.css?v=sgce">
<?= SgceEstilosTema($Pdo) ?>
<link rel="stylesheet" href="assets/css/configuracion-botones-metalicos.css?v=sgce">
</head>
<body>
<div class="SgcePageWrap SgceModuleWrap container-fluid px-4 py-4">
    <section class="SgceHero mb-4">
        <div class="SgceHeroInfo">
            <div class="SgceHeroIcon"><span class="SgceColorIcon" aria-hidden="true">🏫</span></div>
            <div>
                <h1>CONFIGURACIÓN GENERAL</h1>
                <p>Datos institucionales, ciclo escolar activo y periodos usados en reportes, boletas y paneles.</p>
            </div>
        </div>
        <div class="SgceHeroActions">
            <a href="Admin.php?Tab=inicio" class="SgceBtnVolverInicio" title="Volver al inicio" aria-label="Volver al inicio"><i class="fa-solid fa-house"></i><span>Volver al inicio</span></a>
        </div>
    </section>

    <?php if ($Mensaje !== ''): ?>
        <div class="alert alert-<?= HConfig($MensajeTipo) ?> alert-dismissible fade show shadow-sm border-0 rounded-4" role="alert">
            <i class="fa-solid <?= $MensajeTipo === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?> me-2"></i><?= HConfig($Mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <form method="post" class="SgceConfigGrid SgceConfigGridRedisenada">
        <?= CampoCsrf() ?>
        <div class="SgceConfigLeftCol">
        <section class="SgceConfigCard SgceConfigCardWide SgceConfigSchoolCard">
            <div class="SgceConfigHead">
                <span><span class="SgceColorIcon" aria-hidden="true">🏫</span></span>
                <div><h2>Datos de la escuela</h2><p>Esta información aparece en boletas, reportes y pantallas públicas.</p></div>
            </div>
            <div class="row g-3">
                <div class="col-md-8"><label class="SgceFieldLabel">Nombre oficial</label><input class="form-control FormControl InputUpper" name="NombreEscuela" value="<?= HConfig($Config['NombreEscuela']) ?>" required></div>
                <div class="col-md-4"><label class="SgceFieldLabel">CCT / Clave</label><input class="form-control FormControl InputUpper" name="ClaveCentroTrabajo" value="<?= HConfig($Config['ClaveCentroTrabajo']) ?>"></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Director(a)</label><input class="form-control FormControl InputUpper" name="DirectorNombre" value="<?= HConfig($Config['DirectorNombre']) ?>"></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Municipio y estado</label><input class="form-control FormControl InputUpper" name="MunicipioEstado" value="<?= HConfig($Config['MunicipioEstado']) ?>"></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Teléfono</label><input class="form-control FormControl InputDigits" name="TelefonoEscuela" value="<?= HConfig($Config['TelefonoEscuela']) ?>" inputmode="numeric" maxlength="15" pattern="\d{0,15}"></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Correo institucional</label><input class="form-control FormControl" type="email" name="CorreoEscuela" value="<?= HConfig($Config['CorreoEscuela']) ?>" maxlength="120"></div>
                <div class="col-md-8"><label class="SgceFieldLabel">Lema o leyenda inferior</label><input class="form-control FormControl" name="LemaInstitucional" value="<?= HConfig($Config['LemaInstitucional']) ?>"></div>
                <div class="col-md-4"><label class="SgceFieldLabel">Color institucional</label><div class="SgceColorControl"><input class="form-control FormControl" type="color" name="ColorInstitucional" id="ColorInstitucional" value="<?= HConfig(SgceNormalizarColorHex($Config['ColorInstitucional'] ?? '#97051E')) ?>"><span id="ColorInstitucionalTexto"><?= HConfig(SgceNormalizarColorHex($Config['ColorInstitucional'] ?? '#97051E')) ?></span></div></div>
            </div>
        </section>

        <section class="SgceConfigCard SgceConfigCardWide mt-4">
            <div class="SgceConfigHead">
                <span><span class="SgceColorIcon" aria-hidden="true">🧭</span></span>
                <div>
                    <h2>Estructura multiescolar</h2>
                    <p>Define si el sistema trabajará como primaria, secundaria, bachillerato, universidad, maestría, doctorado o curso. La migración usa esta estructura, no reglas fijas.</p>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="SgceFieldLabel">Nombre de la oferta educativa</label>
                    <input class="form-control FormControl InputUpper" name="NombreOfertaAcademica" value="<?= HConfig($Config['NombreOfertaAcademica'] ?? ($OfertaActivaConfig['Nombre'] ?? 'SECUNDARIA')) ?>" maxlength="140" required>
                </div>
                <div class="col-md-6">
                    <label class="SgceFieldLabel">Nivel educativo</label>
                    <select name="NivelEducativo" class="form-select FormControl" required>
                        <?php foreach(SgceNivelEducativoOpciones() as $ClaveNivel => $TextoNivel): ?>
                            <option value="<?= HConfig($ClaveNivel) ?>" <?= $NivelEducativoConfig === $ClaveNivel ? 'selected' : '' ?>><?= HConfig($TextoNivel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="SgceFieldLabel">Organización académica</label>
                    <select name="TipoPeriodizacion" class="form-select FormControl" required>
                        <?php foreach(SgceTipoPeriodizacionOpciones() as $ClaveTipo => $TextoTipo): ?>
                            <option value="<?= HConfig($ClaveTipo) ?>" <?= $TipoPeriodizacionConfig === $ClaveTipo ? 'selected' : '' ?>><?= HConfig($TextoTipo) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="SgceFieldLabel">Cantidad de años / semestres / etapas</label>
                    <input class="form-control FormControl InputDigits" name="TotalEtapas" value="<?= HConfig((string)$TotalEtapasConfig) ?>" min="1" max="20" maxlength="2" inputmode="numeric" required>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <label class="form-check fw-semibold">
                        <input class="form-check-input" type="checkbox" name="UsaCarreras" value="1" <?= $UsaCarrerasConfig ? 'checked' : '' ?>> Usa carreras / programas
                    </label>
                </div>
                <div class="col-12">
                    <label class="SgceFieldLabel">Carreras iniciales opcionales</label>
                    <textarea class="form-control FormControl InputUpper" name="CarrerasIniciales" rows="2" placeholder="Ejemplo: INFORMÁTICA, CONTABILIDAD, ENFERMERÍA"><?php if (!empty($CarrerasConfig)) { echo HConfig(implode(', ', array_column($CarrerasConfig, 'Nombre'))); } ?></textarea>
                    <small class="text-muted fw-semibold">En primaria/secundaria puedes dejarlo vacío. En universidad, maestría, doctorado o bachilleratos técnicos puedes capturar carreras separadas por coma.</small>
                </div>
                <?php if (!empty($EtapasConfig)): ?>
                <div class="col-12">
                    <div class="alert alert-light border rounded-4 mb-0"><strong>Etapas activas:</strong>
                        <?= HConfig(implode(' → ', array_map(static fn($E) => $E['Nombre'], $EtapasConfig))) ?> → EGRESADO
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="SgceConfigActions SgceConfigActionsInline">
            <div>
                <strong><i class="fa-solid fa-circle-info"></i> Cambios globales</strong>
                <span>Al guardar se actualizan reportes, boletas, consulta pública y paneles.</span>
            </div>
            <button type="submit" id="BtnGuardarConfiguracionVerdeMetalico" class="SgceConfigSave BtnConfiguracionGuardarMetalico"><span class="SgceColorIcon" aria-hidden="true">💾</span> Guardar configuración</button>
        </section>
        </div>

        <div class="SgceConfigRightCol">
        <section class="SgceConfigCard SgceConfigCycleCard">
            <div class="SgceConfigHead">
                <span><span class="SgceColorIcon" aria-hidden="true">📅</span></span>
                <div><h2>Ciclo activo</h2><p>Rango usado para asistencias, reportes y estadísticas.</p></div>
            </div>
            <div class="row g-3">
                <div class="col-12"><label class="SgceFieldLabel">Nombre del ciclo</label><input class="form-control FormControl InputUpper" name="CicloNombre" value="<?= HConfig($CicloActivo['Nombre'] ?? '') ?>" maxlength="40" required></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Fecha inicio</label><input class="form-control FormControl" type="date" name="FechaInicio" value="<?= HConfig($CicloActivo['FechaInicio'] ?? '') ?>" required></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Fecha fin</label><input class="form-control FormControl" type="date" name="FechaFin" value="<?= HConfig($CicloActivo['FechaFin'] ?? '') ?>" required></div>
            </div>
        </section>

        <section class="SgceConfigCard SgceConfigPeriodsCard">
            <div class="SgceConfigHead">
                <span><span class="SgceColorIcon" aria-hidden="true">📋</span></span>
                <div><h2>Periodos y planeaciones</h2><p>Parciales disponibles y cantidad de planeaciones que se entregan por materia.</p></div>
            </div>
            <div class="row g-3">
                <div class="col-12"><label class="SgceFieldLabel">Periodo 1</label><input class="form-control FormControl InputUpper" name="PeriodoUno" value="<?= HConfig($PeriodoUno) ?>" maxlength="80" required></div>
                <div class="col-12"><label class="SgceFieldLabel">Periodo 2</label><input class="form-control FormControl InputUpper" name="PeriodoDos" value="<?= HConfig($PeriodoDos) ?>" maxlength="80" required></div>
                <div class="col-12"><label class="SgceFieldLabel">Periodo 3</label><input class="form-control FormControl InputUpper" name="PeriodoTres" value="<?= HConfig($PeriodoTres) ?>" maxlength="80" required></div>
                <div class="col-12"><label class="SgceFieldLabel">Planeaciones por ciclo</label><input class="form-control FormControl InputDigits" name="PlaneacionesCantidad" value="<?= HConfig($PlaneacionesCantidad) ?>" required min="1" max="12" maxlength="2" inputmode="numeric"><small class="text-muted fw-semibold">Define cuántas planeaciones debe entregar cada docente por materia en el ciclo activo.</small></div>
            </div>
        </section>
        </div>
    </form>

    <section class="SgceConfigCard mt-4">
        <div class="SgceConfigHead">
            <span><span class="SgceColorIcon" aria-hidden="true">🔁</span></span>
            <div>
                <h2>Migración de ciclo escolar</h2>
                <p>Promueve alumnos conservando historial según la estructura académica configurada: primaria, secundaria, bachillerato, universidad, posgrado o cursos. Antes de mover alumnos congela su kardex para proteger boletas históricas. Solo permite migrar desde ciclos cerrados/inactivos hacia el ciclo activo.</p>
            </div>
        </div>

        <div class="alert alert-warning border-0 rounded-4 shadow-sm">
            <strong><i class="fa-solid fa-shield-halved me-2"></i>Validación importante:</strong>
            no se renombra el grupo anterior. SGCE crea o reutiliza el grupo equivalente del ciclo activo para no contaminar boletas, asistencias ni calificaciones históricas.
        </div>

        <form method="get" class="row g-3 align-items-end mb-3">
            <input type="hidden" name="Tab" value="configuracion">
            <div class="col-md-8">
                <label class="SgceFieldLabel">Ciclo origen cerrado</label>
                <select name="CicloOrigenId" class="form-select FormControl" onchange="this.form.submit()">
                    <?php if(empty($CiclosInactivosMigracion)): ?>
                        <option value="">No hay ciclos inactivos con grupos para migrar</option>
                    <?php else: ?>
                        <?php foreach($CiclosInactivosMigracion as $Ci): ?>
                            <option value="<?= (int)$Ci['Id'] ?>" <?= (int)$CicloOrigenMigracionId === (int)$Ci['Id'] ? 'selected' : '' ?>><?= HConfig($Ci['Nombre']) ?> · <?= (int)$Ci['TotalGrupos'] ?> grupo(s)</option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-4"><button class="btn btn-outline-secondary w-100" type="submit"><i class="fa-solid fa-filter me-2"></i>Ver grupos</button></div>
        </form>

        <div class="row g-3">
            <div class="col-lg-6">
                <form method="post" class="p-3 border rounded-4 h-100 bg-light">
                    <?= CampoCsrf() ?>
                    <input type="hidden" name="MigrarGrupoAcademico" value="1">
                    <h5 class="fw-bold mb-2"><i class="fa-solid fa-users-viewfinder me-2"></i>Migrar un grupo</h5>
                    <p class="text-muted small fw-semibold">Ejemplo: un grupo de la etapa anterior pasa a la siguiente etapa configurada. Si está en la última etapa, queda como egresado.</p>
                    <label class="SgceFieldLabel">Grupo origen</label>
                    <select name="GrupoOrigenId" class="form-select FormControl mb-3" required <?= empty($GruposMigracion) ? 'disabled' : '' ?>>
                        <?php if(empty($GruposMigracion)): ?>
                            <option value="">Sin grupos disponibles</option>
                        <?php else: ?>
                            <?php foreach($GruposMigracion as $Gm): ?>
                                <option value="<?= (int)$Gm['Id'] ?>"><?= HConfig($Gm['Grado'].' '.$Gm['Grupo'].' '.$Gm['Turno']) ?> · <?= (int)$Gm['TotalAlumnos'] ?> alumno(s)</option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="CopiarAsignaciones" value="1" id="CopiarAsignacionesGrupo">
                        <label class="form-check-label fw-semibold" for="CopiarAsignacionesGrupo">Copiar asignaciones con docente activo al nuevo grupo</label>
                    </div>
                    <button class="btn btn-success w-100" type="submit" <?= empty($GruposMigracion) || empty($CicloActivo['Id']) ? 'disabled' : '' ?> data-sgce-confirm="delete" data-sgce-confirm-title="CONFIRMAR MIGRACIÓN" data-sgce-confirm-subtitle="MIGRAR GRUPO" data-sgce-confirm-message="¿DESEAS MIGRAR ESTE GRUPO AL CICLO ACTIVO?" data-sgce-confirm-detail="Se conservará el historial del ciclo origen y se creará la inscripción del alumno en el ciclo activo." data-sgce-confirm-button="SÍ, MIGRAR GRUPO" data-sgce-confirm-loading="MIGRANDO..." data-sgce-confirm-icon="fa-rotate">
                        <i class="fa-solid fa-arrow-up-a-z me-2"></i>Migrar grupo al siguiente grado
                    </button>
                </form>
            </div>

            <div class="col-lg-6">
                <form method="post" class="p-3 border rounded-4 h-100 bg-light">
                    <?= CampoCsrf() ?>
                    <input type="hidden" name="MigrarCicloAcademico" value="1">
                    <input type="hidden" name="CicloOrigenId" value="<?= (int)$CicloOrigenMigracionId ?>">
                    <h5 class="fw-bold mb-2"><i class="fa-solid fa-school-circle-check me-2"></i>Migrar ciclo completo</h5>
                    <p class="text-muted small fw-semibold">Procesa todos los grupos del ciclo origen seleccionado. Úsalo al cierre oficial del año escolar.</p>
                    <div class="mb-3">
                        <span class="badge text-bg-primary">Destino activo: <?= HConfig($CicloActivo['Nombre'] ?? 'SIN CICLO ACTIVO') ?></span>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="CopiarAsignaciones" value="1" id="CopiarAsignacionesCiclo">
                        <label class="form-check-label fw-semibold" for="CopiarAsignacionesCiclo">Copiar asignaciones con docente activo de todos los grupos</label>
                    </div>
                    <button class="btn btn-success w-100" type="submit" <?= empty($GruposMigracion) || empty($CicloActivo['Id']) ? 'disabled' : '' ?> data-sgce-confirm="delete" data-sgce-confirm-title="CONFIRMAR MIGRACIÓN MASIVA" data-sgce-confirm-subtitle="MIGRAR CICLO COMPLETO" data-sgce-confirm-message="¿DESEAS MIGRAR TODOS LOS GRUPOS DEL CICLO CERRADO?" data-sgce-confirm-detail="Esta operación es segura e idempotente: no duplica alumnos ya inscritos en el ciclo activo; los conflictos se omiten." data-sgce-confirm-button="SÍ, MIGRAR CICLO" data-sgce-confirm-loading="MIGRANDO CICLO..." data-sgce-confirm-icon="fa-school">
                        <i class="fa-solid fa-forward me-2"></i>Migrar todos los grupos
                    </button>
                </form>
            </div>
        </div>
    </section>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/sgce-shared.js?v=sgce"></script>
<script src="assets/js/ConfiguracionAdmin.js?v=sgce"></script>
</body>
</html>
