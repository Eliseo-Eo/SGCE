<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceLayoutFaviconTags(): string {
    return "    <link rel=\"icon\" type=\"image/x-icon\" href=\"assets/media/img/favicon.ico\">\n" .
           "    <link rel=\"shortcut icon\" type=\"image/x-icon\" href=\"assets/media/img/favicon.ico\">\n" .
           "    <link rel=\"apple-touch-icon\" href=\"assets/media/img/favicon.png\">";
}

function SgceLayoutCdnCssTags(bool $ConBootstrap = true, bool $ConFontAwesome = true, bool $ConPoppins = true): string {
    $Tags = [];
    if ($ConBootstrap) { $Tags[] = SgceCss('assets/vendor/bootstrap/5.3.3/css/bootstrap.min.css'); }
    if ($ConFontAwesome) { $Tags[] = SgceCss('assets/vendor/fontawesome/6.5.2/css/all.min.css'); }
    if ($ConPoppins) { $Tags[] = SgceCss('assets/vendor/poppins/5.0.8/poppins-local.css'); }
    return implode("\n", $Tags);
}

function SgceLayoutCdnJsTags(bool $ConBootstrap = true): string {
    return $ConBootstrap ? SgceJs('assets/vendor/bootstrap/5.3.3/js/bootstrap.bundle.min.js') : '';
}

function SgceLayoutNormalizarAssets(array $Assets): array {
    $Normalizados = [];
    foreach ($Assets as $Asset) {
        $Asset = trim((string)$Asset);
        if ($Asset === '' || isset($Normalizados[$Asset])) { continue; }
        $Normalizados[$Asset] = $Asset;
    }
    return array_values($Normalizados);
}


function SgceLayoutCssConsolidationMap(): array {
    return [
        'assets/css/admin-paginacion-busqueda.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/asignaciones-botones-metalicos.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/asistencia-botones-metalicos.css' => 'assets/css/sgce-docente-bundle.min.css',
        'assets/css/avisos-botones-metalicos.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/base/layout.css' => 'assets/css/sgce-bundle.min.css',
        'assets/css/base/motion.css' => 'assets/css/sgce-bundle.min.css',
        'assets/css/base/variables.css' => 'assets/css/sgce-bundle.min.css',
        'assets/css/calificar-botones-metalicos.css' => 'assets/css/sgce-docente-bundle.min.css',
        'assets/css/components/admin-table-layout.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/components/badges-alerts.css' => 'assets/css/sgce-bundle.min.css',
        'assets/css/components/buttons.css' => 'assets/css/sgce-bundle.min.css',
        'assets/css/components/confirm-modal.css' => 'assets/css/sgce-bundle.min.css',
        'assets/css/components/filter-bars.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/components/filters.css' => 'assets/css/sgce-bundle.min.css',
        'assets/css/components/mobile-buttons.css' => 'assets/css/sgce-responsive-bundle.min.css',
        'assets/css/components/pagination.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/components/searchable-selects.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/components/tables.css' => 'assets/css/sgce-bundle.min.css',
        'assets/css/conducta-disciplina.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/configuracion-botones-metalicos.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/consulta-publica-botones-metalicos.css' => 'assets/css/sgce-public-bundle.min.css',
        'assets/css/dashboard-colores-suaves.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/expediente-alumno.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/expedientes-botones-metalicos.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/grupos-alumnos-botones-metalicos.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/maestros-botones-metalicos.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/materias-botones-metalicos.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/migracion-botones-metalicos.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/modules/admin-motion.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/modules/asignaciones-edit-modal.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/modules/bitacora-layout.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/modules/config-users-layout.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/modules/login-motion.css' => 'assets/css/sgce-public-bundle.min.css',
        'assets/css/modules/planeaciones-admin-review-modal.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/modules/planeaciones-docente.css' => 'assets/css/sgce-docente-bundle.min.css',
        'assets/css/periodos-verde-metalico.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/planeaciones-botones-metalicos.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/reportes-botones-metalicos.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/respaldos-botones-metalicos.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/responsive/mobile-actions.css' => 'assets/css/sgce-responsive-bundle.min.css',
        'assets/css/responsive/mobile-admin.css' => 'assets/css/sgce-responsive-bundle.min.css',
        'assets/css/responsive/mobile-core.css' => 'assets/css/sgce-responsive-bundle.min.css',
        'assets/css/responsive/mobile-dashboard.css' => 'assets/css/sgce-responsive-bundle.min.css',
        'assets/css/responsive/mobile-docente.css' => 'assets/css/sgce-responsive-bundle.min.css',
        'assets/css/responsive/mobile-modals.css' => 'assets/css/sgce-responsive-bundle.min.css',
        'assets/css/responsive/mobile-public.css' => 'assets/css/sgce-responsive-bundle.min.css',
        'assets/css/sgce-admin-extra.css' => 'assets/css/sgce-admin-bundle.min.css',
        'assets/css/sgce-base.min.css' => 'assets/css/sgce-bundle.min.css',
        'assets/css/sgce-buttons.css' => 'assets/css/sgce-bundle.min.css',
        'assets/css/sgce-soft-motion.css' => 'assets/css/sgce-bundle.min.css',
        'assets/css/usuarios-botones-metalicos.css' => 'assets/css/sgce-admin-bundle.min.css',
    ];
}

function SgceLayoutNormalizarCssConsolidado(array $Assets): array {
    $Mapa = SgceLayoutCssConsolidationMap();
    $Normalizados = [];
    foreach ($Assets as $Asset) {
        $Asset = trim((string)$Asset);
        if ($Asset === '') { continue; }
        $Asset = $Mapa[$Asset] ?? $Asset;
        if (isset($Normalizados[$Asset])) { continue; }
        $Normalizados[$Asset] = $Asset;
    }
    return array_values($Normalizados);
}

function SgceLayoutCssTags(array $RutasCss): string {
    $Tags = [];
    foreach (SgceLayoutNormalizarCssConsolidado($RutasCss) as $RutaCss) { $Tags[] = SgceCss($RutaCss); }
    return implode("\n", $Tags);
}

function SgceLayoutJsTags(array $RutasJs): string {
    $Tags = [];
    foreach (SgceLayoutNormalizarAssets($RutasJs) as $RutaJs) { $Tags[] = SgceJs($RutaJs); }
    return implode("\n", $Tags);
}

function SgceLayoutCsrfScript(): string {
    if (!function_exists('ImprimirCsrfScript')) { return ''; }
    ob_start();
    ImprimirCsrfScript();
    return trim((string)ob_get_clean());
}

function SgceLayoutBaseCssList(): array {
    return [
        'assets/css/sgce-bundle.min.css',
    ];
}

function SgceLayoutResponsiveCssList(): array {
    return [
        'assets/css/sgce-responsive-bundle.min.css',
    ];
}

function SgceLayoutAdminCssList(): array {
    return [
        'assets/css/sgce-admin-bundle.min.css',
    ];
}

function SgceLayoutBaseCss(PDO $Pdo): string {
    return SgceLayoutCssTags(SgceLayoutBaseCssList()) . "\n" . SgceEstilosTema($Pdo);
}

function SgceLayoutResponsiveCss(): string {
    return SgceLayoutCssTags(SgceLayoutResponsiveCssList());
}

function SgceLayoutHeadBase(string $Titulo, PDO $Pdo, array $CssExtra = []): string {
    $TituloSeguro = htmlspecialchars($Titulo, ENT_QUOTES, 'UTF-8');
    $CssExtraNormalizado = SgceLayoutNormalizarAssets($CssExtra);
    $Html = [];
    $Html[] = '<meta charset="UTF-8">';
    $Html[] = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    $Html[] = SgceLayoutFaviconTags();
    $Html[] = '<title>' . $TituloSeguro . '</title>';
    $Html[] = SgceLayoutCdnCssTags();
    $Html[] = SgceLayoutBaseCss($Pdo);
    $Html[] = SgceLayoutCssTags($CssExtraNormalizado);
    $Html[] = SgceLayoutResponsiveCss();
    return implode("\n", array_filter($Html, static fn($Linea) => trim((string)$Linea) !== ''));
}

function SgceLayoutAdminCss(PDO $Pdo, array $CssModulo = []): string {
    return SgceLayoutHeadBase('SGCE - Administrador', $Pdo, array_merge(SgceLayoutAdminCssList(), $CssModulo));
}

function SgceLayoutSharedJsList(): array {
    return [
        'assets/js/shared/theme.js',
        'assets/js/shared/notifications.js',
        'assets/js/shared/bootstrap-modals.js',
        'assets/js/shared/confirm-modal.js',
        'assets/js/shared/responsive-tables.js',
        'assets/js/shared/maestro-empty-state.js',
        'assets/js/shared/csrf.js',
        'assets/js/shared/autosubmit.js',
    ];
}

function SgceLayoutSharedJs(array $JsModulo = [], bool $ConBootstrap = true, bool $ConCsrf = false): string {
    $Html = [];
    if ($ConBootstrap) { $Html[] = SgceLayoutCdnJsTags(true); }
    if ($ConCsrf) { $Html[] = SgceLayoutCsrfScript(); }
    $Html[] = SgceLayoutJsTags(array_merge(SgceLayoutSharedJsList(), $JsModulo));
    return implode("\n", array_filter($Html, static fn($Linea) => trim((string)$Linea) !== ''));
}

function SgceLayoutAdminApplicationList(): array {
    return [
        'assets/js/admin/AdminUtils.js',
        'assets/js/admin/AdminTableLayout.js',
        'assets/js/admin/AdminEditModals.js',
        'assets/js/admin/AdminInputs.js',
        'assets/js/admin/AdminCore.js',
        'assets/js/admin/AdminSearchableSelects.js',
        'assets/js/admin/AdminClientPagination.js',
        'assets/js/admin/AdminServerFilters.js',
    ];
}

function SgceLayoutAdminAppJs(array $JsModulo = [], bool $ConBootstrap = true, bool $ConCsrf = false): string {
    return SgceLayoutSharedJs(array_merge(SgceLayoutAdminApplicationList(), $JsModulo), $ConBootstrap, $ConCsrf);
}
