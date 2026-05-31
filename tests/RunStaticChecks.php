<?php
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('Uso CLI únicamente.'); }

$Raiz = dirname(__DIR__);
$Errores = [];
$Ok = [];

function SgceTestOk(&$Ok, $Mensaje) { $Ok[] = $Mensaje; }
function SgceTestError(&$Errores, $Mensaje) { $Errores[] = $Mensaje; }
function SgceTestExiste(&$Ok, &$Errores, $Raiz, $Ruta) {
    if (is_file($Raiz . '/' . $Ruta)) { SgceTestOk($Ok, "Existe $Ruta"); }
    else { SgceTestError($Errores, "Falta $Ruta"); }
}
function SgceTestContiene(&$Ok, &$Errores, $Raiz, $Ruta, $Texto, $Mensaje) {
    $Archivo = $Raiz . '/' . $Ruta;
    if (!is_file($Archivo)) { SgceTestError($Errores, "No se pudo revisar $Ruta"); return; }
    $Contenido = file_get_contents($Archivo);
    if (strpos($Contenido, $Texto) !== false) { SgceTestOk($Ok, $Mensaje); }
    else { SgceTestError($Errores, "No cumple: $Mensaje"); }
}

$ArchivosObligatorios = [
    'Admin.php',
    'modules/Admin.php',
    'modules/admin/AdminAcciones.php',
    'modules/admin/AdminDatos.php',
    'modules/admin/AdminVista.php',
    'services/SGCE_ServiceLoader.php',
    'services/AlumnoService.php',
    'services/MaestroService.php',
    'services/GrupoService.php',
    'services/AsistenciaService.php',
    'services/CalificacionService.php',
    'services/ReporteService.php',
    'services/UsuarioService.php',
    'reports/ExportarCalificaciones.php',
    'reports/ExportarAsistencia.php',
    'reports/ExportarAlumno.php',
    'reports/ExportarBoletaPublica.php',
    'reports/ExportarConsultaAsistencia.php',
    'modules/Importar.php',
    'public/index.php',
    'public/ConsultaPadre.php',
    'public/ConsultaCalificaciones.php',
    'assets/css/sgce-base.css',
    'assets/css/sgce-base.min.css',
    'assets/css/src/00-core.css',
    'assets/css/src/90-ajustes-finales.css',
];
foreach ($ArchivosObligatorios as $Ruta) { SgceTestExiste($Ok, $Errores, $Raiz, $Ruta); }



$ArchivosNoVacios = [
    'assets/css/sgce-base.min.css' => 100000,
    'assets/css/sgce-base.css' => 100000,
    'assets/js/sgce-shared.js' => 1000,
];
foreach ($ArchivosNoVacios as $Ruta => $MinimoBytes) {
    $Archivo = $Raiz . '/' . $Ruta;
    if (is_file($Archivo) && filesize($Archivo) >= $MinimoBytes) {
        SgceTestOk($Ok, "$Ruta no está vacío y tiene tamaño esperado.");
    } else {
        SgceTestError($Errores, "$Ruta está vacío, incompleto o no fue compilado correctamente.");
    }
}


SgceTestContiene($Ok, $Errores, $Raiz, 'public/index.php', 'session_regenerate_id(true)', 'Login regenera sesión al autenticarse.');
SgceTestContiene($Ok, $Errores, $Raiz, 'public/index.php', 'SgcePasswordVerify', 'Login usa verificación de contraseña segura.');


SgceTestContiene($Ok, $Errores, $Raiz, 'includes/SGCE_Helpers.php', "'administrativo'", 'Rol administrativo definido.');
SgceTestContiene($Ok, $Errores, $Raiz, 'includes/SGCE_Helpers.php', 'SgceExigirPermiso', 'Validación central de permisos disponible.');
SgceTestContiene($Ok, $Errores, $Raiz, 'modules/Admin.php', 'admin/AdminAcciones.php', 'Admin.php está dividido en acciones.');
SgceTestContiene($Ok, $Errores, $Raiz, 'modules/Admin.php', 'admin/AdminDatos.php', 'Admin.php está dividido en datos.');
SgceTestContiene($Ok, $Errores, $Raiz, 'modules/Admin.php', 'admin/AdminVista.php', 'Admin.php está dividido en vista.');
SgceTestContiene($Ok, $Errores, $Raiz, 'modules/admin/AdminAcciones.php', 'SessionToken = NULL,
                    SessionTokenExpira = NULL', 'Edición de docentes cierra sesiones activas.');
SgceTestContiene($Ok, $Errores, $Raiz, 'modules/admin/AdminAcciones.php', 'SgceMaestroExisteActivo', 'Edición de asignaciones valida docente activo.');
SgceTestContiene($Ok, $Errores, $Raiz, 'modules/admin/AdminAcciones.php', 'SgceGrupoExisteActivo', 'Edición de asignaciones valida grupo activo.');
SgceTestContiene($Ok, $Errores, $Raiz, 'modules/UsuariosAdmin.php', '$DebeCerrarSesiones', 'Edición de usuarios invalida sesiones cuando cambia acceso.');


SgceTestContiene($Ok, $Errores, $Raiz, 'reports/ExportarBoletaPublica.php', 'ConsultaToken', 'Boleta pública requiere token de consulta.');
SgceTestContiene($Ok, $Errores, $Raiz, 'reports/ExportarConsultaAsistencia.php', 'ConsultaToken', 'Asistencia pública requiere token de consulta.');
SgceTestContiene($Ok, $Errores, $Raiz, 'includes/SGCE_PublicConsultas.php', 'SgcePublicoCrearTokenConsulta', 'Consulta pública usa tokens temporales.');
SgceTestContiene($Ok, $Errores, $Raiz, 'includes/SGCE_PublicConsultas.php', 'SgcePublicoRateDisponible', 'Consulta pública tiene límite de intentos.');


SgceTestContiene($Ok, $Errores, $Raiz, 'modules/Importar.php', 'SgcePuedeImportarCatalogos', 'Importación valida permiso en servidor.');
SgceTestContiene($Ok, $Errores, $Raiz, 'cron/backup_diario.php', 'SgceGenerarBackupAutomatico', 'Cron diario genera respaldo fuera del dashboard.');
if (strpos(file_get_contents($Raiz . '/modules/Admin.php'), 'SgceGenerarBackupAutomatico($Pdo)') === false) {
    SgceTestOk($Ok, 'Dashboard no genera respaldo automático al abrir.');
} else {
    SgceTestError($Errores, 'Dashboard todavía genera respaldo automático al abrir.');
}


$Sql = is_file($Raiz . '/install/SGCE.sql') ? file_get_contents($Raiz . '/install/SGCE.sql') : '';
foreach (["'admin'", "'administrativo'", "'maestro'"] as $Rol) {
    if (strpos($Sql, $Rol) !== false) { SgceTestOk($Ok, "SQL incluye rol $Rol"); }
    else { SgceTestError($Errores, "SQL no incluye rol $Rol"); }
}
foreach (["'director'", "'secretario'", "'coordinador'", "'prefecto'"] as $RolNoVigente) {
    if (strpos($Sql, $RolNoVigente) === false) { SgceTestOk($Ok, "SQL no incluye rol no vigente $RolNoVigente"); }
    else { SgceTestError($Errores, "SQL incluye rol no vigente $RolNoVigente"); }
}



$InlineDetectado = false;
foreach ($Iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Raiz, FilesystemIterator::SKIP_DOTS)) as $ArchivoInline) {
    $RutaInline = str_replace($Raiz . '/', '', $ArchivoInline->getPathname());
    if (strpos($RutaInline, 'tests/') === 0) { continue; }
    if (!in_array($ArchivoInline->getExtension(), ['php', 'js'], true)) { continue; }
    $ContenidoInline = file_get_contents($ArchivoInline->getPathname());
    if (preg_match('/<script>\s*$|onclick=|oninput=|onchange=|javascript:/mi', $ContenidoInline)) {
        $InlineDetectado = true;
        SgceTestError($Errores, 'Script o evento inline detectado en ' . $RutaInline);
    }
}
if (!$InlineDetectado) { SgceTestOk($Ok, 'Sin scripts ni eventos inline en PHP/JS revisado.'); }

SgceTestContiene($Ok, $Errores, $Raiz, 'includes/SGCE_Helpers.php', "script-src 'self' https://cdn.jsdelivr.net", 'CSP no permite JavaScript inline.');
SgceTestContiene($Ok, $Errores, $Raiz, 'install/SGCE.sql', 'chk_calificaciones_rango', 'SQL limita calificaciones de 5 a 10.');
SgceTestContiene($Ok, $Errores, $Raiz, '.htaccess', '.*\\.dm', 'Apache bloquea archivos .dm residuales.');
SgceTestContiene($Ok, $Errores, $Raiz, 'modules/RestaurarBD.php', 'VaciarTablasRest($Pdo, false, false)', 'Restauración escolar reemplaza ciclo/periodos para evitar datos cruzados.');
SgceTestContiene($Ok, $Errores, $Raiz, 'public/index.php', 'SgceEstilosTema($Pdo)', 'Login usa el color institucional configurado.');

SgceTestExiste($Ok, $Errores, $Raiz, 'README.md', 'README de GitHub incluido y limpio.');
SgceTestExiste($Ok, $Errores, $Raiz, 'docs/MANUAL_TECNICO_INSTALACION_SGCE.docx', 'Manual técnico DOCX incluido.');
SgceTestExiste($Ok, $Errores, $Raiz, 'docs/MANUAL_TECNICO_INSTALACION_SGCE.pdf', 'Manual técnico PDF incluido.');
SgceTestExiste($Ok, $Errores, $Raiz, 'docs/MANUAL_USUARIO_SGCE.docx', 'Manual de usuario DOCX incluido.');
SgceTestExiste($Ok, $Errores, $Raiz, 'docs/MANUAL_USUARIO_SGCE.pdf', 'Manual de usuario PDF incluido.');
SgceTestContiene($Ok, $Errores, $Raiz, 'README.md', 'Instalación rápida desde cero', 'README describe instalación desde cero.');
SgceTestContiene($Ok, $Errores, $Raiz, 'README.md', 'php tests/RunStaticChecks.php', 'README documenta prueba técnica principal.');

$ResiduosNoPermitidos = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Raiz, FilesystemIterator::SKIP_DOTS)) as $ArchivoResiduo) {
    $RutaResiduo = str_replace($Raiz . '/', '', $ArchivoResiduo->getPathname());
    $NombreResiduo = strtolower($ArchivoResiduo->getFilename());
    $ExtensionResiduo = strtolower($ArchivoResiduo->getExtension());
    if (in_array($ExtensionResiduo, ['md', 'dm'], true) && $RutaResiduo !== 'README.md') { $ResiduosNoPermitidos[] = $RutaResiduo; }
    if (preg_match('/(sgce_' . 'fix|fi' . 'x_|version_' . 'anterior|copia|backup\.|\.bak$|\.old$|\.orig$)/i', $NombreResiduo)) { $ResiduosNoPermitidos[] = $RutaResiduo; }
}
if (!$ResiduosNoPermitidos) { SgceTestOk($Ok, 'Sin archivos .md/.dm no autorizados ni rastros de paquetes de corrección anteriores.'); }
else { SgceTestError($Errores, 'Residuos detectados: ' . implode(', ', array_unique($ResiduosNoPermitidos))); }

$CachePermitido = 'sgce2026final';
$CacheLimpio = true;
$MarcadoresEntregaViejos = ['sgce2026' . 'r', 'sgce2026' . 'upd', 'sgce2026' . 'login' . 'color', 'SGCE_' . 'FI' . 'X_', 'LOGIN_' . 'COLOR_' . 'INSTITUCIONAL'];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Raiz, FilesystemIterator::SKIP_DOTS)) as $ArchivoCache) {
    $RutaCache = str_replace($Raiz . '/', '', $ArchivoCache->getPathname());
    if (strpos($RutaCache, 'tests/') === 0) { continue; }
    if (!in_array(strtolower($ArchivoCache->getExtension()), ['php','js','css','html','txt','ini'], true)) { continue; }
    $ContenidoCache = file_get_contents($ArchivoCache->getPathname());
    if (preg_match_all('/cache=(sgce[A-Za-z0-9_\-]*)/', $ContenidoCache, $CoincidenciasCache)) {
        foreach ($CoincidenciasCache[1] as $CacheEncontrado) {
            if ($CacheEncontrado !== $CachePermitido) {
                $CacheLimpio = false;
                SgceTestError($Errores, 'Cache no normalizado en ' . $RutaCache . ': ' . $CacheEncontrado);
            }
        }
    }
    foreach ($MarcadoresEntregaViejos as $MarcadorCache) {
        if (strpos($ContenidoCache, $MarcadorCache) !== false) {
            $CacheLimpio = false;
            SgceTestError($Errores, 'Marcador de entrega anterior detectado en ' . $RutaCache . ': ' . $MarcadorCache);
        }
    }
}
if ($CacheLimpio) { SgceTestOk($Ok, 'Cachés normalizados en ' . $CachePermitido . ' y sin rastros de entregas intermedias.'); }


$ComentariosCss = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Raiz . '/assets/css', FilesystemIterator::SKIP_DOTS)) as $ArchivoCssComentario) {
    if ($ArchivoCssComentario->getExtension() !== 'css') { continue; }
    $RutaCssComentario = str_replace($Raiz . '/', '', $ArchivoCssComentario->getPathname());
    $ContenidoCssComentario = file_get_contents($ArchivoCssComentario->getPathname());
    if (preg_match('/\/\*/', $ContenidoCssComentario)) { $ComentariosCss[] = $RutaCssComentario; }
}
if (!$ComentariosCss) { SgceTestOk($Ok, 'CSS sin comentarios residuales de ajustes anteriores.'); }
else { SgceTestError($Errores, 'Comentarios CSS residuales detectados en: ' . implode(', ', $ComentariosCss)); }

SgceTestContiene($Ok, $Errores, $Raiz, 'reports/ExportarAsistencia.php', 'Al.Activo=1 AND Asg.Activo=1 AND U.Activo=1', 'Exportación de asistencia por grupo filtra registros activos.');
SgceTestContiene($Ok, $Errores, $Raiz, 'reports/ExportarAsistencia.php', 'A.Activo=1 AND G.Activo=1 AND U.Activo=1', 'Exportación de asistencia por asignación valida carga, grupo y docente activos.');
SgceTestContiene($Ok, $Errores, $Raiz, 'reports/ExportarCalificaciones.php', 'A.Activo = 1 AND U.Activo = 1', 'Exportación de calificaciones por grupo filtra docentes activos.');
SgceTestContiene($Ok, $Errores, $Raiz, 'reports/ExportarCalificaciones.php', 'A.Activo = 1 AND G.Activo = 1 AND U.Activo = 1', 'Exportación de calificaciones por asignación valida carga, grupo y docente activos.');




function SgceCssLlavesBalanceadas($Contenido) {
    $Pila = [];
    $Comentario = false;
    $Comilla = '';
    $Escape = false;
    $Linea = 1;
    $Len = strlen($Contenido);
    for ($I = 0; $I < $Len; $I++) {
        $Ch = $Contenido[$I];
        $Next = $I + 1 < $Len ? $Contenido[$I + 1] : '';
        if ($Ch === "\n") { $Linea++; }
        if ($Comentario) {
            if ($Ch === '*' && $Next === '/') { $Comentario = false; $I++; }
            continue;
        }
        if ($Comilla !== '') {
            if ($Escape) { $Escape = false; continue; }
            if ($Ch === '\\') { $Escape = true; continue; }
            if ($Ch === $Comilla) { $Comilla = ''; }
            continue;
        }
        if ($Ch === '/' && $Next === '*') { $Comentario = true; $I++; continue; }
        if ($Ch === '"' || $Ch === "'") { $Comilla = $Ch; continue; }
        if ($Ch === '{') { $Pila[] = $Linea; }
        if ($Ch === '}') {
            if (!$Pila) { return 'cierre extra en línea ' . $Linea; }
            array_pop($Pila);
        }
    }
    if ($Pila) { return 'bloque sin cerrar iniciado en línea ' . end($Pila); }
    return true;
}

$CssBalanceOk = true;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Raiz . '/assets/css', FilesystemIterator::SKIP_DOTS)) as $ArchivoCss) {
    if ($ArchivoCss->getExtension() !== 'css') { continue; }
    $RutaCss = str_replace($Raiz . '/', '', $ArchivoCss->getPathname());
    $ResultadoCss = SgceCssLlavesBalanceadas(file_get_contents($ArchivoCss->getPathname()));
    if ($ResultadoCss === true) { SgceTestOk($Ok, 'CSS balanceado: ' . $RutaCss); }
    else { $CssBalanceOk = false; SgceTestError($Errores, 'CSS con llaves desbalanceadas en ' . $RutaCss . ': ' . $ResultadoCss); }
}
if ($CssBalanceOk) { SgceTestOk($Ok, 'Todos los CSS revisados tienen llaves balanceadas.'); }

$FormulariosPostSinCsrf = [];
$FormulariosArchivoSinEnctype = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Raiz, FilesystemIterator::SKIP_DOTS)) as $ArchivoForm) {
    if ($ArchivoForm->getExtension() !== 'php') { continue; }
    $RutaForm = str_replace($Raiz . '/', '', $ArchivoForm->getPathname());
    if (strpos($RutaForm, 'tests/') === 0) { continue; }
    $ContenidoForm = file_get_contents($ArchivoForm->getPathname());
    if (preg_match_all('/<form\b([^>]*)>(.*?)<\/form>/is', $ContenidoForm, $Forms, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        foreach ($Forms as $Form) {
            $Attrs = $Form[1][0];
            $Body = $Form[2][0];
            $Linea = substr_count(substr($ContenidoForm, 0, $Form[0][1]), "\n") + 1;
            if (preg_match('/method\s*=\s*["\']?post/i', $Attrs)) {
                if (strpos($Body, 'CampoCsrf') === false && strpos($Body, 'InstalarCampoCsrf') === false && strpos($Body, 'CsrfToken') === false && strpos($Body, 'InstalarCsrfToken') === false) {
                    $FormulariosPostSinCsrf[] = $RutaForm . ':' . $Linea;
                }
            }
            if (preg_match('/<input\b[^>]*type\s*=\s*["\']?file/i', $Body) && !preg_match('/enctype\s*=\s*["\']multipart\/form-data["\']/i', $Attrs)) {
                $FormulariosArchivoSinEnctype[] = $RutaForm . ':' . $Linea;
            }
        }
    }
}
if (!$FormulariosPostSinCsrf) { SgceTestOk($Ok, 'Todos los formularios POST revisados incluyen CSRF.'); }
else { SgceTestError($Errores, 'Formularios POST sin CSRF: ' . implode(', ', $FormulariosPostSinCsrf)); }
if (!$FormulariosArchivoSinEnctype) { SgceTestOk($Ok, 'Todos los formularios con archivo usan multipart/form-data.'); }
else { SgceTestError($Errores, 'Formularios de archivo sin enctype: ' . implode(', ', $FormulariosArchivoSinEnctype)); }

$RutasFaltantes = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Raiz, FilesystemIterator::SKIP_DOTS)) as $ArchivoRuta) {
    if ($ArchivoRuta->getExtension() !== 'php') { continue; }
    $RutaOrigen = str_replace($Raiz . '/', '', $ArchivoRuta->getPathname());
    if (strpos($RutaOrigen, 'tests/') === 0) { continue; }
    $ContenidoRuta = file_get_contents($ArchivoRuta->getPathname());
    if (preg_match_all('/\b(?:href|action|src)\s*=\s*["\']([^"\']+)["\']/i', $ContenidoRuta, $Links, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        foreach ($Links as $Link) {
            $Url = trim($Link[1][0]);
            if ($Url === '' || preg_match('/^(https?:|mailto:|#|javascript:|data:)/i', $Url) || strpos($Url, '<?') !== false || strpos($Url, '$') !== false) { continue; }
            $Limpia = preg_split('/[?#]/', $Url)[0];
            if ($Limpia === '' || preg_match('/\.(css|js|png|ico|jpg|jpeg|gif|webp|woff2|pdf|docx)$/i', $Limpia)) { continue; }
            if (preg_match('/\.php$/i', $Limpia) && !is_file($Raiz . '/' . ltrim($Limpia, './'))) {
                $Linea = substr_count(substr($ContenidoRuta, 0, $Link[0][1]), "\n") + 1;
                $RutasFaltantes[] = $RutaOrigen . ':' . $Linea . ' -> ' . $Url;
            }
        }
    }
}
if (!$RutasFaltantes) { SgceTestOk($Ok, 'Rutas PHP internas enlazadas existen en la raíz pública.'); }
else { SgceTestError($Errores, 'Rutas internas faltantes: ' . implode(', ', $RutasFaltantes)); }



SgceTestContiene($Ok, $Errores, $Raiz, 'modules/Asistencia.php', 'EDITAR_ASISTENCIA', 'Asistencia permite guardar y actualizar pases existentes.');
SgceTestContiene($Ok, $Errores, $Raiz, 'modules/Calificar.php', 'CALIFICACIONES ACTUALIZADAS', 'Calificaciones permiten insertar, actualizar y limpiar registros.');
SgceTestContiene($Ok, $Errores, $Raiz, 'modules/admin/AdminAcciones.php', 'REACTIVAR_DOCENTE', 'Alta docente reactiva registros inactivos sin chocar con índice único.');
SgceTestContiene($Ok, $Errores, $Raiz, 'modules/admin/AdminAcciones.php', 'REACTIVAR_GRUPO', 'Alta grupo reactiva registros inactivos sin chocar con índice único.');
SgceTestContiene($Ok, $Errores, $Raiz, 'modules/admin/AdminAcciones.php', 'REACTIVAR_ALUMNO', 'Alta alumno reactiva registros inactivos sin chocar con índice único.');
SgceTestContiene($Ok, $Errores, $Raiz, 'modules/Importar.php', '$Reactivados', 'Importaciones contemplan reactivación de registros inactivos.');
SgceTestContiene($Ok, $Errores, $Raiz, 'modules/UsuariosAdmin.php', 'Usuario reactivado correctamente', 'Gestión de usuarios permite reactivar usernames inactivos.');
SgceTestContiene($Ok, $Errores, $Raiz, 'services/AlumnoService.php', 'G.Activo = 1', 'Listados y conteos de alumnos respetan grupos activos.');
SgceTestContiene($Ok, $Errores, $Raiz, 'services/AsistenciaService.php', 'INNER JOIN Asignaciones A', 'Resumen de asistencia filtra asignaciones, docentes, grupos y alumnos activos.');
SgceTestContiene($Ok, $Errores, $Raiz, 'services/CalificacionService.php', 'INNER JOIN Grupos G', 'Promedio general filtra asignaciones, grupos y alumnos activos.');

$NodeDisponible = trim((string)shell_exec('command -v node 2>/dev/null')) !== '';
if ($NodeDisponible) {
    $IteradorJs = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Raiz . '/assets/js', FilesystemIterator::SKIP_DOTS));
    foreach ($IteradorJs as $ArchivoJs) {
        if ($ArchivoJs->getExtension() !== 'js') { continue; }
        $CmdJs = 'node --check ' . escapeshellarg($ArchivoJs->getPathname()) . ' 2>&1';
        $SalidaJs = shell_exec($CmdJs);
        if ($SalidaJs === null || (is_string($SalidaJs) && trim($SalidaJs) === '')) {
            SgceTestOk($Ok, 'Sintaxis JS OK: ' . str_replace($Raiz . '/', '', $ArchivoJs->getPathname()));
        } else {
            SgceTestError($Errores, 'Error de sintaxis JS: ' . str_replace($Raiz . '/', '', $ArchivoJs->getPathname()) . ' => ' . trim((string)$SalidaJs));
        }
    }
} else {
    SgceTestOk($Ok, 'Node no disponible; se omitió lint JS externo.');
}


$Iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Raiz, FilesystemIterator::SKIP_DOTS));
foreach ($Iterador as $Archivo) {
    if ($Archivo->getExtension() !== 'php') { continue; }
    $Ruta = $Archivo->getPathname();
    $Cmd = 'php -l ' . escapeshellarg($Ruta) . ' 2>&1';
    $Salida = shell_exec($Cmd);
    if (is_string($Salida) && strpos($Salida, 'No syntax errors detected') !== false) {
        SgceTestOk($Ok, 'Sintaxis PHP OK: ' . str_replace($Raiz . '/', '', $Ruta));
    } else {
        SgceTestError($Errores, 'Error de sintaxis PHP: ' . str_replace($Raiz . '/', '', $Ruta) . ' => ' . trim((string)$Salida));
    }
}

if ($Errores) {
    echo "SGCE STATIC CHECKS: FALLÓ\n";
    foreach ($Errores as $Error) { echo "[ERROR] $Error\n"; }
    exit(1);
}

echo "SGCE STATIC CHECKS: OK\n";
echo "Validaciones correctas: " . count($Ok) . "\n";
foreach ($Ok as $Mensaje) { echo "[OK] $Mensaje\n"; }
