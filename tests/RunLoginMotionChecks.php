<?php
$Root = dirname(__DIR__);
$Project = dirname($Root);
$Errores = [];
$Revisiones = 0;

foreach (['Produccion', 'Desarrollo'] as $Raiz) {
    $Base = $Project . DIRECTORY_SEPARATOR . $Raiz;
    $Bridge = $Base . DIRECTORY_SEPARATOR . 'assets/css/sgce-soft-motion.css';
    $LoginMotion = $Base . DIRECTORY_SEPARATOR . 'assets/css/modules/login-motion.css';
    $Index = $Base . DIRECTORY_SEPARATOR . 'public/index.php';
    $Layout = $Base . DIRECTORY_SEPARATOR . 'includes/SGCE_Layout.php';

    $Revisiones++;
    if (!is_file($Bridge)) { $Errores[] = "Falta puente sgce-soft-motion.css en $Raiz"; continue; }
    $ContenidoBridge = file_get_contents($Bridge);
    foreach (["@import url('modules/admin-motion.css')", "@import url('modules/login-motion.css')"] as $TokenBridge) {
        $Revisiones++;
        if (!str_contains($ContenidoBridge, $TokenBridge)) {
            $Errores[] = "El puente de motion no carga correctamente $TokenBridge en $Raiz";
        }
    }
    if (str_contains($ContenidoBridge, '?v=')) {
        $Errores[] = "El puente de motion no debe fijar versión interna en @import para evitar caché duplicado en $Raiz";
    }

    $Revisiones++;
    if (!is_file($LoginMotion)) { $Errores[] = "Falta login-motion.css en $Raiz"; continue; }
    $ContenidoCss = file_get_contents($LoginMotion);

    foreach (['SgceLoginFeatureSheen', 'SgceLoginFeatureIconPulse', 'SgceLoginPanelIn', 'SgceLoginEnter', 'prefers-reduced-motion'] as $Token) {
        $Revisiones++;
        if (!str_contains($ContenidoCss, $Token)) {
            $Errores[] = "Falta token de animación de login en $Raiz: $Token";
        }
    }

    $Revisiones++;
    if (substr_count($ContenidoCss, '{') !== substr_count($ContenidoCss, '}')) {
        $Errores[] = "Llaves CSS desbalanceadas en $Raiz/assets/css/modules/login-motion.css";
    }

    $Revisiones++;
    $IndexContenido = is_file($Index) ? file_get_contents($Index) : '';
    $LayoutContenido = is_file($Layout) ? file_get_contents($Layout) : '';
    if (!str_contains($IndexContenido, 'SgceLayoutHeadBase') || !str_contains($LayoutContenido, "'assets/css/sgce-soft-motion.css'")) {
        $Errores[] = "El login de $Raiz no carga el puente sgce-soft-motion.css desde el layout centralizado";
    }
}

if ($Errores) {
    echo "SGCE LOGIN MOTION CHECKS: ERROR\n" . implode("\n", $Errores) . "\n";
    exit(1);
}

echo "SGCE LOGIN MOTION CHECKS: OK\nRevisiones ejecutadas: $Revisiones\n";
