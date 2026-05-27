<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/config/Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession || !SgcePuedeConfigurarSistema($UserSession)) { header('Location: index.php'); exit; }
RequerirCsrfPost();

function HConfig($Texto) { return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }
function ConfigNormalizar($Texto, $Mayusculas = true) {
    $Texto = trim(preg_replace('/\s+/u', ' ', (string)$Texto));
    return $Mayusculas ? mb_strtoupper($Texto, 'UTF-8') : $Texto;
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
        $ColorInstitucional = trim((string)($_POST['ColorInstitucional'] ?? '#7A0818'));

        $CicloNombre = ConfigNormalizar($_POST['CicloNombre'] ?? '', true);
        $FechaInicio = trim((string)($_POST['FechaInicio'] ?? ''));
        $FechaFin = trim((string)($_POST['FechaFin'] ?? ''));
        $PeriodoUno = ConfigNormalizar($_POST['PeriodoUno'] ?? '', true);
        $PeriodoDos = ConfigNormalizar($_POST['PeriodoDos'] ?? '', true);
        $PeriodoTres = ConfigNormalizar($_POST['PeriodoTres'] ?? '', true);

        if ($NombreEscuela === '' || mb_strlen($NombreEscuela, 'UTF-8') < 3) { throw new Exception('Escribe el nombre oficial de la escuela.'); }
        if ($CorreoEscuela !== '' && !filter_var($CorreoEscuela, FILTER_VALIDATE_EMAIL)) { throw new Exception('El correo institucional no tiene formato válido.'); }
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $ColorInstitucional)) { $ColorInstitucional = '#7A0818'; }
        if ($CicloNombre === '' || !ConfigFechaValida($FechaInicio) || !ConfigFechaValida($FechaFin) || strtotime($FechaInicio) >= strtotime($FechaFin)) {
            throw new Exception('Revisa el ciclo escolar. Las fechas no son válidas.');
        }
        if ($PeriodoUno === '' || $PeriodoDos === '' || $PeriodoTres === '') { throw new Exception('Los tres periodos son obligatorios.'); }
        if (count(array_unique([$PeriodoUno, $PeriodoDos, $PeriodoTres])) !== 3) { throw new Exception('Los periodos no pueden repetirse.'); }

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
        ]);

        $CicloActivo = SgceCicloActivo($Pdo);
        $CicloId = (int)($CicloActivo['Id'] ?? 0);
        if ($CicloId > 0) {
            $StmtCiclo = $Pdo->prepare('UPDATE CiclosEscolares SET Nombre = ?, FechaInicio = ?, FechaFin = ?, Activo = 1 WHERE Id = ?');
            $StmtCiclo->execute([$CicloNombre, $FechaInicio, $FechaFin, $CicloId]);
        } else {
            $Pdo->prepare('UPDATE CiclosEscolares SET Activo = 0')->execute();
            $StmtCiclo = $Pdo->prepare('INSERT INTO CiclosEscolares (Nombre, FechaInicio, FechaFin, Activo) VALUES (?, ?, ?, 1)');
            $StmtCiclo->execute([$CicloNombre, $FechaInicio, $FechaFin]);
            $CicloId = (int)$Pdo->lastInsertId();
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
        $_SESSION['MensajeConfiguracion'] = $E->getMessage();
        $_SESSION['MensajeConfiguracionTipo'] = 'danger';
    }
    header('Location: ConfiguracionAdmin.php');
    exit;
}

$Config = SgceObtenerConfiguracion($Pdo);
$CicloActivo = SgceCicloActivo($Pdo);
$Periodos = [];
if (!empty($CicloActivo['Id'])) {
    $StmtPeriodos = $Pdo->prepare('SELECT Orden, Nombre FROM PeriodosEvaluacion WHERE CicloId = ? AND Orden BETWEEN 1 AND 3 ORDER BY Orden ASC');
    $StmtPeriodos->execute([(int)$CicloActivo['Id']]);
    foreach ($StmtPeriodos->fetchAll() as $P) { $Periodos[(int)$P['Orden']] = $P['Nombre']; }
}
$PeriodoUno = $Periodos[1] ?? 'PRIMER PARCIAL';
$PeriodoDos = $Periodos[2] ?? 'SEGUNDO PARCIAL';
$PeriodoTres = $Periodos[3] ?? 'TERCER PARCIAL';
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
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sgce-base.css?v=1.0.0">
</head>
<body>
<div class="SgcePageWrap container-fluid px-4 py-4">
    <section class="SgceHero mb-4">
        <div class="SgceHeroInfo">
            <div class="SgceHeroIcon"><i class="fa-solid fa-school-circle-check"></i></div>
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

    <form method="post" class="SgceConfigGrid">
        <?= CampoCsrf() ?>
        <section class="SgceConfigCard SgceConfigCardWide">
            <div class="SgceConfigHead">
                <span><i class="fa-solid fa-school"></i></span>
                <div><h2>Datos de la escuela</h2><p>Esta información aparece en boletas, reportes y pantallas públicas.</p></div>
            </div>
            <div class="row g-3">
                <div class="col-md-8"><label class="SgceFieldLabel">Nombre oficial</label><input class="form-control FormControl InputUpper" name="NombreEscuela" value="<?= HConfig($Config['NombreEscuela']) ?>" required></div>
                <div class="col-md-4"><label class="SgceFieldLabel">CCT / Clave</label><input class="form-control FormControl InputUpper" name="ClaveCentroTrabajo" value="<?= HConfig($Config['ClaveCentroTrabajo']) ?>"></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Director(a)</label><input class="form-control FormControl InputUpper" name="DirectorNombre" value="<?= HConfig($Config['DirectorNombre']) ?>"></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Municipio y estado</label><input class="form-control FormControl InputUpper" name="MunicipioEstado" value="<?= HConfig($Config['MunicipioEstado']) ?>"></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Teléfono</label><input class="form-control FormControl" name="TelefonoEscuela" value="<?= HConfig($Config['TelefonoEscuela']) ?>"></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Correo institucional</label><input class="form-control FormControl" name="CorreoEscuela" value="<?= HConfig($Config['CorreoEscuela']) ?>"></div>
                <div class="col-md-8"><label class="SgceFieldLabel">Lema o leyenda inferior</label><input class="form-control FormControl" name="LemaInstitucional" value="<?= HConfig($Config['LemaInstitucional']) ?>"></div>
                <div class="col-md-4"><label class="SgceFieldLabel">Color institucional</label><input class="form-control FormControl" type="color" name="ColorInstitucional" value="<?= HConfig($Config['ColorInstitucional'] ?: '#7A0818') ?>"></div>
            </div>
        </section>

        <section class="SgceConfigCard">
            <div class="SgceConfigHead">
                <span><i class="fa-solid fa-calendar-days"></i></span>
                <div><h2>Ciclo activo</h2><p>Rango usado para asistencias, reportes y estadísticas.</p></div>
            </div>
            <div class="row g-3">
                <div class="col-12"><label class="SgceFieldLabel">Nombre del ciclo</label><input class="form-control FormControl InputUpper" name="CicloNombre" value="<?= HConfig($CicloActivo['Nombre'] ?? '') ?>" required></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Fecha inicio</label><input class="form-control FormControl" type="date" name="FechaInicio" value="<?= HConfig($CicloActivo['FechaInicio'] ?? '') ?>" required></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Fecha fin</label><input class="form-control FormControl" type="date" name="FechaFin" value="<?= HConfig($CicloActivo['FechaFin'] ?? '') ?>" required></div>
            </div>
        </section>

        <section class="SgceConfigCard">
            <div class="SgceConfigHead">
                <span><i class="fa-solid fa-list-check"></i></span>
                <div><h2>Periodos</h2><p>Los tres parciales disponibles para capturar calificaciones.</p></div>
            </div>
            <div class="row g-3">
                <div class="col-12"><label class="SgceFieldLabel">Periodo 1</label><input class="form-control FormControl InputUpper" name="PeriodoUno" value="<?= HConfig($PeriodoUno) ?>" required></div>
                <div class="col-12"><label class="SgceFieldLabel">Periodo 2</label><input class="form-control FormControl InputUpper" name="PeriodoDos" value="<?= HConfig($PeriodoDos) ?>" required></div>
                <div class="col-12"><label class="SgceFieldLabel">Periodo 3</label><input class="form-control FormControl InputUpper" name="PeriodoTres" value="<?= HConfig($PeriodoTres) ?>" required></div>
            </div>
        </section>

        <section class="SgceConfigActions">
            <div>
                <strong><i class="fa-solid fa-circle-info"></i> Cambios globales</strong>
                <span>Al guardar se actualizan reportes, boletas, consulta pública y paneles.</span>
            </div>
            <button type="submit" class="SgceConfigSave"><i class="fa-solid fa-floppy-disk"></i> Guardar configuración</button>
        </section>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/sgce-shared.js?v=1.0.0"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('.InputUpper').forEach(function(Input){Input.addEventListener('input',function(){var Pos=Input.selectionStart;Input.value=Input.value.toUpperCase();try{Input.setSelectionRange(Pos,Pos);}catch(E){}});});});
</script>
</body>
</html>
