<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceLayoutFaviconTags(): string {
    return "    <link rel=\"icon\" type=\"image/x-icon\" href=\"assets/media/img/favicon.ico\">\n" .
           "    <link rel=\"shortcut icon\" type=\"image/x-icon\" href=\"assets/media/img/favicon.ico\">\n" .
           "    <link rel=\"apple-touch-icon\" href=\"assets/media/img/favicon.png\">";
}

function SgceLayoutCdnCssTags(bool $ConBootstrap = true, bool $ConFontAwesome = true, bool $ConPoppins = true): string {
    $Tags = [];
    if ($ConBootstrap) { $Tags[] = '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">'; }
    if ($ConFontAwesome) { $Tags[] = '<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">'; }
    if ($ConPoppins) { $Tags[] = '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">'; }
    return implode("\n", $Tags);
}

function SgceLayoutCdnJsTags(bool $ConBootstrap = true): string {
    return $ConBootstrap ? '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>' : '';
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

function SgceLayoutCssTags(array $RutasCss): string {
    $Tags = [];
    foreach (SgceLayoutNormalizarAssets($RutasCss) as $RutaCss) { $Tags[] = SgceCss($RutaCss); }
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
        'assets/css/sgce-base.min.css',
        'assets/css/base/variables.css',
        'assets/css/base/layout.css',
        'assets/css/base/motion.css',
        'assets/css/components/buttons.css',
        'assets/css/components/tables.css',
        'assets/css/components/filters.css',
        'assets/css/components/badges-alerts.css',
        'assets/css/components/confirm-modal.css',
        'assets/css/sgce-soft-motion.css',
        'assets/css/sgce-buttons.css',
    ];
}

function SgceLayoutResponsiveCssList(): array {
    return [
        'assets/css/responsive/mobile-core.css',
        'assets/css/responsive/mobile-admin.css',
        'assets/css/responsive/mobile-docente.css',
        'assets/css/responsive/mobile-public.css',
        'assets/css/responsive/mobile-modals.css',
        'assets/css/responsive/mobile-dashboard.css',
        'assets/css/responsive/mobile-actions.css',
        'assets/css/components/mobile-buttons.css',
    ];
}

function SgceLayoutAdminCssList(): array {
    return [
        'assets/css/sgce-admin-extra.css',
        'assets/css/maestros-botones-metalicos.css',
        'assets/css/grupos-alumnos-botones-metalicos.css',
        'assets/css/materias-botones-metalicos.css',
        'assets/css/components/pagination.css',
        'assets/css/components/filter-bars.css',
        'assets/css/components/searchable-selects.css',
        'assets/css/components/admin-table-layout.css',
        'assets/css/modules/bitacora-layout.css',
        'assets/css/modules/config-users-layout.css',
        'assets/css/admin-paginacion-busqueda.css',
        'assets/css/asignaciones-botones-metalicos.css',
        'assets/css/modules/asignaciones-edit-modal.css',
        'assets/css/expedientes-botones-metalicos.css',
        'assets/css/dashboard-colores-suaves.css',
        'assets/css/modules/admin-motion.css',
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
        'assets/js/admin/Admin.js',
    ];
}

function SgceLayoutAdminAppJs(array $JsModulo = [], bool $ConBootstrap = true, bool $ConCsrf = false): string {
    return SgceLayoutSharedJs(array_merge(SgceLayoutAdminApplicationList(), $JsModulo), $ConBootstrap, $ConCsrf);
}
