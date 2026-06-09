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

function SgceLayoutBaseCss(PDO $Pdo): string {
    return SgceCss('assets/css/sgce-base.min.css') . "\n" .
           SgceCss('assets/css/sgce-soft-motion.css') . "\n" .
           SgceEstilosTema($Pdo);
}

function SgceLayoutHeadBase(string $Titulo, PDO $Pdo, array $CssExtra = []): string {
    $TituloSeguro = htmlspecialchars($Titulo, ENT_QUOTES, 'UTF-8');
    $Html = [];
    $Html[] = '<meta charset="UTF-8">';
    $Html[] = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    $Html[] = SgceLayoutFaviconTags();
    $Html[] = '<title>' . $TituloSeguro . '</title>';
    $Html[] = SgceLayoutCdnCssTags();
    $Html[] = SgceLayoutBaseCss($Pdo);
    foreach ($CssExtra as $RutaCss) { $Html[] = SgceCss((string)$RutaCss); }
    return implode("\n", array_filter($Html, static fn($Linea) => trim((string)$Linea) !== ''));
}

function SgceLayoutAdminCss(PDO $Pdo): string {
    return SgceLayoutHeadBase('SGCE - Administrador', $Pdo, [
        'assets/css/sgce-admin-extra.css',
        'assets/css/maestros-botones-metalicos.css',
        'assets/css/grupos-alumnos-botones-metalicos.css',
        'assets/css/materias-botones-metalicos.css',
        'assets/css/admin-paginacion-busqueda.css',
        'assets/css/asignaciones-botones-metalicos.css',
        'assets/css/expedientes-botones-metalicos.css',
        'assets/css/dashboard-colores-suaves.css',
    ]);
}

function SgceLayoutAdminAppJs(): string {
    return SgceJs('assets/js/sgce-shared.js') . "\n" . SgceJs('assets/js/Admin.js');
}

