<?php
$Root = dirname(__DIR__);
$Project = dirname($Root);
$Errores = [];
$Revisiones = 0;

foreach (['Produccion', 'Desarrollo'] as $Raiz) {
    $Base = $Project . DIRECTORY_SEPARATOR . $Raiz;
    $Layout = $Base . DIRECTORY_SEPARATOR . 'includes/SGCE_Layout.php';
    $Installer = $Base . DIRECTORY_SEPARATOR . 'Instalar.php';

    foreach (['theme.js', 'notifications.js', 'bootstrap-modals.js', 'confirm-modal.js', 'maestro-empty-state.js', 'csrf.js'] as $ArchivoJs) {
        $Ruta = $Base . DIRECTORY_SEPARATOR . 'assets/js/shared/' . $ArchivoJs;
        $Revisiones++;
        if (!is_file($Ruta) || trim((string)file_get_contents($Ruta)) === '') {
            $Errores[] = "Falta módulo JS compartido $Raiz/assets/js/shared/$ArchivoJs";
        }
    }

    $Revisiones++;
    if (!is_file($Layout) || !str_contains(file_get_contents($Layout), 'function SgceLayoutSharedJs')) {
        $Errores[] = "SGCE_Layout.php no define SgceLayoutSharedJs en $Raiz";
    }

    foreach (['InstallerCore.php', 'InstallerSqlText.php', 'InstallerDatabase.php', 'InstallerValidation.php', 'InstallerAcademic.php', 'InstallerRuntime.php'] as $ArchivoInstalador) {
        $Ruta = $Base . DIRECTORY_SEPARATOR . 'install/installer/' . $ArchivoInstalador;
        $Revisiones++;
        if (!is_file($Ruta) || trim((string)file_get_contents($Ruta)) === '') {
            $Errores[] = "Falta fragmento del instalador $Raiz/install/installer/$ArchivoInstalador";
        }
    }



    $RuntimeInstaller = $Base . DIRECTORY_SEPARATOR . 'install/installer/InstallerRuntime.php';
    $RuntimeContenido = is_file($RuntimeInstaller) ? file_get_contents($RuntimeInstaller) : '';
    $Revisiones++;
    if (!str_contains($RuntimeContenido, 'function InstalarRutaRaizAplicacion') || !str_contains($RuntimeContenido, 'dirname(__DIR__, 2)')) {
        $Errores[] = "InstallerRuntime.php debe resolver rutas desde la raíz real del sistema en $Raiz";
    }
    $Revisiones++;
    if (str_contains($RuntimeContenido, "__DIR__ . '/storage'") || str_contains($RuntimeContenido, "__DIR__ . '/config'") || str_contains($RuntimeContenido, "__DIR__ . '/install/SGCE.sql'")) {
        $Errores[] = "InstallerRuntime.php conserva rutas relativas viejas que apuntan a install/installer en $Raiz";
    }

    foreach (['AvisosAdminService.php', 'UsuariosAdminService.php', 'ConfiguracionAdminService.php'] as $ArchivoServicio) {
        $Ruta = $Base . DIRECTORY_SEPARATOR . 'services/admin/' . $ArchivoServicio;
        $Revisiones++;
        if (!is_file($Ruta) || !str_contains(file_get_contents($Ruta), 'function Sgce')) {
            $Errores[] = "Falta servicio administrativo $Raiz/services/admin/$ArchivoServicio";
        }
    }


    foreach (['avisos/Index.php', 'usuarios/Index.php', 'configuracion/Index.php'] as $ArchivoVista) {
        $Ruta = $Base . DIRECTORY_SEPARATOR . 'views/admin/' . $ArchivoVista;
        $Revisiones++;
        if (!is_file($Ruta) || !str_contains(file_get_contents($Ruta), 'SgceLayoutHeadBase')) {
            $Errores[] = "La vista administrativa debe usar layout base real: $Raiz/views/admin/$ArchivoVista";
        }
    }

    $Revisiones++;
    if (!is_file($Installer) || substr_count(file_get_contents($Installer), 'function ') > 2) {
        $Errores[] = "Instalar.php debe quedar delgado y delegar helpers a install/installer en $Raiz";
    }
}

if ($Errores) {
    echo "SGCE ARCHITECTURE 1.0.185 CHECKS: ERROR\n" . implode("\n", $Errores) . "\n";
    exit(1);
}

echo "SGCE ARCHITECTURE 1.0.185 CHECKS: OK\nRevisiones ejecutadas: $Revisiones\n";
