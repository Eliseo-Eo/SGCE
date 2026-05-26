<?php
/*
    Archivo: RestaurarBD.php
    Descripción: Importa respaldos de datos SGCE, permite fusionar datos, reemplazar datos escolares o reemplazar todo.
    Por seguridad, está pensado para respaldos generados desde ExportarDatosBD.php.
*/
require 'Conexion.php';
IniciarSesionSegura();
$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession || !in_array($UserSession['Rol'], ['admin','director'], true)) {
    header('Location: index.php');
    exit;
}

$Mensaje = $_SESSION['MensajeRestaurarBD'] ?? '';
$TipoMensaje = $_SESSION['TipoRestaurarBD'] ?? 'info';
unset($_SESSION['MensajeRestaurarBD'], $_SESSION['TipoRestaurarBD']);

function HRest($Texto) { return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }

function RedirectRestaurar($Mensaje, $Tipo = 'success') {
    $_SESSION['MensajeRestaurarBD'] = $Mensaje;
    $_SESSION['TipoRestaurarBD'] = $Tipo;
    header('Location: RestaurarBD.php');
    exit;
}

function QTablaRest($Tabla) { return '`' . str_replace('`','``',$Tabla) . '`'; }

function TablasSistemaRest($Pdo) {
    $Preferidas = ['IntentosSeguridad','BitacoraMovimientos','Avisos','Asistencias','Calificaciones','Asignaciones','Alumnos','Grupos','Usuarios'];
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

function VaciarTablasRest($Pdo, $IncluirUsuarios = false) {
    // Uso DELETE en lugar de TRUNCATE porque TRUNCATE hace COMMIT implícito en MySQL
    // y provocaba el error: There is no active transaction.
    $Tablas = TablasSistemaRest($Pdo);
    $Pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach ($Tablas as $Tabla) {
        if (!$IncluirUsuarios && $Tabla === 'Usuarios') { continue; }
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
            $Stmt = $Pdo->prepare('UPDATE Usuarios SET SessionToken = ? WHERE Username = ? AND Activo = 1 LIMIT 1');
            $Stmt->execute([$Token, $Username]);
        }
    }

    $TotalUsuarios = (int)$Pdo->query('SELECT COUNT(*) FROM Usuarios')->fetchColumn();
    if ($TotalUsuarios <= 0) {
        $Pdo->prepare("INSERT INTO Usuarios (Username, Password, NombreCompleto, Rol, Activo, SessionToken) VALUES (?, ?, ?, 'admin', 1, ?)")
            ->execute(['Admin', 'Admin123', 'ADMINISTRADOR GENERAL', $Token !== '' ? $Token : null]);
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
    return preg_match('/^(SET\s+|INSERT\s+INTO\s+|REPLACE\s+INTO\s+)/i', $Limpia) === 1;
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
            VaciarTablasRest($Pdo, false);
            $Pdo->prepare("INSERT INTO Avisos (Titulo, Mensaje, Publico, Activo) VALUES (?, ?, 'TODOS', 1)")
                ->execute(['SISTEMA REINICIADO', 'LOS DATOS ESCOLARES FUERON BORRADOS. PUEDES IMPORTAR UN RESPALDO DE DATOS.']);
            RegistrarBitacora($Pdo, $UserSession, 'VACIAR_DATOS_ESCOLARES', 'BASE_DE_DATOS', null, 'SE BORRARON DATOS ESCOLARES, CONSERVANDO USUARIOS');
            RedirectRestaurar('Datos escolares borrados correctamente. Los usuarios se conservaron.', 'success');
        } catch (Exception $E) {
            RedirectRestaurar('Error al borrar datos escolares: ' . $E->getMessage(), 'danger');
        }
    }

    if (isset($_POST['ImportarRespaldo'])) {
        if (!isset($_FILES['ArchivoSql']) || !is_uploaded_file($_FILES['ArchivoSql']['tmp_name'])) {
            RedirectRestaurar('Selecciona un archivo .sql válido.', 'danger');
        }
        if ((int)$_FILES['ArchivoSql']['size'] <= 0 || (int)$_FILES['ArchivoSql']['size'] > 80 * 1024 * 1024) {
            RedirectRestaurar('El archivo está vacío o supera el máximo permitido de 80 MB.', 'danger');
        }
        $Nombre = (string)($_FILES['ArchivoSql']['name'] ?? '');
        if (strtolower(pathinfo($Nombre, PATHINFO_EXTENSION)) !== 'sql') {
            RedirectRestaurar('El archivo debe tener extensión .sql.', 'danger');
        }
        $Modo = $_POST['ModoImportacion'] ?? 'fusionar';
        if (!in_array($Modo, ['fusionar','reemplazar_escolar','reemplazar_todo'], true)) {
            $Modo = 'fusionar';
        }
        $Sql = file_get_contents($_FILES['ArchivoSql']['tmp_name']);
        if ($Sql === false || trim($Sql) === '') {
            RedirectRestaurar('No se pudo leer el archivo SQL.', 'danger');
        }
        if (preg_match('/\b(DROP\s+DATABASE|CREATE\s+DATABASE|DROP\s+TABLE|CREATE\s+TABLE|ALTER\s+TABLE)\b/i', $Sql)) {
            RedirectRestaurar('Este importador acepta únicamente respaldos de SOLO DATOS generados por “Exportar solo datos”. Si subiste un respaldo completo con estructura, usa ControlEscolar.sql/Instalar.php de forma manual.', 'danger');
        }

        try {
            $Pdo->beginTransaction();

            if ($Modo === 'reemplazar_escolar') {
                VaciarTablasRest($Pdo, false);
            } elseif ($Modo === 'reemplazar_todo') {
                VaciarTablasRest($Pdo, true);
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
            RedirectRestaurar('Error al importar respaldo: ' . $E->getMessage(), 'danger');
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
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="favicon.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<style>
/* ============================================================
   SGCE FIX7 - DISENO INTEGRADO POR ARCHIVO
   Se elimino sgce-fix.css. Este bloque queda dentro de cada PHP
   para evitar cache y conflictos entre archivos externos.
   ============================================================ */
:root{
    --SgceGuinda:#7A0818;
    --SgceGuinda2:#A10D26;
    --SgceGuindaHover:#4F050F;
    --SgceTexto:#1F2937;
    --SgceMuted:#6B7280;
    --SgceFondo:#EEF2F7;
    --SgceBorde:#E5E7EB;
    --SgceCard:#FFFFFF;
    --SgceAzul:#2563EB;
    --SgceAzulHover:#1D4ED8;
    --SgceRojo:#DC2626;
    --SgceRojoHover:#991B1B;
    --SgceVerde:#15803D;
    --SgceVerdeHover:#166534;
    --SgceNaranja:#C2410C;
    --SgceMorado:#6D28D9;
}
html{scroll-behavior:smooth;}
body{overflow-x:hidden;}
.card,.Card,.Panel,.TablaCard,.DashboardCard,.Contenedor,.ContainerCard{border-radius:22px !important;}
.form-control,.form-select{border-radius:14px !important;border:2px solid var(--SgceBorde) !important;min-height:44px;}
.form-control:focus,.form-select:focus{border-color:var(--SgceGuinda) !important;box-shadow:0 0 0 .18rem rgba(122,8,24,.14) !important;}
.btn:not(.btn-close):not(.navbar-toggler),.ActionBtn,.BotonAccion,.BtnExport,.BtnGuardar,.BtnBack,.ExportIcon,.BtnLogin,.MenuButton,.ModuleButton,.NavButton{min-height:42px;border-radius:999px !important;font-weight:800 !important;letter-spacing:.02em;display:inline-flex;align-items:center;justify-content:center;gap:.45rem;text-decoration:none !important;transition:transform .18s ease, box-shadow .18s ease, background .18s ease, color .18s ease, border-color .18s ease !important;}
.btn:not(.btn-close):not(.navbar-toggler):hover,.ActionBtn:hover,.BotonAccion:hover,.BtnExport:hover,.BtnGuardar:hover,.BtnBack:hover,.ExportIcon:hover,.BtnLogin:hover,.MenuButton:hover,.ModuleButton:hover,.NavButton:hover{transform:translateY(-1px);box-shadow:0 12px 26px rgba(122,8,24,.20) !important;}
.btn-primary,.ActionPrimary,.BtnGuardar,.BtnCalificaciones,.BtnLogin,.ModuleButton,.MenuButton,.NavButton,.BotonAccion{background:linear-gradient(135deg,var(--SgceGuinda),var(--SgceGuinda2)) !important;border:2px solid var(--SgceGuinda) !important;color:#FFFFFF !important;}
.btn-primary:hover,.ActionPrimary:hover,.BtnGuardar:hover,.BtnCalificaciones:hover,.BtnLogin:hover,.ModuleButton:hover,.MenuButton:hover,.NavButton:hover,.BotonAccion:hover{background:linear-gradient(135deg,var(--SgceGuindaHover),var(--SgceGuinda)) !important;border-color:var(--SgceGuindaHover) !important;color:#FFFFFF !important;}
.ActionBtn.ActionEdit,.btn-outline-primary.ActionBtn,.btn-outline-primary:not(.SgceBtnInicio):not(.ReporteBtn){background:linear-gradient(135deg,var(--SgceAzul),#3B82F6) !important;border-color:var(--SgceAzul) !important;color:#FFFFFF !important;}
.ActionBtn.ActionEdit:hover,.btn-outline-primary.ActionBtn:hover,.btn-outline-primary:not(.SgceBtnInicio):not(.ReporteBtn):hover{background:linear-gradient(135deg,var(--SgceAzulHover),var(--SgceAzul)) !important;border-color:var(--SgceAzulHover) !important;color:#FFFFFF !important;}
.ActionBtn.ActionDelete,.btn-outline-danger.ActionBtn,.btn-outline-danger:not(.SgceBtnInicio):not(.ReporteBtn),.btn-danger{background:linear-gradient(135deg,var(--SgceRojo),#EF4444) !important;border-color:var(--SgceRojo) !important;color:#FFFFFF !important;}
.ActionBtn.ActionDelete:hover,.btn-outline-danger.ActionBtn:hover,.btn-outline-danger:not(.SgceBtnInicio):not(.ReporteBtn):hover,.btn-danger:hover{background:linear-gradient(135deg,var(--SgceRojoHover),var(--SgceRojo)) !important;border-color:var(--SgceRojoHover) !important;color:#FFFFFF !important;}
.ExportIcon.ExportExcel,.BtnExport.ExportCalifExcel,.BtnExport.ExportAsisExcel,.btn-success{background:linear-gradient(135deg,var(--SgceVerde),#22C55E) !important;border-color:var(--SgceVerde) !important;color:#FFFFFF !important;}
.ExportIcon.ExportExcel:hover,.BtnExport.ExportCalifExcel:hover,.BtnExport.ExportAsisExcel:hover,.btn-success:hover{background:linear-gradient(135deg,var(--SgceVerdeHover),var(--SgceVerde)) !important;border-color:var(--SgceVerdeHover) !important;color:#FFFFFF !important;}
.ExportIcon.ExportPdf,.BtnExport.ExportCalifPdf,.BtnExport.ExportAsisPdf{background:linear-gradient(135deg,#B91C1C,#EF4444) !important;border-color:#B91C1C !important;color:#FFFFFF !important;}
.ExportIcon.ExportHoy{background:linear-gradient(135deg,var(--SgceNaranja),#F97316) !important;border-color:var(--SgceNaranja) !important;color:#FFFFFF !important;}
.ExportIcon.ExportTodas{background:linear-gradient(135deg,var(--SgceMorado),#8B5CF6) !important;border-color:var(--SgceMorado) !important;color:#FFFFFF !important;}
.SgceBtnInicio,a.SgceBtnInicio,button.SgceBtnInicio,.Top .SgceBtnInicio,.navbar .SgceBtnInicio,.BtnBack.SgceBtnInicio,.ActionBtn.SgceBtnInicio,.btn-outline-light.SgceBtnInicio,.btn-light.SgceBtnInicio,.BtnGuinda.SgceBtnInicio{background:#FFFFFF !important;color:var(--SgceGuinda) !important;border:2px solid rgba(255,255,255,.92) !important;border-radius:999px !important;box-shadow:0 8px 18px rgba(0,0,0,.10) !important;text-decoration:none !important;}
.SgceBtnInicio:hover,a.SgceBtnInicio:hover,button.SgceBtnInicio:hover,.Top .SgceBtnInicio:hover,.navbar .SgceBtnInicio:hover,.BtnBack.SgceBtnInicio:hover,.ActionBtn.SgceBtnInicio:hover,.btn-outline-light.SgceBtnInicio:hover,.btn-light.SgceBtnInicio:hover,.BtnGuinda.SgceBtnInicio:hover{background:#FFFFFF !important;color:var(--SgceGuinda) !important;border-color:#FFFFFF !important;transform:translateY(-1px) !important;box-shadow:0 10px 22px rgba(0,0,0,.14) !important;}
.SgceBtnInicio i,.SgceBtnInicio:hover i{color:var(--SgceGuinda) !important;}
a[href*="Logout.php"],.BtnLogout,a[href*="Logout.php"]:hover,.BtnLogout:hover{background:#FFFFFF !important;color:var(--SgceGuinda) !important;border:2px solid rgba(255,255,255,.92) !important;box-shadow:0 8px 18px rgba(0,0,0,.12) !important;}
a[href*="Logout.php"] i,a[href*="Logout.php"]:hover i,.BtnLogout i,.BtnLogout:hover i{color:var(--SgceGuinda) !important;}
.ReporteBtn,button.ReporteBtn,.card .ReporteBtn,.Card .ReporteBtn,form .ReporteBtn,.btn.ReporteBtn{background:linear-gradient(135deg,var(--SgceGuinda),var(--SgceGuinda2)) !important;color:#FFFFFF !important;border:2px solid var(--SgceGuinda) !important;border-radius:999px !important;min-height:46px !important;font-weight:800 !important;letter-spacing:.3px !important;box-shadow:0 10px 22px rgba(122,8,24,.18) !important;text-decoration:none !important;}
.ReporteBtn:hover,button.ReporteBtn:hover,.card .ReporteBtn:hover,.Card .ReporteBtn:hover,form .ReporteBtn:hover,.btn.ReporteBtn:hover{background:linear-gradient(135deg,var(--SgceGuindaHover),var(--SgceGuinda)) !important;color:#FFFFFF !important;border-color:var(--SgceGuindaHover) !important;transform:translateY(-2px) !important;box-shadow:0 14px 30px rgba(122,8,24,.28) !important;}
.ReporteBtn i,.ReporteBtn:hover i,button.ReporteBtn i,button.ReporteBtn:hover i{color:#FFFFFF !important;}
.table td,.table th{vertical-align:middle !important;}
.modal-dialog{display:flex;align-items:center;min-height:calc(100vh - 1rem);}
.modal-content{border-radius:24px !important;border:0 !important;box-shadow:0 25px 70px rgba(0,0,0,.25) !important;}
@media (max-width:768px){.btn:not(.btn-close):not(.navbar-toggler),.ActionBtn,.BotonAccion,.BtnExport,.ReporteBtn{width:100%;}.table-responsive{border-radius:18px;}}
</style>

<style>
body{background:linear-gradient(to bottom,#F8FAFC,#EEF2F7);font-family:'Segoe UI',sans-serif;color:#1F2937}.Top{background:linear-gradient(135deg,#7A0818,#A10D26);color:white;border-radius:24px;padding:28px;box-shadow:0 16px 35px rgba(122,8,24,.20)}.Card{border:0;border-radius:24px;box-shadow:0 12px 35px rgba(15,23,42,.08)}.IconBox{width:56px;height:56px;border-radius:18px;background:rgba(122,8,24,.10);color:#7A0818;display:flex;align-items:center;justify-content:center;font-size:24px}.FormControl{border:2px solid #E5E7EB;border-radius:16px;min-height:48px}.DangerZone{border:2px dashed #DC2626;background:#FEF2F2}.SmallText{font-size:.92rem;color:#6B7280}.ActionBtn{display:inline-flex;align-items:center;justify-content:center;gap:10px;text-decoration:none;border-radius:999px;padding:12px 18px;font-weight:800;letter-spacing:.3px}.ActionDanger{background:#DC2626;color:white}.ActionDanger:hover{background:#991B1B;color:white}.ActionSuccess{background:#16A34A;color:white}.ActionSuccess:hover{background:#15803D;color:white}.ActionInfo{background:#2563EB;color:white}.ActionInfo:hover{background:#1D4ED8;color:white}.ActionWarning{background:#F59E0B;color:#111827}.ActionWarning:hover{background:#D97706;color:#111827}</style>
</head>
<body>
<div class="container py-4">
    <div class="Top mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="fw-black mb-1"><i class="fa-solid fa-database me-2"></i> RESPALDOS E IMPORTACIÓN</h1>
            <p class="mb-0 opacity-75">Respalda, restaura o limpia los datos del sistema desde un solo lugar.</p>
        </div>
        <a href="Admin.php?Tab=inicio" class="ActionBtn bg-white text-danger SgceBtnInicio"><i class="fa-solid fa-arrow-left"></i> VOLVER A INICIO</a>
    </div>

    <?php if ($Mensaje !== ''): ?>
        <div class="alert alert-<?= HRest($TipoMensaje) ?> border-0 shadow-sm rounded-4 fw-semibold"><?= HRest($Mensaje) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="Card p-4 h-100">
                <div class="d-flex gap-3 align-items-start mb-3"><div class="IconBox"><i class="fa-solid fa-file-export"></i></div><div><h4 class="fw-bold mb-1">Exportar respaldos</h4><p class="SmallText mb-0">Usa este respaldo para restaurar desde esta misma pantalla. No toca la estructura de la base de datos.</p></div></div>
                <div class="d-grid gap-3">
                    <a href="ExportarDatosBD.php" class="ActionBtn ActionSuccess"><i class="fa-solid fa-download"></i> EXPORTAR SOLO DATOS</a>
                    </div>
                <div class="alert alert-info border-0 rounded-4 mt-3 mb-0"><strong>Recomendado:</strong> este es el respaldo correcto para volver a importar desde el sistema. Para respaldo completo/manual sigue existiendo <code>RespaldoBD.php</code>, pero ya no aparece duplicado en el dashboard.</div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="Card p-4 h-100">
                <div class="d-flex gap-3 align-items-start mb-3"><div class="IconBox"><i class="fa-solid fa-file-import"></i></div><div><h4 class="fw-bold mb-1">Importar respaldo de datos</h4><p class="SmallText mb-0">Sube un .sql generado por “Exportar solo datos”.</p></div></div>
                <form method="POST" enctype="multipart/form-data">
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
                    <button class="ActionBtn ActionSuccess w-100 border-0" name="ImportarRespaldo" value="1" type="submit"><i class="fa-solid fa-upload"></i> IMPORTAR RESPALDO</button>
                </form>
            </div>
        </div>

        <div class="col-12">
            <div class="Card DangerZone p-4">
                <div class="d-flex gap-3 align-items-start mb-3"><div class="IconBox"><i class="fa-solid fa-triangle-exclamation"></i></div><div><h4 class="fw-bold text-danger mb-1">Borrar datos escolares</h4><p class="SmallText mb-0">Esto borra grupos, alumnos, asignaciones, asistencias, calificaciones, avisos y bitácora. Conserva usuarios para que no pierdas acceso.</p></div></div>
                <form method="POST" class="row g-3 align-items-end">
                    <?= CampoCsrf() ?>
                    <div class="col-lg-8">
                        <label class="fw-bold mb-2">Confirmación</label>
                        <input type="text" name="Confirmar" class="form-control FormControl" placeholder="Escribe: BORRAR DATOS ESCOLARES">
                    </div>
                    <div class="col-lg-4">
                        <button class="ActionBtn ActionDanger w-100 border-0" type="submit" name="VaciarEscolar" value="1"><i class="fa-solid fa-trash-can"></i> BORRAR DATOS</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php ImprimirCsrfScript(); ?>
</body>
</html>
