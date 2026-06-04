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
        $PeriodosCantidad = max(1, min(12, (int)($_POST['PeriodosCantidad'] ?? 3)));
        $PeriodosNombreBase = SgceNombreBasePeriodoValido((string)($_POST['PeriodosNombreBase'] ?? 'PARCIAL'));
        $PeriodosModo = SgceModoPeriodosValido((string)($_POST['PeriodosModo'] ?? 'AUTOMATICO'));
        $PeriodosPersonalizados = ConfigNormalizar($_POST['PeriodosPersonalizados'] ?? '', true);
        $PeriodosFinales = SgceGenerarNombresPeriodos($PeriodosCantidad, $PeriodosNombreBase, $PeriodosModo, $PeriodosPersonalizados);
        $UsaPlaneaciones = !empty($_POST['UsaPlaneaciones']);
        $TipoPlaneacion = $UsaPlaneaciones ? SgceTipoPlaneacionValido((string)($_POST['TipoPlaneacion'] ?? 'PERIODO')) : 'PERIODO';
        $PlaneacionesCantidad = $UsaPlaneaciones ? max(1, min(12, (int)($_POST['PlaneacionesCantidad'] ?? 0))) : 0;
        $NivelEducativo = SgceNivelEducativoValido((string)($_POST['NivelEducativo'] ?? 'SECUNDARIA'));
        $TipoPeriodizacion = SgceTipoPeriodizacionValido((string)($_POST['TipoPeriodizacion'] ?? 'ANUAL'));
        $TotalEtapas = max(1, min(20, (int)($_POST['TotalEtapas'] ?? 3)));
        $UsaProgramasManual = !empty($_POST['UsaProgramas']);
        $RequiereProgramasPorNivel = SgceRequiereProgramasEducativosPorDefecto($NivelEducativo, $TipoPeriodizacion);
        $UsaProgramas = $UsaProgramasManual || $RequiereProgramasPorNivel;
        $ProgramasIniciales = ConfigNormalizar($_POST['ProgramasIniciales'] ?? '', true);
        $NombreOfertaAcademica = ConfigNormalizar($_POST['NombreOfertaAcademica'] ?? ($NivelEducativo . ' ' . $TipoPeriodizacion), true);
        $ProgramasCapturados = array_values(array_filter(array_map(static function($P) { return ConfigNormalizar($P, true); }, preg_split('/[,;\n]+/u', $ProgramasIniciales))));
        $ProgramasRealesCapturados = array_values(array_filter($ProgramasCapturados, static fn($P) => $P !== 'GENERAL'));

        // Si ya existen programas reales registrados, no se obliga a volver a escribirlos en cada guardado.
        // Así, activar o desactivar planeaciones no falla por una validación que pertenece a otra sección.
        $OfertaActualValidar = SgceOfertaActiva($Pdo);
        $ProgramasRealesExistentes = [];
        if (!empty($OfertaActualValidar['Id'])) {
            foreach (SgceProgramasEducativosListar($Pdo, true, (int)$OfertaActualValidar['Id']) as $ProgramaExistente) {
                $NombreProgramaExistente = ConfigNormalizar($ProgramaExistente['Nombre'] ?? '', true);
                if ($NombreProgramaExistente !== '' && $NombreProgramaExistente !== 'GENERAL') { $ProgramasRealesExistentes[] = $NombreProgramaExistente; }
            }
        }
        $ProgramasRealesDisponibles = array_values(array_unique(array_merge($ProgramasRealesExistentes, $ProgramasRealesCapturados)));
        if (($UsaProgramasManual || $RequiereProgramasPorNivel) && count($ProgramasRealesDisponibles) === 0) {
            throw new Exception('Captura al menos un programa educativo real o desmarca la opción Usa programas educativos si la institución no los maneja.');
        }

        if ($NombreEscuela === '' || ConfigLongitud($NombreEscuela) < 3) { throw new Exception('Escribe el nombre oficial de la escuela.'); }
        if ($ClaveCentroTrabajo !== '' && !preg_match('/^[A-Z0-9-]{3,30}$/', $ClaveCentroTrabajo)) { throw new Exception('La CCT / clave solo debe usar letras, números o guion.'); }
        if ($DirectorNombre !== '' && !preg_match('/^[A-ZÁÉÍÓÚÜÑ .\'-]{3,120}$/u', $DirectorNombre)) { throw new Exception('El nombre del director solo debe contener letras y espacios.'); }
        if ($TelefonoEscuela !== '' && !preg_match('/^\d{7,15}$/', $TelefonoEscuela)) { throw new Exception('El teléfono debe contener solo números, mínimo 7 y máximo 15 dígitos.'); }
        if ($CorreoEscuela !== '' && !filter_var($CorreoEscuela, FILTER_VALIDATE_EMAIL)) { throw new Exception('El correo institucional no tiene formato válido.'); }
        if (!preg_match('/^#[0-9A-F]{6}$/', $ColorInstitucional)) { throw new Exception('El color institucional no tiene formato válido.'); }
        if ($CicloNombre === '' || ConfigLongitud($CicloNombre) > 40 || !ConfigFechaValida($FechaInicio) || !ConfigFechaValida($FechaFin) || strtotime($FechaInicio) >= strtotime($FechaFin)) {
            throw new Exception('Revisa el ciclo escolar. Las fechas no son válidas y el nombre del ciclo no debe superar 40 caracteres.');
        }
        if (count($PeriodosFinales) !== $PeriodosCantidad || count(array_unique($PeriodosFinales)) !== count($PeriodosFinales)) { throw new Exception('Revisa los periodos de evaluación. Deben existir y no repetirse.'); }
        foreach ($PeriodosFinales as $NombrePeriodoValidar) {
            if (ConfigLongitud($NombrePeriodoValidar) > 80) { throw new Exception('El nombre de cada periodo no debe superar 80 caracteres.'); }
        }
        if ($UsaPlaneaciones && ($PlaneacionesCantidad < 1 || $PlaneacionesCantidad > 12)) { throw new Exception('La cantidad de planeaciones debe estar entre 1 y 12.'); }
        if ($TotalEtapas < 1 || $TotalEtapas > 20) { throw new Exception('La cantidad de etapas académicas debe estar entre 1 y 20.'); }
        if ($NombreOfertaAcademica === '' || ConfigLongitud($NombreOfertaAcademica) > 140) { throw new Exception('Escribe un nombre válido para la oferta educativa.'); }
        // Aunque la oferta no use programas visibles, SGCE siempre crea y usa un programa interno GENERAL.
        if ($OfertaActualValidar) {
            $StmtGruposOferta = $Pdo->prepare('SELECT COUNT(*) FROM Grupos WHERE OfertaId = ?');
            $StmtGruposOferta->execute([(int)$OfertaActualValidar['Id']]);
            $TieneGruposOferta = (int)$StmtGruposOferta->fetchColumn() > 0;
            $CambioNombreOferta = (string)$OfertaActualValidar['Nombre'] !== $NombreOfertaAcademica;
            $CambioEstructura = (string)$OfertaActualValidar['NivelEducativo'] !== $NivelEducativo
                || (string)$OfertaActualValidar['TipoPeriodizacion'] !== $TipoPeriodizacion
                || (int)$OfertaActualValidar['TotalEtapas'] !== $TotalEtapas
                || (int)$OfertaActualValidar['UsaProgramas'] !== ($UsaProgramas ? 1 : 0);
            if ($TieneGruposOferta && $CambioNombreOferta) {
                throw new Exception('La oferta educativa ya tiene grupos vinculados. Por seguridad no se puede cambiar su nombre después de crear grupos; así se evita ocultar alumnos, boletas o historial.');
            }
            if ($TieneGruposOferta && $CambioEstructura) {
                throw new Exception('La estructura académica ya tiene grupos vinculados. Por seguridad no se puede cambiar nivel, periodización, etapas o uso de programas después de crear grupos. Puedes agregar programas nuevos en el campo de programas iniciales.');
            }
            $CicloActivoValidar = SgceCicloActivo($Pdo);
            $CicloValidarId = (int)($CicloActivoValidar['Id'] ?? 0);
            $ConfigAcademicaActual = SgceConfiguracionAcademicaPorOferta($Pdo, (int)$OfertaActualValidar['Id']);
            $PeriodosActuales = [
                (int)($ConfigAcademicaActual['CantidadPeriodosEvaluacion'] ?? 0),
                (string)($ConfigAcademicaActual['NombreBasePeriodo'] ?? ''),
                (string)($ConfigAcademicaActual['ModoPeriodos'] ?? ''),
                trim((string)($ConfigAcademicaActual['PeriodosPersonalizados'] ?? '')),
            ];
            $PeriodosNuevos = [$PeriodosCantidad, $PeriodosNombreBase, $PeriodosModo, trim(implode(PHP_EOL, $PeriodosFinales))];
            if ($CicloValidarId > 0 && $PeriodosActuales !== $PeriodosNuevos && SgceCicloOfertaTieneCalificaciones($Pdo, $CicloValidarId, (int)$OfertaActualValidar['Id'])) {
                throw new Exception('No se pueden cambiar los periodos de evaluación porque ya existen calificaciones capturadas en el ciclo activo y oferta educativa. Esta estructura debe definirse antes de capturar calificaciones.');
            }
            $PlaneacionActual = [
                (int)($ConfigAcademicaActual['UsaPlaneaciones'] ?? 1),
                (string)($ConfigAcademicaActual['TipoPlaneacion'] ?? 'PERIODO'),
                (int)($ConfigAcademicaActual['PlaneacionesCantidad'] ?? 3),
            ];
            $PlaneacionNueva = [$UsaPlaneaciones ? 1 : 0, $TipoPlaneacion, $PlaneacionesCantidad];
            if ($CicloValidarId > 0 && $PlaneacionActual !== $PlaneacionNueva && SgceCicloOfertaTienePlaneaciones($Pdo, $CicloValidarId, (int)$OfertaActualValidar['Id'])) {
                throw new Exception('No se puede cambiar la estructura de planeaciones porque ya existen planeaciones cargadas en el ciclo activo y oferta educativa. Esta estructura debe definirse antes de recibir archivos.');
            }
        }

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
            'PeriodosCantidad' => (string)$PeriodosCantidad,
            'PeriodosNombreBase' => $PeriodosNombreBase,
            'PeriodosModo' => $PeriodosModo,
            'PeriodosPersonalizados' => implode(PHP_EOL, $PeriodosFinales),
            'UsaPlaneaciones' => $UsaPlaneaciones ? '1' : '0',
            'TipoPlaneacion' => $TipoPlaneacion,
            'PlaneacionesCantidad' => (string)$PlaneacionesCantidad,
            'NivelEducativo' => $NivelEducativo,
            'TipoPeriodizacion' => $TipoPeriodizacion,
            'TotalEtapas' => (string)$TotalEtapas,
            'UsaProgramas' => $UsaProgramas ? '1' : '0',
            'NombreOfertaAcademica' => $NombreOfertaAcademica,
        ]);
        $OfertaIdConfigurada = SgceConfigurarEstructuraAcademicaInicial($Pdo, $NivelEducativo, $TipoPeriodizacion, $TotalEtapas, $UsaProgramas, implode(PHP_EOL, $ProgramasRealesCapturados), $NombreOfertaAcademica, SgceEtiquetaEtapaPorTipo($TipoPeriodizacion), $PeriodosCantidad, $PeriodosNombreBase, $PeriodosModo, implode(PHP_EOL, $PeriodosFinales), $UsaPlaneaciones, $TipoPlaneacion, $PlaneacionesCantidad);

        $CicloActivo = SgceCicloActivo($Pdo);
        $CicloId = (int)($CicloActivo['Id'] ?? 0);
        $NombreActivoActual = ConfigNormalizar($CicloActivo['Nombre'] ?? '', true);
        $EsCambioDeCiclo = ($CicloId <= 0 || $NombreActivoActual !== $CicloNombre);

        if ($EsCambioDeCiclo) {
            $StmtExisteCiclo = $Pdo->prepare('SELECT Id FROM CiclosEscolares WHERE Nombre = ? LIMIT 1');
            $StmtExisteCiclo->execute([$CicloNombre]);
            $CicloExistenteId = (int)$StmtExisteCiclo->fetchColumn();
            if ($CicloExistenteId > 0) {
                $StmtCiclo = $Pdo->prepare('UPDATE CiclosEscolares SET FechaInicio = ?, FechaFin = ? WHERE Id = ?');
                $StmtCiclo->execute([$FechaInicio, $FechaFin, $CicloExistenteId]);
                $CicloId = $CicloExistenteId;
                SgceActivarCicloUnico($Pdo, $CicloId);
            } else {
                $StmtCiclo = $Pdo->prepare('INSERT INTO CiclosEscolares (Nombre, FechaInicio, FechaFin, Activo) VALUES (?, ?, ?, 0)');
                $StmtCiclo->execute([$CicloNombre, $FechaInicio, $FechaFin]);
                $CicloId = (int)$Pdo->lastInsertId();
                SgceActivarCicloUnico($Pdo, $CicloId);
            }
        } else {
            $StmtCiclo = $Pdo->prepare('UPDATE CiclosEscolares SET FechaInicio = ?, FechaFin = ? WHERE Id = ?');
            $StmtCiclo->execute([$FechaInicio, $FechaFin, $CicloId]);
            SgceActivarCicloUnico($Pdo, $CicloId);
        }

        SgceSincronizarPeriodosCicloOferta($Pdo, $CicloId, (int)($OfertaIdConfigurada ?? 0), $PeriodosFinales);

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
$EtiquetaEtapaUi = SgceEtiquetaEtapaActual($Pdo, (int)($OfertaActivaConfig['Id'] ?? 0));
$EtiquetaEtapaUiMinus = function_exists('mb_strtolower') ? mb_strtolower($EtiquetaEtapaUi, 'UTF-8') : strtolower($EtiquetaEtapaUi);
$NivelEducativoConfig = SgceNivelEducativoValido($Config['NivelEducativo'] ?? ($OfertaActivaConfig['NivelEducativo'] ?? 'SECUNDARIA'));
$TipoPeriodizacionConfig = SgceTipoPeriodizacionValido($Config['TipoPeriodizacion'] ?? ($OfertaActivaConfig['TipoPeriodizacion'] ?? 'ANUAL'));
$TotalEtapasConfig = (int)($Config['TotalEtapas'] ?? ($OfertaActivaConfig['TotalEtapas'] ?? 3));
$UsaProgramasConfig = !empty($Config['UsaProgramas']) || !empty($OfertaActivaConfig['UsaProgramas']);
$ConfigAcademica = SgceConfiguracionAcademicaPorOferta($Pdo, (int)($OfertaActivaConfig['Id'] ?? 0));
$ProgramasConfig = SgceProgramasEducativosListar($Pdo, true, (int)($OfertaActivaConfig['Id'] ?? 0));
$EtapasConfig = !empty($OfertaActivaConfig['Id']) ? SgceEtapasAcademicasListar($Pdo, (int)$OfertaActivaConfig['Id'], true) : [];
$CicloActivo = SgceCicloActivo($Pdo);
$Periodos = [];
if (!empty($CicloActivo['Id']) && !empty($OfertaActivaConfig['Id'])) {
    $StmtPeriodos = $Pdo->prepare('SELECT Orden, Nombre FROM PeriodosEvaluacion WHERE CicloId = ? AND OfertaId = ? AND Activo = 1 ORDER BY Orden ASC');
    $StmtPeriodos->execute([(int)$CicloActivo['Id'], (int)$OfertaActivaConfig['Id']]);
    foreach ($StmtPeriodos->fetchAll() as $P) { $Periodos[(int)$P['Orden']] = $P['Nombre']; }
}
$PeriodosCantidadConfig = (int)($ConfigAcademica['CantidadPeriodosEvaluacion'] ?? ($Config['PeriodosCantidad'] ?? max(3, count($Periodos))));
$PeriodosNombreBaseConfig = (string)($ConfigAcademica['NombreBasePeriodo'] ?? ($Config['PeriodosNombreBase'] ?? 'PARCIAL'));
$PeriodosModoConfig = (string)($ConfigAcademica['ModoPeriodos'] ?? ($Config['PeriodosModo'] ?? 'AUTOMATICO'));
$PeriodosPersonalizadosConfig = trim(implode(PHP_EOL, array_values($Periodos))) !== '' ? implode(PHP_EOL, array_values($Periodos)) : (string)($ConfigAcademica['PeriodosPersonalizados'] ?? '');
$UsaPlaneacionesConfig = (int)($ConfigAcademica['UsaPlaneaciones'] ?? ($Config['UsaPlaneaciones'] ?? 1)) === 1;
$TipoPlaneacionConfig = (string)($ConfigAcademica['TipoPlaneacion'] ?? ($Config['TipoPlaneacion'] ?? 'PERIODO'));
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
<?= SgceCss('assets/css/sgce-base.min.css') ?>
<?= SgceCss('assets/css/sgce-soft-motion.css') ?>
<?= SgceEstilosTema($Pdo) ?>
<?= SgceCss('assets/css/configuracion-botones-metalicos.css') ?>
<style>
.SgcePrettyCheck{display:flex;align-items:center;gap:12px;width:100%;min-height:54px;padding:12px 16px;border:2px solid #E2E8F0;border-radius:18px;background:#fff;box-shadow:0 8px 18px rgba(15,23,42,.035);cursor:pointer;margin:0}.SgcePrettyCheck input{width:22px!important;height:22px!important;min-height:22px!important;margin:0!important;flex:0 0 22px}.SgcePrettyCheck span{display:flex;flex-direction:column;line-height:1.15}.SgcePrettyCheck strong{font-size:.92rem;color:#1F2937}.SgcePrettyCheck small{font-size:.76rem;color:#667085;font-weight:700;margin-top:3px}.SgcePrettyCheck:has(input:checked){border-color:rgba(151,5,30,.42);background:#FFF7FA}.SgcePrettyCheckWrap{display:flex;align-items:end}.SgcePlaneacionesDependiente:disabled{background:#F3F4F6;color:#6B7280;border-color:#E5E7EB;cursor:not-allowed;box-shadow:none}.SgcePlaneacionesHelp{transition:opacity .15s ease}.SgcePlaneacionesHelp.SgceMuted{opacity:.55}

/* Corrección real de distribución Configuración General: filas emparejadas, no columnas tipo masonry. */
.SgceConfigFormRedisenada{display:block!important;width:100%!important;margin:0!important;}
.SgceConfigTwoColumns{display:grid!important;grid-template-columns:minmax(0,1.15fr) minmax(360px,.85fr)!important;grid-template-areas:"school cycle" "academic periods"!important;gap:22px!important;align-items:stretch!important;width:100%!important;}
.SgceConfigTwoColumns .SgceConfigLeftCol,.SgceConfigTwoColumns .SgceConfigRightCol{display:contents!important;}
.SgceConfigSchoolCard{grid-area:school!important;}
.SgceConfigCycleCard{grid-area:cycle!important;}
.SgceConfigAcademicCard{grid-area:academic!important;}
.SgceConfigPeriodsCard{grid-area:periods!important;}
.SgceConfigTwoColumns .SgceConfigCard{margin:0!important;height:100%!important;}
.SgceConfigTwoColumns .SgceConfigCard.mt-4{margin-top:0!important;}
.SgceConfigActionsFull{width:100%!important;margin:22px 0 0!important;min-height:100px!important;padding:22px 24px!important;display:grid!important;grid-template-columns:minmax(0,1fr) auto!important;align-items:center!important;gap:22px!important;border:1px solid rgba(226,232,240,.95)!important;border-radius:24px!important;background:linear-gradient(135deg,#FFFFFF 0%,#F8FBFF 48%,#EEF7FF 100%)!important;box-shadow:0 10px 26px rgba(15,23,42,.055)!important;}
.SgceConfigActionsFull>div{display:flex!important;flex-direction:column!important;justify-content:center!important;gap:4px!important;min-width:0!important;}
.SgceConfigActionsFull strong{color:#97051E!important;font-weight:900!important;letter-spacing:.15px!important;}
.SgceConfigActionsFull span{color:#667085!important;font-weight:700!important;}
.SgceConfigActionsFull .SgceConfigSave{min-width:280px!important;min-height:50px!important;}
.SgceConfigTwoColumns .SgcePrettyCheckWrap{display:flex!important;align-items:stretch!important;}
.SgceConfigTwoColumns .SgcePrettyCheck{height:100%!important;min-height:58px!important;align-items:center!important;}
.SgceConfigTwoColumns .SgcePrettyCheck input{align-self:center!important;}
@media(max-width:1100px){.SgceConfigTwoColumns{grid-template-columns:1fr!important;grid-template-areas:"school" "cycle" "academic" "periods"!important;}.SgceConfigActionsFull{grid-template-columns:1fr!important;text-align:center!important;}.SgceConfigActionsFull .SgceConfigSave{width:100%!important;min-width:0!important;}}

</style>
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

    <form method="post" class="SgceConfigFormRedisenada SgceConfigGridRedisenada">
        <?= CampoCsrf() ?>
        <div class="SgceConfigTwoColumns">
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

        <section class="SgceConfigCard SgceConfigCardWide SgceConfigAcademicCard">
            <div class="SgceConfigHead">
                <span><span class="SgceColorIcon" aria-hidden="true">🧭</span></span>
                <div>
                    <h2>Estructura académica</h2>
                    <p>Define si el sistema trabajará como primaria, secundaria, bachillerato, universidad, maestría, doctorado o curso. El módulo de migración usará esta estructura académica.</p>
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
                <div class="col-md-7 SgceAcademicOrgField">
                    <label class="SgceFieldLabel">Organización académica</label>
                    <select name="TipoPeriodizacion" class="form-select FormControl" required>
                        <?php foreach(SgceTipoPeriodizacionOpciones() as $ClaveTipo => $TextoTipo): ?>
                            <option value="<?= HConfig($ClaveTipo) ?>" <?= $TipoPeriodizacionConfig === $ClaveTipo ? 'selected' : '' ?>><?= HConfig($TextoTipo) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5 SgceAcademicStagesField">
                    <label class="SgceFieldLabel">Cantidad de <?= HConfig($EtiquetaEtapaUiMinus) ?>s / etapas</label>
                    <input class="form-control FormControl InputDigits" name="TotalEtapas" value="<?= HConfig((string)$TotalEtapasConfig) ?>" min="1" max="20" maxlength="2" inputmode="numeric" required>
                </div>
                <div class="col-12 SgcePrettyCheckWrap SgceAcademicProgramsCheckFull">
                    <label class="SgcePrettyCheck SgcePrettyCheckHorizontal">
                        <input class="form-check-input" type="checkbox" name="UsaProgramas" value="1" <?= $UsaProgramasConfig ? 'checked' : '' ?>>
                        <span><strong>Usa programas educativos</strong><small>Programas, especialidades o posgrados</small></span>
                    </label>
                </div>
                <div class="col-12 SgceProgramasTextareaWrap">
                    <label class="SgceFieldLabel">Programas educativos iniciales opcionales</label>
                    <textarea class="form-control FormControl InputUpper SgceProgramasDependiente" name="ProgramasIniciales" rows="2" placeholder="Ejemplo: INFORMÁTICA, CONTABILIDAD, ENFERMERÍA" <?= $UsaProgramasConfig ? '' : 'disabled' ?>><?php $ProgramasVisiblesConfig = array_values(array_filter(array_column($ProgramasConfig, 'Nombre'), static fn($P) => $P !== 'GENERAL')); if (!empty($ProgramasVisiblesConfig)) { echo HConfig(implode(', ', $ProgramasVisiblesConfig)); } ?></textarea>
                    <small class="text-muted fw-semibold SgceProgramasHelp <?= $UsaProgramasConfig ? '' : 'SgceMuted' ?>">Activa “Usa programas educativos” para capturar programas. En primaria/secundaria puedes dejarlo desactivado.</small>
                </div>
            </div>
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
                <div><h2>Periodos y planeaciones</h2><p>Los periodos ya no son fijos. SGCE los crea según la oferta educativa y el ciclo activo.</p></div>
            </div>
            <div class="row g-3">
                <div class="col-md-6"><label class="SgceFieldLabel">Cantidad de periodos</label><input class="form-control FormControl InputDigits" name="PeriodosCantidad" value="<?= HConfig((string)$PeriodosCantidadConfig) ?>" required min="1" max="12" maxlength="2" inputmode="numeric"></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Nombre base</label><input class="form-control FormControl InputUpper" name="PeriodosNombreBase" value="<?= HConfig($PeriodosNombreBaseConfig) ?>" maxlength="60" required placeholder="PARCIAL / TRIMESTRE / UNIDAD"></div>
                <div class="col-12"><label class="SgceFieldLabel">Modo de periodos</label><select class="form-select FormControl" name="PeriodosModo"><option value="AUTOMATICO" <?= $PeriodosModoConfig === 'AUTOMATICO' ? 'selected' : '' ?>>Automático</option><option value="PERSONALIZADO" <?= $PeriodosModoConfig === 'PERSONALIZADO' ? 'selected' : '' ?>>Personalizado</option></select></div>
                <div class="col-12"><label class="SgceFieldLabel">Periodos del ciclo activo</label><textarea class="form-control FormControl InputUpper" name="PeriodosPersonalizados" rows="3" placeholder="PARCIAL 1, PARCIAL 2, ORDINARIO"><?= HConfig($PeriodosPersonalizadosConfig) ?></textarea><small class="text-muted fw-semibold">Si el modo está automático, puedes dejar este campo como guía; SGCE generará los nombres con el nombre base.</small></div>
                <div class="col-12"><label class="SgcePrettyCheck"><input class="form-check-input" type="checkbox" name="UsaPlaneaciones" value="1" <?= $UsaPlaneacionesConfig ? 'checked' : '' ?>><span><strong>La institución usa planeaciones</strong><small>Control de entregas por materia</small></span></label></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Tipo de planeación</label><select class="form-select FormControl SgcePlaneacionesDependiente" name="TipoPlaneacion" <?= $UsaPlaneacionesConfig ? '' : 'disabled' ?>><option value="CICLO" <?= $TipoPlaneacionConfig === 'CICLO' ? 'selected' : '' ?>>Por ciclo</option><option value="PERIODO" <?= $TipoPlaneacionConfig === 'PERIODO' ? 'selected' : '' ?>>Por periodo</option><option value="UNIDAD" <?= $TipoPlaneacionConfig === 'UNIDAD' ? 'selected' : '' ?>>Por unidad/tema</option><option value="SEMANA" <?= $TipoPlaneacionConfig === 'SEMANA' ? 'selected' : '' ?>>Semanal</option></select></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Planeaciones a entregar</label><input class="form-control FormControl InputDigits SgcePlaneacionesDependiente" name="PlaneacionesCantidad" value="<?= HConfig((string)$PlaneacionesCantidad) ?>" <?= $UsaPlaneacionesConfig ? 'required' : 'disabled' ?> min="1" max="12" maxlength="2" inputmode="numeric"><small class="text-muted fw-semibold SgcePlaneacionesHelp <?= $UsaPlaneacionesConfig ? '' : 'SgceMuted' ?>">Se usa para validar entregas por materia.</small></div>
            </div>
        </section>
        </div>
        </div>

        <section class="SgceConfigActions SgceConfigActionsInline SgceConfigActionsFull">
            <div>
                <strong><i class="fa-solid fa-circle-info"></i> Cambios globales</strong>
                <span>Al guardar se actualizan reportes, boletas, consulta pública y paneles.</span>
            </div>
            <button type="submit" id="BtnGuardarConfiguracionVerdeMetalico" class="SgceConfigSave BtnConfiguracionGuardarMetalico"><span class="SgceColorIcon" aria-hidden="true">💾</span> Guardar configuración</button>
        </section>
    </form>


</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?= SgceJs('assets/js/sgce-shared.js') ?>
<?= SgceJs('assets/js/ConfiguracionAdmin.js') ?>
</body>
</html>
