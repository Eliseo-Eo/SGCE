<?php
$Root = dirname(__DIR__);
$Errores = [];
$Revisiones = 0;

function Check($Condicion, $Mensaje) {
    global $Errores, $Revisiones;
    $Revisiones++;
    if (!$Condicion) { $Errores[] = $Mensaje; }
}

function SgceTestRel($Path) {
    global $Root;
    return str_replace($Root . DIRECTORY_SEPARATOR, '', $Path);
}

function SgceTestArchivosPorExtension(array $Extensiones): array {
    global $Root;
    $Resultado = [];
    $Iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Root, FilesystemIterator::SKIP_DOTS));
    foreach ($Iterator as $File) {
        if (!$File->isFile()) { continue; }
        $Ext = strtolower($File->getExtension());
        if (in_array($Ext, $Extensiones, true)) { $Resultado[] = $File->getPathname(); }
    }
    sort($Resultado, SORT_NATURAL);
    return $Resultado;
}

$PhpFiles = SgceTestArchivosPorExtension(['php']);
foreach ($PhpFiles as $Path) {
    $Out = [];
    $Code = 0;
    exec('php -l ' . escapeshellarg($Path) . ' 2>&1', $Out, $Code);
    Check($Code === 0, 'PHP lint falló: ' . SgceTestRel($Path) . ' | ' . implode(' ', $Out));
}

foreach (glob($Root . '/assets/js/*.js') ?: [] as $Path) {
    $Out = [];
    $Code = 0;
    exec('node --check ' . escapeshellarg($Path) . ' 2>&1', $Out, $Code);
    Check($Code === 0, 'JS syntax falló: ' . SgceTestRel($Path) . ' | ' . implode(' ', $Out));
}

Check(!file_exists($Root . '/favicon.ico'), 'No debe existir favicon.ico en raíz.');
Check(!file_exists($Root . '/favicon.png'), 'No debe existir favicon.png en raíz.');
Check(file_exists($Root . '/assets/media/img/favicon.ico'), 'Falta favicon centralizado.');
Check(is_dir($Root . '/repositories'), 'Falta carpeta repositories.');
Check(file_exists($Root . '/repositories/SGCE_RepositoryLoader.php'), 'Falta loader de repositorios.');

foreach (['docentes.csv','grupos.csv','materias.csv','alumnos.csv','archivo_maestro.xlsx'] as $Fixture) {
    Check(file_exists($Root . '/tests/fixtures/' . $Fixture), 'Falta fixture de importación: ' . $Fixture);
}

foreach (['storage/backups','storage/logs','storage/tmp_uploads','storage/tmp_uploads/import_reports','storage/planeaciones','storage/locks'] as $DirProtegido) {
    Check(is_dir($Root . '/' . $DirProtegido), 'Falta carpeta protegida: ' . $DirProtegido);
    Check(file_exists($Root . '/' . $DirProtegido . '/.htaccess'), 'Falta .htaccess en: ' . $DirProtegido);
    Check(file_exists($Root . '/' . $DirProtegido . '/index.html'), 'Falta index.html en: ' . $DirProtegido);
}

$Forbidden = ['.bak','.old','.tmp','.orig','.dm'];
$Iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Root, FilesystemIterator::SKIP_DOTS));
foreach ($Iterator as $File) {
    if (!$File->isFile()) { continue; }
    $Rel = SgceTestRel($File->getPathname());
    Check(strtolower($File->getExtension()) !== 'zip', 'No debe haber ZIP interno: ' . $Rel);
    foreach ($Forbidden as $Ext) {
        Check(substr(strtolower($Rel), -strlen($Ext)) !== $Ext, 'Archivo residual: ' . $Rel);
    }
}

$Sql = file_get_contents($Root . '/install/SGCE.sql');
foreach (['idx_alumnos_activo_grupo_nombre','idx_asignaciones_activo_maestro_grupo_materia','idx_asistencias_rango_reporte','idx_bitacora_fecha_id'] as $Idx) {
    Check(strpos($Sql, $Idx) !== false, 'Falta índice: ' . $Idx);
}
Check(strpos($Sql, '1.0.91-base') !== false, 'La instalación base debe registrarse como 1.0.91-base.');

$VersionEsperada = '1.0.91';
$PhpSources = glob($Root . '/{Instalar.php,modules/*.php,modules/admin/*.php,reports/*.php,public/*.php}', GLOB_BRACE) ?: [];
foreach ($PhpSources as $Path) {
    $Rel = SgceTestRel($Path);
    $Content = @file_get_contents($Path);
    if ($Content === false) { continue; }
    if (preg_match_all('/assets\/(?:css|js)\/[^"\']+\?v=([^"\']+)/', $Content, $Matches)) {
        foreach ($Matches[1] as $VersionDetectada) {
            Check($VersionDetectada === $VersionEsperada, 'Cache-buster desactualizado en ' . $Rel . ': ' . $VersionDetectada);
        }
    }
}

foreach (['README.md','docs/ARQUITECTURA.md','docs/BASE_DE_DATOS.md','docs/CRON_Y_MANTENIMIENTO.md','docs/DIAGRAMA_BASE_DATOS.md','docs/INSTALACION_SERVIDOR_REAL.md','docs/MIGRACIONES.md','docs/RENDIMIENTO.md','docs/INFORME_DEPURACION_1.0.91.md','docs/INFORME_ENDURECIMIENTO_1.0.91.md','docs/PRODUCCION.md'] as $DocPrincipal) {
    $PathDoc = $Root . '/' . $DocPrincipal;
    Check(file_exists($PathDoc), 'Falta documento principal: ' . $DocPrincipal);
    if (file_exists($PathDoc)) {
        $ContenidoDoc = file_get_contents($PathDoc);
        Check(strpos($ContenidoDoc, '1.0.91') !== false, 'Documento sin versión actual: ' . $DocPrincipal);
    }
}
Check(!file_exists($Root . '/docs/INFORME_AUDITORIA_1.0.89.md'), 'No debe conservarse el informe de auditoría anterior.');
Check(file_exists($Root . '/tests/.htaccess'), 'tests/ debe estar protegido con .htaccess.');
Check(file_exists($Root . '/tests/index.html'), 'tests/ debe tener index.html de protección.');
Check(file_exists($Root . '/tests/RunIntegrationChecks.php'), 'Falta prueba de integración MySQL temporal.');
Check(file_exists($Root . '/.sgce-production-exclude'), 'Falta manifiesto de exclusión para producción.');
Check(file_exists($Root . '/tools/CrearPaqueteProduccion.php'), 'Falta herramienta para paquete de producción.');
Check(strpos(@file_get_contents($Root . '/includes/SGCE_UI.php') ?: '', 'function SgceCss') !== false, 'Faltan helpers de assets CSS/JS.');
Check(strpos(@file_get_contents($Root . '/config/Conexion.php') ?: '', 'SGCE_VERSION') !== false, 'Falta versión central SGCE_VERSION.');
$CssBase = @file_get_contents($Root . '/assets/css/sgce-base.min.css') ?: '';
Check(strlen($CssBase) <= 140000, 'sgce-base.min.css sigue demasiado grande: ' . strlen($CssBase) . ' bytes.');
Check(substr_count($CssBase, '!important') <= 1800, 'sgce-base.min.css conserva demasiados !important: ' . substr_count($CssBase, '!important'));
Check(strpos($CssBase, '.SgceBitacoraCard') === false, 'Bitácora no debe seguir dentro del CSS base.');
Check(strpos($CssBase, '.AsignacionesTableCard') === false, 'Asignaciones no debe seguir dentro del CSS base.');
Check((glob($Root . '/install/migrations/*.sql') ?: []) === [], 'No debe haber migraciones históricas dentro de instalación limpia.');
Check(strpos(@file_get_contents($Root . '/docs/ROADMAP.md') ?: '', 'Dividir todavía más `modules/Importar.php`') === false, 'Roadmap desactualizado: importador ya fue separado.');

$TextFiles = SgceTestArchivosPorExtension(['php','js','css','md','txt','sql','ini']);
$ForbiddenText = [
    'multiescolar',
    'DOMDocument',
    'SgceDbAgregarColumnaSiFalta',
    'SgceDbAgregarIndiceSiFalta',
    '1.0.90',
    '1.0.89',
    '1.0.88',
    '1.0.87',
    '1.0.69',
    '20260604_1063_schema_base',
];
$RemovedFunctions = [
    'CrearTablaBitacoraSiNoExiste',
    'CrearTablaRateLimitSiNoExiste',
    'SgceCrearTablaConfiguracionSiNoExiste',
    'SgceCrearTablaPlaneacionesSiNoExiste',
    'SgceDbIndiceExiste',
    'SgceImportacionReportesDepurar',
    'SgceMigracionesEstado',
    'SgceGrupoListarPaginado',
    'SgceMaestroListarPaginado',
    'SgceAlumnoListarFiltradoTodos',
    'SgceAsignacionListarFiltradasTodas',
    'SgceReporteBitacoraPaginadaTodas',
    'SgceLikeBusqueda',
    'SgceBusquedaUsarFullText',
    'SgceUrlAdminConParametros',
    'TextoNodosExcel',
    'SgceRepoAlumnoListarTodos',
    'SgceRepoAsignacionListarTodas',
    'SgceRepoBitacoraListarTodos',
];
foreach ($TextFiles as $Path) {
    $Rel = SgceTestRel($Path);
    if ($Rel === 'tests/RunStaticChecks.php') { continue; }
    $Content = @file_get_contents($Path);
    if ($Content === false) { continue; }
    foreach ($ForbiddenText as $ResiduoTecnico) {
        Check(stripos($Content, $ResiduoTecnico) === false, 'Residuo técnico detectado en ' . $Rel . ': ' . $ResiduoTecnico);
    }
    if (preg_match('/\.php$/i', $Rel)) {
        foreach ($RemovedFunctions as $FuncionRetirada) {
            Check(strpos($Content, $FuncionRetirada) === false, 'Función retirada detectada en ' . $Rel . ': ' . $FuncionRetirada);
        }
    }
}

if ($Errores) {
    echo "SGCE STATIC CHECKS: ERROR\n" . implode("\n", $Errores) . "\n";
    exit(1);
}
echo "SGCE STATIC CHECKS: OK\nRevisiones ejecutadas: {$Revisiones}\n";
