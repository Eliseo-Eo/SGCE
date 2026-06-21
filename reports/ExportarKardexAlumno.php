<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/config/Conexion.php';
require_once dirname(__DIR__) . '/includes/SGCE_Pdf.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { http_response_code(403); exit('Acceso denegado.'); }
if (!SgcePuedeAdministrarReportes($UserSession)) { http_response_code(403); exit('No tienes permiso.'); }

$AlumnoId = (int)($_GET['AlumnoId'] ?? 0);
$CicloId = (int)($_GET['CicloId'] ?? 0);
$Tipo = (($_GET['Tipo'] ?? 'Pdf') === 'Excel') ? 'Excel' : 'Pdf';
if ($AlumnoId <= 0) { http_response_code(400); exit('Alumno inválido.'); }

try {
    $DatosKardex = SgceKardexAlumnoReporteDatos($Pdo, $AlumnoId, $CicloId);
} catch (InvalidArgumentException $E) {
    http_response_code(400);
    exit($E->getMessage());
} catch (RuntimeException $E) {
    http_response_code((int)$E->getCode() === 404 ? 404 : 500);
    exit($E->getMessage());
}

function HKdx($Texto){ return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }

$Filas = $DatosKardex['Filas'];
$TituloArchivo = $DatosKardex['TituloArchivo'];
$Subtitulo = $DatosKardex['Subtitulo'];
if ($Tipo === 'Pdf') {
    SgcePdfRespuestaTabla($Pdo, 'Kardex individual', $Subtitulo, ['Ciclo','Grupo','Materia','Docente','Calificaciones','Prom.'], $Filas, $TituloArchivo, 'L', [80,70,150,135,285,50]);
}

SgceHeaderDescarga($TituloArchivo . '.xls', 'application/vnd.ms-excel; charset=utf-8');
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title><?= HKdx($TituloArchivo) ?></title></head><body>
<h2>KARDEX INDIVIDUAL</h2>
<p><?= HKdx($Subtitulo) ?></p>
<table border="1"><thead><tr><th>Ciclo</th><th>Grupo</th><th>Materia</th><th>Docente</th><th>Calificaciones</th><th>Promedio</th></tr></thead><tbody>
<?php foreach($Filas as $F): ?><tr><?php foreach($F as $C): ?><td><?= HKdx($C) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
</tbody></table></body></html>
