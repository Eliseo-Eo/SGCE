<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/config/Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { header('Location: index.php'); exit; }
SgceExigirPermiso($UserSession, 'migracion', 'Solo el administrador principal puede ejecutar migraciones de ciclo escolar.');
RequerirCsrfPost();

function HMigracion($Texto) { return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }

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
        $_SESSION['MensajeMigracion'] = 'Migración realizada: '.$R['Promovidos'].' alumnos promovidos, '.$R['Egresados'].' egresados, '.$R['Conflictos'].' conflictos omitidos. Kardex congelados: '.($R['KardexCongelados'] ?? 0).'. Asignaciones copiadas: '.($R['AsignacionesCopiadas'] ?? 0).'. Omitidas por docente inactivo: '.($R['AsignacionesOmitidasDocente'] ?? 0).'. Grupos creados: '.(!empty($R['GrupoCreado']) ? '1' : '0').'.';
        $_SESSION['MensajeMigracionTipo'] = 'success';
    } catch (Exception $E) {
        if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
        $_SESSION['MensajeMigracion'] = $E->getMessage();
        $_SESSION['MensajeMigracionTipo'] = 'danger';
    }
    header('Location: MigracionAdmin.php');
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
        $_SESSION['MensajeMigracion'] = 'Ciclo migrado: '.$R['GruposProcesados'].' grupos procesados, '.$R['Promovidos'].' alumnos promovidos, '.$R['Egresados'].' egresados, '.$R['Conflictos'].' conflictos omitidos, '.($R['KardexCongelados'] ?? 0).' kardex congelados y '.$R['GruposCreados'].' grupos creados en el ciclo activo. Asignaciones copiadas: '.($R['AsignacionesCopiadas'] ?? 0).'. Omitidas por docente inactivo: '.($R['AsignacionesOmitidasDocente'] ?? 0).'.';
        $_SESSION['MensajeMigracionTipo'] = 'success';
    } catch (Exception $E) {
        if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
        $_SESSION['MensajeMigracion'] = $E->getMessage();
        $_SESSION['MensajeMigracionTipo'] = 'danger';
    }
    header('Location: MigracionAdmin.php');
    exit;
}

$Config = SgceObtenerConfiguracion($Pdo);
$CicloActivo = SgceCicloActivo($Pdo);
$CiclosInactivosMigracion = SgceCiclosInactivosConGrupos($Pdo);
$CicloOrigenMigracionId = max(0, (int)($_GET['CicloOrigenId'] ?? ($CiclosInactivosMigracion[0]['Id'] ?? 0)));
$GruposMigracion = $CicloOrigenMigracionId > 0 ? SgceGruposListarPorCiclo($Pdo, $CicloOrigenMigracionId, true) : [];
$Mensaje = $_SESSION['MensajeMigracion'] ?? '';
$MensajeTipo = $_SESSION['MensajeMigracionTipo'] ?? 'success';
unset($_SESSION['MensajeMigracion'], $_SESSION['MensajeMigracionTipo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Migración de ciclo escolar | SGCE</title>
<link rel="icon" type="image/x-icon" href="assets/media/img/favicon.ico">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<?= SgceCss('assets/css/sgce-base.min.css') ?>
<?= SgceCss('assets/css/sgce-soft-motion.css') ?>
<?= SgceEstilosTema($Pdo) ?>
<?= SgceCss('assets/css/configuracion-botones-metalicos.css') ?>
<style>
.SgceMigrationLayout{max-width:1180px;margin:0 auto}.SgceMigrationCard{background:#fff;border:1px solid rgba(151,5,30,.12);border-top:4px solid var(--SgceGuinda);border-radius:22px;box-shadow:0 18px 44px rgba(15,23,42,.08);padding:22px}.SgceMigrationHead{display:flex;gap:14px;align-items:flex-start;margin-bottom:16px}.SgceMigrationHead h2{font-weight:900;color:#7f0c21;font-size:1.05rem;text-transform:uppercase;margin:0}.SgceMigrationHead p{margin:.2rem 0 0;color:#5f6b82;font-weight:600}.SgceMigrationOption{background:#fff;border:1px solid #E5E7EB;border-radius:20px;padding:18px;height:100%;box-shadow:0 12px 28px rgba(15,23,42,.055)}.SgceMigrationOption h3{font-size:1.05rem;font-weight:900;color:#243044}.SgceMigrationWarning{border-radius:18px;background:#FFF7ED;border:1px solid #FED7AA;color:#7C2D12;font-weight:700}.SgceMigrationToggle{position:relative;display:flex;align-items:center;gap:14px;width:100%;min-height:64px;margin:0;border:1.5px solid #E2E8F0;border-radius:18px;background:linear-gradient(135deg,#FFFFFF 0%,#F8FAFC 100%);padding:12px 16px;cursor:pointer;box-shadow:0 10px 22px rgba(15,23,42,.045);transition:border-color .18s ease,box-shadow .18s ease,background .18s ease,transform .18s ease}.SgceMigrationToggle:hover{border-color:rgba(var(--SgceGuindaRGB),.25);background:linear-gradient(135deg,#FFFFFF 0%,#FFF7FA 100%);box-shadow:0 14px 26px rgba(15,23,42,.06);transform:translateY(-1px)}.SgceMigrationToggle input{position:absolute;opacity:0;pointer-events:none}.SgceMigrationToggleMark{width:28px;height:28px;min-width:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid #B8DCC4;background:#fff;color:#fff;box-shadow:inset 0 1px 0 rgba(255,255,255,.85);transition:all .18s ease}.SgceMigrationToggleMark i{font-size:.82rem;opacity:0;transform:scale(.55);transition:opacity .18s ease,transform .18s ease}.SgceMigrationToggleText{display:flex;flex-direction:column;justify-content:center;min-width:0;line-height:1.18}.SgceMigrationToggleText strong{font-size:.94rem;font-weight:900;color:#27364a;letter-spacing:.1px}.SgceMigrationToggleText small{margin-top:3px;color:#667085;font-size:.75rem;font-weight:700}.SgceMigrationToggle input:checked+.SgceMigrationToggleMark{border-color:#0EA5E9;background:linear-gradient(135deg,#38BDF8,#2563EB);box-shadow:0 8px 18px rgba(37,99,235,.22)}.SgceMigrationToggle input:checked+.SgceMigrationToggleMark i{opacity:1;transform:scale(1)}.SgceMigrationToggle:has(input:checked){border-color:rgba(37,99,235,.35);background:linear-gradient(135deg,#FFFFFF 0%,#F0F7FF 100%);box-shadow:0 14px 28px rgba(37,99,235,.09)}.SgceBadgeDestino{background:rgba(var(--SgceGuindaRGB),.1);color:var(--SgceGuinda);border:1px solid rgba(var(--SgceGuindaRGB),.18);font-weight:900;border-radius:999px;padding:.45rem .75rem}.SgceBtnDangerAction{background:linear-gradient(135deg,var(--SgceGuinda),var(--SgceGuindaOscuro));color:#fff;border:none;border-radius:16px;font-weight:900;box-shadow:var(--SgceSombraGuinda);padding:.85rem 1rem}.SgceBtnDangerAction:disabled{opacity:.55;box-shadow:none}.SgceQuickLink{border:1px solid #E5E7EB;border-radius:16px;padding:12px 14px;text-decoration:none;color:#27364a;font-weight:800;background:#fff;display:flex;align-items:center;gap:10px;height:100%}.SgceQuickLink:hover{color:var(--SgceGuinda);border-color:rgba(var(--SgceGuindaRGB),.28);background:#FFF8FA}@media (max-width:576px){.SgceMigrationToggle{align-items:flex-start;padding:13px}.SgceMigrationToggleMark{margin-top:2px}}
</style>
</head>
<body>
<div class="SgcePageWrap SgceModuleWrap container-fluid px-4 py-4">
    <section class="SgceHero mb-4 SgceMigrationLayout">
        <div class="SgceHeroInfo">
            <div class="SgceHeroIcon"><span class="SgceColorIcon" aria-hidden="true">🔁</span></div>
            <div>
                <h1>MIGRACIÓN DE CICLO ESCOLAR</h1>
                <p>Módulo exclusivo de administrador para promover grupos, congelar kardex y conservar boletas históricas.</p>
            </div>
        </div>
        <div class="SgceHeroActions">
            <a href="Admin.php?Tab=inicio" class="SgceBtnVolverInicio" title="Volver al inicio" aria-label="Volver al inicio"><i class="fa-solid fa-house"></i><span>Volver al inicio</span></a>
        </div>
    </section>

    <main class="SgceMigrationLayout">
        <?php if ($Mensaje !== ''): ?>
            <div class="alert alert-<?= HMigracion($MensajeTipo) ?> alert-dismissible fade show shadow-sm border-0 rounded-4" role="alert">
                <i class="fa-solid <?= $MensajeTipo === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?> me-2"></i><?= HMigracion($Mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-md-4"><a class="SgceQuickLink" href="ConfiguracionAdmin.php"><i class="fa-solid fa-sliders"></i><span>Revisar configuración académica</span></a></div>
            <div class="col-md-4"><a class="SgceQuickLink" href="PeriodosAdmin.php"><i class="fa-solid fa-calendar-days"></i><span>Revisar ciclos y periodos</span></a></div>
            <?php if (SgcePuedeRespaldos($UserSession)): ?><div class="col-md-4"><a class="SgceQuickLink" href="RestaurarBD.php"><i class="fa-solid fa-database"></i><span>Crear respaldo antes de migrar</span></a></div><?php endif; ?>
        </div>

        <section class="SgceMigrationCard">
            <div class="SgceMigrationHead">
                <span><span class="SgceColorIcon" aria-hidden="true">🔐</span></span>
                <div>
                    <h2>Cierre y promoción académica</h2>
                    <p>Solo permite migrar desde ciclos cerrados o inactivos hacia el ciclo activo. Antes de mover alumnos congela el kardex para proteger boletas históricas.</p>
                </div>
            </div>


            <form method="get" class="row g-3 align-items-end mb-4">
                <div class="col-md-8">
                    <label class="SgceFieldLabel">Ciclo origen cerrado</label>
                    <select name="CicloOrigenId" class="form-select FormControl" onchange="this.form.submit()">
                        <?php if(empty($CiclosInactivosMigracion)): ?>
                            <option value="">No hay ciclos inactivos con grupos para migrar</option>
                        <?php else: ?>
                            <?php foreach($CiclosInactivosMigracion as $Ci): ?>
                                <option value="<?= (int)$Ci['Id'] ?>" <?= (int)$CicloOrigenMigracionId === (int)$Ci['Id'] ? 'selected' : '' ?>><?= HMigracion($Ci['Nombre']) ?> · <?= (int)$Ci['TotalGrupos'] ?> grupo(s)</option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-4"><button class="btn btn-outline-secondary w-100 FormControl" type="submit"><i class="fa-solid fa-filter me-2"></i>Ver grupos</button></div>
            </form>

            <div class="row g-3">
                <div class="col-lg-6">
                    <form method="post" class="SgceMigrationOption">
                        <?= CampoCsrf() ?>
                        <input type="hidden" name="MigrarGrupoAcademico" value="1">
                        <h3><i class="fa-solid fa-users-viewfinder me-2"></i>Migrar un grupo</h3>
                        <p class="text-muted small fw-semibold">Ejemplo: 1A pasa a 2A, 2A pasa a 3A o la última etapa queda egresada según la estructura configurada.</p>
                        <label class="SgceFieldLabel">Grupo origen</label>
                        <select name="GrupoOrigenId" class="form-select FormControl mb-3" required <?= empty($GruposMigracion) ? 'disabled' : '' ?>>
                            <?php if(empty($GruposMigracion)): ?>
                                <option value="">Sin grupos disponibles</option>
                            <?php else: ?>
                                <?php foreach($GruposMigracion as $Gm): ?>
                                    <option value="<?= (int)$Gm['Id'] ?>"><?= HMigracion($Gm['Grado'].' '.$Gm['Grupo'].' '.$Gm['Turno']) ?> · <?= (int)$Gm['TotalAlumnos'] ?> alumno(s)</option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <label class="SgceMigrationToggle mb-3" for="CopiarAsignacionesGrupo">
                            <input type="checkbox" name="CopiarAsignaciones" value="1" id="CopiarAsignacionesGrupo">
                            <span class="SgceMigrationToggleMark" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                            <span class="SgceMigrationToggleText">
                                <strong>Copiar asignaciones con docente activo</strong>
                                <small>Se crearán en el nuevo grupo si el maestro sigue activo.</small>
                            </span>
                        </label>
                        <button class="SgceBtnDangerAction w-100" type="submit" <?= empty($GruposMigracion) || empty($CicloActivo['Id']) ? 'disabled' : '' ?> data-sgce-confirm="delete" data-sgce-confirm-title="CONFIRMAR MIGRACIÓN" data-sgce-confirm-subtitle="MIGRAR GRUPO" data-sgce-confirm-message="¿DESEAS MIGRAR ESTE GRUPO AL CICLO ACTIVO?" data-sgce-confirm-detail="Se conservará el historial del ciclo origen y se creará la inscripción del alumno en el ciclo activo." data-sgce-confirm-button="SÍ, MIGRAR GRUPO" data-sgce-confirm-loading="MIGRANDO..." data-sgce-confirm-icon="fa-rotate">
                            <i class="fa-solid fa-arrow-up-a-z me-2"></i>Migrar grupo a la siguiente etapa
                        </button>
                    </form>
                </div>

                <div class="col-lg-6">
                    <form method="post" class="SgceMigrationOption">
                        <?= CampoCsrf() ?>
                        <input type="hidden" name="MigrarCicloAcademico" value="1">
                        <input type="hidden" name="CicloOrigenId" value="<?= (int)$CicloOrigenMigracionId ?>">
                        <h3><i class="fa-solid fa-school-circle-check me-2"></i>Migrar ciclo completo</h3>
                        <p class="text-muted small fw-semibold">Procesa todos los grupos del ciclo origen seleccionado. Úsalo solo al cierre oficial del ciclo escolar.</p>
                        <div class="mb-3"><span class="SgceBadgeDestino">Destino activo: <?= HMigracion($CicloActivo['Nombre'] ?? 'SIN CICLO ACTIVO') ?></span></div>
                        <label class="SgceMigrationToggle mb-3" for="CopiarAsignacionesCiclo">
                            <input type="checkbox" name="CopiarAsignaciones" value="1" id="CopiarAsignacionesCiclo">
                            <span class="SgceMigrationToggleMark" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                            <span class="SgceMigrationToggleText">
                                <strong>Copiar asignaciones con docente activo</strong>
                                <small>Se aplicará a todos los grupos migrados del ciclo.</small>
                            </span>
                        </label>
                        <button class="SgceBtnDangerAction w-100" type="submit" <?= empty($GruposMigracion) || empty($CicloActivo['Id']) ? 'disabled' : '' ?> data-sgce-confirm="delete" data-sgce-confirm-title="CONFIRMAR MIGRACIÓN MASIVA" data-sgce-confirm-subtitle="MIGRAR CICLO COMPLETO" data-sgce-confirm-message="¿DESEAS MIGRAR TODOS LOS GRUPOS DEL CICLO CERRADO?" data-sgce-confirm-detail="Esta operación es segura e idempotente: No duplica alumnos ya inscritos en el ciclo activo; los conflictos se omiten." data-sgce-confirm-button="SÍ, MIGRAR CICLO" data-sgce-confirm-loading="MIGRANDO CICLO..." data-sgce-confirm-icon="fa-school">
                            <i class="fa-solid fa-forward me-2"></i>Migrar todos los grupos
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?= SgceJs('assets/js/sgce-shared.js') ?>
</body>
</html>
