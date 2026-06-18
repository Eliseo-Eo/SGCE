<?php
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('CLI only'); }
$Root = dirname(__DIR__);
$Errores = [];
$ScriptSh = $Root . '/tools/visual-mobile-smoke.sh';
$ScriptPy = $Root . '/tools/visual-mobile-smoke.py';
if (!is_file($ScriptSh)) { $Errores[] = 'No existe tools/visual-mobile-smoke.sh.'; }
if (!is_file($ScriptPy)) { $Errores[] = 'No existe tools/visual-mobile-smoke.py.'; }
$Contenido = is_file($ScriptPy) ? file_get_contents($ScriptPy) : '';
foreach (['390, 844, "celular"', '430, 932, "celular-grande"', '768, 1024, "tablet"', '1366, 768, "escritorio"'] as $Viewport) {
    if (!str_contains($Contenido, $Viewport)) { $Errores[] = 'Falta viewport: ' . $Viewport; }
}
foreach (['ConsultaPadre.php', 'ConsultaCalificaciones.php', 'Admin.php?Tab=inicio', 'PeriodosAdmin.php', 'ReportesAdmin.php', 'UsuariosAdmin.php', 'RestaurarBD.php'] as $Pagina) {
    if (!str_contains($Contenido, $Pagina)) { $Errores[] = 'Falta página visual: ' . $Pagina; }
}
foreach (['SGCE_VISUAL_AUTH_TOKEN', 'SGCE_VISUAL_LOGIN_USER', 'SGCE_VISUAL_LOGIN_PASSWORD', 'AuthToken'] as $AuthNeedle) {
    if (!str_contains($Contenido, $AuthNeedle)) { $Errores[] = 'Falta soporte autenticado en script visual: ' . $AuthNeedle; }
}
if (!str_contains($Contenido, 'reporte-visual.html')) { $Errores[] = 'El script no genera reporte HTML.'; }
if (str_contains($Contenido, 'public/index.php')) { $Errores[] = 'El script visual intenta capturar public/index.php protegido.'; }
if (is_file($ScriptSh) && !is_executable($ScriptSh)) { $Errores[] = 'El script de capturas no tiene permiso de ejecución.'; }
$Checklist = $Root . '/docs/AUDITORIA_VISUAL_SGCE.md';
if (!is_file($Checklist)) { $Errores[] = 'No existe docs/AUDITORIA_VISUAL_SGCE.md.'; }
else {
    $ContenidoChecklist = file_get_contents($Checklist);
    foreach (['Dashboard', 'Maestros', 'Grupos', 'Materias', 'Alumnos', 'Asignaciones', 'Periodos', 'Reportes', 'Usuarios', 'Respaldos', 'Bitácora', 'Consulta pública'] as $Modulo) {
        if (!str_contains($ContenidoChecklist, $Modulo)) { $Errores[] = 'Checklist visual incompleto. Falta: ' . $Modulo; }
    }
}
if ($Errores) { echo "SGCE VISUAL CAPTURE 1.0.185 CHECKS: ERROR\n" . implode("\n", $Errores) . "\n"; exit(1); }
echo "SGCE VISUAL CAPTURE 1.0.185 CHECKS: OK\n";
