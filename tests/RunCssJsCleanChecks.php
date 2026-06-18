<?php
$Root = dirname(__DIR__);
$Errores = [];
$ArchivosJs = ['assets/js/admin/AdminUtils.js','assets/js/admin/AdminEditModals.js','assets/js/admin/AdminInputs.js','assets/js/admin/AdminCore.js','assets/js/admin/AdminSearchableSelects.js','assets/js/admin/AdminClientPagination.js','assets/js/admin/AdminServerFilters.js','assets/js/admin/AdminTableLayout.js','assets/js/admin/Admin.js'];
foreach ($ArchivosJs as $Archivo) { if (!is_file($Root . '/' . $Archivo)) { $Errores[] = 'Falta ' . $Archivo; } }
$Layout = file_get_contents($Root . '/includes/SGCE_Layout.php');
foreach ($ArchivosJs as $Archivo) { if (!str_contains($Layout, $Archivo)) { $Errores[] = 'SGCE_Layout.php no carga ' . $Archivo; } }
foreach (['assets/js/admin/AdminConfirmaciones.js','ModalConfirmarEliminar','data-confirm-delete'] as $Retirado) { if (str_contains($Layout, $Retirado) || file_exists($Root . '/' . $Retirado)) { $Errores[] = 'Confirmación antigua todavía presente: ' . $Retirado; } }
$Admin = is_file($Root . '/assets/js/admin/Admin.js') ? file_get_contents($Root . '/assets/js/admin/Admin.js') : '';
foreach (['function SetupSearchPagination','function InicializarServerFilters','function DecorarModalesEdicion','function NormalizarInputNombre','ModalConfirmarEliminar','AjustarContenedoresTablas'] as $Token) { if (str_contains($Admin, $Token)) { $Errores[] = 'Admin.js conserva lógica que debe vivir en módulos separados: ' . $Token; } }
$Server = is_file($Root . '/assets/js/admin/AdminServerFilters.js') ? file_get_contents($Root . '/assets/js/admin/AdminServerFilters.js') : '';
foreach (['api/admin/alumnos.php','api/admin/materias.php','api/admin/asignaciones.php','api/admin/bitacora.php'] as $Endpoint) { if (!str_contains($Server, $Endpoint)) { $Errores[] = 'AdminServerFilters.js no referencia ' . $Endpoint; } }
$CssButtons = is_file($Root . '/assets/css/components/buttons.css') ? file_get_contents($Root . '/assets/css/components/buttons.css') : '';
foreach (['.SgceBtn','.SgceBtnPrimary','.SgceBtnPdf'] as $Token) { if (!str_contains($CssButtons, $Token)) { $Errores[] = 'buttons.css no contiene base global: ' . $Token; } }
$NoUsados = ['function SgceCalificacionCssClase','function SgceAdminEjecutarTransaccion'];
$Iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Root, FilesystemIterator::SKIP_DOTS));
foreach ($Iter as $Archivo) { if (!$Archivo->isFile()) continue; $Ruta = $Archivo->getPathname(); if (!preg_match('/\.(php|js|css)$/i', $Ruta)) continue; if (str_ends_with($Ruta, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'RunCssJsCleanChecks.php')) continue; $Contenido = file_get_contents($Ruta); foreach ($NoUsados as $Token) { if (str_contains($Contenido, $Token)) { $Errores[] = 'Función antigua detectada en ' . $Ruta . ': ' . $Token; } } }
if ($Errores) { echo "RunCssJsCleanChecks: FAIL
" . implode("
", $Errores) . "
"; exit(1); }
echo "RunCssJsCleanChecks: OK
";
