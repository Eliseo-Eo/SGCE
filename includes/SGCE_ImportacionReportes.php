<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceImportacionReporteDir(): string {
    $Dir = dirname(__DIR__) . '/storage/tmp_uploads/import_reports';
    if (!is_dir($Dir)) { @mkdir($Dir, 0750, true); }
    return $Dir;
}

function SgceImportacionReporteAgregar(array &$Errores, int $Fila, string $Motivo, array $Datos = []): void {
    if (count($Errores) >= 1000) { return; }
    $Errores[] = [
        'Fila' => $Fila,
        'Motivo' => $Motivo,
        'Datos' => array_values(array_map(static fn($V) => (string)$V, $Datos)),
    ];
}

function SgceImportacionReporteGuardar(string $Tipo, array $Resumen, array $Errores): void {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $Token = bin2hex(random_bytes(12));
    $Ruta = SgceImportacionReporteDir() . '/reporte_' . $Token . '.json';
    $Payload = [
        'Tipo' => $Tipo,
        'Resumen' => $Resumen,
        'Errores' => $Errores,
        'Fecha' => date('Y-m-d H:i:s'),
    ];
    file_put_contents($Ruta, json_encode($Payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    $_SESSION['SgceUltimoReporteImportacion'] = $Ruta;
    $_SESSION['SgceUltimoReporteImportacionToken'] = $Token;
}

function SgceImportacionReporteBoton(): string {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $Ruta = $_SESSION['SgceUltimoReporteImportacion'] ?? '';
    $Token = $_SESSION['SgceUltimoReporteImportacionToken'] ?? '';
    if ($Ruta === '' || $Token === '' || !is_file($Ruta)) { return ''; }
    return '<a class="SgceImportReportLink" href="ExportarErroresImportacion.php?t=' . HGlobal($Token) . '"><i class="fa-solid fa-file-excel"></i> Descargar reporte de importación</a>';
}

