<?php
$Root = dirname(__DIR__);
$Project = dirname($Root);
$Errores = [];
$Revisiones = 0;

foreach (['Produccion', 'Desarrollo'] as $Raiz) {
    $Base = $Project . DIRECTORY_SEPARATOR . $Raiz;
    $Css = $Base . DIRECTORY_SEPARATOR . 'assets/css/modules/admin-motion.css';
    $Login = $Base . DIRECTORY_SEPARATOR . 'assets/css/modules/login-motion.css';
    $Layout = $Base . DIRECTORY_SEPARATOR . 'includes/SGCE_Layout.php';

    $Revisiones++;
    if (!is_file($Css)) { $Errores[] = "Falta admin-motion.css en $Raiz"; continue; }
    $Contenido = file_get_contents($Css);
    foreach (['SgcePageIn', 'SgceSoftUp', 'SgceSoftFade', 'DashboardModuleCard', 'DashboardKpiCard', 'prefers-reduced-motion'] as $Token) {
        $Revisiones++;
        if (!str_contains($Contenido, $Token)) { $Errores[] = "Falta token de transición administrativa en $Raiz: $Token"; }
    }

    $Revisiones++;
    if (substr_count($Contenido, '{') !== substr_count($Contenido, '}')) { $Errores[] = "Llaves CSS desbalanceadas en $Raiz/admin-motion.css"; }

    $Revisiones++;
    if (!is_file($Login) || str_contains(file_get_contents($Login), 'DashboardModuleCard')) { $Errores[] = "login-motion.css debe quedar separado de animaciones administrativas en $Raiz"; }

    $Revisiones++;
    if (!is_file($Layout) || !str_contains(file_get_contents($Layout), "assets/css/modules/admin-motion.css")) { $Errores[] = "SGCE_Layout.php no carga admin-motion.css en $Raiz"; }
}

if ($Errores) {
    echo "SGCE ADMIN MOTION CHECKS: ERROR
" . implode("
", $Errores) . "
";
    exit(1);
}

echo "SGCE ADMIN MOTION CHECKS: OK
Revisiones ejecutadas: $Revisiones
";
