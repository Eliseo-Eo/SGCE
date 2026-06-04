<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

require_once dirname(__DIR__) . '/config/Conexion.php';
require_once dirname(__DIR__) . '/includes/SGCE_Pdf.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { header('Location: index.php'); exit; }
SgceExigirPermiso($UserSession, 'reportes', 'No tienes permiso para exportar historiales académicos.');

$AlumnoId = (int)($_GET['AlumnoId'] ?? 0);
if ($AlumnoId <= 0) { http_response_code(400); exit('Alumno inválido.'); }

$StmtAlumno = $Pdo->prepare('SELECT Id, NombreCompleto FROM Alumnos WHERE Id = ? LIMIT 1');
$StmtAlumno->execute([$AlumnoId]);
$Alumno = $StmtAlumno->fetch();
if (!$Alumno) { http_response_code(404); exit('Alumno no encontrado.'); }

// Primero se consulta el kardex congelado. Si un ciclo todavía no fue congelado,
// se usa el cálculo dinámico como respaldo. El kardex oficial usa JSON para soportar 1 a 12 periodos.
$StmtKardex = $Pdo->prepare("SELECT KA.CicloNombreSnapshot AS CicloNombre,
        KA.GradoSnapshot AS Grado,
        KA.GrupoSnapshot AS Grupo,
        KA.TurnoSnapshot AS Turno,
        KA.EstadoFinal,
        KA.PromedioFinal,
        KD.MateriaNombreSnapshot AS MateriaNombre,
        KD.MaestroNombreSnapshot AS Maestro,
        KD.CalificacionesJson,
        KD.Promedio
    FROM KardexAlumno KA
    INNER JOIN KardexDetalle KD ON KD.KardexId = KA.Id
    WHERE KA.AlumnoId = ?
    ORDER BY KA.CicloId ASC, CAST(KA.GradoSnapshot AS UNSIGNED), KA.GrupoSnapshot, KD.Orden ASC, KD.MateriaNombreSnapshot ASC");
$StmtKardex->execute([$AlumnoId]);
$Kardex = $StmtKardex->fetchAll();

$FilasPdf = [];
$Suma = 0.0;
$Cuenta = 0;
$UsaKardex = !empty($Kardex);

$FormatearJson = static function($Json) use (&$Suma, &$Cuenta): string {
    $Datos = json_decode((string)$Json, true);
    if (!is_array($Datos)) { return ''; }
    $Partes = [];
    foreach ($Datos as $Item) {
        $Nombre = (string)($Item['Nombre'] ?? $Item['Periodo'] ?? '');
        $Cal = $Item['Calificacion'] ?? null;
        if ($Cal !== null && $Cal !== '') { $Suma += (float)$Cal; $Cuenta++; }
        $Partes[] = trim($Nombre . ': ' . ($Cal !== null && $Cal !== '' ? number_format((float)$Cal, 2) : '-'));
    }
    return implode(' | ', array_filter($Partes));
};

if ($UsaKardex) {
    foreach ($Kardex as $R) {
        $Evaluaciones = !empty($R['CalificacionesJson']) ? $FormatearJson($R['CalificacionesJson']) : '';
        if ($Evaluaciones === '') { $Evaluaciones = '-'; }
        $FilasPdf[] = [
            (string)$R['CicloNombre'],
            trim($R['Grado'].' '.$R['Grupo'].' '.$R['Turno']),
            (string)$R['MateriaNombre'],
            $Evaluaciones,
            $R['Promedio'] !== null ? number_format((float)$R['Promedio'], 2) : '-',
        ];
    }
} else {
    $Stmt = $Pdo->prepare("SELECT Cc.Nombre AS CicloNombre, G.Grado, G.Grupo, G.Turno, AI.Estado AS EstadoInscripcion,
               Asg.MateriaNombre, U.NombreCompleto AS Maestro,
               GROUP_CONCAT(CONCAT(P.Nombre, ': ', FORMAT(Cal.Calificacion, 2)) ORDER BY P.Orden SEPARATOR ' | ') AS Evaluaciones,
               ROUND(AVG(CASE WHEN Cal.Calificacion IS NOT NULL THEN Cal.Calificacion END), 2) AS Promedio,
               COUNT(Cal.Id) AS TotalCalificaciones
        FROM AlumnoInscripciones AI
        INNER JOIN CiclosEscolares Cc ON Cc.Id = AI.CicloId
        INNER JOIN Grupos G ON G.Id = AI.GrupoId AND G.CicloId = AI.CicloId
        INNER JOIN Asignaciones Asg ON Asg.CicloId = AI.CicloId AND Asg.GrupoId = AI.GrupoId
        LEFT JOIN Usuarios U ON U.Id = Asg.MaestroId
        LEFT JOIN PeriodosEvaluacion P ON P.CicloId = AI.CicloId AND P.OfertaId = AI.OfertaId AND P.Activo = 1
        LEFT JOIN Calificaciones Cal ON Cal.AlumnoId = AI.AlumnoId AND Cal.AsignacionId = Asg.Id AND Cal.PeriodoId = P.Id
        WHERE AI.AlumnoId = ?
        GROUP BY Cc.Id, Cc.Nombre, G.Grado, G.Grupo, G.Turno, AI.Estado, Asg.Id, Asg.MateriaNombre, U.NombreCompleto
        HAVING TotalCalificaciones > 0
        ORDER BY Cc.FechaInicio ASC, CAST(G.Grado AS UNSIGNED), G.Grupo, Asg.MateriaNombre");
    $Stmt->execute([$AlumnoId]);
    foreach ($Stmt->fetchAll() as $R) {
        if ($R['Promedio'] !== null) { $Suma += (float)$R['Promedio']; $Cuenta++; }
        $FilasPdf[] = [
            (string)$R['CicloNombre'],
            trim($R['Grado'].' '.$R['Grupo'].' '.$R['Turno']),
            (string)$R['MateriaNombre'],
            (string)($R['Evaluaciones'] ?? '-'),
            $R['Promedio'] !== null ? number_format((float)$R['Promedio'], 2) : '-',
        ];
    }
}

$Promedio = $Cuenta > 0 ? number_format($Suma / $Cuenta, 2) : '-';
$Fuente = $UsaKardex ? 'KARDEX CONGELADO' : 'CÁLCULO DINÁMICO';
$Subtitulo = 'Alumno: ' . $Alumno['NombreCompleto'] . ' | Promedio general histórico: ' . $Promedio . ' | Fuente: ' . $Fuente;
RegistrarBitacora($Pdo, $UserSession, 'EXPORTAR_HISTORIAL_ALUMNO', 'Alumnos', $AlumnoId, 'HISTORIAL ACADÉMICO COMPLETO GENERADO');
SgcePdfRespuestaTabla($Pdo, 'Historial académico completo', $Subtitulo, ['Ciclo', 'Grupo', 'Materia', 'Evaluaciones', 'Promedio'], $FilasPdf, 'Historial_' . $Alumno['NombreCompleto'], 'L', [90, 70, 160, 260, 60]);
