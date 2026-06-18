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

$StmtAlumno = $Pdo->prepare('SELECT Id, NombreCompleto FROM Alumnos WHERE Id = ? LIMIT 1');
$StmtAlumno->execute([$AlumnoId]);
$AlumnoBase = $StmtAlumno->fetch();
if (!$AlumnoBase) { http_response_code(404); exit('Alumno no encontrado.'); }

$WhereCiclo = $CicloId > 0 ? ' AND AI.CicloId = ? ' : '';
$ParamsCiclos = [$AlumnoId];
if ($CicloId > 0) { $ParamsCiclos[] = $CicloId; }
$StmtCiclos = $Pdo->prepare("SELECT AI.CicloId, AI.GrupoId, AI.Estado, C.Nombre AS CicloNombre, C.FechaInicio, G.Grado, G.Grupo, G.Turno, G.OfertaId
    FROM AlumnoInscripciones AI
    JOIN CiclosEscolares C ON C.Id = AI.CicloId
    JOIN Grupos G ON G.Id = AI.GrupoId AND G.CicloId = AI.CicloId
    WHERE AI.AlumnoId = ? $WhereCiclo
    ORDER BY C.FechaInicio ASC, C.Id ASC");
$StmtCiclos->execute($ParamsCiclos);
$Ciclos = $StmtCiclos->fetchAll();
if (!$Ciclos) { http_response_code(404); exit('El alumno no tiene inscripción en el ciclo solicitado.'); }

function HKdx($Texto){ return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }
function ArcKdx($Texto){ $Texto = iconv('UTF-8','ASCII//TRANSLIT//IGNORE',(string)$Texto); $Texto = preg_replace('/[^A-Za-z0-9_\-]+/','_', $Texto); return trim($Texto,'_') ?: 'Kardex'; }
function FmtKdx($Valor){ return $Valor !== null && $Valor !== '' ? number_format((float)$Valor, 2) : 'NC'; }

$Filas = [];
$ResumenCiclos = [];
foreach ($Ciclos as $Ciclo) {
    $StmtPeriodos = $Pdo->prepare('SELECT Id, Nombre, Orden FROM PeriodosEvaluacion WHERE CicloId = ? AND OfertaId = ? AND Activo = 1 ORDER BY Orden ASC, Id ASC');
    $StmtPeriodos->execute([(int)$Ciclo['CicloId'], (int)$Ciclo['OfertaId']]);
    $Periodos = $StmtPeriodos->fetchAll();

    $StmtAsignaciones = $Pdo->prepare('SELECT A.Id, A.MateriaNombre, U.NombreCompleto AS Maestro FROM Asignaciones A LEFT JOIN Usuarios U ON U.Id = A.MaestroId WHERE A.CicloId = ? AND A.GrupoId = ? ORDER BY A.MateriaNombre ASC');
    $StmtAsignaciones->execute([(int)$Ciclo['CicloId'], (int)$Ciclo['GrupoId']]);
    $Asignaciones = $StmtAsignaciones->fetchAll();

    $PeriodoIds = array_map(static fn($P) => (int)$P['Id'], $Periodos);
    $Calificaciones = [];
    if ($PeriodoIds && $Asignaciones) {
        $MarcadoresPeriodos = implode(',', array_fill(0, count($PeriodoIds), '?'));
        $AsignacionIds = array_map(static fn($A) => (int)$A['Id'], $Asignaciones);
        $MarcadoresAsignaciones = implode(',', array_fill(0, count($AsignacionIds), '?'));
        $StmtCal = $Pdo->prepare("SELECT AsignacionId, PeriodoId, Calificacion FROM Calificaciones WHERE AlumnoId = ? AND AsignacionId IN ($MarcadoresAsignaciones) AND PeriodoId IN ($MarcadoresPeriodos)");
        $StmtCal->execute(array_merge([$AlumnoId], $AsignacionIds, $PeriodoIds));
        foreach ($StmtCal->fetchAll() as $Cal) { $Calificaciones[(int)$Cal['AsignacionId']][(int)$Cal['PeriodoId']] = $Cal['Calificacion']; }
    }

    $SumaCiclo = 0.0; $CuentaCiclo = 0; $MateriasConCal = 0;
    $GrupoTexto = trim($Ciclo['Grado'] . ' ' . $Ciclo['Grupo'] . ' ' . $Ciclo['Turno']);
    foreach ($Asignaciones as $Asig) {
        $Partes = [];
        $SumaMateria = 0.0; $CuentaMateria = 0;
        foreach ($Periodos as $Periodo) {
            $Valor = $Calificaciones[(int)$Asig['Id']][(int)$Periodo['Id']] ?? null;
            if ($Valor !== null && $Valor !== '') { $SumaMateria += (float)$Valor; $CuentaMateria++; $SumaCiclo += (float)$Valor; $CuentaCiclo++; }
            $Partes[] = $Periodo['Nombre'] . ': ' . FmtKdx($Valor);
        }
        if ($CuentaMateria > 0) { $MateriasConCal++; }
        $Filas[] = [
            (string)$Ciclo['CicloNombre'],
            $GrupoTexto,
            (string)$Asig['MateriaNombre'],
            (string)($Asig['Maestro'] ?? ''),
            implode(' | ', $Partes),
            $CuentaMateria > 0 ? number_format($SumaMateria / $CuentaMateria, 2) : 'NC'
        ];
    }
    if (!$Asignaciones) {
        $Filas[] = [(string)$Ciclo['CicloNombre'], $GrupoTexto, 'SIN MATERIAS', '', 'SIN CALIFICACIONES', 'NC'];
    }
    $ResumenCiclos[] = $Ciclo['CicloNombre'] . ' ' . $GrupoTexto . ' PROMEDIO ' . ($CuentaCiclo > 0 ? number_format($SumaCiclo / $CuentaCiclo, 2) : 'NC') . ' (' . $MateriasConCal . ' MATERIAS CON CALIFICACIÓN)';
}
$TituloArchivo = 'Kardex_Individual_' . ArcKdx($AlumnoBase['NombreCompleto'] . ($CicloId > 0 ? '_' . $Ciclos[0]['CicloNombre'] : '_Completo'));
$Subtitulo = 'Alumno: ' . $AlumnoBase['NombreCompleto'] . ' | ' . implode(' | ', $ResumenCiclos);
if (strlen($Subtitulo) > 430) { $Subtitulo = 'Alumno: ' . $AlumnoBase['NombreCompleto'] . ' | Ciclos incluidos: ' . count($Ciclos); }

if ($Tipo === 'Pdf') {
    SgcePdfRespuestaTabla($Pdo, 'Kardex individual', $Subtitulo, ['Ciclo','Grupo','Materia','Docente','Calificaciones','Prom.'], $Filas, $TituloArchivo, 'L', [80,70,150,135,285,50]);
}

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $TituloArchivo . '.xls');
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title><?= HKdx($TituloArchivo) ?></title></head><body>
<h2>KARDEX INDIVIDUAL</h2>
<p><?= HKdx($Subtitulo) ?></p>
<table border="1"><thead><tr><th>Ciclo</th><th>Grupo</th><th>Materia</th><th>Docente</th><th>Calificaciones</th><th>Promedio</th></tr></thead><tbody>
<?php foreach($Filas as $F): ?><tr><?php foreach($F as $C): ?><td><?= HKdx($C) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
</tbody></table></body></html>
