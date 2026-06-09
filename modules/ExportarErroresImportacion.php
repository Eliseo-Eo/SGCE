<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

require_once dirname(__DIR__) . '/config/Conexion.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession || !SgcePuedeImportarCatalogos($UserSession)) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

$Token = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($_GET['t'] ?? ''));
$SesionToken = (string)($_SESSION['SgceUltimoReporteImportacionToken'] ?? '');
$Ruta = (string)($_SESSION['SgceUltimoReporteImportacion'] ?? '');

if ($Token === '' || $SesionToken === '' || !hash_equals($SesionToken, $Token) || $Ruta === '' || !is_file($Ruta)) {
    http_response_code(404);
    exit('No hay reporte de importación disponible.');
}

$Reporte = json_decode((string)file_get_contents($Ruta), true);
if (!is_array($Reporte)) {
    http_response_code(500);
    exit('El reporte de importación no se pudo leer.');
}

$Tipo = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($Reporte['Tipo'] ?? 'importacion')) ?: 'importacion';
$NombreArchivo = 'SGCE_reporte_' . $Tipo . '_' . date('Ymd_His') . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $NombreArchivo . '"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
echo "\xEF\xBB\xBF";
?>
<html><head><meta charset="utf-8"><title>Reporte de importación SGCE</title></head><body>
<h2>Reporte de importación SGCE</h2>
<table border="1" cellspacing="0" cellpadding="4">
<tr><th>Dato</th><th>Valor</th></tr>
<tr><td>Tipo</td><td><?= htmlspecialchars((string)($Reporte['Tipo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td></tr>
<tr><td>Fecha</td><td><?= htmlspecialchars((string)($Reporte['Fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td></tr>
<?php foreach (($Reporte['Resumen'] ?? []) as $Clave => $Valor): ?>
<tr><td><?= htmlspecialchars((string)$Clave, ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$Valor, ENT_QUOTES, 'UTF-8') ?></td></tr>
<?php endforeach; ?>
</table>
<br>
<h3>Filas observadas</h3>
<table border="1" cellspacing="0" cellpadding="4">
<tr><th>Fila</th><th>Motivo</th><th>Datos</th></tr>
<?php foreach (($Reporte['Errores'] ?? []) as $Error): ?>
<tr>
<td><?= htmlspecialchars((string)($Error['Fila'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars((string)($Error['Motivo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars(json_encode($Error['Datos'] ?? [], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?></td>
</tr>
<?php endforeach; ?>
<?php if (empty($Reporte['Errores'])): ?>
<tr><td colspan="3">Sin errores detallados.</td></tr>
<?php endif; ?>
</table>
</body></html>
